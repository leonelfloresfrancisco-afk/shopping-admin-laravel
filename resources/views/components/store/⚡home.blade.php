<?php

use App\Models\Brand;
use App\Models\CarouselSlide;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts::store', ['title' => 'Inicio'])]
class extends Component
{
    #[Url(as: 'buscar')]
    public string $search = '';

    #[Url(as: 'categoria')]
    public string $categoryFilter = '';

    public function selectCategory(int $categoryId): void
    {
        $categoryExists = Category::query()
            ->whereKey($categoryId)
            ->where('is_active', true)
            ->exists();

        if (! $categoryExists) {
            return;
        }

        $this->categoryFilter = (string) $categoryId;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
    }

    public function with(): array
    {
        $company = CompanySetting::current();

        $now = now();

        $slides = CarouselSlide::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount([
                'products' => function (Builder $query): void {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedCategory = $this->categoryFilter !== ''
            ? Category::query()
                ->whereKey((int) $this->categoryFilter)
                ->where('is_active', true)
                ->first()
            : null;

        $featuredProductsQuery = Product::query()
            ->with([
                'category',
                'brand',
            ])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->where('is_featured', true)
            ->latest('id');

        $featuredProducts = $featuredProductsQuery->get();

        if ($featuredProducts->isEmpty()) {
            $featuredProducts = Product::query()
                ->with([
                    'category',
                    'brand',
                ])
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->latest('id')
                ->get();
        }

        $search = trim($this->search);

        $catalogProducts = Product::query()
            ->with([
                'category',
                'brand',
            ])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $subQuery) use ($search): void {
                        $subQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
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
            ->orderByDesc('is_featured')
            ->latest('id')
            ->get();

        $promotions = Promotion::query()
            ->currentlyActive()
            ->withCount([
                'categories',
                'products',
            ])
            ->latest('id')
            ->limit(4)
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->withCount([
                'products' => function (Builder $query): void {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get();

        $heroProduct = Product::query()
            ->with([
                'category',
                'brand',
            ])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereNotNull('image')
            ->orderByDesc('is_featured')
            ->latest('id')
            ->first();

        $currencySymbol = match ($company->currency_code) {
            'USD' => '$',
            'EUR' => 'â‚¬',
            default => 'S/',
        };

        return [
            'company' => $company,

            'slides' => $slides,

            'categories' => $categories,

            'selectedCategory' => $selectedCategory,

            'featuredProducts' => $featuredProducts,

            'catalogProducts' => $catalogProducts,

            'promotions' => $promotions,

            'brands' => $brands,

            'heroProduct' => $heroProduct,

            'currencySymbol' => $currencySymbol,

            'activeProductsCount' => Product::query()
                ->where('is_active', true)
                ->count(),

            'activeCategoriesCount' => Category::query()
                ->where('is_active', true)
                ->count(),

            'activeBrandsCount' => Brand::query()
                ->where('is_active', true)
                ->count(),

            'activePromotionsCount' => Promotion::query()
                ->currentlyActive()
                ->count(),
        ];
    }
};

?>

<div>

    @if (! $company->store_enabled)

        <section
            class="flex min-h-[75vh] items-center bg-zinc-950 px-4 text-white"
        >
            <div class="mx-auto w-full max-w-3xl py-20 text-center">
                <p
                    class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-400"
                >
                    {{ $company->trade_name }}
                </p>

                <h1
                    class="mt-5 text-4xl font-bold tracking-tight sm:text-6xl"
                >
                    Estamos realizando mejoras
                </h1>

                <p
                    class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-300"
                >
                    {{ $company->maintenance_message ?: 'Nuestra tienda estarÃ¡ disponible nuevamente muy pronto.' }}
                </p>
            </div>
        </section>

    @else

        {{-- Hero --}}
        <section
            id="inicio"
            class="relative overflow-hidden bg-zinc-950"
        >
            @if ($slides->isNotEmpty())

                <div
                    x-data="{
                        active: 0,
                        total: {{ $slides->count() }}
                    }"
                    x-init="
                        if (total > 1) {
                            setInterval(() => {
                                active = (active + 1) % total
                            }, 6500)
                        }
                    "
                    class="relative min-h-144 lg:min-h-168"
                >
                    @foreach ($slides as $slide)
                        <article
                            x-cloak
                            x-show="active === {{ $loop->index }}"
                            x-transition:enter="transition duration-700"
                            x-transition:enter-start="opacity-0 scale-[1.02]"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition duration-500"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute inset-0"
                        >
                            <picture>
                                @if ($slide->mobile_image)
                                    <source
                                        media="(max-width: 767px)"
                                        srcset="{{ $slide->mobile_image_url }}"
                                    >
                                @endif

                                <img
                                    src="{{ $slide->desktop_image_url }}"
                                    alt="{{ $slide->title }}"
                                    class="absolute inset-0 h-full w-full object-cover"
                                >
                            </picture>

                            <div
                                class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/75 to-zinc-950/15"
                            ></div>

                            <div
                                class="absolute inset-0 opacity-30"
                                style="background-image: radial-gradient(circle at 75% 30%, rgba(16, 185, 129, .45), transparent 35%);"
                            ></div>

                            <div
                                class="relative mx-auto grid min-h-144 max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:min-h-168 lg:grid-cols-[1.2fr_.8fr] lg:px-8"
                            >
                                <div class="max-w-3xl">
                                    @if ($slide->subtitle)
                                        <p
                                            class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-400"
                                        >
                                            {{ $slide->subtitle }}
                                        </p>
                                    @endif

                                    <h1
                                        class="mt-5 text-4xl font-bold leading-tight tracking-tight text-white sm:text-6xl lg:text-7xl"
                                    >
                                        {{ $slide->title }}
                                    </h1>

                                    @if ($slide->description)
                                        <p
                                            class="mt-6 max-w-2xl text-base leading-8 text-zinc-200 sm:text-lg"
                                        >
                                            {{ $slide->description }}
                                        </p>
                                    @endif

                                    <div
                                        class="mt-9 flex flex-col gap-3 sm:flex-row"
                                    >
                                        @if ($slide->button_text && $slide->button_url)
                                            <a
                                                href="{{ $slide->button_url }}"
                                                @if ($slide->open_in_new_tab)
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                @endif
                                                class="inline-flex h-13 items-center justify-center gap-2 rounded-2xl bg-emerald-700 px-7 font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-800"
                                            >
                                                {{ $slide->button_text }}

                                                <span>â†’</span>
                                            </a>
                                        @endif

                                        <a
                                            href="#productos"
                                            class="inline-flex h-13 items-center justify-center rounded-2xl border border-white/45 bg-white/10 px-7 font-semibold text-white backdrop-blur transition hover:bg-white hover:text-zinc-950"
                                        >
                                            Explorar productos
                                        </a>
                                    </div>
                                </div>

                                <div
                                    class="hidden rounded-3xl border border-white/15 bg-white/10 p-6 text-white shadow-2xl backdrop-blur-xl lg:block"
                                >
                                    <p
                                        class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-300"
                                    >
                                        CatÃ¡logo actualizado
                                    </p>

                                    <div class="mt-5 grid grid-cols-2 gap-4">
                                        <div
                                            class="rounded-2xl bg-white/10 p-4"
                                        >
                                            <p class="text-3xl font-bold">
                                                {{ $activeProductsCount }}+
                                            </p>

                                            <p class="mt-1 text-xs text-zinc-300">
                                                Productos
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl bg-white/10 p-4"
                                        >
                                            <p class="text-3xl font-bold">
                                                {{ $activeBrandsCount }}+
                                            </p>

                                            <p class="mt-1 text-xs text-zinc-300">
                                                Marcas
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach

                    @if ($slides->count() > 1)
                        <button
                            type="button"
                            x-on:click="
                                active = active === 0
                                    ? total - 1
                                    : active - 1
                            "
                            class="absolute left-4 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-950 shadow-xl transition hover:bg-white sm:flex"
                            aria-label="Banner anterior"
                        >
                            <span class="text-xl">â€¹</span>
                        </button>

                        <button
                            type="button"
                            x-on:click="active = (active + 1) % total"
                            class="absolute right-4 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-950 shadow-xl transition hover:bg-white sm:flex"
                            aria-label="Siguiente banner"
                        >
                            <span class="text-xl">â€º</span>
                        </button>

                        <div
                            class="absolute bottom-7 left-1/2 z-10 flex -translate-x-1/2 gap-2"
                        >
                            @foreach ($slides as $slide)
                                <button
                                    type="button"
                                    x-on:click="active = {{ $loop->index }}"
                                    x-bind:class="
                                        active === {{ $loop->index }}
                                            ? 'w-9 bg-white'
                                            : 'w-2.5 bg-white/45'
                                    "
                                    class="h-2.5 rounded-full transition-all"
                                    aria-label="Banner {{ $loop->iteration }}"
                                ></button>
                            @endforeach
                        </div>
                    @endif
                </div>

            @else

                <div
                    class="relative mx-auto grid min-h-144 max-w-7xl items-center gap-12 overflow-hidden px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8"
                >
                    <div
                        class="absolute inset-0 opacity-40"
                        style="background-image: radial-gradient(circle at 75% 35%, rgba(16, 185, 129, .35), transparent 34%);"
                    ></div>

                    <div class="relative z-10">
                        <p
                            class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-400"
                        >
                            {{ $company->trade_name }}
                        </p>

                        <h1
                            class="mt-5 text-4xl font-bold leading-tight tracking-tight text-white sm:text-6xl lg:text-7xl"
                        >
                            Productos que elevan tu experiencia diaria.
                        </h1>

                        <p
                            class="mt-6 max-w-2xl text-lg leading-8 text-zinc-300"
                        >
                            Explora productos, marcas y promociones seleccionados para ti.
                        </p>

                        <a
                            href="#productos"
                            class="mt-9 inline-flex h-13 items-center justify-center rounded-2xl bg-emerald-700 px-7 font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-800"
                        >
                            Explorar catÃ¡logo
                        </a>
                    </div>

                    <div
                        class="relative z-10 hidden min-h-112 items-center justify-center lg:flex"
                    >
                        @if ($heroProduct?->image)
                            <a
                                href="{{ route('store.product.show', [
                                    'product' => $heroProduct->slug,
                                ]) }}"
                                class="store-image-zoom-wrapper relative block w-full max-w-lg overflow-hidden rounded-[2.25rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur"
                            >
                                <img
                                    src="{{ $heroProduct->image_url }}"
                                    alt="{{ $heroProduct->name }}"
                                    class="store-image-zoom aspect-square w-full rounded-3xl object-cover"
                                >
                            </a>
                        @endif
                    </div>
                </div>

