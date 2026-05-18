<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(GenreSeeder::class);

        User::firstOrCreate(
            ['email' => 'admin@cineafrik.com'],
            [
                'name' => 'Admin CineAfrik',
                'password' => Hash::make('password'),
                'country' => 'CI',
                'status' => 'active',
                'is_admin' => true,
            ]
        );
    }
}
