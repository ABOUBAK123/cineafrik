<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('film_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained()->cascadeOnDelete();
            $table->string('country', 5);
            $table->string('currency', 5);
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->unique(['film_id', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('film_prices');
    }
};
