@extends('admin.layouts.app')

@section('title', isset($film) ? 'Modifier ' . $film->title : 'Nouveau film')
@section('heading', isset($film) ? 'Modifier : ' . $film->title : 'Ajouter un film')

@section('content')

@php
    $action = isset($film) ? route('admin.films.update', $film) : route('admin.films.store');
    $method = isset($film) ? 'PUT' : 'POST';
    $languages = ['fr' => 'Français', 'en' => 'Anglais', 'ar' => 'Arabe', 'dioula' => 'Dioula', 'wolof' => 'Wolof', 'hausa' => 'Haoussa', 'yoruba' => 'Yoruba'];
    $countryLabels = ['CI' => 'Côte d\'Ivoire', 'SN' => 'Sénégal', 'NG' => 'Nigeria', 'GH' => 'Ghana', 'BF' => 'Burkina Faso'];
    $defaultPrices = ['CI' => 500, 'SN' => 500, 'NG' => 300, 'GH' => 5, 'BF' => 500];
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-8 max-w-4xl">
    @csrf
    @method($method)

    {{-- Section : Informations générales --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-5">
        <h2 class="text-sm font-semibold text-gray-300 border-b border-gray-800 pb-3">📋 Informations générales</h2>

        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Titre *</label>
                <input type="text" name="title" value="{{ old('title', $film->title ?? '') }}" required
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Synopsis</label>
                <textarea name="synopsis" rows="4"
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500 resize-none">{{ old('synopsis', $film->synopsis ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Réalisateur</label>
                <input type="text" name="director" value="{{ old('director', $film->director ?? '') }}"
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Durée (minutes)</label>
                <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $film->duration_minutes ?? '') }}" min="1"
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Année de sortie</label>
                <input type="number" name="release_year" value="{{ old('release_year', $film->release_year ?? '') }}" min="1900" max="2030"
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Classification d'âge *</label>
                <select name="age_rating" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
                    @foreach(['ALL' => 'Tous publics', 'G' => 'G', 'PG' => 'PG', 'PG-13' => 'PG-13', 'R' => 'R', 'NC-17' => 'NC-17'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('age_rating', $film->age_rating ?? 'ALL') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">URL Trailer</label>
                <input type="url" name="trailer_url" value="{{ old('trailer_url', $film->trailer_url ?? '') }}"
                    placeholder="https://youtube.com/watch?v=..."
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Casting (noms séparés par virgule)</label>
                <input type="text" name="cast" value="{{ old('cast', $film->cast ?? '') }}"
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>
        </div>
    </div>

    {{-- Section : Genres --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-300 border-b border-gray-800 pb-3 mb-4">🎭 Genres</h2>
        <div class="flex flex-wrap gap-3">
            @foreach($genres as $genre)
                @php $checked = old('genres') ? in_array($genre->id, old('genres')) : ($film->genres ?? collect())->contains($genre->id); @endphp
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="genres[]" value="{{ $genre->id }}" @checked($checked)
                        class="rounded border-gray-600">
                    <span class="text-sm text-gray-300">{{ $genre->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Section : Langues & Sous-titres --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-300 border-b border-gray-800 pb-3 mb-4">🌐 Langues</h2>
        <div class="grid grid-cols-2 gap-6">
            <div>
                <p class="text-xs text-gray-400 mb-3 font-medium">Audio disponible</p>
                @foreach($languages as $code => $label)
                    @php $checked = old('available_languages') ? in_array($code, old('available_languages')) : in_array($code, $film->available_languages ?? []); @endphp
                    <label class="flex items-center gap-2 mb-2 cursor-pointer">
                        <input type="checkbox" name="available_languages[]" value="{{ $code }}" @checked($checked)>
                        <span class="text-sm text-gray-300">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-3 font-medium">Sous-titres disponibles</p>
                @foreach($languages as $code => $label)
                    @php $checked = old('available_subtitles') ? in_array($code, old('available_subtitles')) : in_array($code, $film->available_subtitles ?? []); @endphp
                    <label class="flex items-center gap-2 mb-2 cursor-pointer">
                        <input type="checkbox" name="available_subtitles[]" value="{{ $code }}" @checked($checked)>
                        <span class="text-sm text-gray-300">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Section : Prix par pays --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-300 border-b border-gray-800 pb-3 mb-4">💰 Prix par pays</h2>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($countries as $code)
                @php
                    $existingPrice = isset($film) ? $film->prices->firstWhere('country', $code)?->amount : null;
                    $currency = ['CI' => 'XOF', 'SN' => 'XOF', 'NG' => 'NGN', 'GH' => 'GHS', 'BF' => 'XOF'][$code];
                @endphp
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">
                        {{ $countryLabels[$code] }} ({{ $currency }})
                    </label>
                    <input
                        type="number"
                        name="prices[{{ $code }}]"
                        value="{{ old("prices.$code", $existingPrice ?? $defaultPrices[$code]) }}"
                        min="0"
                        class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500"
                    >
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section : Droits territoriaux --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-300 border-b border-gray-800 pb-3 mb-4">🗺 Disponibilité par pays</h2>
        <div class="flex flex-wrap gap-4">
            @foreach($countries as $code)
                @php $checked = old('available_countries') ? in_array($code, old('available_countries')) : in_array($code, $film->available_countries ?? $countries); @endphp
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="available_countries[]" value="{{ $code }}" @checked($checked)>
                    <span class="text-sm text-gray-300">{{ $countryLabels[$code] }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Section : Médias --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-5">
        <h2 class="text-sm font-semibold text-gray-300 border-b border-gray-800 pb-3">🖼 Images</h2>
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Affiche (thumbnail) — max 2 Mo</label>
                @if(isset($film) && $film->thumbnail)
                    <img src="{{ asset('storage/' . $film->thumbnail) }}" class="w-24 h-32 object-cover rounded mb-2">
                @endif
                <input type="file" name="thumbnail" accept="image/*"
                    class="w-full text-sm text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-gray-700 file:text-gray-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Bannière — max 5 Mo</label>
                @if(isset($film) && $film->banner)
                    <img src="{{ asset('storage/' . $film->banner) }}" class="w-full h-20 object-cover rounded mb-2">
                @endif
                <input type="file" name="banner" accept="image/*"
                    class="w-full text-sm text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-gray-700 file:text-gray-300">
            </div>
        </div>
    </div>

    {{-- Section : DRM --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-300 border-b border-gray-800 pb-3 mb-4">🔒 Protection (DRM)</h2>
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="drm_enabled" value="1" @checked(old('drm_enabled', $film->drm_enabled ?? true))
                class="w-5 h-5 rounded border-gray-600">
            <span class="text-sm text-gray-300">Activer le chiffrement AES-128 pour ce film</span>
        </label>
    </div>

    {{-- Submit --}}
    <div class="flex items-center gap-4">
        <button type="submit"
            class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-3 rounded-xl text-sm transition">
            {{ isset($film) ? 'Enregistrer les modifications' : 'Créer le film' }}
        </button>
        <a href="{{ route('admin.films.index') }}" class="text-gray-400 hover:text-white text-sm transition">Annuler</a>
    </div>
</form>

@endsection
