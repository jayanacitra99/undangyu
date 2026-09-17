<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.2 — one payment attempt against an order.
 *
 * The unique index on `gateway_ref` is the idempotency key for webhooks and the
 * single most important index in the schema (hard rule 3): replaying the same
 * payload cannot create a second settled payment, because the insert loses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 40);
            $table->string('gateway_ref', 190)->nullable()->unique();
            $table->string('method', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default(PaymentStatus::Pending->value);
            $table->string('proof_path', 255)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
