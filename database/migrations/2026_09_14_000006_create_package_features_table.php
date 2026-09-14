<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.2 — one capability of one package.
 *
 * Flags are rows, not columns: adding a capability is a seed, not a migration.
 * EntitlementResolver turns these rows into the flat map that gets snapshotted
 * onto invitations.entitlements at provisioning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 60);
            $table->string('feature_value', 100)->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->timestamps();

            $table->unique(['package_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_features');
    }
};
