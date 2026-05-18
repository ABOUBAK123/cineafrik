<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'first_name', 'last_name',
        'email', 'phone', 'password',
        'google_id', 'apple_id', 'avatar',
        'country', 'language', 'birth_date',
        'parental_control', 'status', 'is_admin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'birth_date' => 'date',
            'parental_control' => 'boolean',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function accesses()
    {
        return $this->hasMany(UserAccess::class);
    }

    public function watchlist()
    {
        return $this->belongsToMany(Film::class, 'watchlists')->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function offlineDownloads()
    {
        return $this->hasMany(OfflineDownload::class);
    }

    public function hasAccessToFilm(int $filmId): bool
    {
        return $this->accesses()
            ->where('film_id', $filmId)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
