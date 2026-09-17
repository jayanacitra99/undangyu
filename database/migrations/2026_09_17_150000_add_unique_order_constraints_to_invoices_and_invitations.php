<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One invoice and one invitation per order, enforced by the database.
 *
 * Both were previously protected only by application code: the invitation by a
 * `lockForUpdate` (correct, but the only line of defence), the invoice by a
 * non-locking existence read that two overlapping jobs could both pass. A
 * unique index is the guarantee; the application checks stay as the polite
 * path that avoids an exception.
 *
 * `invitations.order_id` is nullable and MySQL permits repeated NULLs in a
 * unique index, so an invitation created outside an order is unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique('order_id');
        });

        Schema::table('invitations', function (Blueprint $table): void {
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['order_id']);
        });

        Schema::table('invitations', function (Blueprint $table): void {
            $table->dropUnique(['order_id']);
        });
    }
};
