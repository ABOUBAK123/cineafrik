<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\FilmPrice;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FilmController extends Controller
{
    private const COUNTRIES = ['CI', 'SN', 'NG', 'GH', 'BF'];
    private const CURRENCIES = ['CI' => 'XOF', 'SN' => 'XOF', 'NG' => 'NGN', 'GH' => 'GHS', 'BF' => 'XOF'];

    public function index(Request $request)
    {
        $query = Film::withCount(['accesses as purchases', 'reviews'])
            ->with('genres');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $films = $query->latest()->paginate(15)->withQueryString();

        return view('admin.films.index', compact('films'));
    }

    public function create()
    {
        $genres = Genre::all();
        $countries = self::COUNTRIES;
        return view('admin.films.form', compact('genres', 'countries'));
    }

    public function store(Request $request)
    {
        $data = $this->validateFilm($request);

        $film = Film::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . Str::random(5),
            'synopsis' => $data['synopsis'] ?? null,
            'director' => $data['director'] ?? null,
            'cast' => $data['cast'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'release_year' => $data['release_year'] ?? null,
            'country_of_origin' => $data['country_of_origin'] ?? null,
            'original_language' => $data['original_language'] ?? null,
            'available_languages' => $data['available_languages'] ?? [],
            'available_subtitles' => $data['available_subtitles'] ?? [],
            'age_rating' => $data['age_rating'],
            'available_countries' => $data['available_countries'] ?? self::COUNTRIES,
            'trailer_url' => $data['trailer_url'] ?? null,
            'drm_enabled' => $request->boolean('drm_enabled', true),
            'status' => 'draft',
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store("films/{$film->id}", 'public');
            $film->update(['thumbnail' => $path]);
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store("films/{$film->id}", 'public');
            $film->update(['banner' => $path]);
        }

        if ($request->filled('genres')) {
            $film->genres()->sync($request->genres);
        }

        $this->savePrices($film, $request);

        return redirect()->route('admin.films.index')
            ->with('success', "Film \"{$film->title}\" créé avec succès.");
    }

    public function edit(Film $film)
    {
        $genres = Genre::all();
        $countries = self::COUNTRIES;
        $film->load(['genres', 'prices', 'videoAsset']);
        return view('admin.films.form', compact('film', 'genres', 'countries'));
    }

    public function update(Request $request, Film $film)
    {
        $data = $this->validateFilm($request);

        $film->update([
            'title' => $data['title'],
            'synopsis' => $data['synopsis'] ?? null,
            'director' => $data['director'] ?? null,
            'cast' => $data['cast'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'release_year' => $data['release_year'] ?? null,
            'country_of_origin' => $data['country_of_origin'] ?? null,
            'original_language' => $data['original_language'] ?? null,
            'available_languages' => $data['available_languages'] ?? [],
            'available_subtitles' => $data['available_subtitles'] ?? [],
            'age_rating' => $data['age_rating'],
            'available_countries' => $data['available_countries'] ?? self::COUNTRIES,
            'trailer_url' => $data['trailer_url'] ?? null,
            'drm_enabled' => $request->boolean('drm_enabled', true),
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($film->thumbnail) Storage::disk('public')->delete($film->thumbnail);
            $path = $request->file('thumbnail')->store("films/{$film->id}", 'public');
            $film->update(['thumbnail' => $path]);
        }

        if ($request->hasFile('banner')) {
            if ($film->banner) Storage::disk('public')->delete($film->banner);
            $path = $request->file('banner')->store("films/{$film->id}", 'public');
            $film->update(['banner' => $path]);
        }

        $film->genres()->sync($request->genres ?? []);
        $this->savePrices($film, $request);

        return redirect()->route('admin.films.edit', $film)
            ->with('success', 'Film mis à jour.');
    }

    public function updateStatus(Request $request, Film $film)
    {
        $request->validate(['status' => 'required|in:draft,published,archived']);
        $film->update(['status' => $request->status]);

        return back()->with('success', "Statut mis à jour : {$request->status}.");
    }

    public function destroy(Film $film)
    {
        $film->delete();
        return redirect()->route('admin.films.index')
            ->with('success', "Film supprimé.");
    }

    private function validateFilm(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'director' => 'nullable|string|max:100',
            'cast' => 'nullable|string|max:500',
            'duration_minutes' => 'nullable|integer|min:1|max:999',
            'release_year' => 'nullable|integer|min:1900|max:2030',
            'country_of_origin' => 'nullable|string|max:5',
            'original_language' => 'nullable|string|max:10',
            'available_languages' => 'nullable|array',
            'available_subtitles' => 'nullable|array',
            'age_rating' => 'required|in:G,PG,PG-13,R,NC-17,ALL',
            'available_countries' => 'nullable|array',
            'trailer_url' => 'nullable|url',
            'thumbnail' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:5120',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
        ]);
    }

    private function savePrices(Film $film, Request $request): void
    {
        foreach (self::COUNTRIES as $country) {
            $amount = $request->input("prices.$country");
            if ($amount !== null && $amount !== '') {
                FilmPrice::updateOrCreate(
                    ['film_id' => $film->id, 'country' => $country],
                    ['amount' => (int) $amount, 'currency' => self::CURRENCIES[$country]]
                );
            }
        }
    }
}
