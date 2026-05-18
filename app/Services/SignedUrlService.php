<?php

namespace App\Services;

use Illuminate\Support\Str;

class SignedUrlService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = config('tvod.drm_secret_key') ?: config('app.key');
    }

    /**
     * Génère une URL signée avec expiration et liaison utilisateur.
     * Le CDN (ou notre proxy) vérifie la signature avant de servir le contenu.
     */
    public function sign(string $path, int $userId, int $filmId, int $ttlMinutes = 240): string
    {
        $expires = now()->addMinutes($ttlMinutes)->timestamp;

        $params = http_build_query([
            'u'       => $userId,
            'f'       => $filmId,
            'exp'     => $expires,
        ]);

        $signature = $this->makeSignature("$path?$params");

        return url($path) . "?$params&sig=$signature";
    }

    /**
     * Signe une URL pour les segments HLS (TTL court : 30 min).
     */
    public function signSegment(string $path, int $userId, int $filmId): string
    {
        return $this->sign($path, $userId, $filmId, 30);
    }

    /**
     * Valide une URL signée entrante.
     */
    public function verify(string $path, array $query): bool
    {
        if (empty($query['sig']) || empty($query['exp']) || empty($query['u'])) {
            return false;
        }

        if ((int) $query['exp'] < now()->timestamp) {
            return false;
        }

        $params = http_build_query([
            'u'   => $query['u'],
            'f'   => $query['f'] ?? 0,
            'exp' => $query['exp'],
        ]);

        $expected = $this->makeSignature("$path?$params");

        return hash_equals($expected, $query['sig']);
    }

    /**
     * Génère un token de stream JWT-like lié à user + device + film.
     */
    public function generateStreamToken(int $userId, int $filmId, int $sessionId, string $deviceId): string
    {
        $payload = base64_encode(json_encode([
            'u'   => $userId,
            'f'   => $filmId,
            's'   => $sessionId,
            'd'   => hash('sha256', $deviceId),
            'exp' => now()->addHours(4)->timestamp,
            'jti' => Str::random(8),
        ]));

        $sig = hash_hmac('sha256', $payload, $this->secret);

        return "$payload.$sig";
    }

    /**
     * Valide et décode un token de stream. Retourne le payload ou null.
     */
    public function verifyStreamToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $sig] = $parts;

        $expected = hash_hmac('sha256', $payload, $this->secret);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $data = json_decode(base64_decode($payload), true);

        if (!$data || $data['exp'] < now()->timestamp) {
            return null;
        }

        return $data;
    }

    private function makeSignature(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret);
    }
}
