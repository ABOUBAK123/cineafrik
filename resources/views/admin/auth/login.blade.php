<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — CineAfrik</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    </style>
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center"

<div class="w-full max-w-md px-6">
    <div class="text-center mb-8">
        <span class="text-4xl">🎬</span>
        <h1 class="text-2xl font-bold text-white mt-2">CineAfrik Admin</h1>
        <p class="text-gray-400 text-sm mt-1">Accès réservé aux administrateurs</p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8">
        <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500 transition"
                    placeholder="admin@cineafrik.com"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Mot de passe</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500 transition"
                    placeholder="••••••••"
                >
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded">
                Rester connecté
            </label>

            @if($errors->any())
                <div class="bg-red-900/40 border border-red-700 text-red-300 text-sm px-4 py-3 rounded-lg">
                    {{ $errors->first() }}
                </div>
            @endif

            <button
                type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg text-sm transition"
            >
                Se connecter
            </button>
        </form>
    </div>
</div>

</body>
</html>
