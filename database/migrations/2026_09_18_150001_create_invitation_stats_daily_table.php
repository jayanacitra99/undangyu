<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.7 — the table the charts actually read (M9.2).
 *
 * One row per invitation per day, written by `analytics:rollup`. The unique
 * key is what makes the rollup idempotent: a re-run for a day it has already
 * counted overwrites that day rather than doubling it, which matters because
 * the command will be re-run by hand the first time a number looks wrong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_stats_daily', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('rsvp_yes')->default(0);
            $table->unsignedInteger('rsvp_no')->default(0);
            $table->unsignedInteger('rsvp_maybe')->default(0);
            $table->unsignedInteger('total_pax')->default(0);
            $table->unsignedInteger('wishes_count')->default(0);
            $table->unsignedInteger('guest_opens')->default(0);
            $table->timestamps();

            $table->unique(['invitation_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_stats_daily');
    }
};
