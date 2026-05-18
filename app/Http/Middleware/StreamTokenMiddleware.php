<?php

namespace App\Http\Middleware;

use App\Services\SignedUrlService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StreamTokenMiddleware
{
    public function __construct(private SignedUrlService $signer) {}

    /**
     * Vérifie que chaque requête vers les endpoints de streaming
     * porte un token de stream valide et non expiré.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token')
            ?? $request->header('X-Stream-Token');

        if (!$token) {
            return response()->json(['message' => 'Token de stream manquant.'], 401);
        }

        $payload = $this->signer->verifyStreamToken($token);

        if (!$payload) {
            return response()->json(['message' => 'Token de stream invalide ou expiré.'], 401);
        }

        // Vérification device binding : le token doit avoir été émis pour ce device
        $deviceId = $request->header('X-Device-ID', '');
        if (!empty($payload['d']) && $deviceId) {
            $expectedDeviceHash = hash('sha256', $deviceId);
            if (!hash_equals($payload['d'], $expectedDeviceHash)) {
                return response()->json(['message' => 'Appareil non autorisé pour ce token.'], 403);
            }
        }

        // Injecter le payload dans la requête pour les controllers
        $request->merge(['_stream_payload' => $payload]);

        return $next($request);
    }
}
