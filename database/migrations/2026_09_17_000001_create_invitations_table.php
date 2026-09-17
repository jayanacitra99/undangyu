<?php

declare(strict_types=1);

use App\Enums\InvitationStatus;
use App\Enums\InvitationVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.4 — the central table.
 *
 * Deliberately lean: variable content lives in the child tables and in JSON.
 * `entitlements` is the snapshot taken at provisioning — quotas are read from
 * here for the life of the invitation, never from the live package.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // A reseller who built it for someone else. The owner is user_id.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained()->restrictOnDelete();
            $table->string('template_version', 20);
            $table->foreignId('package_id')->constrained()->restrictOnDelete();

            $table->string('slug', 120)->unique();
            $table->string('custom_domain', 190)->nullable()->unique();
            $table->string('title', 190);

            $table->string('status', 20)->default(InvitationStatus::Draft->value);
            $table->string('visibility', 20)->default(InvitationVisibility::Public->value);
            $table->string('password')->nullable();

            $table->char('language', 5)->default('id');
            $table->string('timezone', 64)->default('Asia/Jakarta');

            $table->json('theme_config')->nullable();
            $table->json('settings');
            $table->json('entitlements');

            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->string('og_image_path', 255)->nullable();

            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            // The daily expiry sweep reads exactly these two columns.
            $table->index(['status', 'expires_at']);
            $table->index('event_type_id');
            $table->index('template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
