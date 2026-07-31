<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

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

    /**
     * Imagen principal del producto.
     */
    public $image = null;

    /**
     * Fotografías adicionales opcionales.
     *
     * @var array<int, mixed>
     */
    public array $gallery_images = [];

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
                'unique:products,sku',
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
                'unique:products,slug',
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

            /*
            |--------------------------------------------------------------------------
            | Fotografías adicionales
            |--------------------------------------------------------------------------
            |
            | Son opcionales. Se permiten hasta seis fotografías adicionales.
            | Cada archivo puede pesar como máximo 3 MB.
            |
            */

            'gallery_images' => [
                'array',
                'max:6',
            ],

            'gallery_images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
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
            'sku.unique' => 'Ya existe un producto con este SKU.',

            'name.required' => 'El nombre del producto es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.max' => 'El slug no puede superar los 170 caracteres.',
            'slug.unique' => 'Ya existe un producto con este slug.',

            'description.max' => 'La descripción no puede superar los 10000 caracteres.',

            'cost_price.required' => 'El costo es obligatorio.',
            'cost_price.numeric' => 'El costo debe ser un número válido.',
            'cost_price.min' => 'El costo no puede ser negativo.',

            'price.required' => 'El precio de venta es obligatorio.',
            'price.numeric' => 'El precio de venta debe ser un número válido.',
            'price.min' => 'El precio de venta debe ser mayor que cero.',

            'compare_at_price.numeric' => 'El precio anterior debe ser un número válido.',
            'compare_at_price.gt' => 'El precio anterior debe ser mayor que el precio de venta.',

            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',

            'image.image' => 'El archivo seleccionado debe ser una imagen.',
            'image.mimes' => 'La imagen principal debe ser JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen principal no puede superar los 3 MB.',

            'gallery_images.array' => 'La galería seleccionada no es válida.',
            'gallery_images.max' => 'Puedes subir como máximo 6 fotografías adicionales.',

            'gallery_images.*.image' => 'Todos los archivos adicionales deben ser imágenes.',
            'gallery_images.*.mimes' => 'Las fotografías adicionales deben ser JPG, JPEG, PNG o WEBP.',
            'gallery_images.*.max' => 'Cada fotografía adicional puede pesar como máximo 3 MB.',
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
     * Elimina una fotografía adicional antes de guardar el producto.
     */
    public function removeGalleryImage(int $index): void
    {
        if (! array_key_exists($index, $this->gallery_images)) {
            return;
        }

        unset($this->gallery_images[$index]);

        $this->gallery_images = array_values(
            $this->gallery_images
        );

        $this->resetValidation();
    }

    /**
     * Elimina la imagen principal temporal.
     */
    public function removeMainImage(): void
    {
        $this->reset('image');
        $this->resetValidation('image');
    }

    /**
     * Guarda el producto y sus fotografías adicionales.
     */
    public function save(): void
    {
        $validated = $this->validate();

        $uploadedMainImage = $validated['image'] ?? null;

        $uploadedGalleryImages = $validated['gallery_images'] ?? [];

        unset(
            $validated['image'],
            $validated['gallery_images']
        );

        $validated['category_id'] = (int) $validated['category_id'];

        $validated['brand_id'] = filled($validated['brand_id'])
            ? (int) $validated['brand_id']
            : null;

        $validated['sku'] = Str::upper(
            trim($validated['sku'])
        );

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['slug'] = Str::slug(
            $validated['slug']
        );

        $validated['description'] = filled($validated['description'])
            ? trim($validated['description'])
            : null;

        $validated['compare_at_price'] = filled(
            $validated['compare_at_price']
        )
            ? $validated['compare_at_price']
            : null;

        /*
        |--------------------------------------------------------------------------
        | Archivos almacenados durante el proceso
        |--------------------------------------------------------------------------
        |
        | En caso de producirse un error, estos archivos se eliminan para
        | evitar imágenes huérfanas dentro del almacenamiento.
        |
        */

        $storedFiles = [];

        $storedGalleryImages = [];

        try {
            if ($uploadedMainImage !== null) {
                $mainImagePath = $uploadedMainImage->store(
                    'products',
                    'public'
                );

                $storedFiles[] = $mainImagePath;

                $validated['image'] = $mainImagePath;
            }

            foreach ($uploadedGalleryImages as $index => $galleryImage) {
                $galleryImagePath = $galleryImage->store(
                    'products/gallery',
                    'public'
                );

                $storedFiles[] = $galleryImagePath;

                $storedGalleryImages[] = [
                    'image' => $galleryImagePath,

                    'alt_text' => $validated['name']
                        . ' - Vista '
                        . ($index + 2),

                    'sort_order' => $index,

                    'is_active' => true,
                ];
            }

            DB::transaction(
                function () use (
                    $validated,
                    $storedGalleryImages
                ): void {
                    $product = Product::query()->create(
                        $validated
                    );

                    foreach ($storedGalleryImages as $galleryImageData) {
                        $product->images()->create(
                            $galleryImageData
                        );
                    }
                }
            );
        } catch (\Throwable $exception) {
            if ($storedFiles !== []) {
                Storage::disk('public')->delete(
                    $storedFiles
                );
            }

            report($exception);

            $this->addError(
                'gallery_images',
                'No se pudo guardar el producto. Verifica las imágenes e intenta nuevamente.'
            );

            return;
        }

        session()->flash(
            'success',
            'El producto fue creado correctamente.'
        );

        $this->redirectRoute(
            'products.index',
            navigate: true
        );
    }

    /**
     * Categorías y marcas disponibles.
     */
    public function with(): array
    {
        return [
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'brands' => Brand::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ];
    }
};

