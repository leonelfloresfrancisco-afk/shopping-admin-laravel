<?php

use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::store', ['title' => 'Detalle del producto'])]
class extends Component
{
    public int $productId;

    /**
     * Recibe el producto mediante el slug de la ruta pública.
     */
    public function mount(Product $product): void
    {
        abort_unless(
            (bool) $product->is_active,
            404
        );

        $this->productId = $product->id;
    }

    /**
     * Normaliza una URL de imagen para evitar rutas como:
     * /storage/https://res.cloudinary.com/...
     */
    private function normalizeImageUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (Str::startsWith($url, ['/storage/http://', '/storage/https://'])) {
            return Str::after($url, '/storage/');
        }

        $absoluteStoragePrefix = rtrim(url('/storage'), '/') . '/';

        if (
            Str::startsWith(
                $url,
                [
                    $absoluteStoragePrefix . 'http://',
                    $absoluteStoragePrefix . 'https://',
                ]
            )
        ) {
            return Str::after($url, $absoluteStoragePrefix);
        }

        return $url;
    }

    /**
     * Obtiene el producto, su galería y contenido relacionado.
     */
    public function with(): array
    {
        $product = Product::query()
            ->with([
                'category',
                'brand',
                'activeImages',
            ])
            ->findOrFail(
                $this->productId
            );

        abort_unless(
            (bool) $product->is_active,
            404
        );

        $company = CompanySetting::current();

        /*
        |--------------------------------------------------------------------------
        | Galería pública
        |--------------------------------------------------------------------------
        |
        | La imagen almacenada en products.image se mantiene como principal.
        | Después se agregan únicamente las fotografías adicionales activas.
        |
        */

        $galleryImages = collect();

        if ($product->image) {
            $galleryImages->push([
                'id' => 'main-' . $product->id,

                'url' => $this->normalizeImageUrl($product->image_url),

                'alt' => $product->name,

                'label' => 'Vista principal',
            ]);
        }

        foreach ($product->activeImages as $index => $productImage) {
            $galleryImages->push([
                'id' => 'gallery-' . $productImage->id,

                'url' => $this->normalizeImageUrl($productImage->image_url),

                'alt' => $productImage->alt_text
                    ?: $product->name
                        . ' - Vista '
                        . ($index + 2),

                'label' => 'Vista ' . ($index + 2),
            ]);
        }

        $galleryImages = $galleryImages
            ->filter(
                fn (array $image): bool =>
                    filled($image['url'] ?? null)
            )
            ->unique('url')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Productos relacionados
        |--------------------------------------------------------------------------
        */

        $relatedProducts = Product::query()
            ->with([
                'category',
                'brand',
            ])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereKeyNot($product->id)
            ->when(
                $product->category_id !== null,
                fn (Builder $query) => $query->where(
                    'category_id',
                    $product->category_id
                )
            )
            ->orderByDesc('is_featured')
            ->latest('id')
            ->limit(4)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Promociones aplicables
        |--------------------------------------------------------------------------
        */

        $promotions = Promotion::query()
            ->currentlyActive()
            ->where(function (Builder $query) use ($product): void {
                $query
                    ->where('applies_to', 'all')
                    ->orWhere(function (
                        Builder $categoryPromotion
                    ) use ($product): void {
                        $categoryPromotion
                            ->where(
                                'applies_to',
                                'categories'
                            )
                            ->whereHas(
                                'categories',
                                fn (Builder $categoryQuery) =>
                                    $categoryQuery->whereKey(
                                        $product->category_id
                                    )
                            );
                    })
                    ->orWhere(function (
                        Builder $productPromotion
                    ) use ($product): void {
                        $productPromotion
                            ->where(
                                'applies_to',
                                'products'
                            )
                            ->whereHas(
                                'products',
                                fn (Builder $productQuery) =>
                                    $productQuery->whereKey(
                                        $product->id
                                    )
                            );
                    });
            })
            ->latest('id')
            ->limit(3)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Moneda y WhatsApp
        |--------------------------------------------------------------------------
        */

        $currencySymbol = match ($company->currency_code) {
            'USD' => '$',
            'EUR' => '€',
            default => 'S/',
        };

        $whatsappNumber = preg_replace(
            '/\D+/',
            '',
            $company->whatsapp ?? ''
        );

        $message = rawurlencode(
            'Hola, deseo información sobre el producto: '
            . $product->name
            . ' | SKU: '
            . $product->sku
            . ' | Precio: '
            . $currencySymbol
            . ' '
            . number_format(
                (float) $product->price,
                2
            )
        );

        $whatsappUrl = $whatsappNumber !== ''
            ? 'https://wa.me/'
                . $whatsappNumber
                . '?text='
                . $message
            : route('home') . '#contacto';

        return [
            'product' => $product,

            'company' => $company,

            'galleryImages' => $galleryImages->all(),

            'relatedProducts' => $relatedProducts,

            'promotions' => $promotions,

            'currencySymbol' => $currencySymbol,

            'whatsappUrl' => $whatsappUrl,

            'hasWhatsapp' => $whatsappNumber !== '',
        ];
    }
};

