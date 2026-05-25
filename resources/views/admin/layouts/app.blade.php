<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — CineAfrik</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: #030712;
            color: #fff;
            min-height: 100vh;
            display: flex;
        }

        aside {
            width: 256px;
            background: #111827;
            border-right: 1px solid #1f2937;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 30;
        }

        .sidebar-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #1f2937;
        }

        .brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: #f97316;
        }

        .brand-sub {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.125rem;
        }

        nav {
            flex: 1;
            padding: 1rem 0.75rem;
            overflow-y: auto;
        }

        nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #9ca3af;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 0.25rem;
        }

        nav a:hover {
            background: #1f2937;
            color: #fff;
        }

        nav a.active {
            background: #f97316;
            color: #fff;
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 1rem 0.75rem;
            border-top: 1px solid #1f2937;
        }

        .user-info {
            padding: 0.75rem;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .logout-btn {
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            border: none;
            background: none;
            color: #9ca3af;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }

        .logout-btn:hover {
            background: #1f2937;
            color: #f87171;
        }

        .main {
            flex: 1;
            margin-left: 256px;
        }

        header {
            background: #111827;
            border-bottom: 1px solid #1f2937;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        header h1 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #fff;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: #9ca3af;
        }

        .flash-messages {
            padding: 1.5rem 2rem 0;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #16a34a;
            color: #86efac;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #dc2626;
            color: #fca5a5;
        }

        main {
            padding: 1.5rem 2rem;
        }

        @media (max-width: 768px) {
            aside {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid #1f2937;
            }

            .main {
                margin-left: 0;
            }

            nav {
                display: flex;
                padding: 0.5rem;
                overflow: auto;
            }

            nav a {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>

<aside>
    <div class="sidebar-header">
        <div class="brand">🎬 CineAfrik</div>
        <div class="brand-sub">Administration</div>
    </div>

    <nav>
        @php
            $currentRoute = request()->route()->getName();
            $isActive = fn(string $route) => str_starts_with($currentRoute ?? '', str_replace('.index', '', $route)) ? 'active' : '';
        @endphp

        @if(\Illuminate\Support\Facades\Route::has('admin.dashboard'))
        <a href="{{ route('admin.dashboard') }}" class="{{ $isActive('admin.dashboard') }}">
            📊 Tableau de bord
        </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.films.index'))
        <a href="{{ route('admin.films.index') }}" class="{{ $isActive('admin.films.index') }}">
            🎬 Films
        </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.users.index'))
        <a href="{{ route('admin.users.index') }}" class="{{ $isActive('admin.users.index') }}">
            👥 Utilisateurs
        </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.transactions.index'))
        <a href="{{ route('admin.transactions.index') }}" class="{{ $isActive('admin.transactions.index') }}">
            💳 Transactions
        </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.profil.index'))
        <a href="{{ route('admin.profil.index') }}" class="{{ $isActive('admin.profil.index') }}">
            ⚙️ Mon Profil
        </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            {{ Auth::user()->name }}<br>
            <span style="color: #f97316;">Administrateur</span>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0;">
            @csrf
            <button class="logout-btn" type="submit">🚪 Déconnexion</button>
        </form>
    </div>
</aside>

<div class="main">
    <header>
        <h1>@yield('heading', 'Dashboard')</h1>
        <div class="topbar-right">
            <span>{{ now()->format('d/m/Y') }}</span>
        </div>
    </header>

    <div class="flash-messages">
        @if(session('success'))
        <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-error">❌ {{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
            <div>⚠ {{ $error }}</div>
            @endforeach
        </div>
        @endif
    </div>

    <main>
        @yield('content')
    </main>
</div>

</body>
</html>
