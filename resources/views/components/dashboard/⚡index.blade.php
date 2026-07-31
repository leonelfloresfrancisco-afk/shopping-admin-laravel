<?php

use App\Models\Brand;
use App\Models\CarouselSlide;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    /**
     * Devuelve todas las estadísticas reales utilizadas por el Dashboard.
     */
    public function with(): array
    {
        $authenticatedUser = auth()->user();

        $company = CompanySetting::current();

        $isAdministrator = $authenticatedUser?->role === 'admin';

        $canManageCatalogs = in_array(
            $authenticatedUser?->role,
            [
                'admin',
                'manager',
            ],
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Resumen general del inventario
        |--------------------------------------------------------------------------
        */

        $productSummary = Product::query()
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw(
                'SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products'
            )
            ->selectRaw(
                'SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_products'
            )
            ->selectRaw(
                'SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock_products'
            )
            ->selectRaw(
                'SUM(CASE WHEN stock BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as low_stock_products'
            )
            ->selectRaw(
                'SUM(CASE WHEN stock > 5 THEN 1 ELSE 0 END) as healthy_stock_products'
            )
            ->selectRaw(
                'SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_products'
            )
            ->selectRaw(
                "SUM(CASE WHEN image IS NULL OR image = '' THEN 1 ELSE 0 END) as products_without_image"
            )
            ->selectRaw(
                'SUM(CASE WHEN brand_id IS NULL THEN 1 ELSE 0 END) as products_without_brand'
            )
            ->selectRaw(
                'COALESCE(SUM(price * stock), 0) as inventory_sale_value'
            )
            ->selectRaw(
                'COALESCE(SUM(cost_price * stock), 0) as inventory_cost_value'
            )
            ->selectRaw(
                'COALESCE(SUM((price - cost_price) * stock), 0) as potential_profit'
            )
            ->first();

        $totalProducts = (int) ($productSummary->total_products ?? 0);

        $activeProducts = (int) (
            $productSummary->active_products ?? 0
        );

        $inactiveProducts = (int) (
            $productSummary->inactive_products ?? 0
        );

        $outOfStockProducts = (int) (
            $productSummary->out_of_stock_products ?? 0
        );

        $lowStockProducts = (int) (
            $productSummary->low_stock_products ?? 0
        );

        $healthyStockProducts = (int) (
            $productSummary->healthy_stock_products ?? 0
        );

        $featuredProducts = (int) (
            $productSummary->featured_products ?? 0
        );

        $productsWithoutImage = (int) (
            $productSummary->products_without_image ?? 0
        );

        $productsWithoutBrand = (int) (
            $productSummary->products_without_brand ?? 0
        );

        $inventorySaleValue = (float) (
            $productSummary->inventory_sale_value ?? 0
        );

        $inventoryCostValue = (float) (
            $productSummary->inventory_cost_value ?? 0
        );

        $potentialProfit = (float) (
            $productSummary->potential_profit ?? 0
        );

        $potentialMargin = $inventorySaleValue > 0
            ? ($potentialProfit / $inventorySaleValue) * 100
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Porcentajes del gráfico de inventario
        |--------------------------------------------------------------------------
        */

        if ($totalProducts > 0) {
            $healthyStockPercentage = round(
                ($healthyStockProducts / $totalProducts) * 100,
                1
            );

            $lowStockPercentage = round(
                ($lowStockProducts / $totalProducts) * 100,
                1
            );

            $outOfStockPercentage = max(
                100 - $healthyStockPercentage - $lowStockPercentage,
                0
            );

            $healthyLimit = $healthyStockPercentage;

            $lowStockLimit = $healthyStockPercentage
                + $lowStockPercentage;

            $inventoryChartStyle = sprintf(
                'background: conic-gradient(#10b981 0%% %s%%, #f59e0b %s%% %s%%, #ef4444 %s%% 100%%);',
                $healthyLimit,
                $healthyLimit,
                $lowStockLimit,
                $lowStockLimit
            );
        } else {
            $healthyStockPercentage = 0;
            $lowStockPercentage = 0;
            $outOfStockPercentage = 0;

            $inventoryChartStyle = 'background: #71717a;';
        }

        $inventoryHealth = $totalProducts > 0
            ? (int) round(
                ($healthyStockProducts / $totalProducts) * 100
            )
            : 100;

        /*
        |--------------------------------------------------------------------------
        | Productos registrados durante los últimos seis meses
        |--------------------------------------------------------------------------
        */

        $growthStartDate = now()
            ->startOfMonth()
            ->subMonths(5);

        $growthResults = Product::query()
            ->selectRaw(
                "DATE_FORMAT(created_at, '%Y-%m') as month_key"
            )
            ->selectRaw('COUNT(*) as total')
            ->where('created_at', '>=', $growthStartDate)
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->pluck('total', 'month_key');

        $spanishMonths = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];

        $productGrowth = collect(
            range(5, 0)
        )->map(function (int $monthsAgo) use (
            $growthResults,
            $spanishMonths
        ): array {
            $date = now()
                ->startOfMonth()
                ->subMonths($monthsAgo);

            $monthKey = $date->format('Y-m');

            return [
                'key' => $monthKey,
                'label' => $spanishMonths[(int) $date->format('n')],
                'year' => $date->format('Y'),
                'total' => (int) (
                    $growthResults[$monthKey] ?? 0
                ),
            ];
        });

        $maximumMonthlyProducts = max(
            (int) $productGrowth->max('total'),
            1
        );

        /*
        |--------------------------------------------------------------------------
        | Productos por categoría
        |--------------------------------------------------------------------------
        */

        $topCategories = DB::table('categories')
            ->leftJoin(
                'products',
                'categories.id',
                '=',
                'products.category_id'
            )
            ->select([
                'categories.id',
                'categories.name',
            ])
            ->selectRaw(
                'COUNT(products.id) as products_count'
            )
            ->groupBy([
                'categories.id',
                'categories.name',
            ])
            ->orderByDesc('products_count')
            ->orderBy('categories.name')
            ->limit(6)
            ->get();

        $maximumCategoryProducts = max(
            (int) $topCategories->max('products_count'),
            1
        );

        /*
        |--------------------------------------------------------------------------
        | Productos por marca
        |--------------------------------------------------------------------------
        */

        $topBrands = DB::table('brands')
            ->leftJoin(
                'products',
                'brands.id',
                '=',
                'products.brand_id'
            )
            ->select([
                'brands.id',
                'brands.name',
            ])
            ->selectRaw(
                'COUNT(products.id) as products_count'
            )
            ->groupBy([
                'brands.id',
                'brands.name',
            ])
            ->orderByDesc('products_count')
            ->orderBy('brands.name')
            ->limit(6)
            ->get();

        $maximumBrandProducts = max(
            (int) $topBrands->max('products_count'),
            1
        );

        /*
        |--------------------------------------------------------------------------
        | Contenido activo
        |--------------------------------------------------------------------------
        */

        $now = now();

        $visibleCarouselSlides = CarouselSlide::query()
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
            ->count();

        $scheduledPromotions = Promotion::query()
            ->where('is_active', true)
            ->where('starts_at', '>', $now)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Moneda
        |--------------------------------------------------------------------------
        */

        $currencySymbol = match ($company->currency_code) {
            'USD' => '$',
            'EUR' => '€',
            default => 'S/',
        };

        return [
            /*
            |--------------------------------------------------------------------------
            | Sesión y permisos
            |--------------------------------------------------------------------------
            */

            'company' => $company,

            'authenticatedUser' => $authenticatedUser,

            'isAdministrator' => $isAdministrator,

            'canManageCatalogs' => $canManageCatalogs,

            'currencySymbol' => $currencySymbol,

            /*
            |--------------------------------------------------------------------------
            | Inventario
            |--------------------------------------------------------------------------
            */

            'totalProducts' => $totalProducts,

            'activeProducts' => $activeProducts,

            'inactiveProducts' => $inactiveProducts,

            'outOfStockProducts' => $outOfStockProducts,

            'lowStockProducts' => $lowStockProducts,

            'healthyStockProducts' => $healthyStockProducts,

            'featuredProducts' => $featuredProducts,

            'productsWithoutImage' => $productsWithoutImage,

            'productsWithoutBrand' => $productsWithoutBrand,

            'inventorySaleValue' => $inventorySaleValue,

            'inventoryCostValue' => $inventoryCostValue,

            'potentialProfit' => $potentialProfit,

            'potentialMargin' => $potentialMargin,

            'inventoryHealth' => $inventoryHealth,

            /*
            |--------------------------------------------------------------------------
            | Gráfico de inventario
            |--------------------------------------------------------------------------
            */

            'healthyStockPercentage' => $healthyStockPercentage,

            'lowStockPercentage' => $lowStockPercentage,

            'outOfStockPercentage' => $outOfStockPercentage,

            'inventoryChartStyle' => $inventoryChartStyle,

            /*
            |--------------------------------------------------------------------------
            | Gráficos estadísticos
            |--------------------------------------------------------------------------
            */

            'productGrowth' => $productGrowth,

            'maximumMonthlyProducts' => $maximumMonthlyProducts,

            'topCategories' => $topCategories,

            'maximumCategoryProducts' => $maximumCategoryProducts,

            'topBrands' => $topBrands,

            'maximumBrandProducts' => $maximumBrandProducts,

            /*
            |--------------------------------------------------------------------------
            | Catálogo y contenido
            |--------------------------------------------------------------------------
            */

            'totalCategories' => Category::query()->count(),

            'activeCategories' => Category::query()
                ->where('is_active', true)
                ->count(),

            'totalBrands' => Brand::query()->count(),

            'activeBrands' => Brand::query()
                ->where('is_active', true)
                ->count(),

            'activePromotions' => Promotion::query()
                ->currentlyActive()
                ->count(),

            'scheduledPromotions' => $scheduledPromotions,

            'visibleCarouselSlides' => $visibleCarouselSlides,

            'activeUsers' => $isAdministrator
                ? User::query()
                    ->where('is_active', true)
                    ->count()
                : null,

            /*
            |--------------------------------------------------------------------------
            | Listados
            |--------------------------------------------------------------------------
            */

            'latestProducts' => Product::query()
                ->with([
                    'category',
                    'brand',
                ])
                ->latest('id')
                ->limit(6)
                ->get(),

            'stockAlerts' => Product::query()
                ->with([
                    'category',
                    'brand',
                ])
                ->where('is_active', true)
                ->where('stock', '<=', 5)
                ->orderBy('stock')
                ->orderBy('name')
                ->limit(6)
                ->get(),
        ];
    }
};