            @endif
        </section>

        {{-- Beneficios --}}
        <section
            class="border-b border-zinc-200 bg-white"
        >
            <div
                class="mx-auto grid max-w-7xl grid-cols-2 gap-px bg-zinc-200 px-4 sm:px-6 md:grid-cols-4 lg:px-8"
            >
                <div class="bg-white px-4 py-5 text-center">
                    <p class="font-semibold text-zinc-900">
                        Stock actualizado
                    </p>

                    <p class="mt-1 text-xs text-zinc-500">
                        Disponibilidad real
                    </p>
                </div>

                <div class="bg-white px-4 py-5 text-center">
                    <p class="font-semibold text-zinc-900">
                        Marcas verificadas
                    </p>

                    <p class="mt-1 text-xs text-zinc-500">
                        Proveedores organizados
                    </p>
                </div>

                <div class="bg-white px-4 py-5 text-center">
                    <p class="font-semibold text-zinc-900">
                        Ofertas vigentes
                    </p>

                    <p class="mt-1 text-xs text-zinc-500">
                        Promociones actuales
                    </p>
                </div>

                <div class="bg-white px-4 py-5 text-center">
                    <p class="font-semibold text-zinc-900">
                        AtenciÃ³n directa
                    </p>

                    <p class="mt-1 text-xs text-zinc-500">
                        Consulta por WhatsApp
                    </p>
                </div>
            </div>
        </section>

