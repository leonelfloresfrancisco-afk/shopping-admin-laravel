<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos administrativos a los usuarios.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 30)
                ->nullable()
                ->after('email');

            $table->string('role', 30)
                ->default('operator')
                ->after('password');

            $table->boolean('is_active')
                ->default(true)
                ->after('role');

            $table->index([
                'role',
                'is_active',
            ]);
        });

        /*
         * Los usuarios existentes fueron creados antes del módulo de roles.
         * Por eso se convierten inicialmente en administradores.
         */
        DB::table('users')->update([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Revierte los campos administrativos.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex([
                'role',
                'is_active',
            ]);

            $table->dropColumn([
                'phone',
                'role',
                'is_active',
            ]);
        });
    }
};