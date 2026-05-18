<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playback_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('film_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 64);
            $table->string('device_type', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('position_seconds')->default(0);
            $table->boolean('is_offline')->default(false);
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'film_id']);
            $table->index(['user_id', 'heartbeat_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_sessions');
    }
};
