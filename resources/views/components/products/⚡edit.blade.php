<?php

use App\Contracts\ImageStorage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $productId;

    public string $category_id = '';

    public string $brand_id = '';

    public string $sku = '';

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $cost_price = '0.00';

    public string $price = '0.00';

    public string $compare_at_price = '';

    public string $stock = '0';

    public bool $is_active = true;

    public bool $is_featured = false;

    public ?string $existingImage = null;

    public bool $remove_image = false;

    public $image = null;

    /**
     * Nuevas fotografías adicionales.
     *
     * @var array<int, mixed>
     */
    public array $gallery_images = [];

    /**
     * Fotografías existentes que se eliminarán al guardar.
     *
     * @var array<int, int>
     */
    public array $gallery_images_to_delete = [];

    /**
     * Carga los datos actuales del producto.
     */
    public function mount(Product $product): void
    {
        $this->productId = $product->id;

        $this->category_id = (string) $product->category_id;

        $this->brand_id = $product->brand_id !== null
            ? (string) $product->brand_id
            : '';

        $this->sku = $product->sku;

        $this->name = $product->name;

        $this->slug = $product->slug;

        $this->description = $product->description ?? '';

        $this->cost_price = (string) $product->cost_price;

        $this->price = (string) $product->price;

        $this->compare_at_price = $product->compare_at_price !== null
            ? (string) $product->compare_at_price
            : '';

        $this->stock = (string) $product->stock;

        $this->is_active = $product->is_active;

        $this->is_featured = $product->is_featured;

        $this->existingImage = $product->image_url;
    }

    /**
     * Reglas de validación.
     */
    protected function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],

            'sku' => [
                'required',
                'string',
                'min:2',
                'max:80',
                Rule::unique('products', 'sku')
                    ->ignore($this->productId),
            ],

            'name' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],

            'slug' => [
                'required',
                'string',
                'max:170',
                Rule::unique('products', 'slug')
                    ->ignore($this->productId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'cost_price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],

            'compare_at_price' => [
                'nullable',
                'numeric',
                'gt:price',
                'max:99999999.99',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
                'max:999999999',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'remove_image' => [
                'boolean',
            ],

            'gallery_images' => [
                'array',
                'max:6',

                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ): void {
                    $pendingDeleteIds = collect(
                        $this->gallery_images_to_delete
                    )
                        ->map(
                            fn ($imageId): int => (int) $imageId
                        )
                        ->unique()
                        ->values()
                        ->all();

                    $existingImagesCount = ProductImage::query()
                        ->where(
                            'product_id',
                            $this->productId
                        )
                        ->when(
                            $pendingDeleteIds !== [],
                            fn ($query) => $query->whereNotIn(
                                'id',
                                $pendingDeleteIds
                            )
                        )
                        ->count();

                    $newImagesCount = is_array($value)
                        ? count($value)
                        : 0;

                    if (
                        $existingImagesCount
                        + $newImagesCount
                        > 6
                    ) {
                        $fail(
                            'La galería admite como máximo 6 fotografías adicionales.'
                        );
                    }
                },
            ],

            'gallery_images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'gallery_images_to_delete' => [
                'array',
            ],

            'gallery_images_to_delete.*' => [
                'integer',

                Rule::exists(
                    'product_images',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'product_id',
                        $this->productId
                    )
                ),
            ],

            'is_active' => [
                'boolean',
            ],

            'is_featured' => [
                'boolean',
            ],
        ];
    }

    /**
     * Mensajes de validación.
     */
    protected function messages(): array
    {
        return [
            'category_id.required' => 'Selecciona una categoría.',

            'category_id.exists' => 'La categoría seleccionada no es válida.',

            'brand_id.integer' => 'La marca seleccionada no es válida.',

            'brand_id.exists' => 'La marca seleccionada no existe.',

            'sku.required' => 'El SKU es obligatorio.',

            'sku.min' => 'El SKU debe tener al menos 2 caracteres.',

            'sku.max' => 'El SKU no puede superar los 80 caracteres.',

            'sku.unique' => 'Ya existe otro producto con este SKU.',

            'name.required' => 'El nombre del producto es obligatorio.',

            'name.min' => 'El nombre debe tener al menos 3 caracteres.',

            'name.max' => 'El nombre no puede superar los 150 caracteres.',

            'slug.required' => 'El slug es obligatorio.',

            'slug.max' => 'El slug no puede superar los 170 caracteres.',

            'slug.unique' => 'Ya existe otro producto con este slug.',

            'description.max' => 'La descripción no puede superar los 10000 caracteres.',

            'cost_price.required' => 'El costo es obligatorio.',

            'cost_price.numeric' => 'El costo debe ser un número válido.',

            'cost_price.min' => 'El costo no puede ser negativo.',

            'price.required' => 'El precio es obligatorio.',

            'price.numeric' => 'El precio debe ser un número válido.',

            'price.min' => 'El precio debe ser mayor que cero.',

            'compare_at_price.numeric' => 'El precio anterior debe ser un número válido.',

            'compare_at_price.gt' => 'El precio anterior debe ser mayor que el precio de venta.',

            'stock.required' => 'El stock es obligatorio.',

            'stock.integer' => 'El stock debe ser un número entero.',

            'stock.min' => 'El stock no puede ser negativo.',

            'image.image' => 'El archivo seleccionado debe ser una imagen.',

            'image.mimes' => 'La imagen principal debe ser JPG, JPEG, PNG o WEBP.',

            'image.max' => 'La imagen principal no puede superar los 3 MB.',

            'gallery_images.array' => 'La selección de fotografías adicionales no es válida.',

            'gallery_images.max' => 'Puedes seleccionar como máximo 6 fotografías adicionales.',

            'gallery_images.*.image' => 'Todos los archivos adicionales deben ser imágenes.',

            'gallery_images.*.mimes' => 'Las fotografías adicionales deben ser JPG, JPEG, PNG o WEBP.',

            'gallery_images.*.max' => 'Cada fotografía adicional puede pesar como máximo 3 MB.',

            'gallery_images_to_delete.*.exists' => 'Una fotografía seleccionada ya no pertenece al producto.',
        ];
    }

    /**
     * Genera automáticamente el slug.
     */
    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    /**
     * Quita una nueva fotografía antes de guardar.
     */
    public function removeNewGalleryImage(
        int $index
    ): void {
        if (
            ! array_key_exists(
                $index,
                $this->gallery_images
            )
        ) {
            return;
        }

        unset(
            $this->gallery_images[$index]
        );

        $this->gallery_images = array_values(
            $this->gallery_images
        );

        $this->resetValidation(
            'gallery_images'
        );
    }

    /**
     * Marca una fotografía existente para eliminarla.
     */
    public function markGalleryImageForDeletion(
        int $imageId
    ): void {
        $imageExists = ProductImage::query()
            ->where(
                'product_id',
                $this->productId
            )
            ->whereKey($imageId)
            ->exists();

        if (! $imageExists) {
            return;
        }

        if (
            in_array(
                $imageId,
                $this->gallery_images_to_delete,
                true
            )
        ) {
            return;
        }

        $this->gallery_images_to_delete[] = $imageId;

        $this->resetValidation(
            'gallery_images'
        );
    }

    /**
     * Deshace la eliminación pendiente de una fotografía.
     */
    public function restoreGalleryImage(
        int $imageId
    ): void {
        $this->gallery_images_to_delete = array_values(
            array_filter(
                $this->gallery_images_to_delete,
                fn ($pendingImageId): bool =>
                    (int) $pendingImageId !== $imageId
            )
        );

        $this->resetValidation(
            'gallery_images'
        );
    }

    /**
     * Actualiza el producto y su galería.
     */
    public function update(): void
    {
        $validated = $this->validate();

        $uploadedMainImage = $validated['image'] ?? null;
        $removeMainImage = (bool) ($validated['remove_image'] ?? false);
        $uploadedGalleryImages = $validated['gallery_images'] ?? [];

        $galleryImagesToDelete = collect(
            $validated['gallery_images_to_delete'] ?? []
        )
            ->map(fn ($imageId): int => (int) $imageId)
            ->unique()
            ->values()
            ->all();

        unset(
            $validated['image'],
            $validated['remove_image'],
            $validated['gallery_images'],
            $validated['gallery_images_to_delete']
        );

        $validated['category_id'] = (int) $validated['category_id'];
        $validated['brand_id'] = filled($validated['brand_id'])
            ? (int) $validated['brand_id']
            : null;
        $validated['sku'] = Str::upper(trim($validated['sku']));
        $validated['name'] = trim($validated['name']);
        $validated['slug'] = Str::slug($validated['slug']);
        $validated['description'] = filled($validated['description'])
            ? trim($validated['description'])
            : null;
        $validated['compare_at_price'] = filled(
            $validated['compare_at_price']
        )
            ? $validated['compare_at_price']
            : null;

        $product = Product::query()->findOrFail(
            $this->productId
        );

        $galleryRecordsToDelete = ProductImage::query()
            ->where('product_id', $this->productId)
            ->when(
                $galleryImagesToDelete !== [],
                fn ($query) => $query->whereIn(
                    'id',
                    $galleryImagesToDelete
                ),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->get([
                'id',
                'image',
                'image_public_id',
                'image_provider',
            ]);

        /** @var ImageStorage $imageStorage */
        $imageStorage = app(ImageStorage::class);

        $uploadedResources = [];
        $resourcesToDeleteAfterUpdate = [];
        $newGalleryRows = [];

        try {
            if ($uploadedMainImage !== null) {
                $mainImageUpload = $imageStorage->upload(
                    $uploadedMainImage,
                    'wasyntek/products/main'
                );

                $uploadedResources[] = $mainImageUpload;

                $validated['image'] = $mainImageUpload['url'];
                $validated['image_public_id'] = $mainImageUpload['public_id'];
                $validated['image_provider'] = $mainImageUpload['provider'];

                if ($product->image) {
                    $resourcesToDeleteAfterUpdate[] = [
                        'image' => $product->getRawOriginal('image'),
                        'public_id' => $product->image_public_id,
                        'provider' => $product->image_provider,
                    ];
                }
            } elseif ($removeMainImage) {
                $validated['image'] = null;
                $validated['image_public_id'] = null;
                $validated['image_provider'] = null;

                if ($product->image) {
                    $resourcesToDeleteAfterUpdate[] = [
                        'image' => $product->getRawOriginal('image'),
                        'public_id' => $product->image_public_id,
                        'provider' => $product->image_provider,
                    ];
                }
            }

            $lastSortOrder = ProductImage::query()
                ->where('product_id', $this->productId)
                ->when(
                    $galleryImagesToDelete !== [],
                    fn ($query) => $query->whereNotIn(
                        'id',
                        $galleryImagesToDelete
                    )
                )
                ->max('sort_order');

            $nextSortOrder = $lastSortOrder !== null
                ? (int) $lastSortOrder + 1
                : 0;

            foreach ($uploadedGalleryImages as $index => $galleryImage) {
                $galleryUpload = $imageStorage->upload(
                    $galleryImage,
                    'wasyntek/products/gallery'
                );

                $uploadedResources[] = $galleryUpload;

                $newGalleryRows[] = [
                    'image' => $galleryUpload['url'],
                    'image_public_id' => $galleryUpload['public_id'],
                    'image_provider' => $galleryUpload['provider'],
                    'alt_text' => $validated['name']
                        . ' - Vista '
                        . ($nextSortOrder + $index + 1),
                    'sort_order' => $nextSortOrder + $index,
                    'is_active' => true,
                ];
            }

            DB::transaction(
                function () use (
                    $product,
                    $validated,
                    $galleryImagesToDelete,
                    $newGalleryRows
                ): void {
                    $product->update($validated);

                    if ($galleryImagesToDelete !== []) {
                        ProductImage::query()
                            ->where('product_id', $product->id)
                            ->whereIn('id', $galleryImagesToDelete)
                            ->delete();
                    }

                    foreach ($newGalleryRows as $galleryRow) {
                        $product->images()->create($galleryRow);
                    }
                }
            );
        } catch (\Throwable $exception) {
            foreach (array_reverse($uploadedResources) as $resource) {
                try {
                    $imageStorage->delete(
                        $resource['public_id'],
                        $resource['url']
                    );
                } catch (\Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            report($exception);

            $this->addError(
                'gallery_images',
                'No se pudo actualizar el producto. Verifica las imágenes e intenta nuevamente.'
            );

            return;
        }

        foreach ($galleryRecordsToDelete as $galleryRecord) {
            $resourcesToDeleteAfterUpdate[] = [
                'image' => $galleryRecord->getRawOriginal('image'),
                'public_id' => $galleryRecord->image_public_id,
                'provider' => $galleryRecord->image_provider,
            ];
        }

        foreach ($resourcesToDeleteAfterUpdate as $resource) {
            $this->deleteStoredResource(
                imageStorage: $imageStorage,
                image: $resource['image'],
                publicId: $resource['public_id'],
                provider: $resource['provider'],
            );
        }

        session()->flash(
            'success',
            'El producto fue actualizado correctamente.'
        );

        $this->redirectRoute(
            'products.index',
            navigate: true
        );
    }

    /**
     * Elimina una imagen de Cloudinary o una imagen local antigua.
     */
    private function deleteStoredResource(
        ImageStorage $imageStorage,
        ?string $image,
        ?string $publicId,
        ?string $provider,
    ): void {
        if (! is_string($image) || trim($image) === '') {
            return;
        }

        try {
            if (
                $provider === 'cloudinary'
                || filled($publicId)
                || Str::startsWith(
                    $image,
                    [
                        'http://',
                        'https://',
                    ]
                )
            ) {
                $imageStorage->delete($publicId, $image);

                return;
            }

            $localPath = ltrim(
                str_replace('\\', '/', $image),
                '/'
            );

            if (Str::startsWith($localPath, 'storage/')) {
                $localPath = Str::after(
                    $localPath,
                    'storage/'
                );
            }

            Storage::disk('public')->delete($localPath);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Categorías, marcas y fotografías actuales.
     */
    public function with(): array
    {
        $pendingDeleteIds = collect(
            $this->gallery_images_to_delete
        )
            ->map(
                fn ($imageId): int => (int) $imageId
            )
            ->unique()
            ->values()
            ->all();

        $existingGalleryImages = ProductImage::query()
            ->where(
                'product_id',
                $this->productId
            )
            ->when(
                $pendingDeleteIds !== [],
                fn ($query) => $query->whereNotIn(
                    'id',
                    $pendingDeleteIds
                )
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $pendingDeletionImages = $pendingDeleteIds !== []
            ? ProductImage::query()
                ->where(
                    'product_id',
                    $this->productId
                )
                ->whereIn(
                    'id',
                    $pendingDeleteIds
                )
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
            : collect();

        return [
            'categories' => Category::query()
                ->where(function ($query): void {
                    $query
                        ->where('is_active', true)
                        ->orWhere(
                            'id',
                            (int) $this->category_id
                        );
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'brands' => Brand::query()
                ->where(function ($query): void {
                    $query
                        ->where('is_active', true)
                        ->orWhere(
                            'id',
                            (int) $this->brand_id
                        );
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'existingGalleryImages' => $existingGalleryImages,

            'pendingDeletionImages' => $pendingDeletionImages,

            'galleryTotal' => $existingGalleryImages->count()
                + count($this->gallery_images),
        ];
    }
};

?>

<div class="mx-auto w-full max-w-6xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Editar producto
            </flux:heading>

            <flux:text class="mt-1">
                Actualiza su categoría, marca, información comercial e inventario.
            </flux:text>
        </div>

        <flux:button
            icon="arrow-left"
            :href="route('products.index')"
            wire:navigate
        >
            Volver
        </flux:button>

    </div>

    <form wire:submit="update" class="space-y-6">

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Información principal
                </flux:heading>

                <flux:text class="mt-1">
                    Identificación, categoría y marca del producto.
                </flux:text>
            </div>

            <div class="grid gap-6 md:grid-cols-2">

                <div>
                    <label
                        for="product-category"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Categoría
                    </label>

                    <select
                        id="product-category"
                        wire:model="category_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:ring-zinc-700"
                    >
                        <option value="">
                            Selecciona una categoría
                        </option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('category_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="product-brand"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Marca
                    </label>

                    <select
                        id="product-brand"
                        wire:model="brand_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:ring-zinc-700"
                    >
                        <option value="">
                            Sin marca
                        </option>

                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('brand_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div class="grid gap-6 md:grid-cols-2">

                <div>
                    <flux:input
                        label="SKU"
                        wire:model="sku"
                        required
                    />

                    @error('sku')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Nombre"
                        wire:model.live.debounce.400ms="name"
                        required
                    />

                    @error('name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div>
                <flux:input
                    label="Slug"
                    wire:model="slug"
                    required
                />

                @error('slug')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <flux:textarea
                    label="Descripción"
                    rows="6"
                    wire:model="description"
                />

                @error('description')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Precios e inventario
            </flux:heading>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">

                <div>
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0"
                        label="Costo"
                        wire:model="cost_price"
                        required
                    />

                    @error('cost_price')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0.01"
                        label="Precio de venta"
                        wire:model="price"
                        required
                    />

                    @error('price')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0"
                        label="Precio anterior"
                        placeholder="Opcional"
                        wire:model="compare_at_price"
                    />

                    @error('compare_at_price')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="number"
                        min="0"
                        label="Stock"
                        wire:model="stock"
                        required
                    />

                    @error('stock')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Imagen y publicación
            </flux:heading>

            {{-- Imagen principal --}}
            <div class="grid gap-6 lg:grid-cols-2">

                <div class="space-y-4">

                    <div>
                        <label
                            for="product-image"
                            class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Nueva imagen
                        </label>

                        <input
                            id="product-image"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            wire:model="image"
                            class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                        >

                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            La nueva imagen reemplazará la imagen principal actual. Máximo 3 MB.
                        </p>

                        @error('image')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror

                        <div
                            wire:loading
                            wire:target="image"
                            class="mt-2 text-sm text-zinc-500 dark:text-zinc-400"
                        >
                            Procesando imagen...
                        </div>
                    </div>

                    @if ($existingImage)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                            <flux:checkbox
                                label="Eliminar imagen actual"
                                description="La imagen se eliminará cuando guardes los cambios."
                                wire:model="remove_image"
                            />

                        </div>
                    @endif

                </div>

                <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                    @if ($image)
                        <img
                            src="{{ $image->temporaryUrl() }}"
                            alt="Nueva imagen"
                            class="h-52 w-full object-contain"
                        >
                    @elseif ($existingImage && ! $remove_image)
                        <img
                            src="{{ $existingImage }}"
                            alt="{{ $name }}"
                            class="h-52 w-full object-contain"
                        >
                    @else
                        <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Producto sin imagen.
                        </p>
                    @endif

                </div>

            </div>

            {{-- Fotografías adicionales --}}
            <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">

                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                            Fotografías adicionales
                        </h3>

                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Conserva, elimina o agrega nuevas tomas del producto.
                        </p>
                    </div>

                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        {{ $galleryTotal }} de 6
                    </span>

                </div>

                {{-- Imágenes existentes --}}
                @if ($existingGalleryImages->isNotEmpty())

                    <div class="mt-5">

                        <p class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Fotografías guardadas
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                            @foreach ($existingGalleryImages as $galleryImage)

                                <div
                                    wire:key="existing-gallery-image-{{ $galleryImage->id }}"
                                    class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800"
                                >

                                    <div class="flex h-40 items-center justify-center overflow-hidden">

                                        <img
                                            src="{{ $galleryImage->image_url }}"
                                            alt="{{ $galleryImage->alt_text ?: $name }}"
                                            class="h-full w-full object-contain"
                                        >

                                    </div>

                                    <div class="flex items-center justify-between gap-3 border-t border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">

                                        <div class="min-w-0">

                                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                                Vista {{ $loop->iteration }}
                                            </p>

                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Imagen guardada
                                            </p>

                                        </div>

                                        <button
                                            type="button"
                                            wire:click="markGalleryImageForDeletion({{ $galleryImage->id }})"
                                            class="shrink-0 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950"
                                        >
                                            Eliminar
                                        </button>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endif

                {{-- Eliminaciones pendientes --}}
                @if ($pendingDeletionImages->isNotEmpty())

                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">

                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                            Fotografías que se eliminarán al guardar
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">

                            @foreach ($pendingDeletionImages as $pendingImage)

                                <button
                                    type="button"
                                    wire:key="pending-gallery-deletion-{{ $pendingImage->id }}"
                                    wire:click="restoreGalleryImage({{ $pendingImage->id }})"
                                    class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200"
                                >
                                    Deshacer eliminación
                                </button>

                            @endforeach

                        </div>

                    </div>

                @endif

                {{-- Nuevas imágenes --}}
                <div class="mt-5">

                    <label
                        for="product-gallery-images"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Agregar fotografías
                    </label>

                    @if ($galleryTotal < 6)

                        <input
                            id="product-gallery-images"
                            type="file"
                            multiple
                            accept="image/png,image/jpeg,image/webp"
                            wire:model="gallery_images"
                            class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                        >

                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            Puedes completar hasta 6 fotografías adicionales. Cada archivo puede pesar como máximo 3 MB.
                        </p>

                    @else

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            La galería ya contiene el máximo de 6 fotografías. Elimina una para agregar otra.
                        </div>

                    @endif

                    @error('gallery_images')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('gallery_images.*')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    <div
                        wire:loading
                        wire:target="gallery_images"
                        class="mt-2 text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        Procesando fotografías adicionales...
                    </div>

                </div>

                {{-- Previsualización de nuevas imágenes --}}
                @if ($gallery_images !== [])

                    <div class="mt-5">

                        <p class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Nuevas fotografías
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                            @foreach ($gallery_images as $index => $galleryImage)

                                <div
                                    wire:key="new-gallery-image-{{ $index }}"
                                    class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800"
                                >

                                    <div class="flex h-40 items-center justify-center overflow-hidden">

                                        <img
                                            src="{{ $galleryImage->temporaryUrl() }}"
                                            alt="Nueva vista {{ $index + 1 }}"
                                            class="h-full w-full object-contain"
                                        >

                                    </div>

                                    <div class="flex items-center justify-between gap-3 border-t border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">

                                        <div class="min-w-0">

                                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                                Nueva vista {{ $index + 1 }}
                                            </p>

                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Pendiente de guardar
                                            </p>

                                        </div>

                                        <button
                                            type="button"
                                            wire:click="removeNewGalleryImage({{ $index }})"
                                            class="shrink-0 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950"
                                        >
                                            Quitar
                                        </button>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endif

            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                    <flux:checkbox
                        label="Producto activo"
                        description="Disponible en el catálogo."
                        wire:model="is_active"
                    />

                </div>

                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                    <flux:checkbox
                        label="Producto destacado"
                        description="Visible en secciones principales."
                        wire:model="is_featured"
                    />

                </div>

            </div>

        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <flux:button
                :href="route('products.index')"
                wire:navigate
            >
                Cancelar
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                icon="check"
                wire:loading.attr="disabled"
                wire:target="update,image,gallery_images"
            >
                <span wire:loading.remove wire:target="update">
                    Guardar cambios
                </span>

                <span wire:loading wire:target="update">
                    Actualizando...
                </span>
            </flux:button>

        </div>

    </form>

</div>