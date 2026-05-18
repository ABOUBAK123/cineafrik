<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoAccessLog extends Model
{
    protected $fillable = [
        'film_id', 'user_id', 'ip_address',
        'action', 'detail', 'device_id', 'user_agent',
    ];

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Détecte si une IP a tenté de télécharger de façon anormale
     * (ex. : > 50 requêtes segments en 5 min depuis la même IP).
     */
    public static function isSuspiciousIp(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where('action', 'segment_served')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count() > 50;
    }
}
