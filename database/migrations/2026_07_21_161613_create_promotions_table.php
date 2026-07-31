<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla principal de promociones.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 160);

            $table->string('code', 80)
                ->nullable()
                ->unique();

            $table->text('description')
                ->nullable();

            $table->enum('discount_type', [
                'percentage',
                'fixed',
            ]);

            $table->decimal('discount_value', 12, 2);

            $table->enum('applies_to', [
                'all',
                'categories',
                'products',
            ])->default('all');

            $table->decimal('minimum_purchase', 12, 2)
                ->default(0);

            $table->unsignedInteger('usage_limit')
                ->nullable();

            $table->unsignedInteger('used_count')
                ->default(0);

            $table->dateTime('starts_at')
                ->nullable();

            $table->dateTime('ends_at')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'is_active',
                'starts_at',
                'ends_at',
            ]);

            $table->index([
                'discount_type',
                'applies_to',
            ]);
        });
    }

    /**
     * Elimina la tabla de promociones.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};