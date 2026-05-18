@extends('admin.layouts.app')

@section('title', $user->name)
@section('heading', 'Profil : ' . $user->name)

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Infos utilisateur --}}
    <div class="space-y-5">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">👤 Informations</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Nom</dt>
                    <dd class="text-white font-medium">{{ $user->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Email</dt>
                    <dd class="text-gray-300">{{ $user->email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Téléphone</dt>
                    <dd class="text-gray-300">{{ $user->phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Pays</dt>
                    <dd class="text-gray-300">{{ $user->country }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Statut</dt>
                    <dd>
                        <span @class([
                            'text-xs px-2 py-0.5 rounded-full',
                            'bg-green-900 text-green-300' => $user->status === 'active',
                            'bg-yellow-900 text-yellow-300' => $user->status === 'suspended',
                            'bg-red-900 text-red-300' => $user->status === 'banned',
                        ])>{{ ucfirst($user->status) }}</span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Inscrit le</dt>
                    <dd class="text-gray-400 text-xs">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Actions --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">⚙ Actions</h2>
            <form method="POST" action="{{ route('admin.users.status', $user) }}" class="space-y-2">
                @csrf @method('PATCH')
                @if($user->status !== 'active')
                    <input type="hidden" name="status" value="active">
                    <button class="w-full bg-green-700 hover:bg-green-600 text-white text-sm py-2 rounded-lg transition">✅ Réactiver</button>
                @endif
                @if($user->status !== 'suspended')
                    <input type="hidden" name="status" value="suspended">
                    <button class="w-full bg-yellow-700 hover:bg-yellow-600 text-white text-sm py-2 rounded-lg transition">⏸ Suspendre</button>
                @endif
                @if($user->status !== 'banned')
                    <input type="hidden" name="status" value="banned">
                    <button class="w-full bg-red-700 hover:bg-red-600 text-white text-sm py-2 rounded-lg transition">🚫 Bannir</button>
                @endif
            </form>
        </div>
    </div>

    {{-- Achats & Transactions --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Films achetés --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">🎬 Films achetés ({{ $user->accesses->count() }})</h2>
            @if($user->accesses->isEmpty())
                <p class="text-gray-500 text-sm">Aucun film acheté.</p>
            @else
                <div class="grid grid-cols-2 gap-3">
                    @foreach($user->accesses as $access)
                        <div class="flex items-center gap-3 bg-gray-800 rounded-lg p-3">
                            @if($access->film?->thumbnail)
                                <img src="{{ asset('storage/' . $access->film->thumbnail) }}" class="w-10 h-14 object-cover rounded">
                            @else
                                <div class="w-10 h-14 bg-gray-700 rounded flex items-center justify-center text-lg">🎬</div>
                            @endif
                            <div class="min-w-0">
                                <div class="text-sm text-white truncate">{{ $access->film?->title ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $access->created_at->format('d/m/Y') }}</div>
                                @if($access->first_played_at)
                                    <div class="text-xs text-green-400">Visionné</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Historique transactions --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">💳 Transactions récentes</h2>
            @if($user->transactions->isEmpty())
                <p class="text-gray-500 text-sm">Aucune transaction.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-gray-500 text-xs border-b border-gray-800">
                        <tr>
                            <th class="text-left pb-2">Film</th>
                            <th class="text-right pb-2">Montant</th>
                            <th class="text-center pb-2">Méthode</th>
                            <th class="text-center pb-2">Statut</th>
                            <th class="text-right pb-2">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($user->transactions as $tx)
                            <tr>
                                <td class="py-2 text-gray-300 truncate max-w-32">{{ $tx->film?->title ?? '—' }}</td>
                                <td class="py-2 text-right text-white font-medium">{{ number_format($tx->amount) }} {{ $tx->currency }}</td>
                                <td class="py-2 text-center text-gray-400 text-xs">{{ $tx->payment_method }}</td>
                                <td class="py-2 text-center">
                                    <span @class([
                                        'text-xs px-2 py-0.5 rounded-full',
                                        'bg-green-900 text-green-300' => $tx->status === 'completed',
                                        'bg-yellow-900 text-yellow-300' => $tx->status === 'pending',
                                        'bg-red-900 text-red-300' => $tx->status === 'failed',
                                        'bg-gray-700 text-gray-400' => $tx->status === 'refunded',
                                    ])>{{ $tx->status }}</span>
                                </td>
                                <td class="py-2 text-right text-gray-500 text-xs">{{ $tx->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white text-sm">← Retour à la liste</a>
</div>

@endsection
