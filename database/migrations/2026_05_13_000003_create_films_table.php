<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('films', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis')->nullable();
            $table->string('director')->nullable();
            $table->string('cast')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->year('release_year')->nullable();
            $table->string('country_of_origin', 5)->nullable();
            $table->string('original_language', 10)->nullable();
            $table->json('available_languages')->nullable();
            $table->json('available_subtitles')->nullable();
            $table->enum('age_rating', ['G', 'PG', 'PG-13', 'R', 'NC-17', 'ALL'])->default('ALL');
            $table->string('thumbnail')->nullable();
            $table->string('banner')->nullable();
            $table->string('trailer_url')->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // Droits territoriaux
            $table->json('available_countries')->nullable();

            // Statut
            $table->enum('status', ['draft', 'processing', 'published', 'archived'])->default('draft');
            $table->boolean('drm_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('film_genre', function (Blueprint $table) {
            $table->foreignId('film_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->primary(['film_id', 'genre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('film_genre');
        Schema::dropIfExists('films');
    }
};
