<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.4 — ordering and visibility of the block system (M4.11).
 *
 * Provisioning seeds these from the event type's `default_sections`, so a
 * wedding starts with a cover, the couple, the events and so on, in order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 60);
            $table->string('title', 150)->nullable();
            $table->json('content')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            // Every read is "this invitation's sections, in order".
            $table->index(['invitation_id', 'sort_order']);

            // A section key appears once per invitation. `custom` is the
            // exception the ERD's alternative index allows for, so the unique
            // covers the sort order too rather than the key alone.
            $table->unique(['invitation_id', 'section_key', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_sections');
    }
};
