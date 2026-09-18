<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.5 — the guest list (M5.1).
 *
 * `token` is the `?to=` value and carries a unique index: it is the identifier
 * a guest's whole experience hangs off, and two guests sharing one would mean
 * one of them opening the other's RSVP. The index is also what makes the
 * generator's collision retry cheap — the database decides, not a read first.
 *
 * A group is `nullOnDelete`: deleting "Kantor" ungroups its guests, it does
 * not delete forty invitees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 30)->nullable();
            $table->string('name', 190);
            $table->string('phone', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('address')->nullable();
            $table->string('token', 80)->unique();
            $table->unsignedTinyInteger('max_pax')->default(2);
            $table->boolean('is_vip')->default(false);
            $table->string('table_number', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invitation_id', 'name']);
            $table->index('guest_group_id');
            $table->index(['invitation_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
