<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            'Action', 'Comédie', 'Drame', 'Romance', 'Thriller',
            'Horreur', 'Animation', 'Documentaire', 'Science-Fiction',
            'Historique', 'Bollywood', 'Nollywood', 'Africain',
        ];

        foreach ($genres as $name) {
            Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
