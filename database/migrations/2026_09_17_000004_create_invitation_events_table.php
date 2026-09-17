<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.4 — the dated occasions within one invitation.
 *
 * Akad plus Resepsi is two rows, not two invitations. Datetimes are stored
 * UTC; the invitation's own timezone is what they are rendered in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('venue_name', 190);
            $table->text('address')->nullable();
            $table->string('maps_url', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('dress_code', 190)->nullable();
            $table->string('live_stream_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invitation_id', 'sort_order']);
            // The countdown and "what's next" both ask for the soonest event.
            $table->index(['invitation_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_events');
    }
};
