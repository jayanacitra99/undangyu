<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.5 — the guestbook (M6.4).
 *
 * `guest_id` nulls on delete, like an RSVP: a message left by someone who is
 * later removed from the guest list is still a message the couple were sent.
 *
 * `ip_hash` rather than the address, for the same reason as rsvps: abuse
 * review needs "the same someone", not who they are.
 *
 * The index carries status and created_at together, because the only query
 * that matters — the public feed — is "approved, newest first, pinned above
 * everything".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 190);
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_pinned')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['invitation_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};
