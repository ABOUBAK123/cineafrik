<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('film_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 64);
            $table->string('drm_license_token', 512)->nullable();
            $table->timestamp('downloaded_at');
            $table->timestamp('first_played_at')->nullable();
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_downloads');
    }
};
