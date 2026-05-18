<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AntiHotlinkMiddleware
{
    /**
     * Vérifie que les requêtes vers les endpoints vidéo proviennent
     * uniquement de l'application mobile ou du backoffice autorisé.
     * Bloque tout accès direct depuis un navigateur ou un outil de téléchargement.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->userAgent() ?? '';
        $referer   = $request->header('Referer', '');

        // Bloquer les User-Agents connus de téléchargement
        $blockedAgents = [
            'wget', 'curl', 'python-requests', 'go-http', 'libwww',
            'httrack', 'scrapy', 'youtube-dl', 'yt-dlp', 'aria2',
            'internetdownloadmanager', 'fdm', 'uget',
        ];

        $uaLower = strtolower($userAgent);
        foreach ($blockedAgents as $blocked) {
            if (str_contains($uaLower, $blocked)) {
                Log::warning('Anti-hotlink: UA bloqué', [
                    'ua' => $userAgent,
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                ]);
                abort(403, 'Accès refusé.');
            }
        }

        // En production : exiger un Referer de l'application ou l'absence de Referer (app native)
        if (app()->isProduction() && $referer !== '') {
            $allowedOrigins = config('tvod.allowed_origins', []);
            $refererHost = parse_url($referer, PHP_URL_HOST) ?? '';
            $allowed = false;

            foreach ($allowedOrigins as $origin) {
                if (str_ends_with($refererHost, parse_url($origin, PHP_URL_HOST) ?? $origin)) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                Log::warning('Anti-hotlink: Referer non autorisé', [
                    'referer' => $referer,
                    'ip'      => $request->ip(),
                ]);
                abort(403, 'Origine non autorisée.');
            }
        }

        return $next($request);
    }
}
