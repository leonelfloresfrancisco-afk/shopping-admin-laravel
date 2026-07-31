<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth"
>

<head>
    @include('partials.head')

    <style>
        [x-cloak] {
            display: none !important;
        }

        html {
            scroll-padding-top: 6rem;
        }

        body {
            -webkit-tap-highlight-color: transparent;
        }

        .store-reveal {
            opacity: 0;
            transform: translateY(28px);
            transition:
                opacity 700ms ease,
                transform 700ms ease;
        }

        .store-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .store-reveal-delay-1 {
            transition-delay: 80ms;
        }

        .store-reveal-delay-2 {
            transition-delay: 160ms;
        }

        .store-reveal-delay-3 {
            transition-delay: 240ms;
        }

        .store-product-card {
            transition:
                transform 350ms ease,
                box-shadow 350ms ease,
                border-color 350ms ease;
        }

        .store-product-card:hover {
            transform: translateY(-6px);
        }

        .store-product-image {
            transform: scale(1);
            transition:
                transform 650ms cubic-bezier(.2, .7, .2, 1),
                filter 400ms ease;
        }

        .store-product-card:hover .store-product-image {
            transform: scale(1.08);
        }

        .store-image-zoom {
            transform: scale(1);
            transition: transform 800ms cubic-bezier(.2, .7, .2, 1);
        }

        .store-image-zoom-wrapper:hover .store-image-zoom {
            transform: scale(1.13);
        }

        .store-no-scrollbar {
            scrollbar-width: none;
        }

        .store-no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .mobile-store-dock {
            box-shadow:
                0 -10px 28px rgba(15, 23, 42, .08),
                0 -1px 0 rgba(15, 23, 42, .05);
        }

        @media (max-width: 767px) {
            body {
                padding-bottom: calc(5.25rem + env(safe-area-inset-bottom));
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .store-reveal,
            .store-product-card,
            .store-product-image,
            .store-image-zoom {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>

@php
    $storeName = $companySettings?->trade_name ?: 'Mi Tienda';

    $storeLogoUrl = $companySettings?->logo
        ? asset('storage/' . $companySettings->logo)
        : null;

    $logoVersion = $companySettings?->updated_at?->timestamp ?? time();

    $whatsappNumber = preg_replace(
        '/\D+/',
        '',
        $companySettings?->whatsapp ?? ''
    );

    $whatsappUrl = $whatsappNumber !== ''
        ? 'https://wa.me/' . $whatsappNumber
        : route('home') . '#contacto';

    $phoneLink = preg_replace(
        '/[^\d+]/',
        '',
        $companySettings?->phone ?? ''
    );

    $websiteUrl = null;

    if (filled($companySettings?->website)) {
        $websiteUrl = \Illuminate\Support\Str::startsWith(
            $companySettings->website,
            [
                'http://',
                'https://',
            ]
        )
            ? $companySettings->website
            : 'https://' . $companySettings->website;
    }

    try {
        $storeNavigationCategories = \App\Models\Category::query()
            ->where('is_active', true)
            ->withCount([
                'products' => function ($query): void {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $storeNavigationBrands = \App\Models\Brand::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get();
    } catch (\Throwable) {
        $storeNavigationCategories = collect();
        $storeNavigationBrands = collect();
    }
@endphp

<body
    x-data="{
        tabletMenuOpen: false,
        desktopMenu: null,
        mobileSection: 'inicio',

        closeMenus() {
            this.tabletMenuOpen = false;
            this.desktopMenu = null;
        },

        focusStoreSearch() {
            this.mobileSection = 'buscar';

            const section = document.getElementById('productos');

            if (! section) {
                window.location.href = '{{ route('home') }}#productos';

                return;
            }

            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            window.setTimeout(() => {
                const input = section.querySelector(
                    'input[type=search]'
                );

                input?.focus();
            }, 650);
        }
    }"
    x-on:keydown.escape.window="closeMenus()"
    x-on:store-section.window="mobileSection = $event.detail"
    class="min-h-screen bg-white text-zinc-950 antialiased"
>

    {{-- Cabecera pública --}}
    <header class="sticky top-0 z-50 border-b border-zinc-200/80 bg-white/92 shadow-[0_8px_30px_rgba(15,23,42,0.05)] backdrop-blur-xl">

        <div class="mx-auto flex h-18 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">

            {{-- Identidad --}}
            <a
                href="{{ route('home') }}"
                class="group flex min-w-0 items-center gap-3 rounded-2xl py-1"
                aria-label="Ir al inicio de {{ $storeName }}"
            >
                <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 group-hover:-translate-y-0.5 group-hover:border-emerald-300 group-hover:shadow-md">

                    @if ($storeLogoUrl)
                        <img
                            src="{{ $storeLogoUrl }}?v={{ $logoVersion }}"
                            alt="{{ $storeName }}"
                            class="h-full w-full object-contain p-1"
                        >
                    @else
                        <x-app-logo-icon class="h-7 w-7 text-emerald-700" />
                    @endif

                </span>

                <span class="min-w-0">

                    <span class="block max-w-40 truncate text-lg font-extrabold tracking-tight text-zinc-950 sm:max-w-56">
                        {{ $storeName }}
                    </span>

                    <span class="hidden text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-400 sm:block">
                        Catálogo digital
                    </span>

                </span>
            </a>

            {{-- Navegación de escritorio --}}
            <nav
                class="mx-auto hidden items-center gap-1 xl:flex"
                x-on:mouseleave="desktopMenu = null"
                aria-label="Navegación principal"
            >

                <a
                    href="{{ route('home') }}#inicio"
                    class="relative rounded-2xl px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50"
                >
                    Inicio

                    <span class="absolute inset-x-4 -bottom-0.5 h-0.5 rounded-full bg-emerald-600"></span>
                </a>

                {{-- Catálogo --}}
                <div
                    class="relative"
                    x-on:mouseenter="desktopMenu = 'catalogo'"
                >
                    <button
                        type="button"
                        x-on:click="
                            desktopMenu = desktopMenu === 'catalogo'
                                ? null
                                : 'catalogo'
                        "
                        x-bind:class="
                            desktopMenu === 'catalogo'
                                ? 'bg-zinc-100 text-zinc-950'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950'
                        "
                        class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold transition"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 5h6v6H4V5Zm10 0h6v6h-6V5ZM4 15h6v4H4v-4Zm10 0h6v4h-6v-4Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Catálogo
                    </button>

                    <div
                        x-cloak
                        x-show="desktopMenu === 'catalogo'"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="translate-y-3 scale-[.98] opacity-0"
                        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                        x-transition:leave="transition duration-150 ease-in"
                        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                        x-transition:leave-end="translate-y-2 scale-[.98] opacity-0"
                        class="absolute left-1/2 top-full w-[720px] -translate-x-1/2 pt-4"
                    >
                        <div class="grid grid-cols-[.9fr_1.1fr] gap-4 rounded-[2rem] border border-zinc-200 bg-white p-4 shadow-[0_28px_80px_rgba(15,23,42,.18)]">

                            <div class="rounded-[1.5rem] bg-zinc-950 p-5 text-white">

                                <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-emerald-400">
                                    Explorar
                                </p>

                                <div class="mt-4 grid gap-2">

                                    <a
                                        href="{{ route('home') }}#productos"
                                        class="group rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-emerald-500/40 hover:bg-emerald-500/10"
                                    >
                                        <div class="flex items-center gap-3">

                                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-emerald-300">

                                                <svg
                                                    class="h-5 w-5"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M4 7h16l-1 13H5L4 7Zm4 0a4 4 0 0 1 8 0"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>

                                            </span>

                                            <span>
                                                <span class="block text-sm font-bold">
                                                    Todos los productos
                                                </span>

                                                <span class="mt-0.5 block text-xs text-zinc-400">
                                                    Explora el catálogo disponible
                                                </span>
                                            </span>

                                        </div>
                                    </a>

                                    <a
                                        href="{{ route('home') }}#destacados"
                                        class="group rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-emerald-500/40 hover:bg-emerald-500/10"
                                    >
                                        <div class="flex items-center gap-3">

                                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-emerald-300">

                                                <svg
                                                    class="h-5 w-5"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>

                                            </span>

                                            <span>
                                                <span class="block text-sm font-bold">
                                                    Productos destacados
                                                </span>

                                                <span class="mt-0.5 block text-xs text-zinc-400">
                                                    Selección recomendada
                                                </span>
                                            </span>

                                        </div>
                                    </a>

                                    <a
                                        href="{{ route('home') }}#marcas"
                                        class="group rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-emerald-500/40 hover:bg-emerald-500/10"
                                    >
                                        <div class="flex items-center gap-3">

                                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-emerald-300">

                                                <svg
                                                    class="h-5 w-5"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M6 5h12v14H6V5Zm3 4h6M9 13h6"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>

                                            </span>

                                            <span>
                                                <span class="block text-sm font-bold">
                                                    Marcas disponibles
                                                </span>

                                                <span class="mt-0.5 block text-xs text-zinc-400">
                                                    Conoce las líneas del catálogo
                                                </span>
                                            </span>

                                        </div>
                                    </a>

                                </div>

                            </div>

                            <div class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-5">

                                <div class="flex items-center justify-between gap-4">

                                    <div>
                                        <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-zinc-400">
                                            Categorías
                                        </p>

                                        <p class="mt-1 text-sm font-semibold text-zinc-700">
                                            Compra según lo que necesitas
                                        </p>
                                    </div>

                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-zinc-500 shadow-sm">
                                        {{ $storeNavigationCategories->count() }}
                                    </span>

                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2">

                                    @forelse ($storeNavigationCategories as $navigationCategory)

                                        <a
                                            href="{{ route('home', [
                                                'categoria' => $navigationCategory->id,
                                            ]) }}#productos"
                                            class="group flex min-w-0 items-center gap-3 rounded-2xl border border-transparent bg-white p-3 shadow-sm transition hover:border-emerald-200 hover:shadow-md"
                                        >

                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-zinc-100 text-sm font-black text-zinc-600">

                                                @if ($navigationCategory->image)
                                                    <img
                                                        src="{{ asset('storage/' . $navigationCategory->image) }}"
                                                        alt="{{ $navigationCategory->name }}"
                                                        class="h-full w-full object-cover"
                                                    >
                                                @else
                                                    {{ mb_strtoupper(
                                                        mb_substr(
                                                            $navigationCategory->name,
                                                            0,
                                                            1
                                                        )
                                                    ) }}
                                                @endif

                                            </span>

                                            <span class="min-w-0">

                                                <span class="block truncate text-sm font-bold text-zinc-800 transition group-hover:text-emerald-700">
                                                    {{ $navigationCategory->name }}
                                                </span>

                                                <span class="mt-0.5 block text-[11px] text-zinc-400">
                                                    {{ $navigationCategory->products_count }}
                                                    {{ $navigationCategory->products_count === 1 ? 'producto' : 'productos' }}
                                                </span>

                                            </span>

                                        </a>

                                    @empty

                                        <p class="col-span-2 rounded-2xl bg-white p-5 text-center text-sm text-zinc-500">
                                            No hay categorías disponibles.
                                        </p>

                                    @endforelse

                                </div>

                            </div>

                        </div>
                    </div>
                </div>

                {{-- Descubrir --}}
                <div
                    class="relative"
                    x-on:mouseenter="desktopMenu = 'descubrir'"
                >
                    <button
                        type="button"
                        x-on:click="
                            desktopMenu = desktopMenu === 'descubrir'
                                ? null
                                : 'descubrir'
                        "
                        x-bind:class="
                            desktopMenu === 'descubrir'
                                ? 'bg-zinc-100 text-zinc-950'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950'
                        "
                        class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold transition"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Descubrir
                    </button>

                    <div
                        x-cloak
                        x-show="desktopMenu === 'descubrir'"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="translate-y-3 scale-[.98] opacity-0"
                        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                        x-transition:leave="transition duration-150 ease-in"
                        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                        x-transition:leave-end="translate-y-2 scale-[.98] opacity-0"
                        class="absolute left-1/2 top-full w-[390px] -translate-x-1/2 pt-4"
                    >
                        <div class="rounded-[2rem] border border-zinc-200 bg-white p-3 shadow-[0_28px_80px_rgba(15,23,42,.18)]">

                            <a
                                href="{{ route('home') }}#promociones"
                                class="group flex items-start gap-4 rounded-[1.5rem] p-4 transition hover:bg-red-50"
                            >
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600">

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="m7 7 10 10M8.5 9A1.5 1.5 0 1 0 8.5 6a1.5 1.5 0 0 0 0 3Zm7 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                        />
                                    </svg>

                                </span>

                                <span>
                                    <span class="block font-bold text-zinc-900">
                                        Promociones vigentes
                                    </span>

                                    <span class="mt-1 block text-sm leading-6 text-zinc-500">
                                        Revisa campañas, beneficios y descuentos disponibles.
                                    </span>
                                </span>
                            </a>

                            <a
                                href="{{ route('home') }}#marcas"
                                class="group flex items-start gap-4 rounded-[1.5rem] p-4 transition hover:bg-emerald-50"
                            >
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M5 7h14v10H5V7Zm3-3v3m8-3v3M8 17v3m8-3v3"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>

                                </span>

                                <span>
                                    <span class="block font-bold text-zinc-900">
                                        Marcas del catálogo
                                    </span>

                                    <span class="mt-1 block text-sm leading-6 text-zinc-500">
                                        Explora proveedores y líneas disponibles.
                                    </span>
                                </span>
                            </a>

                            <a
                                href="{{ route('home') }}#contacto"
                                class="group flex items-start gap-4 rounded-[1.5rem] p-4 transition hover:bg-zinc-100"
                            >
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-700">

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5Z"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>

                                </span>

                                <span>
                                    <span class="block font-bold text-zinc-900">
                                        Atención personalizada
                                    </span>

                                    <span class="mt-1 block text-sm leading-6 text-zinc-500">
                                        Consulta disponibilidad y características.
                                    </span>
                                </span>
                            </a>

                        </div>
                    </div>
                </div>

                <a
                    href="{{ route('home') }}#promociones"
                    class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold text-zinc-600 transition hover:bg-red-50 hover:text-red-600"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="m7 7 10 10M8.5 9A1.5 1.5 0 1 0 8.5 6a1.5 1.5 0 0 0 0 3Zm7 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>

                    Ofertas
                </a>

                <a
                    href="{{ route('home') }}#contacto"
                    class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                    Contacto
                </a>

            </nav>

            {{-- Acciones --}}
            <div class="ml-auto flex shrink-0 items-center gap-2">

                {{-- Buscar en catálogo, sin modal --}}
                <button
                    type="button"
                    x-on:click="focusStoreSearch()"
                    class="hidden h-11 items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 text-left transition hover:border-emerald-300 hover:bg-white hover:shadow-md lg:inline-flex"
                    aria-label="Ir al buscador del catálogo"
                >
                    <svg
                        class="h-4 w-4 text-zinc-400"
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
                            d="m16.5 16.5 4 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>

                    <span class="w-28 truncate text-sm font-medium text-zinc-500">
                        Buscar productos
                    </span>
                </button>

                {{-- Buscar en tablet --}}
                <button
                    type="button"
                    x-on:click="focusStoreSearch()"
                    class="hidden h-11 w-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-600 transition hover:border-emerald-300 hover:text-emerald-700 md:inline-flex lg:hidden"
                    aria-label="Buscar productos"
                >
                    <svg
                        class="h-5 w-5"
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
                            d="m16.5 16.5 4 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>

                {{-- Menú sándwich para tablet --}}
                <button
                    type="button"
                    x-on:click="tabletMenuOpen = true"
                    class="hidden h-11 w-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-700 transition hover:border-emerald-300 hover:text-emerald-700 md:inline-flex xl:hidden"
                    aria-label="Abrir menú de navegación"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M4 7h16M4 12h16M4 17h16"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>

                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="hidden h-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-bold text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950 lg:inline-flex"
                    >
                        Panel
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="hidden h-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-bold text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950 lg:inline-flex"
                    >
                        Ingresar
                    </a>
                @endauth

                {{-- WhatsApp --}}
                <a
                    href="{{ $whatsappUrl }}"
                    @if ($whatsappNumber !== '')
                        target="_blank"
                        rel="noopener noreferrer"
                    @endif
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-700 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-lg sm:w-auto sm:gap-2 sm:px-4"
                    aria-label="Contactar por WhatsApp"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                    <span class="hidden text-sm font-bold sm:inline">
                        Hablemos
                    </span>
                </a>

            </div>

        </div>

    </header>

    {{-- Menú lateral de tablet --}}
    <div
        x-cloak
        x-show="tabletMenuOpen"
        class="fixed inset-0 z-[70] hidden md:block xl:hidden"
    >
        <div
            x-show="tabletMenuOpen"
            x-transition.opacity
            x-on:click="tabletMenuOpen = false"
            class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"
        ></div>

        <aside
            x-show="tabletMenuOpen"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 w-full max-w-md overflow-y-auto bg-white shadow-2xl"
        >

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-zinc-200 bg-white/95 px-6 py-5 backdrop-blur">

                <div class="flex min-w-0 items-center gap-3">

                    <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-white">

                        @if ($storeLogoUrl)
                            <img
                                src="{{ $storeLogoUrl }}?v={{ $logoVersion }}"
                                alt="{{ $storeName }}"
                                class="h-full w-full object-contain p-1"
                            >
                        @else
                            <x-app-logo-icon class="h-7 w-7 text-emerald-700" />
                        @endif

                    </span>

                    <span class="min-w-0">
                        <span class="block truncate font-extrabold text-zinc-950">
                            {{ $storeName }}
                        </span>

                        <span class="text-xs font-medium text-zinc-400">
                            Navegación de la tienda
                        </span>
                    </span>

                </div>

                <button
                    type="button"
                    x-on:click="tabletMenuOpen = false"
                    class="flex h-11 w-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-700 transition hover:border-zinc-400"
                    aria-label="Cerrar menú"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="m6 6 12 12M18 6 6 18"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>

            </div>

            <div class="space-y-6 p-6">

                <button
                    type="button"
                    x-on:click="
                        tabletMenuOpen = false;
                        focusStoreSearch();
                    "
                    class="flex h-13 w-full items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 text-left text-sm font-semibold text-zinc-600 transition hover:border-emerald-300 hover:bg-white hover:text-emerald-700"
                >
                    <svg
                        class="h-5 w-5"
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
                            d="m16.5 16.5 4 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>

                    Buscar productos en el catálogo
                </button>

                <div class="grid gap-2">

                    <a
                        href="{{ route('home') }}#inicio"
                        x-on:click="tabletMenuOpen = false"
                        class="flex items-center gap-3 rounded-2xl bg-emerald-50 px-4 py-3.5 font-bold text-emerald-800"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Inicio
                    </a>

                    <a
                        href="{{ route('home') }}#categorias"
                        x-on:click="tabletMenuOpen = false"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3.5 font-semibold text-zinc-700 transition hover:bg-zinc-100"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 5h6v6H4V5Zm10 0h6v6h-6V5ZM4 15h6v4H4v-4Zm10 0h6v4h-6v-4Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Categorías
                    </a>

                    <a
                        href="{{ route('home') }}#productos"
                        x-on:click="tabletMenuOpen = false"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3.5 font-semibold text-zinc-700 transition hover:bg-zinc-100"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 7h16l-1 13H5L4 7Zm4 0a4 4 0 0 1 8 0"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Productos
                    </a>

                    <a
                        href="{{ route('home') }}#promociones"
                        x-on:click="tabletMenuOpen = false"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3.5 font-semibold text-zinc-700 transition hover:bg-red-50 hover:text-red-700"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="m7 7 10 10M8.5 9A1.5 1.5 0 1 0 8.5 6a1.5 1.5 0 0 0 0 3Zm7 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>

                        Promociones
                    </a>

                    <a
                        href="{{ route('home') }}#marcas"
                        x-on:click="tabletMenuOpen = false"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3.5 font-semibold text-zinc-700 transition hover:bg-zinc-100"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M5 7h14v10H5V7Zm3-3v3m8-3v3M8 17v3m8-3v3"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        Marcas
                    </a>

                </div>

                @if ($storeNavigationBrands->isNotEmpty())

                    <div>

                        <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-zinc-400">
                            Marcas disponibles
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">

                            @foreach ($storeNavigationBrands as $navigationBrand)

                                <span class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs font-bold text-zinc-600">
                                    {{ $navigationBrand->name }}
                                </span>

                            @endforeach

                        </div>

                    </div>

                @endif

                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-zinc-950 px-5 font-bold text-white"
                    >
                        Abrir panel administrativo
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-zinc-950 px-5 font-bold text-white"
                    >
                        Acceso administrativo
                    </a>
                @endauth

            </div>

        </aside>

    </div>

    {{-- Contenido --}}
    <main>
        {{ $slot }}
    </main>

    {{-- WhatsApp flotante: solo escritorio y tablet --}}
    @if ($whatsappNumber !== '')
        <a
            href="{{ $whatsappUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-6 right-6 z-40 hidden h-14 w-14 items-center justify-center rounded-full bg-emerald-600 text-white shadow-2xl transition hover:scale-105 hover:bg-emerald-700 md:flex"
            aria-label="Contactar por WhatsApp"
        >
            <svg
                class="h-7 w-7"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </a>
    @endif

    {{-- Footer empresarial --}}
    <footer
        id="contacto"
        class="relative overflow-hidden bg-zinc-950 text-white"
    >

        <div
            class="pointer-events-none absolute inset-0 opacity-30"
            style="background-image:
                radial-gradient(circle at 5% 10%, rgba(16,185,129,.35), transparent 24%),
                radial-gradient(circle at 95% 80%, rgba(59,130,246,.20), transparent 28%);"
        ></div>

        {{-- Llamado previo al footer --}}
        <div class="relative border-b border-white/10">

            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-10 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">

                <div>

                    <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-emerald-400">
                        Atención comercial
                    </p>

                    <h2 class="mt-3 max-w-3xl text-2xl font-bold tracking-tight sm:text-3xl">
                        Recibe información clara antes de elegir tu producto.
                    </h2>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-zinc-400">
                        Consulta disponibilidad, características, promociones y alternativas directamente con nuestro equipo.
                    </p>

                </div>

                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">

                    @if ($whatsappNumber !== '')
                        <a
                            href="{{ $whatsappUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 font-bold text-white transition hover:bg-emerald-500"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5Z"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            WhatsApp
                        </a>
                    @endif

                    @if ($companySettings?->email)
                        <a
                            href="mailto:{{ $companySettings->email }}"
                            class="inline-flex h-12 items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-5 font-bold text-white transition hover:bg-white/10"
                        >
                            Enviar correo
                        </a>
                    @endif

                </div>

            </div>

        </div>

        <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-[1.25fr_.8fr_1fr_1fr] lg:px-8">

            {{-- Identidad --}}
            <div>

                <div class="flex items-center gap-3">

                    <span class="flex h-13 w-13 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-lg">

                        @if ($storeLogoUrl)
                            <img
                                src="{{ $storeLogoUrl }}?v={{ $logoVersion }}"
                                alt="{{ $storeName }}"
                                class="h-full w-full object-contain p-1"
                            >
                        @else
                            <x-app-logo-icon class="h-8 w-8 text-emerald-700" />
                        @endif

                    </span>

                    <span>

                        <span class="block text-xl font-extrabold">
                            {{ $storeName }}
                        </span>

                        @if ($companySettings?->legal_name)
                            <span class="mt-0.5 block text-xs text-zinc-500">
                                {{ $companySettings->legal_name }}
                            </span>
                        @endif

                    </span>

                </div>

                <p class="mt-5 max-w-sm text-sm leading-7 text-zinc-400">
                    Catálogo digital con productos, marcas y promociones administrados desde una plataforma moderna y organizada.
                </p>

                {{-- Redes sociales --}}
                <div class="mt-6 flex flex-wrap gap-2">

                    @if ($companySettings?->facebook_url)
                        <a
                            href="{{ $companySettings->facebook_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-zinc-300 transition hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-300"
                            aria-label="Facebook"
                        >
                            <span class="text-sm font-black">
                                f
                            </span>
                        </a>
                    @endif

                    @if ($companySettings?->instagram_url)
                        <a
                            href="{{ $companySettings->instagram_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-zinc-300 transition hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-300"
                            aria-label="Instagram"
                        >
                            <span class="text-sm font-black">
                                IG
                            </span>
                        </a>
                    @endif

                    @if ($companySettings?->tiktok_url)
                        <a
                            href="{{ $companySettings->tiktok_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-zinc-300 transition hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-300"
                            aria-label="TikTok"
                        >
                            <span class="text-xs font-black">
                                TT
                            </span>
                        </a>
                    @endif

                    @if ($companySettings?->youtube_url)
                        <a
                            href="{{ $companySettings->youtube_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-zinc-300 transition hover:border-red-500/40 hover:bg-red-500/10 hover:text-red-300"
                            aria-label="YouTube"
                        >
                            <span class="text-xs font-black">
                                YT
                            </span>
                        </a>
                    @endif

                </div>

            </div>

            {{-- Navegación --}}
            <div>

                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-white">
                    Explorar
                </h3>

                <div class="mt-5 grid gap-3 text-sm text-zinc-400">

                    <a
                        href="{{ route('home') }}#inicio"
                        class="transition hover:text-emerald-300"
                    >
                        Inicio
                    </a>

                    <a
                        href="{{ route('home') }}#categorias"
                        class="transition hover:text-emerald-300"
                    >
                        Categorías
                    </a>

                    <a
                        href="{{ route('home') }}#productos"
                        class="transition hover:text-emerald-300"
                    >
                        Productos
                    </a>

                    <a
                        href="{{ route('home') }}#destacados"
                        class="transition hover:text-emerald-300"
                    >
                        Destacados
                    </a>

                    <a
                        href="{{ route('home') }}#promociones"
                        class="transition hover:text-emerald-300"
                    >
                        Promociones
                    </a>

                    <a
                        href="{{ route('home') }}#marcas"
                        class="transition hover:text-emerald-300"
                    >
                        Marcas
                    </a>

                </div>

            </div>

            {{-- Empresa --}}
            <div>

                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-white">
                    Información empresarial
                </h3>

                <div class="mt-5 grid gap-3 text-sm leading-6 text-zinc-400">

                    @if ($companySettings?->legal_name)
                        <p>
                            {{ $companySettings->legal_name }}
                        </p>
                    @endif

                    @if ($companySettings?->tax_id)
                        <p>
                            Identificación:
                            {{ $companySettings->tax_id }}
                        </p>
                    @endif

                    @if ($companySettings?->address)
                        <p>
                            {{ $companySettings->address }}
                        </p>
                    @endif

                    @if (
                        $companySettings?->city
                        || $companySettings?->state
                        || $companySettings?->country
                    )
                        <p>
                            {{ collect([
                                $companySettings?->city,
                                $companySettings?->state,
                                $companySettings?->country,
                            ])->filter()->implode(', ') }}
                        </p>
                    @endif

                    @if ($companySettings?->postal_code)
                        <p>
                            Código postal:
                            {{ $companySettings->postal_code }}
                        </p>
                    @endif

                    @if ($websiteUrl)
                        <a
                            href="{{ $websiteUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="break-all transition hover:text-emerald-300"
                        >
                            {{ $companySettings->website }}
                        </a>
                    @endif

                </div>

            </div>

            {{-- Contacto --}}
            <div>

                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-white">
                    Contacto
                </h3>

                <div class="mt-5 grid gap-4 text-sm text-zinc-400">

                    @if ($companySettings?->phone)
                        <a
                            href="tel:{{ $phoneLink }}"
                            class="group"
                        >
                            <span class="block text-xs font-bold uppercase tracking-wide text-zinc-600">
                                Teléfono
                            </span>

                            <span class="mt-1 block transition group-hover:text-emerald-300">
                                {{ $companySettings->phone }}
                            </span>
                        </a>
                    @endif

                    @if ($companySettings?->whatsapp)
                        <a
                            href="{{ $whatsappUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group"
                        >
                            <span class="block text-xs font-bold uppercase tracking-wide text-zinc-600">
                                WhatsApp
                            </span>

                            <span class="mt-1 block transition group-hover:text-emerald-300">
                                {{ $companySettings->whatsapp }}
                            </span>
                        </a>
                    @endif

                    @if ($companySettings?->email)
                        <a
                            href="mailto:{{ $companySettings->email }}"
                            class="group min-w-0"
                        >
                            <span class="block text-xs font-bold uppercase tracking-wide text-zinc-600">
                                Correo
                            </span>

                            <span class="mt-1 block break-all transition group-hover:text-emerald-300">
                                {{ $companySettings->email }}
                            </span>
                        </a>
                    @endif

                </div>

            </div>

        </div>

        <div class="relative border-t border-white/10">

            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">

                <p>
                    © {{ now()->year }} {{ $storeName }}.
                    Todos los derechos reservados.
                </p>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">

                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="transition hover:text-white"
                        >
                            Panel administrativo
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="transition hover:text-white"
                        >
                            Acceso administrativo
                        </a>
                    @endauth

                    <span>
                        {{ $companySettings?->country ?: 'Tienda digital' }}
                    </span>

                </div>

            </div>

        </div>

    </footer>

    {{-- Navegación inferior profesional: solo celular --}}
    <nav
        class="mobile-store-dock fixed inset-x-0 bottom-0 z-50 border-t border-zinc-200 bg-white/95 backdrop-blur-xl md:hidden"
        style="padding-bottom: env(safe-area-inset-bottom);"
        aria-label="Navegación móvil"
    >

        <div class="grid h-18 grid-cols-5 px-1">

            {{-- Inicio --}}
            <a
                href="{{ route('home') }}#inicio"
                x-on:click="mobileSection = 'inicio'"
                x-bind:class="
                    mobileSection === 'inicio'
                        ? 'text-emerald-700'
                        : 'text-zinc-400'
                "
                class="relative flex flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-bold transition duration-200 active:bg-zinc-100"
            >

                <span
                    x-bind:class="
                        mobileSection === 'inicio'
                            ? 'bg-emerald-50'
                            : 'bg-transparent'
                    "
                    class="flex h-9 w-11 items-center justify-center rounded-2xl transition duration-200"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>

                <span>
                    Inicio
                </span>

            </a>

            {{-- Categorías --}}
            <a
                href="{{ route('home') }}#categorias"
                x-on:click="mobileSection = 'categorias'"
                x-bind:class="
                    mobileSection === 'categorias'
                        ? 'text-emerald-700'
                        : 'text-zinc-400'
                "
                class="relative flex flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-bold transition duration-200 active:bg-zinc-100"
            >

                <span
                    x-bind:class="
                        mobileSection === 'categorias'
                            ? 'bg-emerald-50'
                            : 'bg-transparent'
                    "
                    class="flex h-9 w-11 items-center justify-center rounded-2xl transition duration-200"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M4 5h6v6H4V5Zm10 0h6v6h-6V5ZM4 15h6v4H4v-4Zm10 0h6v4h-6v-4Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>

                <span>
                    Categorías
                </span>

            </a>

            {{-- Buscar --}}
            <button
                type="button"
                x-on:click="focusStoreSearch()"
                x-bind:class="
                    mobileSection === 'buscar'
                        ? 'text-emerald-700'
                        : 'text-zinc-400'
                "
                class="relative flex flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-bold transition duration-200 active:bg-zinc-100"
                aria-label="Buscar productos"
            >

                <span
                    x-bind:class="
                        mobileSection === 'buscar'
                            ? 'bg-emerald-50'
                            : 'bg-transparent'
                    "
                    class="flex h-9 w-11 items-center justify-center rounded-2xl transition duration-200"
                >
                    <svg
                        class="h-5 w-5"
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
                            d="m16.5 16.5 4 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>

                <span>
                    Buscar
                </span>

            </button>

            {{-- Productos --}}
            <a
                href="{{ route('home') }}#productos"
                x-on:click="mobileSection = 'productos'"
                x-bind:class="
                    mobileSection === 'productos'
                        ? 'text-emerald-700'
                        : 'text-zinc-400'
                "
                class="relative flex flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-bold transition duration-200 active:bg-zinc-100"
            >

                <span
                    x-bind:class="
                        mobileSection === 'productos'
                            ? 'bg-emerald-50'
                            : 'bg-transparent'
                    "
                    class="flex h-9 w-11 items-center justify-center rounded-2xl transition duration-200"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M4 7h16l-1 13H5L4 7Zm4 0a4 4 0 0 1 8 0"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>

                <span>
                    Productos
                </span>

            </a>

            {{-- Ofertas --}}
            <a
                href="{{ route('home') }}#promociones"
                x-on:click="mobileSection = 'promociones'"
                x-bind:class="
                    mobileSection === 'promociones'
                        ? 'text-emerald-700'
                        : 'text-zinc-400'
                "
                class="relative flex flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-bold transition duration-200 active:bg-zinc-100"
            >

                <span
                    x-bind:class="
                        mobileSection === 'promociones'
                            ? 'bg-emerald-50'
                            : 'bg-transparent'
                    "
                    class="flex h-9 w-11 items-center justify-center rounded-2xl transition duration-200"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="m7 7 10 10M8.5 9A1.5 1.5 0 1 0 8.5 6a1.5 1.5 0 0 0 0 3Zm7 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>

                <span>
                    Ofertas
                </span>

            </a>

        </div>

    </nav>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts

    <script>
        (() => {
            const initializeStoreAnimations = () => {
                const elements = document.querySelectorAll(
                    '.store-reveal:not(.is-visible)'
                );

                if (
                    window.matchMedia(
                        '(prefers-reduced-motion: reduce)'
                    ).matches
                ) {
                    elements.forEach((element) => {
                        element.classList.add('is-visible');
                    });

                    return;
                }

                const observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((entry) => {
                            if (! entry.isIntersecting) {
                                return;
                            }

                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        });
                    },
                    {
                        threshold: 0.12,
                        rootMargin: '0px 0px -40px 0px',
                    }
                );

                elements.forEach((element) => {
                    observer.observe(element);
                });
            };

            const initializeStoreSectionObserver = () => {
                window.__storeSectionObserver?.disconnect();

                const sections = [
                    'inicio',
                    'categorias',
                    'productos',
                    'promociones',
                ]
                    .map((sectionId) => {
                        return document.getElementById(sectionId);
                    })
                    .filter(Boolean);

                if (sections.length === 0) {
                    return;
                }

                window.__storeSectionObserver = new IntersectionObserver(
                    (entries) => {
                        const visibleEntry = entries
                            .filter((entry) => {
                                return entry.isIntersecting;
                            })
                            .sort((firstEntry, secondEntry) => {
                                return secondEntry.intersectionRatio
                                    - firstEntry.intersectionRatio;
                            })[0];

                        if (! visibleEntry) {
                            return;
                        }

                        window.dispatchEvent(
                            new CustomEvent(
                                'store-section',
                                {
                                    detail: visibleEntry.target.id,
                                }
                            )
                        );
                    },
                    {
                        threshold: [
                            0.18,
                            0.35,
                            0.55,
                        ],
                        rootMargin: '-15% 0px -55% 0px',
                    }
                );

                sections.forEach((section) => {
                    window.__storeSectionObserver.observe(section);
                });
            };

            const initializeStoreInterface = () => {
                initializeStoreAnimations();
                initializeStoreSectionObserver();
            };

            document.addEventListener(
                'DOMContentLoaded',
                initializeStoreInterface
            );

            document.addEventListener(
                'livewire:navigated',
                initializeStoreInterface
            );

            document.addEventListener(
                'livewire:init',
                () => {
                    if (! window.Livewire) {
                        return;
                    }

                    Livewire.hook(
                        'morph.updated',
                        () => {
                            requestAnimationFrame(
                                initializeStoreInterface
                            );
                        }
                    );
                }
            );
        })();
    </script>

</body>

</html>
|