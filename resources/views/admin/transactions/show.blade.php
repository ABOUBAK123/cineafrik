@extends('admin.layouts.app')

@section('title', 'Transaction ' . Str::limit($transaction->reference, 12))
@section('heading', 'Détail transaction')

@section('content')

<div class="max-w-2xl">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">

        <div class="flex items-center justify-between pb-4 border-b border-gray-800">
            <div>
                <div class="font-mono text-sm text-gray-400">{{ $transaction->reference }}</div>
                <div class="text-2xl font-bold text-white mt-1">
                    {{ number_format($transaction->amount) }} {{ $transaction->currency }}
                </div>
            </div>
            <span @class([
                'text-sm px-4 py-1.5 rounded-full font-semibold',
                'bg-green-900 text-green-300' => $transaction->status === 'completed',
                'bg-yellow-900 text-yellow-300' => $transaction->status === 'pending',
                'bg-red-900 text-red-300' => $transaction->status === 'failed',
                'bg-gray-700 text-gray-300' => $transaction->status === 'refunded',
            ])>{{ ucfirst($transaction->status) }}</span>
        </div>

        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Utilisateur</dt>
                <dd class="text-white">
                    <a href="{{ route('admin.users.show', $transaction->user) }}" class="text-orange-400 hover:underline">
                        {{ $transaction->user?->name }}
                    </a>
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Film</dt>
                <dd class="text-white">{{ $transaction->film?->title }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Téléphone</dt>
                <dd class="text-gray-300">{{ $transaction->phone ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Méthode</dt>
                <dd class="text-gray-300">{{ str_replace('_', ' ', $transaction->payment_method) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">ID Provider</dt>
                <dd class="font-mono text-xs text-gray-400">{{ $transaction->provider_transaction_id ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Pays</dt>
                <dd class="text-gray-300">{{ $transaction->country }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Tentatives</dt>
                <dd class="text-gray-300">{{ $transaction->retry_count + 1 }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Créée le</dt>
                <dd class="text-gray-300">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</dd>
            </div>
            @if($transaction->paid_at)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Payée le</dt>
                    <dd class="text-green-400">{{ $transaction->paid_at->format('d/m/Y H:i:s') }}</dd>
                </div>
            @endif
            @if($transaction->access)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Accès accordé</dt>
                    <dd class="text-green-400">✅ Oui</dd>
                </div>
            @endif
        </dl>

        @if($transaction->provider_response)
            <div class="mt-4 pt-4 border-t border-gray-800">
                <p class="text-xs text-gray-500 mb-2">Réponse provider</p>
                <pre class="bg-gray-800 rounded-lg p-3 text-xs text-gray-300 overflow-auto max-h-40">{{ json_encode($transaction->provider_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    </div>

    @if($transaction->status === 'completed')
        <form method="POST" action="{{ route('admin.transactions.refund', $transaction) }}"
            class="mt-4" onsubmit="return confirm('Confirmer le remboursement ?')">
            @csrf @method('PATCH')
            <button class="bg-red-700 hover:bg-red-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition">
                💸 Rembourser cette transaction
            </button>
        </form>
    @endif

    <div class="mt-4">
        <a href="{{ route('admin.transactions.index') }}" class="text-gray-400 hover:text-white text-sm">← Retour</a>
    </div>
</div>

@endsection
