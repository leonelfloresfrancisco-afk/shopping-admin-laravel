<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la relación muchos-a-muchos entre categorías y promociones.
     */
    public function up(): void
    {
        Schema::create('category_promotion', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('promotion_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'category_id',
                'promotion_id',
            ]);
        });
    }

    /**
     * Elimina la relación entre categorías y promociones.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_promotion');
    }
};