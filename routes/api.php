<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\DrmKeyController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlaybackController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VideoProxyController;
use Illuminate\Support\Facades\Route;

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('otp/send', [AuthController::class, 'sendOtp'])->middleware('throttle:otp');
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('profile', [AuthController::class, 'updateProfile']);
    });
});

// ─── Catalogue (public) ──────────────────────────────────────────────────────
Route::prefix('catalogue')->group(function () {
    Route::get('/', [CatalogueController::class, 'index']);
    Route::get('genres', [CatalogueController::class, 'genres']);
    Route::get('featured', [CatalogueController::class, 'featured']);
    Route::get('{slug}', [CatalogueController::class, 'show']);
});

// ─── Paiement ────────────────────────────────────────────────────────────────
Route::prefix('payment')->group(function () {
    Route::post('webhook/{provider}', [PaymentController::class, 'webhook']);

    Route::middleware(['auth:sanctum', 'throttle:payment'])->group(function () {
        Route::post('initiate', [PaymentController::class, 'initiate']);
        Route::get('status/{reference}', [PaymentController::class, 'status']);
        Route::get('history', [PaymentController::class, 'history']);
    });
});

// ─── Streaming sécurisé ──────────────────────────────────────────────────────
// Proxy manifest HLS : token de stream requis + anti-hotlink + rate limit
Route::prefix('video')->middleware(['anti.hotlink', 'security.headers'])->group(function () {

    // Manifest HLS proxy (token dans query string, pas Sanctum, car appelé par le player)
    Route::get('{filmId}/manifest', [VideoProxyController::class, 'manifest'])
        ->middleware('throttle:manifest')
        ->name('video.manifest');

    // Segments HLS proxy (URL signée)
    Route::get('{filmId}/segment', [VideoProxyController::class, 'segment'])
        ->middleware('throttle:segment')
        ->name('video.segment');
});

// ─── Serveur de clés DRM AES-128 ────────────────────────────────────────────
// Pas de Sanctum ici : le player HLS appelle cet endpoint directement
Route::get('drm/key/{filmId}', [DrmKeyController::class, 'serveKey'])
    ->middleware(['anti.hotlink', 'security.headers', 'throttle:drm_key'])
    ->name('drm.key');

// ─── Sessions de lecture ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('playback')->group(function () {
    Route::post('stream/{filmId}', [PlaybackController::class, 'requestStream']);
    Route::post('heartbeat/{sessionId}', [PlaybackController::class, 'heartbeat']);
    Route::post('end/{sessionId}', [PlaybackController::class, 'endSession']);
    Route::post('offline/{filmId}', [PlaybackController::class, 'requestOfflineLicense']);
    Route::get('offline/{filmId}/verify', [PlaybackController::class, 'verifyOfflineLicense']);
});

// ─── Espace utilisateur ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('library', [UserController::class, 'library']);
    Route::get('watchlist', [UserController::class, 'watchlist']);
    Route::post('watchlist/{filmId}', [UserController::class, 'addToWatchlist']);
    Route::delete('watchlist/{filmId}', [UserController::class, 'removeFromWatchlist']);
    Route::get('downloads', [UserController::class, 'offlineDownloads']);
    Route::delete('downloads/{downloadId}', [UserController::class, 'deleteOfflineDownload']);
    Route::post('reviews/{filmId}', [UserController::class, 'submitReview']);
});
