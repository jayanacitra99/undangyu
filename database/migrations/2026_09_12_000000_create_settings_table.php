<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.9 — global settings, one row per key, cache-backed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 60);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');

            // Public settings are safe to expose on an invitation page; the rest
            // stay behind the admin panel.
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