?>

<div class="bg-zinc-50">

    {{-- Navegación secundaria --}}
    <section class="border-b border-zinc-200 bg-white">

        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-4 text-sm text-zinc-500 sm:px-6 lg:px-8">

            <a
                href="{{ route('home') }}"
                class="transition hover:text-emerald-700"
            >
                Inicio
            </a>

            <span aria-hidden="true">
                /
            </span>

            <a
                href="{{ route('home', [
                    'categoria' => $product->category_id,
                ]) }}#productos"
                class="transition hover:text-emerald-700"
            >
                {{ $product->category?->name ?? 'Productos' }}
            </a>

            <span aria-hidden="true">
                /
            </span>

            <span class="max-w-full truncate font-medium text-zinc-900">
                {{ $product->name }}
            </span>

        </div>

    </section>

    {{-- Producto --}}
    <section class="py-8 sm:py-12 lg:py-16">

        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.08fr_.92fr] lg:gap-12 lg:px-8">

            {{-- Galería --}}
            <div
                x-data="{
                    images: @js($galleryImages),
                    active: 0,
                    viewerOpen: false,
                    hoverZoom: false,
                    zoomX: 50,
                    zoomY: 50,

                    selectImage(index) {
                        this.active = index;
                        this.hoverZoom = false;
                    },

                    nextImage() {
                        if (this.images.length <= 1) {
                            return;
                        }

                        this.active =
                            (this.active + 1)
                            % this.images.length;

                        this.hoverZoom = false;
                    },

                    previousImage() {
                        if (this.images.length <= 1) {
                            return;
                        }

                        this.active =
                            this.active === 0
                                ? this.images.length - 1
                                : this.active - 1;

                        this.hoverZoom = false;
                    },

                    updateZoom(event) {
                        if (
                            ! window.matchMedia(
                                '(hover: hover)'
                            ).matches
                        ) {
                            return;
                        }

                        const rect =
                            event.currentTarget
                                .getBoundingClientRect();

                        this.zoomX =
                            (
                                (
                                    event.clientX
                                    - rect.left
                                )
                                / rect.width
                            ) * 100;

                        this.zoomY =
                            (
                                (
                                    event.clientY
                                    - rect.top
                                )
                                / rect.height
                            ) * 100;
                    }
                }"
                x-effect="document.documentElement.style.overflow = viewerOpen ? 'hidden' : ''"
                x-on:keydown.escape.window="viewerOpen = false"
                x-on:keydown.arrow-right.window="viewerOpen && nextImage()"
                x-on:keydown.arrow-left.window="viewerOpen && previousImage()"
                class="min-w-0"
            >

                {{-- Imagen seleccionada --}}
                <div class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm">

                    <div
                        class="relative aspect-square overflow-hidden bg-zinc-100"
                        x-on:mousemove="updateZoom($event)"
                        x-on:mouseenter="hoverZoom = window.matchMedia('(hover: hover)').matches && images.length > 0"
                        x-on:mouseleave="hoverZoom = false"
                    >

                        <template x-if="images.length > 0">

                            <button
                                type="button"
                                x-on:click="viewerOpen = true"
                                class="group block h-full w-full cursor-zoom-in overflow-hidden"
                                aria-label="Ampliar fotografía del producto"
                            >

                                <img
                                    x-bind:src="images[active]?.url || ''"
                                    x-bind:alt="images[active]?.alt || 'Imagen del producto'"
                                    x-bind:style="{
                                        transformOrigin: `${zoomX}% ${zoomY}%`,
                                        transform: `scale(${hoverZoom ? 2.25 : 1})`
                                    }"
                                    class="h-full w-full object-contain p-5 transition-transform duration-200 ease-out sm:p-8"
                                >

                            </button>

                        </template>

                        <template x-if="images.length === 0">

                            <div class="flex h-full w-full flex-col items-center justify-center gap-4 p-8 text-center">

                                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white text-zinc-400 shadow-sm">

                                    <svg
                                        class="h-9 w-9"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M4 5h16v14H4V5Zm0 10 4-4 4 4 2-2 6 6"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />

                                        <circle
                                            cx="15.5"
                                            cy="9"
                                            r="1.5"
                                            stroke="currentColor"
                                            stroke-width="1.6"
                                        />
                                    </svg>

                                </div>

                                <div>
                                    <p class="font-semibold text-zinc-700">
                                        Producto sin imágenes
                                    </p>

                                    <p class="mt-1 text-sm text-zinc-500">
                                        Comunícate con nosotros para recibir más información.
                                    </p>
                                </div>

                            </div>

                        </template>

                        {{-- Etiquetas --}}
                        <div class="pointer-events-none absolute left-4 top-4 flex flex-col gap-2 sm:left-5 sm:top-5">

                            @if ($product->is_featured)
                                <span class="rounded-full bg-zinc-950 px-4 py-2 text-xs font-bold text-white shadow-lg">
                                    Producto destacado
                                </span>
                            @endif

                            @if ($product->has_discount)
                                <span class="rounded-full bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-lg">
                                    -{{ $product->discount_percentage }}%
                                </span>
                            @endif

                        </div>

                        {{-- Indicador de zoom --}}
                        <template x-if="images.length > 0">

                            <div class="pointer-events-none absolute bottom-4 right-4">

                                <div class="hidden items-center gap-2 rounded-full bg-white/95 px-4 py-2 text-xs font-semibold text-zinc-700 shadow-lg backdrop-blur md:flex">

                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <circle
                                            cx="11"
                                            cy="11"
                                            r="7"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        />

                                        <path
                                            d="M11 8v6M8 11h6m2.5 5.5 4 4"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                        />
                                    </svg>

                                    Mueve el cursor para ampliar

                                </div>

                                <div class="rounded-full bg-white/95 px-4 py-2 text-xs font-semibold text-zinc-700 shadow-lg backdrop-blur md:hidden">
                                    Toca para ampliar
                                </div>

                            </div>

                        </template>

                    </div>

                    {{-- Información inferior --}}
                    <template x-if="images.length > 0">

                        <div class="flex items-center justify-between gap-4 border-t border-zinc-200 px-4 py-3 sm:px-5">

                            <div class="min-w-0">

                                <p
                                    x-text="images[active]?.label || 'Vista principal'"
                                    class="truncate text-sm font-semibold text-zinc-900"
                                ></p>

                                <p class="text-xs text-zinc-500">
                                    Fotografía
                                    <span x-text="active + 1"></span>
                                    de
                                    <span x-text="images.length"></span>
                                </p>

                            </div>

                            <button
                                type="button"
                                x-on:click="viewerOpen = true"
                                class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-zinc-300 bg-white px-4 text-sm font-semibold text-zinc-700 transition hover:border-emerald-600 hover:text-emerald-700"
                            >
                                Ver en grande
                            </button>

                        </div>

                    </template>

                </div>

                {{-- Miniaturas --}}
                <div
                    x-show="images.length > 1"
                    x-cloak
                    class="mt-4"
                >

                    <div class="flex gap-3 overflow-x-auto pb-2">

                        <template
                            x-for="(image, index) in images"
                            x-bind:key="image.id"
                        >

                            <button
                                type="button"
                                x-on:click="selectImage(index)"
                                x-bind:class="
                                    active === index
                                        ? 'border-emerald-600 ring-2 ring-emerald-100'
                                        : 'border-zinc-200 hover:border-zinc-400'
                                "
                                class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border-2 bg-white p-1 transition sm:h-24 sm:w-24"
                                x-bind:aria-label="
                                    'Mostrar '
                                    + image.label
                                "
                            >

                                <img
                                    x-bind:src="image.url"
                                    x-bind:alt="image.alt"
                                    class="h-full w-full rounded-xl object-contain"
                                >

                            </button>

                        </template>

                    </div>

                </div>

                {{-- Navegación rápida --}}
                <div
                    x-show="images.length > 1"
                    x-cloak
                    class="mt-3 flex justify-end gap-2"
                >

                    <button
                        type="button"
                        x-on:click="previousImage"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-300 bg-white text-zinc-700 transition hover:border-emerald-600 hover:text-emerald-700"
                        aria-label="Fotografía anterior"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="m15 18-6-6 6-6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>

                    <button
                        type="button"
                        x-on:click="nextImage"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-300 bg-white text-zinc-700 transition hover:border-emerald-600 hover:text-emerald-700"
                        aria-label="Siguiente fotografía"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="m9 18 6-6-6-6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>

                </div>

                {{-- Visor ampliado --}}
                <div
                    x-cloak
                    x-show="viewerOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-zinc-950/95 p-3 backdrop-blur-sm sm:p-6"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Galería ampliada del producto"
                    x-on:click.self="viewerOpen = false"
                >

                    {{-- Cerrar --}}
                    <button
                        type="button"
                        x-on:click="viewerOpen = false"
                        class="absolute right-4 top-4 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white text-zinc-950 shadow-xl transition hover:scale-105 sm:right-6 sm:top-6"
                        aria-label="Cerrar visor"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="m6 6 12 12M18 6 6 18"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>

                    {{-- Imagen ampliada --}}
                    <div class="flex h-full w-full max-w-7xl flex-col items-center justify-center">

                        <div class="relative flex min-h-0 w-full flex-1 items-center justify-center">

                            <template x-if="images.length > 0">

                                <img
                                    x-bind:src="images[active]?.url || ''"
                                    x-bind:alt="images[active]?.alt || 'Imagen del producto'"
                                    class="max-h-full max-w-full select-none object-contain"
                                >

                            </template>

                            {{-- Flecha anterior --}}
                            <button
                                x-show="images.length > 1"
                                type="button"
                                x-on:click.stop="previousImage"
                                class="absolute left-1 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-zinc-950 shadow-xl transition hover:scale-105 sm:left-5"
                                aria-label="Fotografía anterior"
                            >
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <path
                                        d="m15 18-6-6 6-6"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </button>

                            {{-- Flecha siguiente --}}
                            <button
                                x-show="images.length > 1"
                                type="button"
                                x-on:click.stop="nextImage"
                                class="absolute right-1 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-zinc-950 shadow-xl transition hover:scale-105 sm:right-5"
                                aria-label="Siguiente fotografía"
                            >
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <path
                                        d="m9 18 6-6-6-6"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </button>

                        </div>

                        {{-- Pie del visor --}}
                        <div class="mt-4 flex w-full max-w-3xl items-center justify-between gap-4 rounded-2xl bg-white/10 px-4 py-3 text-white backdrop-blur">

                            <p
                                x-text="
                                    images.length > 0
                                        ? images[active].label
                                        : ''
                                "
                                class="truncate text-sm font-semibold"
                            ></p>

                            <p class="shrink-0 text-sm text-zinc-300">
                                <span x-text="active + 1"></span>
                                /
                                <span x-text="images.length"></span>
                            </p>

                        </div>

                    </div>

                </div>

            </div>

            {{-- Información comercial --}}
            <div class="lg:sticky lg:top-28 lg:self-start">

                <div class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">

                    <div class="flex flex-wrap items-center gap-2">

                        @if ($product->brand)
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.13em] text-emerald-700">
                                {{ $product->brand->name }}
                            </span>
                        @endif

                        @if ($product->category)
                            <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-semibold text-zinc-600">
                                {{ $product->category->name }}
                            </span>
                        @endif

                    </div>

                    <h1 class="mt-5 text-3xl font-bold leading-tight tracking-tight text-zinc-950 sm:text-5xl">
                        {{ $product->name }}
                    </h1>

                    <p class="mt-3 text-sm font-medium text-zinc-500">
                        SKU: {{ $product->sku }}
                    </p>

                    <div class="mt-7 flex flex-wrap items-end gap-3">

                        <span class="text-4xl font-bold tracking-tight text-zinc-950">
                            {{ $currencySymbol }}
                            {{ number_format(
                                (float) $product->price,
                                2
                            ) }}
                        </span>

                        @if ($product->has_discount)
                            <span class="pb-1 text-lg text-zinc-400 line-through">
                                {{ $currencySymbol }}
                                {{ number_format(
                                    (float) $product->compare_at_price,
                                    2
                                ) }}
                            </span>
                        @endif

                    </div>

                    @if ($product->has_discount)
                        <p class="mt-2 text-sm font-semibold text-red-600">
                            Ahorras
                            {{ $currencySymbol }}
                            {{ number_format(
                                (float) $product->compare_at_price
                                    - (float) $product->price,
                                2
                            ) }}
                        </p>
                    @endif

                    {{-- Inventario --}}
                    <div class="mt-7 flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 p-4">

                        @if ($product->stock === 0)

                            <span class="h-3 w-3 shrink-0 rounded-full bg-red-500"></span>

                            <div>
                                <p class="font-semibold text-red-700">
                                    Producto agotado
                                </p>

                                <p class="text-xs text-zinc-500">
                                    Consulta cuándo estará disponible.
                                </p>
                            </div>

                        @elseif ($product->stock <= 5)

                            <span class="h-3 w-3 shrink-0 rounded-full bg-amber-500"></span>

                            <div>
                                <p class="font-semibold text-amber-700">
                                    Pocas unidades disponibles
                                </p>

                                <p class="text-xs text-zinc-500">
                                    Quedan {{ $product->stock }} unidades.
                                </p>
                            </div>

                        @else

                            <span class="h-3 w-3 shrink-0 rounded-full bg-emerald-500"></span>

                            <div>
                                <p class="font-semibold text-emerald-700">
                                    Producto disponible
                                </p>

                                <p class="text-xs text-zinc-500">
                                    Stock actual:
                                    {{ $product->stock }}
                                    unidades.
                                </p>
                            </div>

                        @endif

                    </div>

                    {{-- Acciones --}}
                    <div class="mt-7 grid gap-3 sm:grid-cols-2">

                        <a
                            href="{{ $whatsappUrl }}"
                            @if ($hasWhatsapp)
                                target="_blank"
                                rel="noopener noreferrer"
                            @endif
                            class="inline-flex h-13 items-center justify-center gap-2 rounded-2xl bg-emerald-700 px-6 text-center font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-800"
                        >
                            Consultar por WhatsApp
                        </a>

                        <a
                            href="{{ route('home') }}#productos"
                            class="inline-flex h-13 items-center justify-center rounded-2xl border border-zinc-300 bg-white px-6 text-center font-semibold text-zinc-800 transition hover:border-emerald-600 hover:text-emerald-700"
                        >
                            Volver al catálogo
                        </a>

                    </div>

                    {{-- Datos --}}
                    <div class="mt-8 grid grid-cols-2 gap-3 border-t border-zinc-200 pt-6">

                        <div class="rounded-2xl bg-zinc-50 p-4">

                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">
                                Categoría
                            </p>

                            <p class="mt-2 font-semibold text-zinc-900">
                                {{ $product->category?->name ?? 'Sin categoría' }}
                            </p>

                        </div>

                        <div class="rounded-2xl bg-zinc-50 p-4">

                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">
                                Marca
                            </p>

                            <p class="mt-2 font-semibold text-zinc-900">
                                {{ $product->brand?->name ?? 'Sin marca' }}
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    {{-- Descripción y promociones --}}
    <section class="pb-16 sm:pb-24">

        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1.2fr_.8fr] lg:px-8">

            <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">

                <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
                    Información
                </p>

                <h2 class="mt-3 text-2xl font-bold text-zinc-950 sm:text-3xl">
                    Descripción del producto
                </h2>

                <div class="mt-6 whitespace-pre-line text-base leading-8 text-zinc-600">
                    {{ $product->description ?: 'Este producto todavía no tiene una descripción detallada. Comunícate con nosotros para recibir más información.' }}
                </div>

            </article>

            <aside class="rounded-[2rem] border border-zinc-200 bg-zinc-950 p-6 text-white shadow-sm sm:p-8">

                <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-400">
                    Beneficios disponibles
                </p>

                <h2 class="mt-3 text-2xl font-bold">
                    Promociones aplicables
                </h2>

                <div class="mt-6 grid gap-3">

                    @forelse ($promotions as $promotion)

                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">

                            <div class="flex items-start justify-between gap-3">

                                <div>
                                    <p class="font-semibold">
                                        {{ $promotion->name }}
                                    </p>

                                    @if ($promotion->code)
                                        <p class="mt-1 text-xs text-zinc-400">
                                            Código:
                                            {{ $promotion->code }}
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs text-emerald-400">
                                            Aplicación automática
                                        </p>
                                    @endif
                                </div>

                                <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-sm font-bold text-emerald-300">
                                    {{ $promotion->discount_label }}
                                </span>

                            </div>

                        </div>

                    @empty

                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">

                            <p class="text-sm text-zinc-400">
                                No existen promociones adicionales para este producto.
                            </p>

                        </div>

                    @endforelse

                </div>

            </aside>

        </div>

    </section>

    {{-- Productos relacionados --}}
    @if ($relatedProducts->isNotEmpty())

        <section class="bg-white py-16 sm:py-24">

            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <div>

                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
                        También puede interesarte
                    </p>

                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl">
                        Productos relacionados
                    </h2>

                </div>

                <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">

                    @foreach ($relatedProducts as $relatedProduct)

                        <a
                            wire:key="related-product-{{ $relatedProduct->id }}"
                            href="{{ route('store.product.show', [
                                'product' => $relatedProduct->slug,
                            ]) }}"
                            class="store-product-card group overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm hover:border-emerald-500 hover:shadow-2xl"
                        >

                            <div class="aspect-square overflow-hidden bg-zinc-100">

                                @if ($relatedProduct->image)
                                    <img
                                        src="{{ $relatedProduct->image_url }}"
                                        alt="{{ $relatedProduct->name }}"
                                        class="store-product-image h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-sm text-zinc-400">
                                        Sin imagen
                                    </div>
                                @endif

                            </div>

                            <div class="p-4 sm:p-5">

                                <p class="truncate text-xs font-bold uppercase tracking-wide text-emerald-700">
                                    {{ $relatedProduct->brand?->name ?? $relatedProduct->category?->name }}
                                </p>

                                <h3 class="mt-2 line-clamp-2 min-h-11 font-semibold text-zinc-950">
                                    {{ $relatedProduct->name }}
                                </h3>

                                <p class="mt-3 text-lg font-bold text-zinc-950">
                                    {{ $currencySymbol }}
                                    {{ number_format(
                                        (float) $relatedProduct->price,
                                        2
                                    ) }}
                                </p>

                            </div>

                        </a>

                    @endforeach

                </div>

            </div>

        </section>

    @endif

</div>