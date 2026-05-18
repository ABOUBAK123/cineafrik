<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — CineAfrik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex">

{{-- Sidebar --}}
<aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col min-h-screen fixed top-0 left-0 z-30">
    <div class="px-6 py-5 border-b border-gray-800">
        <span class="text-xl font-bold text-orange-400">🎬 CineAfrik</span>
        <p class="text-xs text-gray-500 mt-0.5">Administration</p>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1">
        @php
            $currentRoute = request()->route()->getName();
            $navLink = function(string $route, string $icon, string $label) use ($currentRoute): string {
                if (!\Illuminate\Support\Facades\Route::has($route)) return '';
                $active = str_starts_with($currentRoute ?? '', str_replace('.index', '', $route));
                $cls = $active ? 'bg-orange-500 text-white font-semibold' : 'text-gray-400 hover:bg-gray-800 hover:text-white';
                return '<a href="' . route($route) . '" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition ' . $cls . '">' . $icon . ' ' . $label . '</a>';
            };
        @endphp

        {!! $navLink('admin.dashboard', '📊', 'Tableau de bord') !!}
        {!! $navLink('admin.films.index', '🎬', 'Films') !!}
        {!! $navLink('admin.users.index', '👥', 'Utilisateurs') !!}
        {!! $navLink('admin.transactions.index', '💳', 'Transactions') !!}
        {!! $navLink('admin.profil.index', '⚙️', 'Mon Profil') !!}
    </nav>

    <div class="px-3 py-4 border-t border-gray-800">
        <div class="px-3 py-2 text-xs text-gray-500">
            {{ Auth::user()->name }}<br>
            <span class="text-orange-400">Administrateur</span>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" class="mt-2">
            @csrf
            <button class="w-full text-left flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-gray-800 hover:text-red-400 transition">
                🚪 Déconnexion
            </button>
        </form>
    </div>
</aside>

{{-- Main content --}}
<div class="flex-1 ml-64">
    {{-- Topbar --}}
    <header class="bg-gray-900 border-b border-gray-800 px-8 py-4 flex items-center justify-between sticky top-0 z-20">
        <h1 class="text-lg font-semibold text-white">@yield('heading', 'Dashboard')</h1>
        <div class="flex items-center gap-4 text-sm text-gray-400">
            <span>{{ now()->format('d/m/Y') }}</span>
        </div>
    </header>

    {{-- Flash messages --}}
    <div class="px-8 pt-4">
        @if(session('success'))
            <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg text-sm mb-4">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg text-sm mb-4">
                ❌ {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg text-sm mb-4">
                @foreach($errors->all() as $error)
                    <div>⚠ {{ $error }}</div>
                @endforeach
            </div>
        @endif
    </div>

    <main class="px-8 py-6">
        @yield('content')
    </main>
</div>

</body>
</html>
