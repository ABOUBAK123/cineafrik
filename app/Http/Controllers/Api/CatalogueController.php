<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $country = $request->user()?->country ?? $request->header('X-Country', 'CI');

        $query = Film::published()
            ->availableIn($country)
            ->with(['genres', 'prices' => fn($q) => $q->where('country', $country)])
            ->withCount('reviews');

        if ($request->filled('genre')) {
            $query->whereHas('genres', fn($q) => $q->where('slug', $request->genre));
        }

        if ($request->filled('language')) {
            $query->whereJsonContains('available_languages', $request->language);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('director', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('age_rating')) {
            $query->where('age_rating', $request->age_rating);
        }

        $sortMap = [
            'latest' => ['created_at', 'desc'],
            'rating' => ['rating', 'desc'],
            'title' => ['title', 'asc'],
        ];
        [$sortCol, $sortDir] = $sortMap[$request->sort ?? 'latest'];
        $query->orderBy($sortCol, $sortDir);

        $films = $query->paginate(20);

        return response()->json($films);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $country = $request->user()?->country ?? $request->header('X-Country', 'CI');

        $film = Film::published()
            ->where('slug', $slug)
            ->with([
                'genres',
                'prices' => fn($q) => $q->where('country', $country),
                'reviews' => fn($q) => $q->with('user:id,name')->latest()->limit(10),
                'videoAsset:id,film_id,hls_url,dash_url,status',
            ])
            ->firstOrFail();

        $film->user_has_access = $request->user()?->hasAccessToFilm($film->id) ?? false;

        return response()->json($film);
    }

    public function genres(): JsonResponse
    {
        return response()->json(Genre::all());
    }

    public function featured(Request $request): JsonResponse
    {
        $country = $request->user()?->country ?? $request->header('X-Country', 'CI');

        $films = Film::published()
            ->availableIn($country)
            ->with(['genres', 'prices' => fn($q) => $q->where('country', $country)])
            ->orderByDesc('rating')
            ->limit(10)
            ->get();

        return response()->json($films);
    }
}
