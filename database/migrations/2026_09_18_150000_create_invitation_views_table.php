<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.7 — one row per view of a published invitation (M9.1).
 *
 * High volume by design: a wedding link shared to five hundred guests on a
 * Friday night writes tens of thousands of rows over a weekend. Nothing user
 * facing ever reads this table — the charts read the daily rollup — and
 * `analytics:prune` deletes anything older than ninety days.
 *
 * No foreign key on `guest_id` beyond nullOnDelete, and `ip_hash` rather than
 * the address: the same rule as rsvps and wishes. Counting unique visitors
 * needs "the same someone", not who they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('referrer', 255)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('viewed_at');

            // The only index this table gets. Every query against it is "this
            // invitation, this window" — the rollup and the prune — and an
            // index nobody uses is a write cost on every single view.
            $table->index(['invitation_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_views');
    }
};
