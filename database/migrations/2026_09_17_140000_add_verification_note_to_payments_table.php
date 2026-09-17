<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why an admin approved or rejected a manual transfer (M2.7).
 *
 * Not in docs/03 § 3.2's original column list: a rejection has to say what was
 * wrong — the amount did not match, the proof was unreadable — and the client
 * needs to read it. docs/03 is updated alongside this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('verification_note', 500)->nullable()->after('verified_by');
            $table->timestamp('verified_at')->nullable()->after('verification_note');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['verification_note', 'verified_at']);
        });
    }
};
