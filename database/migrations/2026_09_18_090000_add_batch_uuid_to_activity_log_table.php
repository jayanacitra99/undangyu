<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spatie/laravel-activitylog v4 writes `batch_uuid` on every entry, and the
 * table published in Session 1 predates that column — the package ships it as
 * a follow-up migration that was never published, so the first real
 * `activity()` call in Session 23 failed with
 * "table activity_log has no column named batch_uuid".
 *
 * Matches the package's own stub, including the connection and table name
 * coming from its config rather than being hardcoded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table((string) config('activitylog.table_name'), function (Blueprint $table): void {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table((string) config('activitylog.table_name'), function (Blueprint $table): void {
                $table->dropColumn('batch_uuid');
            });
    }
};
