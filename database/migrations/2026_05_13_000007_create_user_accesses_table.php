<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Droits de visionnage accordés après paiement
        Schema::create('user_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('film_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->restrictOnDelete();
            $table->timestamp('first_played_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('offline_available')->default(false);
            $table->timestamp('offline_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'film_id']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accesses');
    }
};
