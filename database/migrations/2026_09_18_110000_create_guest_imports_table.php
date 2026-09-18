<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.5 — one upload of a guest spreadsheet (M5.3).
 *
 * The counts and the errors JSON are the whole point of the row: an import is
 * reported per row, never all-or-nothing, so "488 imported, 12 errors" has to
 * survive the job finishing and be readable afterwards.
 *
 * `user_id` is the person who uploaded rather than the invitation's owner: a
 * reseller importing on a client's behalf is who the error report belongs to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path', 255);
            $table->string('original_filename', 255);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->string('status', 20)->default('queued');
            $table->timestamps();

            $table->index(['invitation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_imports');
    }
};
