<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $fillable = [
        'user_id', 'film_id', 'transaction_id',
        'first_played_at', 'expires_at',
        'offline_available', 'offline_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'first_played_at' => 'datetime',
            'expires_at' => 'datetime',
            'offline_available' => 'boolean',
            'offline_expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isValid(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }

    public function markFirstPlay(): void
    {
        if (!$this->first_played_at) {
            $offlineExpiryHours = (int) config('tvod.offline_expiry_hours', 48);
            $this->update([
                'first_played_at' => now(),
                'offline_expires_at' => now()->addHours($offlineExpiryHours),
            ]);
        }
    }
}
