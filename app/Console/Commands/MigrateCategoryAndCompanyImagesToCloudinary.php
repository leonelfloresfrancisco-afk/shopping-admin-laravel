<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\ImageStorage;
use App\Models\Category;
use App\Models\CompanySetting;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class MigrateCategoryAndCompanyImagesToCloudinary extends Command
{
    protected $signature = 'images:migrate-category-company';

    protected $description =
        'Migra imágenes de categorías, logo y favicon a Cloudinary.';

    public function handle(ImageStorage $imageStorage): int
    {
        $disk = Storage::disk('public');

        Category::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id')
            ->each(function (Category $category) use (
                $disk,
                $imageStorage
            ): void {
                $this->migrateField(
                    record: $category,
                    field: 'image',
                    folder: 'wasyntek/categories',
                    label: "Categoría {$category->name}",
                    disk: $disk,
                    imageStorage: $imageStorage,
                );
            });

        $company = CompanySetting::query()->first();

        if ($company !== null) {
            $this->migrateField(
                record: $company,
                field: 'logo',
                folder: 'wasyntek/company/logo',
                label: 'Logo de empresa',
                disk: $disk,
                imageStorage: $imageStorage,
            );

            $this->migrateField(
                record: $company,
                field: 'favicon',
                folder: 'wasyntek/company/favicon',
                label: 'Favicon de empresa',
                disk: $disk,
                imageStorage: $imageStorage,
            );
        }

        $this->newLine();
        $this->info('Migración terminada.');

        return self::SUCCESS;
    }

    private function migrateField(
        Model $record,
        string $field,
        string $folder,
        string $label,
        mixed $disk,
        ImageStorage $imageStorage,
    ): void {
        $value = $record->getRawOriginal($field);

        if (
            ! is_string($value)
            || trim($value) === ''
        ) {
            $this->line("Omitido: {$label}");

            return;
        }

        if (
            Str::startsWith(
                $value,
                [
                    'http://',
                    'https://',
                ]
            )
        ) {
            $this->line("Ya está en la nube: {$label}");

            return;
        }

        $relativePath = ltrim(
            str_replace('\\', '/', $value),
            '/'
        );

        if (! $disk->exists($relativePath)) {
            $this->warn(
                "No encontrado: {$label} ({$relativePath})"
            );

            return;
        }

        try {
            $upload = $imageStorage->upload(
                $disk->path($relativePath),
                $folder
            );

            $record->forceFill([
                $field => $upload['url'],
            ])->saveOrFail();

            $this->info("Migrado: {$label}");
        } catch (Throwable $exception) {
            report($exception);

            $this->error(
                "Error en {$label}: {$exception->getMessage()}"
            );
        }
    }
}