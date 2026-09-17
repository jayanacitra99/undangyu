<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.4 — where guests send a gift.
 *
 * `account_number` is wider than a bank account needs because the column holds
 * ciphertext: the model casts it `encrypted`. It is shown publicly by design,
 * but encrypting at rest keeps a bulk scrape of every client's banking details
 * off the table if the database leaks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_gifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('provider_name', 100)->nullable();
            $table->string('account_name', 190)->nullable();
            $table->text('account_number')->nullable();
            $table->string('qris_image', 255)->nullable();
            $table->string('recipient_name', 190)->nullable();
            $table->text('address')->nullable();
            $table->string('notes', 255)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invitation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_gifts');
    }
};
