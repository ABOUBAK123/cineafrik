<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybackSession extends Model
{
    protected $fillable = [
        'user_id', 'film_id', 'device_id', 'device_type',
        'ip_address', 'position_seconds', 'is_offline',
        'heartbeat_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'is_offline' => 'boolean',
            'heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
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

    public static function activeStreamsCount(int $userId): int
    {
        $threshold = now()->subMinutes(2);
        return static::where('user_id', $userId)
            ->whereNull('ended_at')
            ->where('heartbeat_at', '>=', $threshold)
            ->count();
    }
}
