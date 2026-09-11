<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings `users` in line with docs/03-database-erd.md § 3.1.
 *
 * `referred_by` points at `affiliates.id`, which doesn't exist until Session 38 —
 * the column and its index land here, the foreign key comes with that table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 150)->change();
            $table->string('email', 190)->change();

            $table->string('phone', 30)->after('email_verified_at');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('avatar')->nullable()->after('password');
            $table->enum('status', ['active', 'suspended', 'banned'])
                ->default('active')
                ->after('avatar');
            $table->unsignedBigInteger('referred_by')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('referred_by');
            $table->softDeletes();

            $table->unique('phone');
            $table->index('status');
            $table->index('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['status']);
            $table->dropIndex(['referred_by']);

            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'avatar',
                'status',
                'referred_by',
                'last_login_at',
            ]);

            $table->string('email', 255)->change();
            $table->string('name', 255)->change();
        });
    }
};
