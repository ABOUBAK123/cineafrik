@extends('admin.layouts.app')

@section('title', 'Transactions')
@section('heading', 'Transactions & Paiements')

@section('content')

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <div class="text-xs text-gray-400 mb-1">Revenus filtrés</div>
        <div class="text-xl font-bold text-green-400">{{ number_format($summary['total_completed']) }} XOF</div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <div class="text-xs text-gray-400 mb-1">Complétées</div>
        <div class="text-xl font-bold text-white">{{ $summary['count_completed'] }}</div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <div class="text-xs text-gray-400 mb-1">En attente</div>
        <div class="text-xl font-bold text-yellow-400">{{ $summary['count_pending'] }}</div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <div class="text-xs text-gray-400 mb-1">Échouées</div>
        <div class="text-xl font-bold text-red-400">{{ $summary['count_failed'] }}</div>
    </div>
</div>

{{-- Filtres --}}
<form method="GET" class="flex gap-3 mb-6 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="Référence, numéro..."
        class="bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2 text-sm w-56 focus:outline-none focus:border-orange-500">

    <select name="status" class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Tous statuts</option>
        <option value="completed" @selected(request('status') === 'completed')>Complétée</option>
        <option value="pending" @selected(request('status') === 'pending')>En attente</option>
        <option value="failed" @selected(request('status') === 'failed')>Échouée</option>
        <option value="refunded" @selected(request('status') === 'refunded')>Remboursée</option>
    </select>

    <select name="method" class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Toutes méthodes</option>
        @foreach(['cinetpay', 'wave', 'orange_money', 'mtn_momo', 'fedapay', 'paystack'] as $m)
            <option value="{{ $m }}" @selected(request('method') === $m)>{{ ucwords(str_replace('_', ' ', $m)) }}</option>
        @endforeach
    </select>

    <select name="country" class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Tous pays</option>
        @foreach(['CI', 'SN', 'NG', 'GH', 'BF'] as $c)
            <option value="{{ $c }}" @selected(request('country') === $c)>{{ $c }}</option>
        @endforeach
    </select>

    <input type="date" name="from" value="{{ request('from') }}"
        class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm">
    <input type="date" name="to" value="{{ request('to') }}"
        class="bg-gray-800 border border-gray-700 text-gray-300 rounded-lg px-3 py-2 text-sm">

    <button class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm transition">Filtrer</button>

    <a href="{{ route('admin.transactions.export') . '?' . http_build_query(request()->all()) }}"
        class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition">
        ⬇ CSV
    </a>
</form>

<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-800 text-gray-400 text-xs uppercase tracking-wider">
            <tr>
                <th class="text-left px-5 py-3">Référence</th>
                <th class="text-left px-4 py-3">Utilisateur</th>
                <th class="text-left px-4 py-3">Film</th>
                <th class="text-right px-4 py-3">Montant</th>
                <th class="text-center px-4 py-3">Méthode</th>
                <th class="text-center px-4 py-3">Pays</th>
                <th class="text-center px-4 py-3">Statut</th>
                <th class="text-right px-4 py-3">Date</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @forelse($transactions as $tx)
                <tr class="hover:bg-gray-800/50 transition">
                    <td class="px-5 py-3 font-mono text-xs text-gray-400">{{ Str::limit($tx->reference, 12, '…') }}</td>
                    <td class="px-4 py-3">
                        <div class="text-white text-xs">{{ $tx->user?->name ?? '—' }}</div>
                        <div class="text-gray-500 text-xs">{{ $tx->phone }}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-300 text-xs truncate max-w-32">{{ $tx->film?->title ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-white">{{ number_format($tx->amount) }} <span class="text-gray-500 text-xs font-normal">{{ $tx->currency }}</span></td>
                    <td class="px-4 py-3 text-center text-gray-400 text-xs">{{ str_replace('_', ' ', $tx->payment_method) }}</td>
                    <td class="px-4 py-3 text-center"><span class="bg-gray-700 text-gray-300 text-xs px-2 py-0.5 rounded">{{ $tx->country }}</span></td>
                    <td class="px-4 py-3 text-center">
                        <span @class([
                            'text-xs px-2.5 py-1 rounded-full font-medium',
                            'bg-green-900 text-green-300' => $tx->status === 'completed',
                            'bg-yellow-900 text-yellow-300' => $tx->status === 'pending',
                            'bg-red-900 text-red-300' => $tx->status === 'failed',
                            'bg-gray-700 text-gray-400' => $tx->status === 'refunded',
                        ])>{{ $tx->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500 text-xs">{{ $tx->created_at->format('d/m H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.transactions.show', $tx) }}"
                                class="text-orange-400 text-xs px-2 py-1 rounded bg-orange-900/30 hover:bg-orange-900/60 transition">Voir</a>
                            @if($tx->status === 'completed')
                                <form method="POST" action="{{ route('admin.transactions.refund', $tx) }}"
                                    onsubmit="return confirm('Rembourser cette transaction ?')">
                                    @csrf @method('PATCH')
                                    <button class="text-red-400 text-xs px-2 py-1 rounded bg-red-900/30 hover:bg-red-900/60 transition">Remb.</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-5 py-12 text-center text-gray-500">Aucune transaction trouvée.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">{{ $transactions->links('vendor.pagination.tailwind') }}</div>

@endsection
