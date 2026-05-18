<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoAsset extends Model
{
    protected $fillable = [
        'film_id', 's3_key', 'hls_url', 'dash_url',
        'status', 'bitrates', 'file_size_bytes',
        'drm_key_id', 'drm_key_encrypted', 'drm_key_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'bitrates' => 'array',
            'drm_key_expires_at' => 'datetime',
        ];
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function isDrmKeyExpired(): bool
    {
        return $this->drm_key_expires_at && $this->drm_key_expires_at->isPast();
    }
}
