<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table
                ->string('image_public_id')
                ->nullable()
                ->after('image');

            $table
                ->string('image_provider', 30)
                ->nullable()
                ->after('image_public_id');
        });

        Schema::table('product_images', function (Blueprint $table): void {
            $table
                ->string('image_public_id')
                ->nullable()
                ->after('image');

            $table
                ->string('image_provider', 30)
                ->nullable()
                ->after('image_public_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table): void {
            $table->dropColumn([
                'image_public_id',
                'image_provider',
            ]);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'image_public_id',
                'image_provider',
            ]);
        });
    }
};