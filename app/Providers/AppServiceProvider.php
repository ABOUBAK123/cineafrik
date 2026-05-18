<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Injection de dépendances pour les services DRM
        $this->app->singleton(\App\Services\DrmService::class);
        $this->app->singleton(\App\Services\SignedUrlService::class);
    }

    public function boot(): void
    {
        Builder::defaultStringLength(191);

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // API générale : 60 req/min par IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Authentification : 10 tentatives/min (anti brute-force)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // OTP : 3 envois/heure par téléphone (anti-spam SMS)
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perHour(3)->by($request->input('phone', $request->ip()));
        });

        // Initiation paiement : 5/min par user (anti-fraude)
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Clé DRM : 30/min par user (lecture normale ≤ 1 clé/24h, mais on tolère les retries)
        RateLimiter::for('drm_key', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Proxy manifest HLS : 10/min par user (1 film = 1 manifest)
        RateLimiter::for('manifest', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Segments vidéo : 300/min par user (≈ 5 segments/s = qualité 720p normale)
        RateLimiter::for('segment', function (Request $request) {
            return [
                Limit::perMinute(300)->by($request->user()?->id ?: $request->ip()),
                Limit::perMinute(500)->by($request->ip()), // double garde par IP brute
            ];
        });
    }
}
