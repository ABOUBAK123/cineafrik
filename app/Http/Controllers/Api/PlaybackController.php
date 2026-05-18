<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\OfflineDownload;
use App\Models\PlaybackSession;
use App\Models\VideoAccessLog;
use App\Services\DrmService;
use App\Services\SignedUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaybackController extends Controller
{
    public function __construct(
        private DrmService $drm,
        private SignedUrlService $signer,
    ) {}

    /**
     * Initie une session de lecture :
     * - Vérifie l'accès (achat validé)
     * - Vérifie la limite d'appareils simultanés
     * - Retourne une URL de manifest signée (jamais l'URL S3 directe)
     * - Génère un token DRM lié à l'utilisateur et à l'appareil
     */
    public function requestStream(Request $request, int $filmId): JsonResponse
    {
        $user   = $request->user();
        $film   = Film::published()->with('videoAsset')->findOrFail($filmId);
        $device = $request->header('X-Device-ID', 'unknown');

        if (!$user->hasAccessToFilm($filmId)) {
            $this->log($filmId, $user->id, $request, 'access_denied', 'no_purchase');
            return response()->json(['message' => 'Achetez ce film pour le regarder.'], 403);
        }

        $maxStreams = (int) config('tvod.max_simultaneous_streams', 2);
        if (PlaybackSession::activeStreamsCount($user->id) >= $maxStreams) {
            return response()->json([
                'message' => "Limite de $maxStreams appareils simultanés atteinte.",
            ], 429);
        }

        if (!$film->videoAsset || $film->videoAsset->status !== 'ready') {
            return response()->json(['message' => 'Vidéo en cours de traitement.'], 503);
        }

        $access = $user->accesses()->where('film_id', $filmId)->first();
        $access->markFirstPlay();

        $session = PlaybackSession::create([
            'user_id'     => $user->id,
            'film_id'     => $filmId,
            'device_id'   => $device,
            'device_type' => $request->header('X-Device-Type'),
            'ip_address'  => $request->ip(),
            'heartbeat_at' => now(),
        ]);

        // Token de stream lié à user + device (le player doit l'envoyer à chaque requête)
        $streamToken = $this->signer->generateStreamToken($user->id, $filmId, $session->id, $device);

        // URL du manifest proxifiée (jamais l'URL S3 directe)
        $manifestUrl = route('video.manifest', $filmId) . '?token=' . urlencode($streamToken);

        // Token DRM séparé pour le serveur de clés AES-128
        $drmToken = $this->drm->generateKeyToken($user->id, $filmId, $device);

        $this->log($filmId, $user->id, $request, 'stream_started', $device);

        return response()->json([
            'session_id'   => $session->id,
            'manifest_url' => $manifestUrl,   // URL proxy signée, pas S3
            'stream_token' => $streamToken,
            'drm_token'    => $drmToken,
            'drm_enabled'  => $film->drm_enabled,
            'expires_in'   => 14400,           // 4 heures en secondes
        ]);
    }

    public function heartbeat(Request $request, int $sessionId): JsonResponse
    {
        $request->validate(['position_seconds' => 'required|integer|min:0']);

        $session = PlaybackSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->whereNull('ended_at')
            ->firstOrFail();

        $session->update([
            'position_seconds' => $request->position_seconds,
            'heartbeat_at'     => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function endSession(Request $request, int $sessionId): JsonResponse
    {
        $request->validate(['position_seconds' => 'nullable|integer|min:0']);

        $session = PlaybackSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $session->update([
            'position_seconds' => $request->position_seconds ?? $session->position_seconds,
            'ended_at'         => now(),
        ]);

        return response()->json(['message' => 'Session terminée.']);
    }

    /**
     * Émet une licence offline chiffrée liée au device.
     * Le fichier vidéo téléchargé ne peut être lu que sur le device ayant cette licence.
     */
    public function requestOfflineLicense(Request $request, int $filmId): JsonResponse
    {
        $user   = $request->user();
        $film   = Film::published()->with('videoAsset')->findOrFail($filmId);
        $device = $request->header('X-Device-ID', 'unknown');

        if (!$user->hasAccessToFilm($filmId)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $maxDownloads = (int) config('tvod.max_offline_downloads', 3);
        if ($user->offlineDownloads()->active()->count() >= $maxDownloads) {
            return response()->json([
                'message' => "Limite de $maxDownloads téléchargements simultanés atteinte.",
            ], 429);
        }

        if (!$film->videoAsset || $film->videoAsset->status !== 'ready') {
            return response()->json(['message' => 'Vidéo non disponible.'], 503);
        }

        $expiryDays   = (int) config('tvod.offline_download_expiry_days', 7);
        $licenseToken = $this->drm->generateKeyToken($user->id, $filmId, $device, $expiryDays * 24 * 60);

        $download = OfflineDownload::updateOrCreate(
            ['user_id' => $user->id, 'film_id' => $filmId, 'device_id' => $device],
            [
                'drm_license_token' => $licenseToken,
                'downloaded_at'     => now(),
                'expires_at'        => now()->addDays($expiryDays),
                'status'            => 'active',
            ]
        );

        $access = $user->accesses()->where('film_id', $filmId)->first();
        $access->markFirstPlay();

        $this->log($filmId, $user->id, $request, 'offline_license_issued', $device);

        return response()->json([
            'license_token' => $licenseToken,
            'expires_at'    => $download->expires_at,
            // Manifest HLS chiffré à télécharger (le player stocke les segments chiffrés)
            'manifest_url'  => route('video.manifest', $filmId) . '?token=' . urlencode(
                $this->signer->generateStreamToken($user->id, $filmId, 0, $device)
            ),
        ]);
    }

    /**
     * Vérifie la validité d'une licence offline (appelé par l'app toutes les 72h si connectée).
     */
    public function verifyOfflineLicense(Request $request, int $filmId): JsonResponse
    {
        $device   = $request->header('X-Device-ID', 'unknown');
        $download = $request->user()
            ->offlineDownloads()
            ->where('film_id', $filmId)
            ->where('device_id', $device)
            ->active()
            ->first();

        if (!$download) {
            return response()->json(['valid' => false, 'message' => 'Licence expirée ou invalide.'], 403);
        }

        return response()->json([
            'valid'      => true,
            'expires_at' => $download->expires_at,
        ]);
    }

    private function log(int $filmId, int $userId, Request $request, string $action, ?string $detail = null): void
    {
        VideoAccessLog::create([
            'film_id'    => $filmId,
            'user_id'    => $userId,
            'ip_address' => $request->ip(),
            'action'     => $action,
            'detail'     => $detail,
            'device_id'  => $request->header('X-Device-ID'),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
