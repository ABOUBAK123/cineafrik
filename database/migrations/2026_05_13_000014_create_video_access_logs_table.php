<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('film_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45);
            $table->enum('action', [
                'stream_started',
                'drm_key_served',
                'drm_key_denied',
                'manifest_served',
                'segment_served',
                'offline_license_issued',
                'access_denied',
                'download_attempt_blocked',
            ]);
            $table->string('detail')->nullable();
            $table->string('device_id', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['film_id', 'action']);
            $table->index(['user_id', 'action']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_access_logs');
    }
};
