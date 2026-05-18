<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('is_admin', false)
            ->withCount(['transactions', 'accesses as purchases'])
            ->withSum(['transactions as total_spent' => fn($q) => $q->where('status', 'completed')], 'amount');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$term%")
                ->orWhere('email', 'like', "%$term%")
                ->orWhere('phone', 'like', "%$term%")
            );
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load([
            'accesses.film:id,title,thumbnail',
            'transactions' => fn($q) => $q->with('film:id,title')->latest()->limit(20),
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate(['status' => 'required|in:active,suspended,banned']);

        if ($user->is_admin) {
            return back()->with('error', 'Impossible de modifier un administrateur.');
        }

        $user->update(['status' => $request->status]);

        return back()->with('success', "Statut utilisateur mis à jour : {$request->status}.");
    }
}
