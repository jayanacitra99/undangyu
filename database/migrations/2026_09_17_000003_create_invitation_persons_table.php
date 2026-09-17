<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.4 — who the event is about.
 *
 * `role` is validated against the parent event type's `person_roles`, which is
 * why it is a plain string here rather than an enum column: the valid set
 * depends on the row's event type, not on the schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_persons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('full_name', 190);
            $table->string('nickname', 80)->nullable();
            $table->string('photo', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('parent_father', 190)->nullable();
            $table->string('parent_mother', 190)->nullable();
            $table->string('child_order', 50)->nullable();
            $table->string('instagram', 100)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invitation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_persons');
    }
};
