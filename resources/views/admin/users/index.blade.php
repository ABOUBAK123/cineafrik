@extends('admin.layouts.app')

@section('title', 'Utilisateurs')
@section('heading', 'Gestion des utilisateurs')

@section('content')

{{-- Filtres --}}
<form method="GET" class="flex gap-3 mb-6 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="Nom, email ou téléphone..."
        class="bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2 text-sm w-64 focus:outline-none focus:border-orange-500">

    <select name="country" class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">Tous les pays</option>
        @foreach(['CI' => 'Côte d\'Ivoire', 'SN' => 'Sénégal', 'NG' => 'Nigeria', 'GH' => 'Ghana', 'BF' => 'Burkina Faso'] as $code => $label)
            <option value="{{ $code }}" @selected(request('country') === $code)>{{ $label }}</option>
        @endforeach
    </select>

    <select name="status" class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">Tous les statuts</option>
        <option value="active" @selected(request('status') === 'active')>Actif</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Suspendu</option>
        <option value="banned" @selected(request('status') === 'banned')>Banni</option>
    </select>

    <button class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm transition">Filtrer</button>
    @if(request()->hasAny(['search', 'country', 'status']))
        <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white px-3 py-2 text-sm">Réinitialiser</a>
    @endif
</form>

<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-800 text-gray-400 text-xs uppercase tracking-wider">
            <tr>
                <th class="text-left px-5 py-3">Utilisateur</th>
                <th class="text-left px-4 py-3">Contact</th>
                <th class="text-center px-4 py-3">Pays</th>
                <th class="text-center px-4 py-3">Achats</th>
                <th class="text-center px-4 py-3">Dépensé</th>
                <th class="text-center px-4 py-3">Statut</th>
                <th class="text-center px-4 py-3">Inscription</th>
                <th class="text-left px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @forelse($users as $user)
                <tr class="hover:bg-gray-800/50 transition">
                    <td class="px-5 py-3">
                        <div class="font-medium text-white">{{ $user->name }}</div>
                        @if($user->google_id) <span class="text-xs text-blue-400">Google</span> @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400">
                        <div>{{ $user->email ?? '—' }}</div>
                        <div class="text-xs">{{ $user->phone ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="bg-gray-700 text-gray-300 text-xs px-2 py-0.5 rounded">{{ $user->country }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-300">{{ $user->purchases }}</td>
                    <td class="px-4 py-3 text-center text-green-400 text-xs">
                        {{ $user->total_spent ? number_format($user->total_spent) . ' XOF' : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span @class([
                            'text-xs px-2.5 py-1 rounded-full font-medium',
                            'bg-green-900 text-green-300' => $user->status === 'active',
                            'bg-yellow-900 text-yellow-300' => $user->status === 'suspended',
                            'bg-red-900 text-red-300' => $user->status === 'banned',
                        ])>{{ ucfirst($user->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.users.show', $user) }}"
                                class="text-orange-400 hover:text-orange-300 text-xs px-2 py-1 rounded bg-orange-900/30 transition">Voir</a>

                            <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                @csrf @method('PATCH')
                                @if($user->status === 'active')
                                    <input type="hidden" name="status" value="suspended">
                                    <button class="text-yellow-400 text-xs px-2 py-1 rounded bg-yellow-900/30 hover:bg-yellow-900/60 transition">Suspendre</button>
                                @else
                                    <input type="hidden" name="status" value="active">
                                    <button class="text-green-400 text-xs px-2 py-1 rounded bg-green-900/30 hover:bg-green-900/60 transition">Réactiver</button>
                                @endif
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-12 text-center text-gray-500">Aucun utilisateur trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">{{ $users->links('vendor.pagination.tailwind') }}</div>

@endsection