?>

<div class="mx-auto w-full max-w-6xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Nuevo producto
            </flux:heading>

            <flux:text class="mt-1">
                Registra su categoría, marca, precios, descuento, inventario e imagen.
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

    @if ($categories->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            Debes crear o activar una categoría antes de registrar productos.
        </div>
    @endif

    @if ($brands->isEmpty())
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
            No hay marcas activas. Puedes registrar el producto sin marca o activar una desde el módulo Marcas.
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">

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
                        placeholder="Ejemplo: SAM-TAB-001"
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
                        placeholder="Ejemplo: Tablet Samsung Galaxy"
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
                    placeholder="tablet-samsung-galaxy"
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
                    placeholder="Características y detalles del producto."
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

            <div>
                <flux:heading size="lg">
                    Precios e inventario
                </flux:heading>

                <flux:text class="mt-1">
                    El descuento se calcula comparando el precio anterior con el precio de venta.
                </flux:text>
            </div>

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

                <div>
                    <label
                        for="product-image"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Imagen principal
                    </label>

                    <input
                        id="product-image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="image"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        JPG, PNG o WEBP. Máximo 3 MB.
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

                    @if ($image)
                        <button
                            type="button"
                            wire:click="removeMainImage"
                            class="mt-3 inline-flex items-center justify-center rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            Quitar imagen
                        </button>
                    @endif
                </div>

                <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                    @if ($image)
                        <img
                            src="{{ $image->temporaryUrl() }}"
                            alt="Vista previa"
                            class="h-52 w-full object-contain"
                        >
                    @else
                        <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            La vista previa aparecerá aquí.
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
                            Agrega tomas desde diferentes ángulos. Este campo es opcional.
                        </p>
                    </div>

                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        {{ count($gallery_images) }} de 6
                    </span>

                </div>

                <div class="mt-5">

                    <label
                        for="product-gallery-images"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Seleccionar fotografías
                    </label>

                    <input
                        id="product-gallery-images"
                        type="file"
                        multiple
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="gallery_images"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        Puedes seleccionar hasta 6 fotografías. Cada archivo puede pesar como máximo 3 MB.
                    </p>

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

                @if ($gallery_images !== [])

                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                        @foreach ($gallery_images as $index => $galleryImage)

                            <div
                                wire:key="new-gallery-image-{{ $index }}"
                                class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800"
                            >

                                <div class="flex h-40 items-center justify-center overflow-hidden">

                                    <img
                                        src="{{ $galleryImage->temporaryUrl() }}"
                                        alt="Vista adicional {{ $index + 1 }}"
                                        class="h-full w-full object-contain"
                                    >

                                </div>

                                <div class="flex items-center justify-between gap-3 border-t border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">

                                    <div class="min-w-0">

                                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                            Vista {{ $index + 1 }}
                                        </p>

                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            Fotografía adicional
                                        </p>

                                    </div>

                                    <button
                                        type="button"
                                        wire:click="removeGalleryImage({{ $index }})"
                                        class="shrink-0 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950"
                                    >
                                        Quitar
                                    </button>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @else

                    <div class="mt-5 rounded-xl border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-600">

                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                            No hay fotografías adicionales seleccionadas.
                        </p>

                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            El producto puede guardarse únicamente con su imagen principal.
                        </p>

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
                :disabled="$categories->isEmpty()"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    Guardar producto
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </flux:button>

        </div>

    </form>

</div>