<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilmPrice extends Model
{
    protected $fillable = ['film_id', 'country', 'currency', 'amount'];

    public function film()
    {
        return $this->belongsTo(Film::class);
    }
}
