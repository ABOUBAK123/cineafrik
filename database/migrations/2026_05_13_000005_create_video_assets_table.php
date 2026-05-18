<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained()->cascadeOnDelete();
            $table->string('s3_key');
            $table->string('hls_url')->nullable();
            $table->string('dash_url')->nullable();
            $table->enum('status', ['uploading', 'transcoding', 'ready', 'error'])->default('uploading');
            $table->json('bitrates')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('drm_key_id')->nullable();
            $table->text('drm_key_encrypted')->nullable();
            $table->timestamp('drm_key_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_assets');
    }
};
