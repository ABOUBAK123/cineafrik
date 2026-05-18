@extends('admin.layouts.app')

@section('title', 'Films')
@section('heading', 'Gestion des films')

@section('content')

{{-- Header actions --}}
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex gap-3">
        <input
            type="text" name="search" value="{{ request('search') }}"
            placeholder="Rechercher un film..."
            class="bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2 text-sm w-64 focus:outline-none focus:border-orange-500"
        >
        <select name="status" class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
            <option value="">Tous les statuts</option>
            <option value="draft" @selected(request('status') === 'draft')>Brouillon</option>
            <option value="published" @selected(request('status') === 'published')>Publié</option>
            <option value="archived" @selected(request('status') === 'archived')>Archivé</option>
        </select>
        <button class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm transition">Filtrer</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.films.index') }}" class="text-gray-400 hover:text-white px-3 py-2 text-sm">Réinitialiser</a>
        @endif
    </form>
    <a href="{{ route('admin.films.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
        + Ajouter un film
    </a>
</div>

{{-- Films table --}}
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-800 text-gray-400 text-xs uppercase tracking-wider">
            <tr>
                <th class="text-left px-5 py-3">Film</th>
                <th class="text-left px-4 py-3">Genres</th>
                <th class="text-center px-4 py-3">Statut</th>
                <th class="text-center px-4 py-3">Achats</th>
                <th class="text-center px-4 py-3">Note</th>
                <th class="text-left px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @forelse($films as $film)
                <tr class="hover:bg-gray-800/50 transition">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            @if($film->thumbnail)
                                <img src="{{ asset('storage/' . $film->thumbnail) }}" class="w-10 h-14 object-cover rounded" alt="">
                            @else
                                <div class="w-10 h-14 bg-gray-800 rounded flex items-center justify-center text-gray-600">🎬</div>
                            @endif
                            <div>
                                <div class="text-white font-medium">{{ $film->title }}</div>
                                <div class="text-gray-500 text-xs">{{ $film->duration_minutes ? $film->duration_minutes . ' min' : '' }} {{ $film->release_year }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach($film->genres->take(2) as $genre)
                                <span class="bg-gray-700 text-gray-300 text-xs px-2 py-0.5 rounded">{{ $genre->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span @class([
                            'text-xs px-2.5 py-1 rounded-full font-medium',
                            'bg-yellow-900 text-yellow-300' => $film->status === 'draft',
                            'bg-green-900 text-green-300' => $film->status === 'published',
                            'bg-gray-700 text-gray-400' => $film->status === 'archived',
                            'bg-blue-900 text-blue-300' => $film->status === 'processing',
                        ])>{{ ucfirst($film->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-300">{{ $film->purchases }}</td>
                    <td class="px-4 py-3 text-center text-yellow-400">{{ $film->rating > 0 ? '⭐ ' . $film->rating : '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.films.edit', $film) }}" class="text-orange-400 hover:text-orange-300 text-xs px-2 py-1 rounded bg-orange-900/30 hover:bg-orange-900/60 transition">Modifier</a>

                            {{-- Quick status toggle --}}
                            <form method="POST" action="{{ route('admin.films.status', $film) }}">
                                @csrf @method('PATCH')
                                @if($film->status === 'draft')
                                    <input type="hidden" name="status" value="published">
                                    <button class="text-green-400 hover:text-green-300 text-xs px-2 py-1 rounded bg-green-900/30 hover:bg-green-900/60 transition">Publier</button>
                                @elseif($film->status === 'published')
                                    <input type="hidden" name="status" value="archived">
                                    <button class="text-gray-400 hover:text-gray-300 text-xs px-2 py-1 rounded bg-gray-700 hover:bg-gray-600 transition">Archiver</button>
                                @else
                                    <input type="hidden" name="status" value="draft">
                                    <button class="text-yellow-400 hover:text-yellow-300 text-xs px-2 py-1 rounded bg-yellow-900/30 transition">Dépublier</button>
                                @endif
                            </form>

                            <form method="POST" action="{{ route('admin.films.destroy', $film) }}" onsubmit="return confirm('Supprimer ce film ?')">
                                @csrf @method('DELETE')
                                <button class="text-red-400 hover:text-red-300 text-xs px-2 py-1 rounded bg-red-900/30 hover:bg-red-900/60 transition">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                        Aucun film trouvé. <a href="{{ route('admin.films.create') }}" class="text-orange-400 hover:underline">Ajouter le premier film</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="mt-5">
    {{ $films->links('vendor.pagination.tailwind') }}
</div>

@endsection
