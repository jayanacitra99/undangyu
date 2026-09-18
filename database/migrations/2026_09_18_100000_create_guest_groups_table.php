<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.5 — named, colour-coded buckets for a guest list (M5.2).
 *
 * Family, friends, office, neighbours. The colour is what makes a 600-row
 * table readable at a glance, so it is stored with the group rather than
 * derived from the name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 20)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invitation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_groups');
    }
};
