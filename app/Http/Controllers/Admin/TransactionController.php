<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user:id,name,email,phone', 'film:id,title']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn($q) => $q
                ->where('reference', 'like', "%$term%")
                ->orWhere('provider_transaction_id', 'like', "%$term%")
                ->orWhere('phone', 'like', "%$term%")
            );
        }

        $summary = [
            'total_completed' => (clone $query)->where('status', 'completed')->sum('amount'),
            'count_completed' => (clone $query)->where('status', 'completed')->count(),
            'count_pending' => (clone $query)->where('status', 'pending')->count(),
            'count_failed' => (clone $query)->where('status', 'failed')->count(),
        ];

        $transactions = $query->latest()->paginate(25)->withQueryString();

        return view('admin.transactions.index', compact('transactions', 'summary'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'film', 'access']);
        return view('admin.transactions.show', compact('transaction'));
    }

    public function refund(Transaction $transaction)
    {
        if (!$transaction->isCompleted()) {
            return back()->with('error', 'Seules les transactions complétées peuvent être remboursées.');
        }

        $transaction->update(['status' => 'refunded']);

        // Révoquer l'accès
        $transaction->access?->delete();

        return back()->with('success', "Transaction {$transaction->reference} remboursée.");
    }

    public function export(Request $request)
    {
        $transactions = Transaction::with(['user:id,name,email,phone', 'film:id,title'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->get();

        $csv = "Référence,Utilisateur,Film,Montant,Devise,Méthode,Statut,Date\n";
        foreach ($transactions as $t) {
            $csv .= implode(',', [
                $t->reference,
                $t->user?->name ?? '',
                '"' . ($t->film?->title ?? '') . '"',
                $t->amount,
                $t->currency,
                $t->payment_method,
                $t->status,
                $t->created_at->format('Y-m-d H:i'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transactions_' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
