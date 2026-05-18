<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Headers appliqués à toutes les réponses API et vidéo pour :
     * - Interdire la mise en cache des contenus protégés
     * - Bloquer l'intégration dans des iframes (clickjacking)
     * - Empêcher le MIME sniffing (qui pourrait contourner les protections)
     * - Restreindre les sources autorisées (CSP)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Bloquer complètement la mise en cache des réponses API/vidéo
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // Bloquer l'intégration dans des iframes
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêcher le MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Forcer HTTPS en production
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content-Security-Policy strict pour les routes API
        if ($request->is('api/*')) {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        }

        // Bloquer Referrer pour éviter de fuiter les URLs internes
        $response->headers->set('Referrer-Policy', 'no-referrer');

        // Interdire l'accès aux APIs depuis du JS non autorisé
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        return $response;
    }
}
