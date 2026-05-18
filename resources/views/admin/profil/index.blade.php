@extends('admin.layouts.app')

@section('title', 'Mon Profil')
@section('heading', 'Mon Profil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Avatar + identité --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 flex items-center gap-6">
        <div class="relative group flex-shrink-0">
            @if($user->avatar)
                <img src="{{ Storage::url($user->avatar) }}" alt="Avatar"
                     class="w-24 h-24 rounded-full object-cover border-4 border-orange-500">
            @else
                <div class="w-24 h-24 rounded-full bg-orange-500 flex items-center justify-center text-3xl font-bold text-white border-4 border-orange-600">
                    {{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}
                </div>
            @endif
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">{{ $user->name }}</h2>
            <p class="text-orange-400 text-sm mt-0.5">Administrateur</p>
            <p class="text-gray-400 text-sm mt-1">{{ $user->email }}</p>
            <p class="text-gray-600 text-xs mt-1">Membre depuis {{ $user->created_at->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Informations personnelles --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h3 class="text-base font-semibold text-white mb-5 flex items-center gap-2">
            <span class="text-orange-400">👤</span> Informations personnelles
        </h3>
        <form method="POST" action="{{ route('admin.profil.update-info') }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Prénom <span class="text-red-400">*</span></label>
                    <input type="text" name="first_name"
                           value="{{ old('first_name', $user->first_name ?? explode(' ', $user->name)[0] ?? '') }}"
                           required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('first_name') border-red-500 @enderror">
                    @error('first_name')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Nom <span class="text-red-400">*</span></label>
                    <input type="text" name="last_name"
                           value="{{ old('last_name', $user->last_name ?? (count(explode(' ', $user->name)) > 1 ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '')) }}"
                           required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('last_name') border-red-500 @enderror">
                    @error('last_name')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Adresse e-mail <span class="text-red-400">*</span></label>
                    <input type="email" name="email"
                           value="{{ old('email', $user->email) }}"
                           required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="submit"
                    class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                💾 Enregistrer les informations
            </button>
        </form>
    </div>

    {{-- Avatar --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h3 class="text-base font-semibold text-white mb-5 flex items-center gap-2">
            <span class="text-orange-400">🖼️</span> Photo de profil
        </h3>
        <form method="POST" action="{{ route('admin.profil.update-avatar') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="flex items-center gap-5">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}" alt="Avatar"
                         class="w-16 h-16 rounded-full object-cover border-2 border-gray-700">
                @else
                    <div class="w-16 h-16 rounded-full bg-orange-500 flex items-center justify-center text-xl font-bold text-white">
                        {{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <input type="file" name="avatar" id="avatar" accept="image/*"
                           class="w-full text-sm text-gray-400
                                  file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                                  file:text-sm file:font-medium file:bg-orange-500 file:text-white
                                  hover:file:bg-orange-600 file:cursor-pointer">
                    <p class="text-xs text-gray-500 mt-1.5">JPG, PNG, WEBP — max 2 Mo</p>
                    @error('avatar')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit"
                        class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    📷 Mettre à jour l'avatar
                </button>
            </div>
        </form>
    </div>

    {{-- Mot de passe --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h3 class="text-base font-semibold text-white mb-5 flex items-center gap-2">
            <span class="text-orange-400">🔒</span> Changer le mot de passe
        </h3>
        <form method="POST" action="{{ route('admin.profil.update-password') }}">
            @csrf @method('PUT')
            <div class="space-y-4 mb-5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Mot de passe actuel <span class="text-red-400">*</span></label>
                    <input type="password" name="current_password" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Nouveau mot de passe <span class="text-red-400">*</span></label>
                    <input type="password" name="password" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    @error('password')
                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Confirmer le mot de passe <span class="text-red-400">*</span></label>
                    <input type="password" name="password_confirmation" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-4">Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.</p>
            <button type="submit"
                    class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                🔑 Changer le mot de passe
            </button>
        </form>
    </div>

</div>
@endsection
