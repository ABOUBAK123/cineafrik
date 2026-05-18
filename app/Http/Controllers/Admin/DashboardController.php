<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_films' => Film::count(),
            'published_films' => Film::where('status', 'published')->count(),
            'total_users' => User::where('is_admin', false)->count(),
            'total_revenue' => Transaction::where('status', 'completed')->sum('amount'),
            'revenue_currency' => 'XOF',
            'transactions_today' => Transaction::whereDate('created_at', today())->where('status', 'completed')->count(),
            'pending_transactions' => Transaction::where('status', 'pending')->count(),
        ];

        $recentTransactions = Transaction::with(['user:id,name,email,phone', 'film:id,title'])
            ->latest()
            ->limit(10)
            ->get();

        $topFilms = Film::select('films.id', 'films.title', 'films.thumbnail', 'films.rating')
            ->withCount(['accesses as purchases'])
            ->orderByDesc('purchases')
            ->limit(5)
            ->get();

        $revenueByDay = Transaction::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $usersByCountry = User::where('is_admin', false)
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'recentTransactions', 'topFilms', 'revenueByDay', 'usersByCountry'
        ));
    }
}
