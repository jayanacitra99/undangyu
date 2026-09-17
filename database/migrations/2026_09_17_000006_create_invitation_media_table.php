<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.4 — gallery images, video and background music.
 *
 * `file_size` is kept for quota accounting: max_photos and max_video_mb are
 * counted against what is actually stored, not against a row count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('source', 20);
            $table->string('disk', 30)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('embed_url', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->json('conversions')->nullable();
            $table->string('caption', 255)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            // docs/03 § 5 names this one exactly: the gallery asks for one
            // invitation's images in order.
            $table->index(['invitation_id', 'type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_media');
    }
};
