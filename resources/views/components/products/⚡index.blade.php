<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    // NUEVO: filtro por marca.
    public string $brandFilter = '';

    public string $statusFilter = '';

    public string $perPage = '10';

    public ?string $message = null;

    public string $messageType = 'success';

    /**
     * Reinicia la paginación cuando cambia la búsqueda.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando cambia la categoría.
     */
    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    /**
     * NUEVO: reinicia la paginación cuando cambia la marca.
     */
    public function updatedBrandFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando cambia el estado.
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Valida la cantidad permitida de registros por página.
     */
    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, ['10', '25', '50'], true)) {
            $this->perPage = '10';
        }

        $this->resetPage();
    }

    /**
     * Limpia todos los filtros.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->brandFilter = '';
        $this->statusFilter = '';

        $this->resetPage();
    }

    /**
     * Activa o desactiva un producto.
     */
    public function toggleStatus(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $product->update([
            'is_active' => ! $product->is_active,
        ]);

        $this->messageType = 'success';

        $this->message = $product->is_active
            ? 'El producto fue activado correctamente.'
            : 'El producto fue desactivado correctamente.';
    }

    /**
     * Marca o desmarca un producto como destacado.
     */
    public function toggleFeatured(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $product->update([
            'is_featured' => ! $product->is_featured,
        ]);

        $this->messageType = 'success';

        $this->message = $product->is_featured
            ? 'El producto fue marcado como destacado.'
            : 'El producto fue retirado de destacados.';
    }

    /**
     * Elimina un producto y su imagen principal.
     */
    public function delete(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        $this->messageType = 'success';
        $this->message = 'El producto fue eliminado correctamente.';

        $this->resetPage();
    }

    /**
     * Datos disponibles en la interfaz.
     */
    public function with(): array
    {
        $search = trim($this->search);

        $summary = Product::query()
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products')
            ->selectRaw('SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as low_stock_products')
            ->selectRaw('COALESCE(SUM((price - cost_price) * stock), 0) as potential_profit')
            ->first();

        return [
            'products' => Product::query()
                // NUEVO: carga también la relación con la marca.
                ->with([
                    'category',
                    'brand',
                ])
                ->when(
                    $search !== '',
                    function (Builder $query) use ($search): void {
                        $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%")
                                ->orWhereHas(
                                    'category',
                                    function (Builder $categoryQuery) use ($search): void {
                                        $categoryQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        );
                                    }
                                )
                                ->orWhereHas(
                                    'brand',
                                    function (Builder $brandQuery) use ($search): void {
                                        $brandQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        );
                                    }
                                );
                        });
                    }
                )
                ->when(
                    $this->categoryFilter !== '',
                    fn (Builder $query) => $query->where(
                        'category_id',
                        (int) $this->categoryFilter
                    )
                )
                // NUEVO: aplica el filtro por marca.
                ->when(
                    $this->brandFilter !== '',
                    fn (Builder $query) => $query->where(
                        'brand_id',
                        (int) $this->brandFilter
                    )
                )
                ->when(
                    $this->statusFilter === 'active',
                    fn (Builder $query) => $query->where(
                        'is_active',
                        true
                    )
                )
                ->when(
                    $this->statusFilter === 'inactive',
                    fn (Builder $query) => $query->where(
                        'is_active',
                        false
                    )
                )
                ->latest('id')
                ->paginate((int) $this->perPage),

            'categories' => Category::query()
                ->orderBy('name')
                ->get(),

            // NUEVO: marcas disponibles para el filtro.
            'brands' => Brand::query()
                ->orderByDesc('is_active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'totalProducts' => (int) ($summary->total_products ?? 0),

            'activeProducts' => (int) ($summary->active_products ?? 0),

            'lowStockProducts' => (int) ($summary->low_stock_products ?? 0),

            'potentialProfit' => (float) ($summary->potential_profit ?? 0),
        ];
    }
};

