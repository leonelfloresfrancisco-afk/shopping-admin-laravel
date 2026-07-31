<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos comerciales necesarios para administrar productos.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // NUEVO: código único de inventario.
            $table->string('sku', 80)
                ->unique()
                ->after('category_id');

            // NUEVO: precio pagado al proveedor.
            $table->decimal('cost_price', 10, 2)
                ->default(0)
                ->after('description');

            // NUEVO: precio anterior utilizado para mostrar descuentos.
            $table->decimal('compare_at_price', 10, 2)
                ->nullable()
                ->after('price');

            // NUEVO: permite destacar productos en la tienda.
            $table->boolean('is_featured')
                ->default(false)
                ->after('is_active');
        });
    }

    /**
     * Elimina los campos comerciales agregados.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_sku_unique');

            $table->dropColumn([
                'sku',
                'cost_price',
                'compare_at_price',
                'is_featured',
            ]);
        });
    }
};