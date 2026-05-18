<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineDownload extends Model
{
    protected $fillable = [
        'user_id', 'film_id', 'device_id', 'drm_license_token',
        'downloaded_at', 'first_played_at', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
            'first_played_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }
}
