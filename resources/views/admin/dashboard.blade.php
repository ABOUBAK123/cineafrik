@extends('admin.layouts.app')

@section('title', 'Tableau de bord')
@section('heading', 'Tableau de bord')

@section('content')

{{-- KPI Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @php
        $cards = [
            ['label' => 'Films publiés', 'value' => $stats['published_films'] . ' / ' . $stats['total_films'], 'icon' => '🎬', 'color' => 'orange'],
            ['label' => 'Utilisateurs', 'value' => number_format($stats['total_users']), 'icon' => '👥', 'color' => 'blue'],
            ['label' => 'Revenus totaux', 'value' => number_format($stats['total_revenue']) . ' XOF', 'icon' => '💰', 'color' => 'green'],
            ['label' => 'En attente', 'value' => $stats['pending_transactions'] . ' tx.', 'icon' => '⏳', 'color' => 'yellow'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="text-2xl mb-2">{{ $card['icon'] }}</div>
            <div class="text-2xl font-bold text-white">{{ $card['value'] }}</div>
            <div class="text-sm text-gray-400 mt-1">{{ $card['label'] }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    {{-- Revenus 30 derniers jours --}}
    <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-300 mb-4">💹 Revenus — 30 derniers jours</h2>
        @if($revenueByDay->isEmpty())
            <p class="text-gray-500 text-sm">Aucune donnée disponible.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 border-b border-gray-800">
                            <th class="text-left pb-2">Date</th>
                            <th class="text-right pb-2">Transactions</th>
                            <th class="text-right pb-2">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($revenueByDay->take(10) as $row)
                            <tr class="text-gray-300">
                                <td class="py-2">{{ \Carbon\Carbon::parse($row->date)->format('d/m') }}</td>
                                <td class="py-2 text-right">{{ $row->count }}</td>
                                <td class="py-2 text-right font-medium text-green-400">{{ number_format($row->total) }} XOF</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Top pays --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-300 mb-4">🌍 Utilisateurs par pays</h2>
        @forelse($usersByCountry as $row)
            <div class="flex items-center justify-between py-2 border-b border-gray-800 last:border-0">
                <span class="text-gray-300 text-sm">{{ $row->country }}</span>
                <span class="text-orange-400 font-semibold text-sm">{{ $row->count }}</span>
            </div>
        @empty
            <p class="text-gray-500 text-sm">Aucun utilisateur.</p>
        @endforelse
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Transactions récentes --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-300">💳 Transactions récentes</h2>
            <a href="{{ route('admin.transactions.index') }}" class="text-xs text-orange-400 hover:underline">Voir tout</a>
        </div>
        <div class="space-y-3">
            @forelse($recentTransactions as $tx)
                <div class="flex items-center justify-between text-sm">
                    <div class="min-w-0">
                        <div class="text-gray-200 truncate">{{ $tx->user?->name ?? '—' }}</div>
                        <div class="text-gray-500 text-xs truncate">{{ $tx->film?->title ?? '—' }}</div>
                    </div>
                    <div class="text-right ml-4 shrink-0">
                        <div class="font-semibold text-white">{{ number_format($tx->amount) }} {{ $tx->currency }}</div>
                        <span @class([
                            'text-xs px-2 py-0.5 rounded-full',
                            'bg-green-900 text-green-300' => $tx->status === 'completed',
                            'bg-yellow-900 text-yellow-300' => $tx->status === 'pending',
                            'bg-red-900 text-red-300' => $tx->status === 'failed',
                            'bg-gray-800 text-gray-400' => $tx->status === 'refunded',
                        ])>{{ $tx->status }}</span>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Aucune transaction.</p>
            @endforelse
        </div>
    </div>

    {{-- Top films --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-300">🏆 Top films</h2>
            <a href="{{ route('admin.films.index') }}" class="text-xs text-orange-400 hover:underline">Voir tout</a>
        </div>
        <div class="space-y-3">
            @forelse($topFilms as $i => $film)
                <div class="flex items-center gap-3">
                    <span class="text-gray-600 text-sm w-4">{{ $i + 1 }}</span>
                    @if($film->thumbnail)
                        <img src="{{ asset('storage/' . $film->thumbnail) }}" class="w-10 h-14 object-cover rounded" alt="">
                    @else
                        <div class="w-10 h-14 bg-gray-800 rounded flex items-center justify-center text-xl">🎬</div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-gray-200 text-sm font-medium truncate">{{ $film->title }}</div>
                        <div class="text-gray-500 text-xs">{{ $film->purchases }} achats · ⭐ {{ $film->rating }}</div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Aucun film.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
