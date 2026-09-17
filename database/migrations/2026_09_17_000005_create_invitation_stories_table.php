<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.4 — the "our story" timeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_stories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invitation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_stories');
    }
};
