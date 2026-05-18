<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('admin.login'));

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Auth (sans middleware)
    Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'login']);
    Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

    // Routes protégées
    Route::middleware(['auth', 'admin'])->group(function () {

        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Films
        Route::get('films', [Admin\FilmController::class, 'index'])->name('films.index');
        Route::get('films/create', [Admin\FilmController::class, 'create'])->name('films.create');
        Route::post('films', [Admin\FilmController::class, 'store'])->name('films.store');
        Route::get('films/{film}/edit', [Admin\FilmController::class, 'edit'])->name('films.edit');
        Route::put('films/{film}', [Admin\FilmController::class, 'update'])->name('films.update');
        Route::patch('films/{film}/status', [Admin\FilmController::class, 'updateStatus'])->name('films.status');
        Route::delete('films/{film}', [Admin\FilmController::class, 'destroy'])->name('films.destroy');

        // Users
        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::patch('users/{user}/status', [Admin\UserController::class, 'updateStatus'])->name('users.status');

        // Transactions
        Route::get('transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/export', [Admin\TransactionController::class, 'export'])->name('transactions.export');
        Route::get('transactions/{transaction}', [Admin\TransactionController::class, 'show'])->name('transactions.show');
        Route::patch('transactions/{transaction}/refund', [Admin\TransactionController::class, 'refund'])->name('transactions.refund');
    });
});
