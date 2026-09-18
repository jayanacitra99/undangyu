<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.5 — attendance confirmations (M6.1, M6.9).
 *
 * `guest_id` is nullable and nulls on delete: an anonymous RSVP from someone
 * who was sent the public link has no guest row at all, and a guest removed
 * from the list must not take their answer with them — the client already
 * counted it.
 *
 * `ip_hash` rather than the address. Rate limiting and abuse review need to
 * know "the same someone", not who they are, and a guest list with five
 * hundred IP addresses in it is a liability with no use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsvps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invitation_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 190);
            $table->string('phone', 30)->nullable();
            $table->string('attendance', 10);
            $table->unsignedTinyInteger('pax')->default(1);
            $table->string('meal_preference', 60)->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('responded_at');
            $table->timestamps();

            $table->index(['invitation_id', 'attendance']);
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
