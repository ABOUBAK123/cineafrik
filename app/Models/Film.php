<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Film extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'synopsis', 'director', 'cast',
        'duration_minutes', 'release_year', 'country_of_origin',
        'original_language', 'available_languages', 'available_subtitles',
        'age_rating', 'thumbnail', 'banner', 'trailer_url',
        'rating', 'rating_count', 'available_countries',
        'status', 'drm_enabled',
    ];

    protected function casts(): array
    {
        return [
            'available_languages' => 'array',
            'available_subtitles' => 'array',
            'available_countries' => 'array',
            'drm_enabled' => 'boolean',
            'rating' => 'float',
        ];
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'film_genre');
    }

    public function prices()
    {
        return $this->hasMany(FilmPrice::class);
    }

    public function videoAsset()
    {
        return $this->hasOne(VideoAsset::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function accesses()
    {
        return $this->hasMany(UserAccess::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeAvailableIn($query, string $country)
    {
        return $query->whereJsonContains('available_countries', $country)
            ->orWhereNull('available_countries');
    }

    public function getPriceForCountry(string $country): ?FilmPrice
    {
        return $this->prices->firstWhere('country', $country);
    }
}