?>

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">

        <div>
            <flux:heading size="xl">
                Dashboard
            </flux:heading>

            <flux:text class="mt-1">
                Bienvenido, {{ $authenticatedUser->name }}. Este es el estado actual de {{ $company->trade_name }}.
            </flux:text>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">

            <flux:button
                icon="plus"
                :href="route('products.create')"
                wire:navigate
            >
                Nuevo producto
            </flux:button>

            @if ($canManageCatalogs)
                <flux:button
                    variant="primary"
                    icon="tag"
                    :href="route('categories.create')"
                    wire:navigate
                >
                    Nueva categoría
                </flux:button>
            @endif

        </div>

    </div>

    {{-- Indicadores principales --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total de productos
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $totalProducts }}
            </p>

            <div class="mt-4 flex flex-wrap gap-2">

                <flux:badge color="green">
                    {{ $activeProducts }} activos
                </flux:badge>

                @if ($inactiveProducts > 0)
                    <flux:badge color="red">
                        {{ $inactiveProducts }} inactivos
                    </flux:badge>
                @endif

            </div>

        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Valor de venta del inventario
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $currencySymbol }}
                {{ number_format($inventorySaleValue, 2) }}
            </p>

            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                Costo:
                {{ $currencySymbol }}
                {{ number_format($inventoryCostValue, 2) }}
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
                {{ $currencySymbol }}
                {{ number_format($potentialProfit, 2) }}
            </p>

            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                Margen estimado:
                {{ number_format($potentialMargin, 1) }}%
            </p>

        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Salud del inventario
            </p>

            <p
                @class([
                    'mt-2 text-3xl font-semibold',
                    'text-green-600 dark:text-green-400' => $inventoryHealth >= 80,
                    'text-amber-600 dark:text-amber-400' => $inventoryHealth >= 50 && $inventoryHealth < 80,
                    'text-red-600 dark:text-red-400' => $inventoryHealth < 50,
                ])
            >
                {{ $inventoryHealth }}%
            </p>

            <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">

                <div
                    @class([
                        'h-full rounded-full',
                        'bg-green-500' => $inventoryHealth >= 80,
                        'bg-amber-500' => $inventoryHealth >= 50 && $inventoryHealth < 80,
                        'bg-red-500' => $inventoryHealth < 50,
                    ])
                    style="width: {{ $inventoryHealth }}%"
                ></div>

            </div>

        </div>

    </div>

    {{-- Estadísticas visuales --}}
    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Distribución del inventario --}}
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Distribución del inventario
                </flux:heading>

                <flux:text class="mt-1">
                    Estado actual de las existencias.
                </flux:text>
            </div>

            <div class="mt-6 flex flex-col items-center">

                <div
                    class="relative flex h-48 w-48 items-center justify-center rounded-full"
                    style="{{ $inventoryChartStyle }}"
                >
                    <div class="flex h-28 w-28 flex-col items-center justify-center rounded-full bg-white dark:bg-zinc-900">

                        <span class="text-3xl font-semibold text-zinc-900 dark:text-white">
                            {{ $totalProducts }}
                        </span>

                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            productos
                        </span>

                    </div>
                </div>

                <div class="mt-6 grid w-full gap-3">

                    <div class="flex items-center justify-between gap-4">

                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-green-500"></span>

                            <span class="text-sm text-zinc-600 dark:text-zinc-300">
                                Stock saludable
                            </span>
                        </div>

                        <span class="font-semibold text-zinc-900 dark:text-white">
                            {{ $healthyStockProducts }}
                            ·
                            {{ number_format($healthyStockPercentage, 1) }}%
                        </span>

                    </div>

                    <div class="flex items-center justify-between gap-4">

                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>

                            <span class="text-sm text-zinc-600 dark:text-zinc-300">
                                Stock bajo
                            </span>
                        </div>

                        <span class="font-semibold text-zinc-900 dark:text-white">
                            {{ $lowStockProducts }}
                            ·
                            {{ number_format($lowStockPercentage, 1) }}%
                        </span>

                    </div>

                    <div class="flex items-center justify-between gap-4">

                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-red-500"></span>

                            <span class="text-sm text-zinc-600 dark:text-zinc-300">
                                Agotados
                            </span>
                        </div>

                        <span class="font-semibold text-zinc-900 dark:text-white">
                            {{ $outOfStockProducts }}
                            ·
                            {{ number_format($outOfStockPercentage, 1) }}%
                        </span>

                    </div>

                </div>

            </div>

        </section>

        {{-- Productos registrados por mes --}}
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Crecimiento del catálogo
                </flux:heading>

                <flux:text class="mt-1">
                    Productos registrados durante los últimos seis meses.
                </flux:text>
            </div>

            <div class="mt-8 flex h-72 items-end gap-2 sm:gap-4">

                @foreach ($productGrowth as $month)

                    @php
                        $monthlyHeight = $month['total'] > 0
                            ? max(
                                ($month['total'] / $maximumMonthlyProducts) * 100,
                                8
                            )
                            : 0;
                    @endphp

                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-3">

                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ $month['total'] }}
                        </span>

                        <div class="flex h-52 w-full items-end rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">

                            <div
                                class="w-full rounded-lg bg-zinc-900 transition-all dark:bg-white"
                                style="height: {{ $monthlyHeight }}%"
                                title="{{ $month['total'] }} productos"
                            ></div>

                        </div>

                        <div class="text-center">

                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ $month['label'] }}
                            </p>

                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $month['year'] }}
                            </p>

                        </div>

                    </div>

                @endforeach

            </div>

        </section>

    </div>

    {{-- Distribución por categorías y marcas --}}
    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Categorías --}}
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-700 dark:bg-zinc-900">

            <div class="flex items-start justify-between gap-4">

                <div>
                    <flux:heading size="lg">
                        Productos por categoría
                    </flux:heading>

                    <flux:text class="mt-1">
                        Categorías con mayor cantidad de productos.
                    </flux:text>
                </div>

                @if ($canManageCatalogs)
                    <flux:button
                        size="sm"
                        :href="route('categories.index')"
                        wire:navigate
                    >
                        Ver categorías
                    </flux:button>
                @endif

            </div>

            <div class="mt-6 space-y-5">

                @forelse ($topCategories as $category)

                    @php
                        $categoryWidth = (
                            (int) $category->products_count
                            / $maximumCategoryProducts
                        ) * 100;
                    @endphp

                    <div>

                        <div class="mb-2 flex items-center justify-between gap-4">

                            <span class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ $category->name }}
                            </span>

                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $category->products_count }}
                            </span>

                        </div>

                        <div class="h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">

                            <div
                                class="h-full rounded-full bg-zinc-900 dark:bg-white"
                                style="width: {{ $categoryWidth }}%"
                            ></div>

                        </div>

                    </div>

                @empty

                    <div class="py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        No existen categorías registradas.
                    </div>

                @endforelse

            </div>

        </section>

        {{-- Marcas --}}
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-700 dark:bg-zinc-900">

            <div class="flex items-start justify-between gap-4">

                <div>
                    <flux:heading size="lg">
                        Productos por marca
                    </flux:heading>

                    <flux:text class="mt-1">
                        Marcas con mayor presencia en el catálogo.
                    </flux:text>
                </div>

                @if ($canManageCatalogs)
                    <flux:button
                        size="sm"
                        :href="route('brands.index')"
                        wire:navigate
                    >
                        Ver marcas
                    </flux:button>
                @endif

            </div>

            <div class="mt-6 space-y-5">

                @forelse ($topBrands as $brand)

                    @php
                        $brandWidth = (
                            (int) $brand->products_count
                            / $maximumBrandProducts
                        ) * 100;
                    @endphp

                    <div>

                        <div class="mb-2 flex items-center justify-between gap-4">

                            <span class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ $brand->name }}
                            </span>

                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $brand->products_count }}
                            </span>

                        </div>

                        <div class="h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">

                            <div
                                class="h-full rounded-full bg-zinc-600 dark:bg-zinc-300"
                                style="width: {{ $brandWidth }}%"
                            ></div>

                        </div>

                    </div>

                @empty

                    <div class="py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        No existen marcas registradas.
                    </div>

                @endforelse

            </div>

        </section>

    </div>

    {{-- Calidad del catálogo --}}
    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-700 dark:bg-zinc-900">

        <div>
            <flux:heading size="lg">
                Calidad del catálogo
            </flux:heading>

            <flux:text class="mt-1">
                Información que ayuda a mantener los productos completos y listos para la tienda.
            </flux:text>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Sin imagen
                </p>

                <p
                    @class([
                        'mt-2 text-2xl font-semibold',
                        'text-green-600 dark:text-green-400' => $productsWithoutImage === 0,
                        'text-amber-600 dark:text-amber-400' => $productsWithoutImage > 0,
                    ])
                >
                    {{ $productsWithoutImage }}
                </p>

            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Sin marca
                </p>

                <p
                    @class([
                        'mt-2 text-2xl font-semibold',
                        'text-green-600 dark:text-green-400' => $productsWithoutBrand === 0,
                        'text-amber-600 dark:text-amber-400' => $productsWithoutBrand > 0,
                    ])
                >
                    {{ $productsWithoutBrand }}
                </p>

            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Destacados
                </p>

                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                    {{ $featuredProducts }}
                </p>

            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Productos inactivos
                </p>

                <p
                    @class([
                        'mt-2 text-2xl font-semibold',
                        'text-green-600 dark:text-green-400' => $inactiveProducts === 0,
                        'text-red-600 dark:text-red-400' => $inactiveProducts > 0,
                    ])
                >
                    {{ $inactiveProducts }}
                </p>

            </div>

        </div>

    </section>

    {{-- Estado de módulos --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Categorías activas
            </p>

            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ $activeCategories }}
                /
                {{ $totalCategories }}
            </p>

        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Marcas activas
            </p>

            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ $activeBrands }}
                /
                {{ $totalBrands }}
            </p>

        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Promociones
            </p>

            <p class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                {{ $activePromotions }} activas
            </p>

            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $scheduledPromotions }} programadas
            </p>

        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Banners visibles
            </p>

            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ $visibleCarouselSlides }}
            </p>

            @if ($isAdministrator)
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $activeUsers }} usuarios activos
                </p>
            @endif

        </div>

    </div>

    {{-- Productos recientes y alertas --}}
    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Productos recientes --}}
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 p-5 dark:border-zinc-700">

                <div>
                    <flux:heading size="lg">
                        Productos recientes
                    </flux:heading>

                    <flux:text class="mt-1">
                        Últimos productos registrados.
                    </flux:text>
                </div>

                <flux:button
                    size="sm"
                    :href="route('products.index')"
                    wire:navigate
                >
                    Ver todos
                </flux:button>

            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">

                @forelse ($latestProducts as $product)

                    <a
                        wire:key="latest-product-{{ $product->id }}"
                        href="{{ route('products.edit', $product) }}"
                        wire:navigate
                        class="flex items-center gap-4 p-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60"
                    >

                        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">

                            @if ($product->image)
                                <img
                                    src="{{ asset('storage/' . $product->image) }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                <span class="px-2 text-center text-[10px] text-zinc-500 dark:text-zinc-400">
                                    Sin imagen
                                </span>
                            @endif

                        </div>

                        <div class="min-w-0 flex-1">

                            <p class="truncate font-semibold text-zinc-900 dark:text-white">
                                {{ $product->name }}
                            </p>

                            <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $product->category?->name ?? 'Sin categoría' }}
                                ·
                                {{ $product->brand?->name ?? 'Sin marca' }}
                            </p>

                            <p class="mt-1 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ $currencySymbol }}
                                {{ number_format((float) $product->price, 2) }}
                            </p>

                        </div>

                        <div class="text-right">

                            <p
                                @class([
                                    'font-semibold',
                                    'text-red-600 dark:text-red-400' => $product->stock <= 5,
                                    'text-zinc-900 dark:text-white' => $product->stock > 5,
                                ])
                            >
                                {{ $product->stock }}
                            </p>

                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                unidades
                            </p>

                        </div>

                    </a>

                @empty

                    <div class="px-6 py-12 text-center">

                        <p class="font-medium text-zinc-700 dark:text-zinc-200">
                            Todavía no existen productos.
                        </p>

                        <flux:button
                            class="mt-4"
                            variant="primary"
                            :href="route('products.create')"
                            wire:navigate
                        >
                            Crear producto
                        </flux:button>

                    </div>

                @endforelse

            </div>

        </section>

        {{-- Alertas de inventario --}}
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 p-5 dark:border-zinc-700">

                <div>
                    <flux:heading size="lg">
                        Alertas de inventario
                    </flux:heading>

                    <flux:text class="mt-1">
                        Productos agotados o con cinco unidades o menos.
                    </flux:text>
                </div>

                <flux:badge
                    :color="$stockAlerts->isEmpty() ? 'green' : 'red'"
                >
                    {{ $stockAlerts->count() }}
                </flux:badge>

            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">

                @forelse ($stockAlerts as $product)

                    <a
                        wire:key="stock-alert-{{ $product->id }}"
                        href="{{ route('products.edit', $product) }}"
                        wire:navigate
                        class="flex items-center justify-between gap-4 p-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60"
                    >

                        <div class="min-w-0">

                            <p class="truncate font-semibold text-zinc-900 dark:text-white">
                                {{ $product->name }}
                            </p>

                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                SKU: {{ $product->sku }}
                            </p>

                            <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $product->category?->name ?? 'Sin categoría' }}
                                ·
                                {{ $product->brand?->name ?? 'Sin marca' }}
                            </p>

                        </div>

                        @if ($product->stock === 0)
                            <flux:badge color="red">
                                Agotado
                            </flux:badge>
                        @else
                            <flux:badge color="amber">
                                {{ $product->stock }} disponibles
                            </flux:badge>
                        @endif

                    </a>

                @empty

                    <div class="px-6 py-12 text-center">

                        <p class="font-medium text-green-700 dark:text-green-300">
                            No existen alertas de inventario.
                        </p>

                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Todos los productos tienen existencias suficientes.
                        </p>

                    </div>

                @endforelse

            </div>

        </section>

    </div>

</div>