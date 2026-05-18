<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoAsset;
use App\Services\DrmService;
use App\Services\SignedUrlService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoProxyController extends Controller
{
    public function __construct(
        private DrmService $drm,
        private SignedUrlService $signer,
    ) {}

    /**
     * Proxy du manifest HLS (.m3u8) :
     * - Valide le token de stream
     * - Récupère le manifest depuis S3/CDN
     * - Réécrit les URLs de segments en URLs signées passant par notre proxy
     * - Injecte l'URL du serveur de clés DRM (AES-128)
     * - Renvoie le manifest sans exposer les URLs S3 directes
     */
    public function manifest(Request $request, int $filmId): Response
    {
        $token = $request->query('token');
        $payload = $this->signer->verifyStreamToken($token ?? '');

        if (!$payload || (int) $payload['f'] !== $filmId) {
            Log::warning('Manifest proxy: token invalide', ['film_id' => $filmId, 'ip' => $request->ip()]);
            abort(403, 'Token de stream invalide ou expiré.');
        }

        $userId = (int) $payload['u'];
        $asset  = VideoAsset::where('film_id', $filmId)->where('status', 'ready')->firstOrFail();

        // Récupérer le manifest HLS depuis le CDN/S3
        $cdnUrl = $asset->hls_url;
        if (!$cdnUrl) {
            abort(503, 'Vidéo non disponible.');
        }

        try {
            $manifestContent = Http::timeout(10)->get($cdnUrl)->body();
        } catch (\Exception $e) {
            Log::error('Manifest proxy: erreur CDN', ['url' => $cdnUrl, 'error' => $e->getMessage()]);
            abort(502, 'Erreur de récupération du manifest.');
        }

        // Générer le token DRM pour le serveur de clés
        $deviceId = $request->header('X-Device-ID', 'unknown');
        $drmToken = $this->drm->generateKeyToken($userId, $filmId, $deviceId);

        // URL du serveur de clés DRM (notre endpoint)
        $keyServerUrl = route('drm.key', $filmId) . '?token=' . urlencode($drmToken);

        // Réécrire le manifest
        $rewritten = $this->rewriteManifest($manifestContent, $filmId, $userId, $keyServerUrl);

        return response($rewritten, 200)
            ->header('Content-Type', 'application/vnd.apple.mpegurl')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'DENY')
            ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request));
    }

    /**
     * Proxy de segments HLS chiffrés (.ts).
     * Valide la signature avant de relayer le segment depuis le CDN.
     */
    public function segment(Request $request, int $filmId): Response
    {
        if (!$this->signer->verify('/api/video/' . $filmId . '/segment', $request->query())) {
            Log::warning('Segment proxy: signature invalide', ['ip' => $request->ip(), 'film_id' => $filmId]);
            abort(403, 'Signature invalide.');
        }

        $segmentPath = $request->query('path');
        if (!$segmentPath || !$this->isValidSegmentPath($segmentPath)) {
            abort(400, 'Chemin de segment invalide.');
        }

        try {
            $response = Http::timeout(15)->get($segmentPath);
            $content  = $response->body();
        } catch (\Exception $e) {
            abort(502, 'Erreur de récupération du segment.');
        }

        return response($content, 200)
            ->header('Content-Type', 'video/mp2t')
            ->header('Cache-Control', 'no-store, no-cache, private')
            ->header('Pragma', 'no-cache')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Réécrit le manifest M3U8 :
     * - Remplace les #EXT-X-KEY par l'URL de notre serveur de clés
     * - Remplace les URLs de segments par des URLs signées passant par notre proxy
     * - Remplace les URLs de sous-manifests (variantes) par notre proxy
     */
    private function rewriteManifest(string $content, int $filmId, int $userId, string $keyServerUrl): string
    {
        $lines = explode("\n", $content);
        $output = [];

        foreach ($lines as $line) {
            $line = rtrim($line);

            // Injecter/remplacer la directive de chiffrement AES-128
            if (str_starts_with($line, '#EXT-X-KEY')) {
                $line = '#EXT-X-KEY:METHOD=AES-128,URI="' . $keyServerUrl . '",IV=0x00000000000000000000000000000000';
            }

            // Réécrire les URLs de variantes (playlist maître → sous-playlists)
            if (!str_starts_with($line, '#') && str_contains($line, '.m3u8') && filter_var($line, FILTER_VALIDATE_URL)) {
                $line = $this->proxyManifestUrl($line, $filmId, $userId);
            }

            // Réécrire les URLs de segments .ts
            if (!str_starts_with($line, '#') && (str_contains($line, '.ts') || str_contains($line, '.aac')) && filter_var($line, FILTER_VALIDATE_URL)) {
                $line = $this->proxySegmentUrl($line, $filmId, $userId);
            }

            $output[] = $line;
        }

        // Forcer l'ajout du chiffrement si absent du manifest
        if (!str_contains($content, '#EXT-X-KEY')) {
            array_splice($output, 1, 0, [
                '#EXT-X-KEY:METHOD=AES-128,URI="' . $keyServerUrl . '",IV=0x00000000000000000000000000000000',
            ]);
        }

        return implode("\n", $output);
    }

    private function proxyManifestUrl(string $originalUrl, int $filmId, int $userId): string
    {
        $token = $this->signer->generateStreamToken($userId, $filmId, 0, 'proxy');
        return route('video.manifest', $filmId) . '?token=' . urlencode($token) . '&src=' . urlencode($originalUrl);
    }

    private function proxySegmentUrl(string $originalUrl, int $filmId, int $userId): string
    {
        $signedUrl = $this->signer->signSegment('/api/video/' . $filmId . '/segment', $userId, $filmId);
        return $signedUrl . '&path=' . urlencode($originalUrl);
    }

    private function isValidSegmentPath(string $path): bool
    {
        // Seules les URLs HTTPS de domaines CDN connus sont autorisées
        if (!str_starts_with($path, 'https://')) {
            return false;
        }

        $allowedHosts = [
            's3.amazonaws.com',
            's3.eu-west-3.amazonaws.com',
            'cloudfront.net',
            'd1.cloudfront.net',
            config('tvod.cdn_host', ''),
        ];

        $host = parse_url($path, PHP_URL_HOST) ?? '';
        foreach ($allowedHosts as $allowed) {
            if ($allowed && str_ends_with($host, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function getAllowedOrigin(Request $request): string
    {
        $origin = $request->header('Origin', '');
        $allowed = config('tvod.allowed_origins', []);

        if (in_array($origin, $allowed)) {
            return $origin;
        }

        // En dev, accepter localhost
        if (app()->isLocal() && str_contains($origin, 'localhost')) {
            return $origin;
        }

        return '';
    }
}
