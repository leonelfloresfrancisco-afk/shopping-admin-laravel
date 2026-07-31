<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la configuración general de la empresa.
     */
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();

            // Identidad empresarial
            $table->string('trade_name', 160);
            $table->string('legal_name', 200)->nullable();
            $table->string('tax_id', 30)->nullable();
            $table->string('website')->nullable();

            // Contacto
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();

            // Dirección
            $table->string('address', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('country', 120)->default('Perú');
            $table->string('postal_code', 20)->nullable();

            // Localización y moneda
            $table->char('currency_code', 3)->default('PEN');
            $table->string('timezone', 64)->default('America/Lima');
            $table->decimal('tax_rate', 5, 2)->default(0);

            // Facturación
            $table->string('invoice_prefix', 20)->default('FAC');
            $table->unsignedBigInteger('invoice_next_number')->default(1);

            // Identidad visual
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();

            // Redes sociales
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('youtube_url')->nullable();

            // Estado general de la tienda
            $table->boolean('store_enabled')->default(true);
            $table->text('maintenance_message')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Elimina la configuración empresarial.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};