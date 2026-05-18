<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function library(Request $request): JsonResponse
    {
        $accesses = $request->user()
            ->accesses()
            ->with('film:id,title,slug,thumbnail,duration_minutes')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->paginate(20);

        return response()->json($accesses);
    }

    public function watchlist(Request $request): JsonResponse
    {
        $films = $request->user()
            ->watchlist()
            ->with(['prices' => fn($q) => $q->where('country', $request->user()->country)])
            ->paginate(20);

        return response()->json($films);
    }

    public function addToWatchlist(Request $request, int $filmId): JsonResponse
    {
        $film = Film::published()->findOrFail($filmId);
        $request->user()->watchlist()->syncWithoutDetaching([$film->id]);
        return response()->json(['message' => 'Ajouté à votre liste.']);
    }

    public function removeFromWatchlist(Request $request, int $filmId): JsonResponse
    {
        $request->user()->watchlist()->detach($filmId);
        return response()->json(['message' => 'Retiré de votre liste.']);
    }

    public function offlineDownloads(Request $request): JsonResponse
    {
        $downloads = $request->user()
            ->offlineDownloads()
            ->active()
            ->with('film:id,title,slug,thumbnail,duration_minutes')
            ->get();

        return response()->json($downloads);
    }

    public function deleteOfflineDownload(Request $request, int $downloadId): JsonResponse
    {
        $download = $request->user()
            ->offlineDownloads()
            ->findOrFail($downloadId);

        $download->update(['status' => 'deleted']);

        return response()->json(['message' => 'Téléchargement supprimé.']);
    }

    public function submitReview(Request $request, int $filmId): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAccessToFilm($filmId)) {
            return response()->json(['message' => 'Vous devez acheter ce film pour le noter.'], 403);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = $user->reviews()->updateOrCreate(
            ['film_id' => $filmId],
            array_merge($data, ['status' => 'pending'])
        );

        return response()->json($review, 201);
    }
}
