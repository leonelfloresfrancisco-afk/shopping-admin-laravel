<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de diapositivas del carrusel.
     */
    public function up(): void
    {
        Schema::create('carousel_slides', function (Blueprint $table): void {
            $table->id();

            // Contenido principal
            $table->string('title', 160);
            $table->string('subtitle', 255)->nullable();
            $table->text('description')->nullable();

            // Botón de llamada a la acción
            $table->string('button_text', 80)->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->boolean('open_in_new_tab')->default(false);

            // Imágenes responsive
            $table->string('desktop_image');
            $table->string('mobile_image')->nullable();

            // Organización y publicación
            $table->unsignedInteger('sort_order')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'is_active',
                'sort_order',
            ]);

            $table->index([
                'starts_at',
                'ends_at',
            ]);
        });
    }

    /**
     * Elimina la tabla de diapositivas.
     */
    public function down(): void
    {
        Schema::dropIfExists('carousel_slides');
    }
};