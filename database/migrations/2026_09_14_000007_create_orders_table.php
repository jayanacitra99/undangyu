<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.2 — one purchase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();

            // The coupons table is M2.12, in a later session; that migration
            // adds the foreign key. The column exists now so discount_amount
            // has somewhere to say where the discount came from.
            $table->unsignedBigInteger('coupon_id')->nullable();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('status', 20)->default(OrderStatus::Pending->value);
            $table->timestamp('payment_deadline')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            // The hourly expiry sweep reads exactly these two columns.
            $table->index(['status', 'payment_deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
