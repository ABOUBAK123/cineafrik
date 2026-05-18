<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoAccessLog;
use App\Models\VideoAsset;
use App\Services\DrmService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DrmKeyController extends Controller
{
    public function __construct(private DrmService $drm) {}

    /**
     * Endpoint AES-128 key server.
     * Le lecteur HLS appelle cette URL pour obtenir la clé de déchiffrement.
     * Protégé par token DRM signé (pas par Sanctum, car appelé par le player natif).
     */
    public function serveKey(Request $request, int $filmId): Response
    {
        $token = $request->query('token') ?? $request->header('X-DRM-Token');

        if (!$token) {
            $this->logDenied($request, $filmId, 'missing_token');
            abort(401, 'DRM token manquant.');
        }

        $payload = $this->drm->verifyKeyToken($token);

        if (!$payload) {
            $this->logDenied($request, $filmId, 'invalid_token');
            abort(401, 'Token DRM invalide ou expiré.');
        }

        // Vérifier que le token correspond au bon film
        if ((int) $payload['f'] !== $filmId) {
            $this->logDenied($request, $filmId, 'film_mismatch');
            abort(403, 'Token non valide pour ce film.');
        }

        // Vérifier que l'utilisateur a encore accès
        $userId = (int) $payload['u'];
        $hasAccess = \App\Models\UserAccess::where('user_id', $userId)
            ->where('film_id', $filmId)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if (!$hasAccess) {
            $this->logDenied($request, $filmId, 'access_revoked');
            abort(403, 'Accès révoqué.');
        }

        $asset = VideoAsset::where('film_id', $filmId)->where('status', 'ready')->firstOrFail();

        $rawKey = $this->drm->getActiveKey($asset);

        $this->logGranted($request, $filmId, $userId);

        return response($rawKey, 200)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Length', strlen($rawKey))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function logDenied(Request $request, int $filmId, string $reason): void
    {
        Log::warning('DRM key denied', [
            'film_id' => $filmId,
            'reason'  => $reason,
            'ip'      => $request->ip(),
            'ua'      => $request->userAgent(),
        ]);

        VideoAccessLog::create([
            'film_id'    => $filmId,
            'user_id'    => null,
            'ip_address' => $request->ip(),
            'action'     => 'drm_key_denied',
            'detail'     => $reason,
        ]);
    }

    private function logGranted(Request $request, int $filmId, int $userId): void
    {
        VideoAccessLog::create([
            'film_id'    => $filmId,
            'user_id'    => $userId,
            'ip_address' => $request->ip(),
            'action'     => 'drm_key_served',
            'detail'     => null,
        ]);
    }
}
