<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.6 — the texts a client sends guests (M8.2).
 *
 * Three scopes in one table, which is what the two nullable foreign keys are
 * for: a system template belongs to nobody and is what every new invitation
 * starts from, a user template is one a reseller reuses across their clients,
 * and an invitation template is this wedding's own wording.
 *
 * `is_system` is not derived from the nulls: a seeded default must stay
 * recognisable as one after someone copies it, so that the seeder can update
 * its own rows without touching a client's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('channel', 20)->default('whatsapp');
            $table->string('subject', 190)->nullable();
            $table->text('body');
            $table->json('variables')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['invitation_id', 'channel']);
            $table->index(['is_system', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
