<?php

declare(strict_types=1);

use App\Enums\TemplateStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * docs/03 § 3.3 — the template catalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail', 255);
            $table->string('view_key', 100);
            $table->string('version', 20)->default('1.0.0');

            // The packages table arrives in Session 7; that migration adds the
            // foreign key. Keeping the column here lets the tier gate be stored
            // from the start without an out-of-order constraint.
            $table->unsignedBigInteger('min_package_id')->nullable();

            $table->json('config_schema');
            $table->json('default_config');
            $table->json('demo_data')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->decimal('extra_price', 12, 2)->default(0);
            $table->string('status', 20)->default(TemplateStatus::Draft->value);
            $table->unsignedInteger('usage_count')->default(0);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // The gallery filters on status and orders on sort_order, always.
            $table->index(['status', 'sort_order']);
            $table->index(['template_category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
