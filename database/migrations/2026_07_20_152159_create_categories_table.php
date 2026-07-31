<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {

            $table->id();

            // Información principal
            $table->string('name', 150);
            $table->string('slug', 170)->unique();

            // Contenido
            $table->text('description')->nullable();

            // Imagen de la categoría
            $table->string('image')->nullable();

            // Organización
            $table->unsignedInteger('sort_order')->default(0);

            // Estado
            $table->boolean('is_active')->default(true);

            // SEO (nos servirá para el sitio web)
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 255)->nullable();

            $table->timestamps();

        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};