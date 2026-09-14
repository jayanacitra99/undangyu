<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.3 — the taxonomy that keeps the product event-agnostic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('icon', 60)->nullable();

            // Person slots an invitation of this type offers, e.g. ["bride","groom"].
            $table->json('person_roles');

            // Ordered section keys a new invitation of this type starts with.
            $table->json('default_sections');

            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_types');
    }
};
