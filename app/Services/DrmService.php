<?php

namespace App\Services;

use App\Models\VideoAsset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DrmService
{
    private string $masterSecret;
    private int $keyRotationHours;

    public function __construct()
    {
        $this->masterSecret    = config('tvod.drm_secret_key') ?: config('app.key');
        $this->keyRotationHours = (int) config('tvod.drm_key_rotation_hours', 24);
    }

    /**
     * Retourne la clé AES-128 active pour un film (rotation automatique toutes les N heures).
     * La clé est stockée chiffrée en base ; on la régénère si expirée.
     */
    public function getActiveKey(VideoAsset $asset): string
    {
        if ($asset->drm_key_expires_at && $asset->drm_key_expires_at->isFuture()) {
            return $this->decryptKey($asset->drm_key_encrypted);
        }

        return $this->rotateKey($asset);
    }

    /**
     * Génère et persiste une nouvelle clé AES-128.
     */
    public function rotateKey(VideoAsset $asset): string
    {
        $rawKey = random_bytes(16); // 128 bits

        $asset->update([
            'drm_key_id'        => Str::uuid(),
            'drm_key_encrypted' => $this->encryptKey($rawKey),
            'drm_key_expires_at' => now()->addHours($this->keyRotationHours),
        ]);

        return $rawKey;
    }

    /**
     * Génère un token d'accès DRM signé (user + film + device + expiry).
     * Ce token est passé à l'app mobile et présenté à /drm/key pour obtenir la clé.
     */
    public function generateKeyToken(int $userId, int $filmId, string $deviceId, int $ttlMinutes = 240): string
    {
        $payload = base64_encode(json_encode([
            'u'   => $userId,
            'f'   => $filmId,
            'd'   => hash('sha256', $deviceId),
            'exp' => now()->addMinutes($ttlMinutes)->timestamp,
            'jti' => Str::random(8),
        ]));

        $sig = hash_hmac('sha256', $payload, $this->masterSecret);

        return "$payload.$sig";
    }

    /**
     * Valide et décode un token DRM. Retourne le payload ou null si invalide.
     */
    public function verifyKeyToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $sig] = $parts;

        // Vérification HMAC (timing-safe)
        $expected = hash_hmac('sha256', $payload, $this->masterSecret);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $data = json_decode(base64_decode($payload), true);

        if (!$data || $data['exp'] < now()->timestamp) {
            return null;
        }

        return $data;
    }

    /**
     * Génère un IV (Initialization Vector) unique par segment pour AES-128 CTR.
     */
    public function generateSegmentIv(int $segmentIndex): string
    {
        return pack('N4', 0, 0, 0, $segmentIndex);
    }

    private function encryptKey(string $rawKey): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($rawKey, 'AES-256-CBC', substr($this->masterSecret, 7, 32), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decryptKey(string $encryptedKey): string
    {
        $data = base64_decode($encryptedKey);
        $iv   = substr($data, 0, 16);
        $enc  = substr($data, 16);
        return openssl_decrypt($enc, 'AES-256-CBC', substr($this->masterSecret, 7, 32), OPENSSL_RAW_DATA, $iv);
    }
}
