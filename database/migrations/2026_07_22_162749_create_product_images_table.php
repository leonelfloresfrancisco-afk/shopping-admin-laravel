<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de imágenes adicionales de productos.
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Producto relacionado
            |--------------------------------------------------------------------------
            |
            | Cada imagen pertenece a un único producto.
            | Al eliminar el producto, sus imágenes también se eliminan
            | automáticamente de la base de datos.
            |
            */

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Archivo e información visual
            |--------------------------------------------------------------------------
            */

            $table->string('image');

            $table->string('alt_text', 180)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Organización
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'product_id',
                'sort_order',
            ]);

            $table->index([
                'product_id',
                'is_active',
            ]);
        });
    }

    /**
     * Elimina la tabla de imágenes adicionales.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};