        {{-- CategorÃ­as profesionales --}}
        <section
            id="categorias"
            class="overflow-hidden bg-white py-18 sm:py-24"
        >
            <div
                x-data="{
                    isDragging: false,
                    dragMoved: false,
                    startX: 0,
                    startScrollLeft: 0,

                    scrollCategories(distance) {
                        this.$refs.categoryTrack.scrollBy({
                            left: distance,
                            behavior: 'smooth'
                        })
                    },

                    startCategoryDrag(event) {
                        if (event.pointerType !== 'mouse') {
                            return
                        }

                        this.isDragging = true
                        this.dragMoved = false
                        this.startX = event.clientX
                        this.startScrollLeft = this.$refs.categoryTrack.scrollLeft

                        this.$refs.categoryTrack.setPointerCapture?.(
                            event.pointerId
                        )
                    },

                    moveCategoryDrag(event) {
                        if (! this.isDragging) {
                            return
                        }

                        const distance = event.clientX - this.startX

                        if (Math.abs(distance) > 6) {
                            this.dragMoved = true
                        }

                        this.$refs.categoryTrack.scrollLeft =
                            this.startScrollLeft - distance
                    },

                    endCategoryDrag(event) {
                        if (! this.isDragging) {
                            return
                        }

                        this.isDragging = false

                        if (
                            this.$refs.categoryTrack.hasPointerCapture?.(
                                event.pointerId
                            )
                        ) {
                            this.$refs.categoryTrack.releasePointerCapture(
                                event.pointerId
                            )
                        }

                        if (this.dragMoved) {
                            window.setTimeout(() => {
                                this.dragMoved = false
                            }, 140)
                        }
                    },

                    scrollCategoriesWithWheel(event) {
                        if (
                            Math.abs(event.deltaY)
                            <= Math.abs(event.deltaX)
                        ) {
                            return
                        }

                        event.preventDefault()

                        this.$refs.categoryTrack.scrollLeft += event.deltaY
                    },

