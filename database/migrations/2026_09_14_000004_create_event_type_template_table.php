<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.3 — which event types a template supports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_type_template', function (Blueprint $table): void {
            $table->foreignId('event_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();

            $table->primary(['event_type_id', 'template_id']);
            $table->index('template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_type_template');
    }
};
