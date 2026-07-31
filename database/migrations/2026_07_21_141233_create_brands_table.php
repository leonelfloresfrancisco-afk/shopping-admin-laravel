<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de marcas.
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 150);

            $table->string('slug', 170)
                ->unique();

            $table->text('description')
                ->nullable();

            $table->string('logo')
                ->nullable();

            $table->string('website')
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'is_active',
                'sort_order',
            ]);
        });
    }

    /**
     * Elimina la tabla de marcas.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};