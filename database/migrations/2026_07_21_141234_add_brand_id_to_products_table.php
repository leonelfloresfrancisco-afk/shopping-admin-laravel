<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la marca a los productos.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // NUEVO: relación opcional con marcas.
            $table->foreignId('brand_id')
                ->nullable()
                ->after('category_id')
                ->constrained('brands')
                ->restrictOnDelete();
        });
    }

    /**
     * Elimina la relación con marcas.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign([
                'brand_id',
            ]);

            $table->dropColumn('brand_id');
        });
    }
};