?>

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Productos
            </flux:heading>

            <flux:text class="mt-1">
                Administra precios, marcas, descuentos, márgenes, stock e información del catálogo.
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            :href="route('products.create')"
            wire:navigate
        >
            Nuevo producto
        </flux:button>

    </div>

    {{-- Mensaje recibido desde creación o edición --}}
    @if (session('success'))
        <div
            role="alert"
            class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
        >
            {{ session('success') }}
        </div>
    @endif

    {{-- Mensajes generados en el listado --}}
    @if ($message)
        <div
            role="alert"
            @class([
                'rounded-xl border px-4 py-3 text-sm font-medium',
                'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200' => $messageType === 'success',
                'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200' => $messageType === 'error',
            ])
        >
            {{ $message }}
        </div>
    @endif

    {{-- Indicadores --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total de productos
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $totalProducts }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Productos activos
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $activeProducts }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Stock bajo o agotado
            </p>

            <p
                @class([
                    'mt-2 text-3xl font-semibold',
                    'text-red-600 dark:text-red-400' => $lowStockProducts > 0,
                    'text-zinc-900 dark:text-white' => $lowStockProducts === 0,
                ])
            >
                {{ $lowStockProducts }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Ganancia potencial
            </p>

            <p
                @class([
                    'mt-2 text-3xl font-semibold',
                    'text-green-600 dark:text-green-400' => $potentialProfit >= 0,
                    'text-red-600 dark:text-red-400' => $potentialProfit < 0,
                ])
            >
                S/ {{ number_format($potentialProfit, 2) }}
            </p>

            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                Según precio, costo y stock actual.
            </p>
        </div>

    </div>

    {{-- Contenedor principal --}}
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        {{-- Filtros --}}
        <div class="grid gap-4 border-b border-zinc-200 p-4 md:grid-cols-2 xl:grid-cols-6 dark:border-zinc-700">

            <div class="md:col-span-2">
                <flux:input
                    label="Buscar productos"
                    placeholder="Nombre, SKU, categoría o marca..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.500ms="search"
                />
            </div>

            <div>
                <label
                    for="product-category-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Categoría
                </label>

                <select
                    id="product-category-filter"
                    wire:model.live="categoryFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                >
                    <option value="">
                        Todas las categorías
                    </option>

                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- NUEVO: filtro por marca. --}}
            <div>
                <label
                    for="product-brand-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Marca
                </label>

                <select
                    id="product-brand-filter"
                    wire:model.live="brandFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                >
                    <option value="">
                        Todas las marcas
                    </option>

                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">
                            {{ $brand->name }}

                            @if (! $brand->is_active)
                                (Inactiva)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="product-status-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Estado
                </label>

                <select
                    id="product-status-filter"
                    wire:model.live="statusFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                >
                    <option value="">
                        Todos los estados
                    </option>

                    <option value="active">
                        Activos
                    </option>

                    <option value="inactive">
                        Inactivos
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="products-per-page"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Registros
                </label>

                <select
                    id="products-per-page"
                    wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                >
                    <option value="10">
                        10 registros
                    </option>

                    <option value="25">
                        25 registros
                    </option>

                    <option value="50">
                        50 registros
                    </option>
                </select>
            </div>

            @if (
                $search !== ''
                || $categoryFilter !== ''
                || $brandFilter !== ''
                || $statusFilter !== ''
            )
                <div class="md:col-span-2 xl:col-span-6">
                    <flux:button
                        size="sm"
                        icon="x-mark"
                        wire:click="clearFilters"
                    >
                        Limpiar filtros
                    </flux:button>
                </div>
            @endif

        </div>

        {{-- Vista móvil --}}
        <div class="divide-y divide-zinc-200 lg:hidden dark:divide-zinc-700">

            @forelse ($products as $product)

                @php
                    $price = (float) $product->price;
                    $costPrice = (float) $product->cost_price;
                    $unitProfit = $price - $costPrice;

                    $marginPercentage = $price > 0
                        ? ($unitProfit / $price) * 100
                        : 0;
                @endphp

                <article
                    wire:key="product-mobile-{{ $product->id }}"
                    class="space-y-5 p-4"
                >
                    <div class="flex items-start gap-4">

                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">

                            @if ($product->image)
                                <img
                                    src="{{ asset('storage/' . $product->image) }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center px-2 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                    Sin imagen
                                </div>
                            @endif

                        </div>

                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-center gap-2">

                                <h3 class="font-semibold text-zinc-900 dark:text-white">
                                    {{ $product->name }}
                                </h3>

                                @if ($product->is_featured)
                                    <flux:badge color="amber">
                                        Destacado
                                    </flux:badge>
                                @endif

                            </div>

                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                SKU: {{ $product->sku }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">

                                <flux:badge>
                                    {{ $product->category?->name ?? 'Sin categoría' }}
                                </flux:badge>

                                {{-- NUEVO: muestra la marca. --}}
                                <flux:badge color="blue">
                                    {{ $product->brand?->name ?? 'Sin marca' }}
                                </flux:badge>

                            </div>

                        </div>

                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Precio
                            </p>

                            <p class="mt-1 font-semibold text-zinc-900 dark:text-white">
                                S/ {{ number_format($price, 2) }}
                            </p>

                            @if ($product->has_discount)

                                <div class="mt-1 flex flex-wrap items-center gap-2">

                                    <span class="text-sm text-zinc-500 line-through">
                                        S/ {{ number_format((float) $product->compare_at_price, 2) }}
                                    </span>

                                    <flux:badge color="green">
                                        -{{ $product->discount_percentage }}%
                                    </flux:badge>

                                </div>

                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Stock
                            </p>

                            <p
                                @class([
                                    'mt-1 font-semibold',
                                    'text-red-600 dark:text-red-400' => $product->stock <= 5,
                                    'text-zinc-900 dark:text-white' => $product->stock > 5,
                                ])
                            >
                                {{ $product->stock }} unidades
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Ganancia unitaria
                            </p>

                            <p
                                @class([
                                    'mt-1 font-semibold',
                                    'text-green-600 dark:text-green-400' => $unitProfit >= 0,
                                    'text-red-600 dark:text-red-400' => $unitProfit < 0,
                                ])
                            >
                                S/ {{ number_format($unitProfit, 2) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Margen
                            </p>

                            <p
                                @class([
                                    'mt-1 font-semibold',
                                    'text-green-600 dark:text-green-400' => $marginPercentage >= 0,
                                    'text-red-600 dark:text-red-400' => $marginPercentage < 0,
                                ])
                            >
                                {{ number_format($marginPercentage, 1) }}%
                            </p>
                        </div>

                    </div>

                    <div class="flex flex-wrap items-center gap-2">

                        @if ($product->is_active)
                            <flux:badge color="green">
                                Activo
                            </flux:badge>
                        @else
                            <flux:badge color="red">
                                Inactivo
                            </flux:badge>
                        @endif

                        @if ($product->stock === 0)
                            <flux:badge color="red">
                                Agotado
                            </flux:badge>
                        @elseif ($product->stock <= 5)
                            <flux:badge color="amber">
                                Stock bajo
                            </flux:badge>
                        @endif

                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">

                        <flux:button
                            size="sm"
                            icon="pencil-square"
                            :href="route('products.edit', $product)"
                            wire:navigate
                        >
                            Editar
                        </flux:button>

                        <flux:button
                            size="sm"
                            icon="power"
                            wire:click="toggleStatus({{ $product->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStatus({{ $product->id }})"
                        >
                            {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                        </flux:button>

                        <flux:button
                            size="sm"
                            icon="star"
                            wire:click="toggleFeatured({{ $product->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleFeatured({{ $product->id }})"
                        >
                            {{ $product->is_featured ? 'Quitar destacado' : 'Destacar' }}
                        </flux:button>

                        <flux:button
                            size="sm"
                            variant="danger"
                            icon="trash"
                            wire:click="delete({{ $product->id }})"
                            wire:confirm="¿Seguro que deseas eliminar este producto?"
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $product->id }})"
                        >
                            Eliminar
                        </flux:button>

                    </div>

                </article>

            @empty

                <div class="px-6 py-14 text-center">

                    <p class="font-medium text-zinc-700 dark:text-zinc-200">
                        No se encontraron productos.
                    </p>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea un producto o modifica los filtros.
                    </p>

                    <flux:button
                        class="mt-5"
                        variant="primary"
                        icon="plus"
                        :href="route('products.create')"
                        wire:navigate
                    >
                        Crear producto
                    </flux:button>

                </div>

            @endforelse

        </div>

        {{-- Vista de escritorio --}}
        <div class="hidden overflow-x-auto lg:block">

            <table class="w-full min-w-350 text-sm">

                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/70">

                    <tr>

                        <th class="px-5 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Producto
                        </th>

                        <th class="px-5 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Categoría
                        </th>

                        {{-- NUEVO: columna de marca. --}}
                        <th class="px-5 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Marca
                        </th>

                        <th class="px-5 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Precios
                        </th>

                        <th class="px-5 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Ganancia
                        </th>

                        <th class="px-5 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Inventario
                        </th>

                        <th class="px-5 py-4 text-right font-semibold text-zinc-700 dark:text-zinc-200">
                            Acciones
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse ($products as $product)

                        @php
                            $price = (float) $product->price;
                            $costPrice = (float) $product->cost_price;
                            $unitProfit = $price - $costPrice;

                            $marginPercentage = $price > 0
                                ? ($unitProfit / $price) * 100
                                : 0;
                        @endphp

                        <tr
                            wire:key="product-desktop-{{ $product->id }}"
                            class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                        >
                            <td class="px-5 py-4">

                                <div class="flex min-w-0 items-center gap-4">

                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">

                                        @if ($product->image)
                                            <img
                                                src="{{ asset('storage/' . $product->image) }}"
                                                alt="{{ $product->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center px-1 text-center text-[10px] text-zinc-500 dark:text-zinc-400">
                                                Sin imagen
                                            </div>
                                        @endif

                                    </div>

                                    <div class="min-w-0">

                                        <div class="flex flex-wrap items-center gap-2">

                                            <p class="max-w-64 truncate font-semibold text-zinc-900 dark:text-white">
                                                {{ $product->name }}
                                            </p>

                                            @if ($product->is_featured)
                                                <flux:badge color="amber">
                                                    Destacado
                                                </flux:badge>
                                            @endif

                                        </div>

                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            SKU: {{ $product->sku }}
                                        </p>

                                        <p class="mt-1 max-w-64 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $product->slug }}
                                        </p>

                                    </div>

                                </div>

                            </td>

                            <td class="px-5 py-4 text-zinc-700 dark:text-zinc-200">
                                {{ $product->category?->name ?? 'Sin categoría' }}
                            </td>

                            {{-- NUEVO: muestra la marca del producto. --}}
                            <td class="px-5 py-4">

                                @if ($product->brand)
                                    <div class="flex items-center gap-3">

                                        @if ($product->brand->logo)
                                            <img
                                                src="{{ asset('storage/' . $product->brand->logo) }}"
                                                alt="{{ $product->brand->name }}"
                                                class="h-8 w-8 rounded-lg border border-zinc-200 object-contain p-1 dark:border-zinc-700"
                                                loading="lazy"
                                            >
                                        @endif

                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-white">
                                                {{ $product->brand->name }}
                                            </p>

                                            @if (! $product->brand->is_active)
                                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                                    Marca inactiva
                                                </p>
                                            @endif
                                        </div>

                                    </div>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">
                                        Sin marca
                                    </span>
                                @endif

                            </td>

                            <td class="px-5 py-4">

                                <p class="font-semibold text-zinc-900 dark:text-white">
                                    S/ {{ number_format($price, 2) }}
                                </p>

                                @if ($product->has_discount)

                                    <div class="mt-1 flex flex-wrap items-center gap-2">

                                        <span class="text-sm text-zinc-500 line-through">
                                            S/ {{ number_format((float) $product->compare_at_price, 2) }}
                                        </span>

                                        <flux:badge color="green">
                                            -{{ $product->discount_percentage }}%
                                        </flux:badge>

                                    </div>

                                @endif

                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Costo: S/ {{ number_format($costPrice, 2) }}
                                </p>

                            </td>

                            <td class="px-5 py-4">

                                <p
                                    @class([
                                        'font-semibold',
                                        'text-green-600 dark:text-green-400' => $unitProfit >= 0,
                                        'text-red-600 dark:text-red-400' => $unitProfit < 0,
                                    ])
                                >
                                    S/ {{ number_format($unitProfit, 2) }}
                                </p>

                                <p
                                    @class([
                                        'mt-1 text-xs font-medium',
                                        'text-green-600 dark:text-green-400' => $marginPercentage >= 0,
                                        'text-red-600 dark:text-red-400' => $marginPercentage < 0,
                                    ])
                                >
                                    Margen: {{ number_format($marginPercentage, 1) }}%
                                </p>

                            </td>

                            <td class="px-5 py-4">

                                <p
                                    @class([
                                        'font-semibold',
                                        'text-red-600 dark:text-red-400' => $product->stock <= 5,
                                        'text-zinc-900 dark:text-white' => $product->stock > 5,
                                    ])
                                >
                                    {{ $product->stock }} unidades
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">

                                    @if ($product->is_active)
                                        <flux:badge color="green">
                                            Activo
                                        </flux:badge>
                                    @else
                                        <flux:badge color="red">
                                            Inactivo
                                        </flux:badge>
                                    @endif

                                    @if ($product->stock === 0)
                                        <flux:badge color="red">
                                            Agotado
                                        </flux:badge>
                                    @elseif ($product->stock <= 5)
                                        <flux:badge color="amber">
                                            Stock bajo
                                        </flux:badge>
                                    @endif

                                </div>

                            </td>

                            <td class="px-5 py-4">

                                <div class="flex flex-wrap justify-end gap-2">

                                    <flux:button
                                        size="sm"
                                        icon="pencil-square"
                                        :href="route('products.edit', $product)"
                                        wire:navigate
                                    >
                                        Editar
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        icon="power"
                                        wire:click="toggleStatus({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleStatus({{ $product->id }})"
                                    >
                                        {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        icon="star"
                                        wire:click="toggleFeatured({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleFeatured({{ $product->id }})"
                                    >
                                        {{ $product->is_featured ? 'Quitar destacado' : 'Destacar' }}
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="delete({{ $product->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar este producto?"
                                        wire:loading.attr="disabled"
                                        wire:target="delete({{ $product->id }})"
                                    >
                                        Eliminar
                                    </flux:button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="7"
                                class="px-6 py-14 text-center"
                            >
                                <p class="font-medium text-zinc-700 dark:text-zinc-200">
                                    No se encontraron productos.
                                </p>

                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    Crea un producto o modifica los filtros.
                                </p>

                                <flux:button
                                    class="mt-5"
                                    variant="primary"
                                    icon="plus"
                                    :href="route('products.create')"
                                    wire:navigate
                                >
                                    Crear producto
                                </flux:button>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- Paginación --}}
    @if ($products->hasPages())
        <div>
            {{ $products->links() }}
        </div>
    @endif

</div>