                    protectCategoryClick(event) {
                        if (! this.dragMoved) {
                            return
                        }

                        event.preventDefault()
                        event.stopImmediatePropagation()
                        this.dragMoved = false
                    }
                }"
                class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >

                <div class="store-reveal flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">

                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700">
                            Colecciones
                        </p>

                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-950 sm:text-5xl">
                            Encuentra lo que necesitas
                        </h2>

                        <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-600">
                            Explora todas las categorÃ­as con el dedo, la rueda del mouse, arrastrando o usando las flechas.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="#productos"
                            class="inline-flex items-center gap-2 font-semibold text-emerald-700 transition hover:gap-3"
                        >
                            Ver catÃ¡logo completo

                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M5 12h14m-5-5 5 5-5 5"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </a>

                        @if ($categories->count() > 1)
                            <div class="hidden items-center gap-2 sm:flex">
                                <button
                                    type="button"
                                    x-on:click="scrollCategories(-360)"
                                    class="flex h-11 w-11 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-700 shadow-sm transition hover:border-emerald-600 hover:text-emerald-700"
                                    aria-label="Ver categorÃ­as anteriores"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
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
                                    x-on:click="scrollCategories(360)"
                                    class="flex h-11 w-11 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-700 shadow-sm transition hover:border-emerald-600 hover:text-emerald-700"
                                    aria-label="Ver mÃ¡s categorÃ­as"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
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
                        @endif
                    </div>

                </div>

                <div
                    x-ref="categoryTrack"
                    x-on:pointerdown="startCategoryDrag($event)"
                    x-on:pointermove="moveCategoryDrag($event)"
                    x-on:pointerup="endCategoryDrag($event)"
                    x-on:pointercancel="endCategoryDrag($event)"
                    x-on:pointerleave="endCategoryDrag($event)"
                    x-on:wheel="scrollCategoriesWithWheel($event)"
                    x-on:dragstart.prevent
                    x-bind:class="isDragging ? 'cursor-grabbing select-none' : 'cursor-grab'"
                    class="store-no-scrollbar -mx-4 mt-10 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto overscroll-x-contain px-4 pb-5 scroll-smooth touch-pan-x sm:mx-0 sm:scroll-px-0 sm:px-0 lg:gap-5"
                    aria-label="Colecciones disponibles"
                >

                    @forelse ($categories as $category)

                        @php
                            $categoryInitial = mb_strtoupper(
                                mb_substr($category->name, 0, 1)
                            );

                            $categoryDelay = match ($loop->index % 3) {
                                0 => 'store-reveal-delay-1',
                                1 => 'store-reveal-delay-2',
                                default => 'store-reveal-delay-3',
                            };
                        @endphp

                        <button
                            type="button"
                            wire:key="professional-category-{{ $category->id }}"
                            wire:click="selectCategory({{ $category->id }})"
                            x-on:click.capture="protectCategoryClick($event)"
                            x-on:click="
                                if (! dragMoved) {
                                    setTimeout(() => {
                                        document
                                            .getElementById('productos')
                                            ?.scrollIntoView({
                                                behavior: 'smooth'
                                            })
                                    }, 150)
                                }
                            "
                            class="store-reveal {{ $categoryDelay }} group relative w-[82vw] max-w-[320px] shrink-0 snap-start overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-950 text-left shadow-sm transition duration-500 hover:-translate-y-1.5 hover:border-emerald-500 hover:shadow-2xl sm:w-[300px] lg:w-[320px] xl:w-[340px]"
                            aria-label="Explorar la categorÃ­a {{ $category->name }}"
                        >
                            <div class="relative aspect-[4/3] overflow-hidden">

                                @if ($category->image)

                                    <img
                                        src="{{ $category->image_url }}"
                                        alt="{{ $category->name }}"
                                        class="pointer-events-none h-full w-full select-none object-cover transition duration-700 ease-out group-hover:scale-110"
                                        loading="lazy"
                                        draggable="false"
                                    >

                                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/35 to-transparent"></div>

                                @else

                                    <div class="absolute inset-0 bg-zinc-900">

                                        <div
                                            class="absolute inset-0 opacity-60"
                                            style="background-image:
                                                radial-gradient(circle at 78% 18%, rgba(16, 185, 129, .55), transparent 30%),
                                                radial-gradient(circle at 15% 85%, rgba(59, 130, 246, .35), transparent 30%);"
                                        ></div>

                                        <div
                                            class="absolute inset-0 opacity-[0.08]"
                                            style="background-image:
                                                linear-gradient(rgba(255,255,255,.7) 1px, transparent 1px),
                                                linear-gradient(90deg, rgba(255,255,255,.7) 1px, transparent 1px);
                                                background-size: 28px 28px;"
                                        ></div>

                                        <div class="relative flex h-full items-center justify-center">

                                            <div class="flex h-24 w-24 items-center justify-center rounded-[2rem] border border-white/15 bg-white/10 text-5xl font-bold text-white shadow-2xl backdrop-blur">
                                                {{ $categoryInitial }}
                                            </div>

                                        </div>

                                    </div>

                                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/20 to-transparent"></div>

                                @endif

                                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">

                                    <div class="flex items-end justify-between gap-4">

                                        <div class="min-w-0">

                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/65">
                                                {{ $category->products_count }}
                                                {{ $category->products_count === 1 ? 'producto' : 'productos' }}
                                            </p>

                                            <h3 class="mt-2 line-clamp-2 text-xl font-bold leading-tight text-white sm:text-2xl">
                                                {{ $category->name }}
                                            </h3>

                                            @if ($category->description)
                                                <p class="mt-2 line-clamp-2 text-sm leading-5 text-white/70">
                                                    {{ $category->description }}
                                                </p>
                                            @endif

                                        </div>

                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur transition duration-300 group-hover:translate-x-1 group-hover:bg-emerald-600">

                                            <svg
                                                class="h-5 w-5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="M5 12h14m-5-5 5 5-5 5"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>

                                        </span>

                                    </div>

                                </div>

                            </div>

                        </button>

                    @empty

                        <div class="w-full rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center">

                            <p class="font-semibold text-zinc-700">
                                TodavÃ­a no hay categorÃ­as disponibles.
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Las categorÃ­as activas aparecerÃ¡n automÃ¡ticamente en esta secciÃ³n.
                            </p>

                        </div>

                    @endforelse

                </div>

                @if ($categories->count() > 1)
                    <div class="mt-3 flex items-center justify-center gap-2 text-xs font-medium text-zinc-400">
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M7 8 3 12l4 4M17 8l4 4-4 4M3 12h18"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Desliza, arrastra, usa la rueda o las flechas para ver todas
                    </div>
                @endif

            </div>
        </section>

        {{-- Productos destacados --}}
        @if ($featuredProducts->isNotEmpty())
            <section
                id="destacados"
                class="bg-zinc-950 py-18 text-white sm:py-24"
            >
                <div
                    x-data
                    class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
                >

                    <div class="store-reveal flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">

                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-400">
                                SelecciÃ³n especial
                            </p>

                            <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                                Productos destacados
                            </h2>

                            <p class="mt-4 max-w-2xl text-zinc-400">
                                Desliza para revisar todos los productos destacados disponibles.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a
                                href="#productos"
                                class="inline-flex items-center gap-2 font-semibold text-emerald-400 transition hover:gap-3"
                            >
                                Ver catÃ¡logo

                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M5 12h14m-5-5 5 5-5 5"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </a>

                            @if ($featuredProducts->count() > 1)
                                <div class="hidden items-center gap-2 sm:flex">
                                    <button
                                        type="button"
                                        x-on:click="$refs.featuredTrack.scrollBy({ left: -340, behavior: 'smooth' })"
                                        class="flex h-11 w-11 items-center justify-center rounded-full border border-zinc-700 bg-zinc-900 text-white shadow-sm transition hover:border-emerald-500 hover:text-emerald-400"
                                        aria-label="Ver productos anteriores"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
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
                                        x-on:click="$refs.featuredTrack.scrollBy({ left: 340, behavior: 'smooth' })"
                                        class="flex h-11 w-11 items-center justify-center rounded-full border border-zinc-700 bg-zinc-900 text-white shadow-sm transition hover:border-emerald-500 hover:text-emerald-400"
                                        aria-label="Ver mÃ¡s productos"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
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
                            @endif
                        </div>

                    </div>

                    <div
                        x-ref="featuredTrack"
                        class="store-no-scrollbar -mx-4 mt-10 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto overscroll-x-contain px-4 pb-5 scroll-smooth touch-pan-x sm:mx-0 sm:scroll-px-0 sm:px-0 lg:gap-5"
                    >

                        @foreach ($featuredProducts as $product)

                            <a
                                wire:key="featured-product-{{ $product->id }}"
                                href="{{ route('store.product.show', [
                                    'product' => $product->slug,
                                ]) }}"
                                class="store-product-card group flex w-[82vw] max-w-[310px] shrink-0 snap-start flex-col overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900 shadow-xl hover:border-emerald-500 sm:w-[290px] sm:rounded-3xl lg:w-[310px]"
                            >
                                <div class="relative aspect-square overflow-hidden bg-white/[0.04]">

                                    @if ($product->image)

                                        <img
                                            src="{{ $product->image_url }}"
                                            alt="{{ $product->name }}"
                                            class="store-product-image h-full w-full object-contain p-4 sm:p-5"
                                            loading="lazy"
                                        >

                                    @else

                                        <div class="flex h-full w-full flex-col items-center justify-center gap-3 p-5 text-center text-zinc-500">

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

                                            <span class="text-xs">
                                                Sin imagen
                                            </span>

                                        </div>

                                    @endif

                                    <div class="absolute left-3 top-3 flex flex-col gap-1.5 sm:left-4 sm:top-4 sm:gap-2">

                                        @if ($product->is_featured)
                                            <span class="rounded-full bg-zinc-950/90 px-3 py-1 text-[10px] font-bold text-white shadow-lg backdrop-blur sm:text-xs">
                                                Destacado
                                            </span>
                                        @endif

                                        @if ($product->has_discount)
                                            <span class="rounded-full bg-red-600 px-3 py-1 text-[10px] font-bold text-white shadow-lg sm:text-xs">
                                                -{{ $product->discount_percentage }}%
                                            </span>
                                        @endif

                                    </div>

                                    <div class="absolute inset-x-3 bottom-3 hidden translate-y-4 opacity-0 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100 sm:block">

                                        <span class="flex h-10 items-center justify-center rounded-xl bg-white/95 text-sm font-semibold text-zinc-950 shadow-xl backdrop-blur">
                                            Ver detalles
                                        </span>

                                    </div>

                                </div>

                                <div class="flex flex-1 flex-col p-4 sm:p-5">

                                    <p class="truncate text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-400 sm:text-xs sm:tracking-[0.15em]">
                                        {{ $product->brand?->name ?? $product->category?->name ?? 'Producto' }}
                                    </p>

                                    <h3 class="mt-2 line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-white sm:min-h-12 sm:text-base sm:leading-6">
                                        {{ $product->name }}
                                    </h3>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">

                                        <span class="text-lg font-bold text-white sm:text-xl">
                                            {{ $currencySymbol }}
                                            {{ number_format((float) $product->price, 2) }}
                                        </span>

                                        @if ($product->has_discount)
                                            <span class="text-xs text-zinc-500 line-through sm:text-sm">
                                                {{ $currencySymbol }}
                                                {{ number_format((float) $product->compare_at_price, 2) }}
                                            </span>
                                        @endif

                                    </div>

                                    <div class="mt-auto flex items-center justify-between gap-2 pt-4">

                                        @if ($product->stock <= 5)
                                            <span class="truncate text-[11px] font-semibold text-amber-400 sm:text-xs">
                                                Solo {{ $product->stock }}
                                            </span>
                                        @else
                                            <span class="truncate text-[11px] font-medium text-emerald-400 sm:text-xs">
                                                Disponible
                                            </span>
                                        @endif

                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-zinc-950 transition duration-300 group-hover:bg-emerald-500 group-hover:text-white">

                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="M5 12h14m-5-5 5 5-5 5"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>

                                        </span>

                                    </div>

                                </div>

                            </a>

                        @endforeach

                    </div>

                    @if ($featuredProducts->count() > 1)
                        <div class="mt-3 flex items-center justify-center gap-2 text-xs font-medium text-zinc-400">
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M7 8 3 12l4 4M17 8l4 4-4 4M3 12h18"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            Desliza o usa las flechas para ver todos
                        </div>
                    @endif

                </div>
            </section>
        @endif

        {{-- CatÃ¡logo --}}
        <section
            id="productos"
            class="bg-zinc-50 py-18 sm:py-24"
        >
            <div
                class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div class="store-reveal">
                    <p
                        class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700"
                    >
                        CatÃ¡logo
                    </p>

                    <div
                        class="mt-3 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div>
                            <h2
                                class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-5xl"
                            >
                                @if ($selectedCategory)
                                    {{ $selectedCategory->name }}
                                @elseif ($search !== '')
                                    Resultados de bÃºsqueda
                                @else
                                    Todos los productos
                                @endif
                            </h2>

                            <p class="mt-4 max-w-2xl text-zinc-600">
                                Consulta caracterÃ­sticas, precio, marca y disponibilidad.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if ($selectedCategory || $search !== '')
                                <button
                                    type="button"
                                    wire:click="clearFilters"
                                    class="inline-flex h-11 items-center justify-center rounded-2xl border border-zinc-300 bg-white px-5 text-sm font-semibold text-zinc-700 transition hover:border-emerald-600 hover:text-emerald-700"
                                >
                                    Limpiar filtros
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Buscador y filtro --}}
                <div
                    class="store-reveal mt-9 grid gap-4 rounded-3xl border border-zinc-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_260px]"
                >
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />

                            <path
                                d="m16.5 16.5 4 4"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>

                        <input
                            type="search"
                            wire:model.live.debounce.400ms="search"
                            placeholder="Buscar producto, marca, categorÃ­a o SKU..."
                            class="h-13 w-full rounded-2xl border border-zinc-300 bg-white pl-12 pr-4 text-zinc-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
                        >
                    </div>

                    <select
                        wire:model.live="categoryFilter"
                        class="h-13 w-full rounded-2xl border border-zinc-300 bg-white px-4 text-zinc-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
                    >
                        <option value="">
                            Todas las categorÃ­as
                        </option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    wire:loading.class="opacity-50"
                    class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
                >
                    @forelse ($catalogProducts as $product)
                        <a
                            wire:key="catalog-product-{{ $product->id }}"
                            href="{{ route('store.product.show', [
                                'product' => $product->slug,
                            ]) }}"
                            class="store-product-card store-reveal group overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm hover:border-emerald-500 hover:shadow-2xl"
                        >
                            <div
                                class="relative aspect-square overflow-hidden bg-zinc-100"
                            >
                                @if ($product->image)
                                    <img
                                        src="{{ $product->image_url }}"
                                        alt="{{ $product->name }}"
                                        class="store-product-image h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div
                                        class="flex h-full w-full items-center justify-center text-sm text-zinc-400"
                                    >
                                        Sin imagen
                                    </div>
                                @endif

                                <div
                                    class="absolute left-3 top-3 flex flex-col gap-2"
                                >
                                    @if ($product->is_featured)
                                        <span
                                            class="rounded-full bg-zinc-950 px-3 py-1 text-[11px] font-bold text-white"
                                        >
                                            Destacado
                                        </span>
                                    @endif

                                    @if ($product->has_discount)
                                        <span
                                            class="rounded-full bg-red-600 px-3 py-1 text-[11px] font-bold text-white"
                                        >
                                            -{{ $product->discount_percentage }}%
                                        </span>
                                    @endif
                                </div>

                                <div
                                    class="absolute inset-x-3 bottom-3 translate-y-5 opacity-0 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100"
                                >
                                    <span
                                        class="flex h-11 items-center justify-center rounded-2xl bg-white/95 text-sm font-semibold text-zinc-950 shadow-xl backdrop-blur"
                                    >
                                        Ver detalles
                                    </span>
                                </div>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="truncate text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-700"
                                    >
                                        {{ $product->brand?->name ?? 'Sin marca' }}
                                    </p>

                                    <p class="text-[11px] text-zinc-400">
                                        {{ $product->category?->name }}
                                    </p>
                                </div>

                                <h3
                                    class="mt-2 line-clamp-2 min-h-11 text-sm font-semibold leading-5 text-zinc-950 sm:min-h-12 sm:text-base"
                                >
                                    {{ $product->name }}
                                </h3>

                                <div
                                    class="mt-3 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="text-base font-bold text-zinc-950 sm:text-lg"
                                    >
                                        {{ $currencySymbol }}
                                        {{ number_format((float) $product->price, 2) }}
                                    </span>

                                    @if ($product->has_discount)
                                        <span
                                            class="text-xs text-zinc-400 line-through sm:text-sm"
                                        >
                                            {{ $currencySymbol }}
                                            {{ number_format((float) $product->compare_at_price, 2) }}
                                        </span>
                                    @endif
                                </div>

                                <div
                                    class="mt-3 flex items-center justify-between"
                                >
                                    @if ($product->stock <= 5)
                                        <span
                                            class="text-xs font-semibold text-amber-700"
                                        >
                                            Solo {{ $product->stock }}
                                        </span>
                                    @else
                                        <span
                                            class="text-xs font-medium text-emerald-700"
                                        >
                                            Disponible
                                        </span>
                                    @endif

                                    <span
                                        class="text-lg text-emerald-700 transition-transform group-hover:translate-x-1"
                                    >
                                        â†’
                                    </span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div
                            class="col-span-full rounded-3xl border border-dashed border-zinc-300 bg-white p-12 text-center"
                        >
                            <p class="font-semibold text-zinc-700">
                                No encontramos productos.
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Cambia la bÃºsqueda o elimina los filtros.
                            </p>

                            <button
                                type="button"
                                wire:click="clearFilters"
                                class="mt-5 font-semibold text-emerald-700"
                            >
                                Mostrar todo
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Promociones --}}
        <section
            id="promociones"
            class="overflow-hidden bg-white py-18 sm:py-24"
        >
            <div
                class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div class="store-reveal text-center">
                    <p
                        class="text-sm font-bold uppercase tracking-[0.2em] text-red-600"
                    >
                        Beneficios
                    </p>

                    <h2
                        class="mt-3 text-3xl font-bold tracking-tight text-zinc-950 sm:text-5xl"
                    >
                        Promociones vigentes
                    </h2>

                    <p
                        class="mx-auto mt-4 max-w-2xl text-zinc-600"
                    >
                        Descuentos reales administrados desde el panel.
                    </p>
                </div>

                <div class="mt-11 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    @forelse ($promotions as $promotion)
                        <article
                            wire:key="professional-promotion-{{ $promotion->id }}"
                            class="store-reveal group relative min-h-72 overflow-hidden rounded-3xl bg-zinc-950 p-6 text-white shadow-xl transition hover:-translate-y-2 hover:shadow-2xl"
                        >
                            <div
                                class="absolute -right-14 -top-14 h-44 w-44 rounded-full bg-emerald-600/25 blur-xl"
                            ></div>

                            <div
                                class="relative flex h-full flex-col justify-between"
                            >
                                <div>
                                    <span
                                        class="inline-flex rounded-full bg-emerald-500/15 px-3 py-1.5 text-sm font-bold text-emerald-300"
                                    >
                                        {{ $promotion->discount_label }}
                                    </span>

                                    <h3
                                        class="mt-5 text-2xl font-bold leading-tight"
                                    >
                                        {{ $promotion->name }}
                                    </h3>

                                    @if ($promotion->description)
                                        <p
                                            class="mt-3 line-clamp-3 text-sm leading-6 text-zinc-400"
                                        >
                                            {{ $promotion->description }}
                                        </p>
                                    @endif
                                </div>

                                <div class="mt-6">
                                    @if ($promotion->code)
                                        <div
                                            class="rounded-2xl border border-dashed border-zinc-600 px-4 py-3"
                                        >
                                            <p
                                                class="text-[11px] uppercase tracking-[0.16em] text-zinc-500"
                                            >
                                                CÃ³digo
                                            </p>

                                            <p
                                                class="mt-1 font-bold tracking-wide"
                                            >
                                                {{ $promotion->code }}
                                            </p>
                                        </div>
                                    @else
                                        <p
                                            class="text-sm font-medium text-emerald-300"
                                        >
                                            AplicaciÃ³n automÃ¡tica
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div
                            class="col-span-full rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center"
                        >
                            <p class="font-semibold text-zinc-700">
                                No hay promociones vigentes.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- EstadÃ­sticas --}}
        <section
            class="relative overflow-hidden bg-zinc-950 py-14 text-white"
        >
            <div
                class="absolute inset-0 opacity-35"
                style="background-image: radial-gradient(circle at 90% 40%, rgba(16, 185, 129, .45), transparent 30%);"
            ></div>

            <div
                class="relative mx-auto grid max-w-7xl grid-cols-2 gap-y-10 px-4 sm:px-6 lg:grid-cols-4 lg:px-8"
            >
                <div class="store-reveal text-center lg:border-r lg:border-zinc-800">
                    <p class="text-4xl font-bold text-emerald-500">
                        {{ $activeProductsCount }}+
                    </p>

                    <p class="mt-2 text-sm text-zinc-400">
                        Productos activos
                    </p>
                </div>

                <div class="store-reveal text-center lg:border-r lg:border-zinc-800">
                    <p class="text-4xl font-bold text-emerald-500">
                        {{ $activeCategoriesCount }}+
                    </p>

                    <p class="mt-2 text-sm text-zinc-400">
                        CategorÃ­as
                    </p>
                </div>

                <div class="store-reveal text-center lg:border-r lg:border-zinc-800">
                    <p class="text-4xl font-bold text-emerald-500">
                        {{ $activeBrandsCount }}+
                    </p>

                    <p class="mt-2 text-sm text-zinc-400">
                        Marcas disponibles
                    </p>
                </div>

                <div class="store-reveal text-center">
                    <p class="text-4xl font-bold text-emerald-500">
                        {{ $activePromotionsCount }}
                    </p>

                    <p class="mt-2 text-sm text-zinc-400">
                        Promociones activas
                    </p>
                </div>
            </div>
        </section>

        {{-- Marcas --}}
        <section
            id="marcas"
            class="bg-zinc-50 py-18 sm:py-24"
        >
            <div
                class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div class="store-reveal text-center">
                    <p
                        class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700"
                    >
                        Confianza
                    </p>

                    <h2
                        class="mt-3 text-3xl font-bold tracking-tight text-zinc-950 sm:text-5xl"
                    >
                        Marcas disponibles
                    </h2>

                    <p
                        class="mx-auto mt-4 max-w-2xl text-zinc-600"
                    >
                        Trabajamos con marcas organizadas y productos claramente identificados.
                    </p>
                </div>

                <div
                    class="mt-11 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
                >
                    @forelse ($brands as $brand)
                        <div
                            wire:key="professional-brand-{{ $brand->id }}"
                            class="store-reveal group flex min-h-40 flex-col items-center justify-center rounded-3xl border border-zinc-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-1 hover:border-emerald-500 hover:shadow-xl"
                        >
                            @if ($brand->logo)
                                <img
                                    src="{{ $brand->logo_url }}"
                                    alt="{{ $brand->name }}"
                                    class="h-16 w-full object-contain transition duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                            @else
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-950 text-xl font-bold text-white"
                                >
                                    {{ mb_strtoupper(
                                        mb_substr($brand->name, 0, 1)
                                    ) }}
                                </div>
                            @endif

                            <p
                                class="mt-4 font-semibold text-zinc-900"
                            >
                                {{ $brand->name }}
                            </p>

                            <p class="mt-1 text-xs text-zinc-500">
                                {{ $brand->products_count }}
                                productos
                            </p>
                        </div>
                    @empty
                        <div
                            class="col-span-full rounded-3xl border border-dashed border-zinc-300 bg-white p-12 text-center"
                        >
                            Sin marcas disponibles.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Experiencia --}}
        <section
            class="bg-white py-18 sm:py-24"
        >
            <div
                class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="grid items-center gap-12 lg:grid-cols-[.9fr_1.1fr]"
                >
                    <div class="store-reveal">
                        <p
                            class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700"
                        >
                            Nuestra experiencia
                        </p>

                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight text-zinc-950 sm:text-5xl"
                        >
                            Comprar debe ser simple, claro y confiable.
                        </h2>

                        <p
                            class="mt-5 text-base leading-8 text-zinc-600"
                        >
                            Cada producto contiene precio, stock, marca, categorÃ­a y contacto directo para ayudarte a decidir.
                        </p>

                        <a
                            href="#productos"
                            class="mt-7 inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-700 px-6 font-semibold text-white transition hover:bg-emerald-800"
                        >
                            Explorar productos
                        </a>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div
                            class="store-reveal rounded-3xl border border-zinc-200 bg-zinc-50 p-6"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl text-emerald-800"
                            >
                                âœ“
                            </div>

                            <h3 class="mt-5 font-bold text-zinc-950">
                                InformaciÃ³n real
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                Datos obtenidos directamente del panel administrativo.
                            </p>
                        </div>

                        <div
                            class="store-reveal store-reveal-delay-1 rounded-3xl border border-zinc-200 bg-zinc-50 p-6"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-xl text-blue-800"
                            >
                                â†—
                            </div>

                            <h3 class="mt-5 font-bold text-zinc-950">
                                AtenciÃ³n directa
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                Consulta cada producto directamente por WhatsApp.
                            </p>
                        </div>

                        <div
                            class="store-reveal store-reveal-delay-2 rounded-3xl border border-zinc-200 bg-zinc-50 p-6"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-xl text-amber-800"
                            >
                                %
                            </div>

                            <h3 class="mt-5 font-bold text-zinc-950">
                                Promociones visibles
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                CampaÃ±as activas y descuentos claramente presentados.
                            </p>
                        </div>

                        <div
                            class="store-reveal store-reveal-delay-3 rounded-3xl border border-zinc-200 bg-zinc-50 p-6"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-xl text-violet-800"
                            >
                                â—‡
                            </div>

                            <h3 class="mt-5 font-bold text-zinc-950">
                                DiseÃ±o responsive
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                Experiencia optimizada para computadora, tablet y celular.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section
            class="relative overflow-hidden bg-zinc-950 py-18 text-white"
        >
            @php
                $contactWhatsapp = preg_replace(
                    '/\D+/',
                    '',
                    $company->whatsapp ?? ''
                );

                $generalMessage = rawurlencode(
                    'Hola, deseo recibir informaciÃ³n sobre los productos de '
                    . $company->trade_name
                );

                $generalWhatsappUrl = $contactWhatsapp !== ''
                    ? 'https://wa.me/'
                        . $contactWhatsapp
                        . '?text='
                        . $generalMessage
                    : null;
            @endphp

            <div
                class="absolute inset-0 opacity-35"
                style="background-image: radial-gradient(circle at 85% 50%, rgba(16, 185, 129, .5), transparent 28%);"
            ></div>

            <div
                class="store-reveal relative mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8"
            >
                <div>
                    <p
                        class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-400"
                    >
                        AtenciÃ³n personalizada
                    </p>

                    <h2
                        class="mt-4 max-w-3xl text-3xl font-bold tracking-tight sm:text-5xl"
                    >
                        Â¿Necesitas ayuda para elegir?
                    </h2>

                    <p
                        class="mt-5 max-w-2xl text-lg leading-8 text-zinc-300"
                    >
                        EscrÃ­benos y recibe informaciÃ³n sobre disponibilidad, caracterÃ­sticas y promociones.
                    </p>
                </div>

                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                    @if ($generalWhatsappUrl)
                        <a
                            href="{{ $generalWhatsappUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-13 items-center justify-center rounded-2xl bg-emerald-700 px-7 font-semibold text-white shadow-lg transition hover:bg-emerald-800"
                        >
                            Hablar por WhatsApp
                        </a>
                    @endif

                    @if ($company->email)
                        <a
                            href="mailto:{{ $company->email }}"
                            class="inline-flex h-13 items-center justify-center rounded-2xl border border-zinc-600 px-7 font-semibold text-white transition hover:border-white"
                        >
                            Enviar correo
                        </a>
                    @endif
                </div>
            </div>
        </section>

    @endif

</div>