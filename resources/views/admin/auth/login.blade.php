<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — CineAfrik</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: #030712;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .container {
            width: 100%;
            max-width: 28rem;
            padding: 1.5rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .emoji {
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0.5rem 0 0;
        }

        .header p {
            color: #9ca3af;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 1.5rem;
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #d1d5db;
            margin-bottom: 0.375rem;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            background: #1f2937;
            border: 1px solid #374151;
            color: #fff;
            border-radius: 0.5rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #f97316;
            background: #1f2937;
        }

        input::placeholder {
            color: #6b7280;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #9ca3af;
            cursor: pointer;
            margin-bottom: 1.25rem;
        }

        input[type="checkbox"] {
            cursor: pointer;
        }

        .error {
            background: rgba(159, 18, 57, 0.4);
            border: 1px solid #be185d;
            color: #fca5a5;
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
        }

        button {
            width: 100%;
            background: #f97316;
            color: #fff;
            font-weight: 600;
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        button:hover {
            background: #ea580c;
        }

        button:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <span class="emoji">🎬</span>
        <h1>CineAfrik Admin</h1>
        <p>Accès réservé aux administrateurs</p>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    placeholder="admin@cineafrik.com"
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="••••••••"
                >
            </div>

            <label class="checkbox-group">
                <input type="checkbox" name="remember">
                Rester connecté
            </label>

            @if($errors->any())
                <div class="error">
                    {{ $errors->first() }}
                </div>
            @endif

            <button type="submit">Se connecter</button>
        </form>
    </div>
</div>

</body>
</html>
