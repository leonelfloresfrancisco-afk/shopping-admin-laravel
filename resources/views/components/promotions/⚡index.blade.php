<?php

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public string $scopeFilter = '';

    public string $perPage = '10';

    public ?string $message = null;

    public string $messageType = 'success';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, ['10', '25', '50'], true)) {
            $this->perPage = '10';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->scopeFilter = '';

        $this->resetPage();
    }

    /**
     * Activa o desactiva una promoción.
     */
    public function toggleStatus(int $promotionId): void
    {
        $promotion = Promotion::query()->findOrFail($promotionId);

        $promotion->update([
            'is_active' => ! $promotion->is_active,
        ]);

        $this->messageType = 'success';

        $this->message = $promotion->is_active
            ? 'La promoción fue activada correctamente.'
            : 'La promoción fue desactivada correctamente.';
    }

    /**
     * Elimina una promoción y sus relaciones.
     */
    public function delete(int $promotionId): void
    {
        $promotion = Promotion::query()->findOrFail($promotionId);

        $promotion->delete();

        $this->messageType = 'success';
        $this->message = 'La promoción fue eliminada correctamente.';

        $this->resetPage();
    }

    /**
     * Datos enviados a la interfaz.
     */
    public function with(): array
    {
        $search = trim($this->search);
        $now = now();

        return [
            'promotions' => Promotion::query()
                ->withCount([
                    'categories',
                    'products',
                ])
                ->when(
                    $search !== '',
                    function (Builder $query) use ($search): void {
                        $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                )
                ->when(
                    $this->typeFilter !== '',
                    fn (Builder $query) => $query->where(
                        'discount_type',
                        $this->typeFilter
                    )
                )
                ->when(
                    $this->scopeFilter !== '',
                    fn (Builder $query) => $query->where(
                        'applies_to',
                        $this->scopeFilter
                    )
                )
                ->when(
                    $this->statusFilter === 'active',
                    fn (Builder $query) => $query->currentlyActive()
                )
                ->when(
                    $this->statusFilter === 'scheduled',
                    fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->where('starts_at', '>', $now)
                )
                ->when(
                    $this->statusFilter === 'expired',
                    fn (Builder $query) => $query
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '<', $now)
                )
                ->when(
                    $this->statusFilter === 'exhausted',
                    fn (Builder $query) => $query
                        ->whereNotNull('usage_limit')
                        ->whereColumn('used_count', '>=', 'usage_limit')
                )
                ->when(
                    $this->statusFilter === 'inactive',
                    fn (Builder $query) => $query
                        ->where('is_active', false)
                )
                ->latest('id')
                ->paginate((int) $this->perPage),

            'totalPromotions' => Promotion::query()->count(),

            'activePromotions' => Promotion::query()
                ->currentlyActive()
                ->count(),

            'scheduledPromotions' => Promotion::query()
                ->where('is_active', true)
                ->where('starts_at', '>', $now)
                ->count(),

            'inactivePromotions' => Promotion::query()
                ->where('is_active', false)
                ->count(),
        ];
    }
};

?>

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Promociones
            </flux:heading>

            <flux:text class="mt-1">
                Administra descuentos globales, por categorías o por productos.
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            :href="route('promotions.create')"
            wire:navigate
        >
            Nueva promoción
        </flux:button>

    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($message)
        <div
            @class([
                'rounded-xl border px-4 py-3 text-sm font-medium',
                'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200' => $messageType === 'success',
                'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200' => $messageType === 'error',
            ])
        >
            {{ $message }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $totalPromotions }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Activas actualmente
            </p>

            <p class="mt-2 text-3xl font-semibold text-green-600 dark:text-green-400">
                {{ $activePromotions }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Programadas
            </p>

            <p class="mt-2 text-3xl font-semibold text-blue-600 dark:text-blue-400">
                {{ $scheduledPromotions }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Inactivas
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $inactivePromotions }}
            </p>
        </div>

    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        <div class="grid gap-4 border-b border-zinc-200 p-4 md:grid-cols-2 xl:grid-cols-6 dark:border-zinc-700">

            <div class="md:col-span-2">
                <flux:input
                    label="Buscar promociones"
                    placeholder="Nombre, código o descripción..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.500ms="search"
                />
            </div>

            <div>
                <label
                    for="promotion-status-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Estado
                </label>

                <select
                    id="promotion-status-filter"
                    wire:model.live="statusFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="">Todos</option>
                    <option value="active">Activas</option>
                    <option value="scheduled">Programadas</option>
                    <option value="expired">Finalizadas</option>
                    <option value="exhausted">Agotadas</option>
                    <option value="inactive">Inactivas</option>
                </select>
            </div>

            <div>
                <label
                    for="promotion-type-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Tipo
                </label>

                <select
                    id="promotion-type-filter"
                    wire:model.live="typeFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="">Todos</option>
                    <option value="percentage">Porcentaje</option>
                    <option value="fixed">Monto fijo</option>
                </select>
            </div>

            <div>
                <label
                    for="promotion-scope-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Alcance
                </label>

                <select
                    id="promotion-scope-filter"
                    wire:model.live="scopeFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="">Todos</option>
                    <option value="all">Toda la tienda</option>
                    <option value="categories">Categorías</option>
                    <option value="products">Productos</option>
                </select>
            </div>

            <div>
                <label
                    for="promotions-per-page"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Registros
                </label>

                <select
                    id="promotions-per-page"
                    wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            @if (
                $search !== ''
                || $statusFilter !== ''
                || $typeFilter !== ''
                || $scopeFilter !== ''
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

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">

            @forelse ($promotions as $promotion)

                <article
                    wire:key="promotion-{{ $promotion->id }}"
                    class="grid gap-5 p-4 transition lg:grid-cols-[minmax(0,2fr)_150px_190px_180px_auto] lg:items-center sm:p-5 dark:hover:bg-zinc-800/50"
                >
                    <div class="min-w-0">

                        <div class="flex flex-wrap items-center gap-2">

                            <h3 class="font-semibold text-zinc-900 dark:text-white">
                                {{ $promotion->name }}
                            </h3>

                            @if ($promotion->status === 'active')
                                <flux:badge color="green">
                                    Activa
                                </flux:badge>
                            @elseif ($promotion->status === 'scheduled')
                                <flux:badge color="blue">
                                    Programada
                                </flux:badge>
                            @elseif ($promotion->status === 'expired')
                                <flux:badge color="amber">
                                    Finalizada
                                </flux:badge>
                            @elseif ($promotion->status === 'exhausted')
                                <flux:badge color="amber">
                                    Agotada
                                </flux:badge>
                            @else
                                <flux:badge color="red">
                                    Inactiva
                                </flux:badge>
                            @endif

                        </div>

                        @if ($promotion->code)
                            <p class="mt-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                Código: {{ $promotion->code }}
                            </p>
                        @else
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                Aplicación automática
                            </p>
                        @endif

                        @if ($promotion->description)
                            <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $promotion->description }}
                            </p>
                        @endif

                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Descuento
                        </p>

                        <p class="mt-1 text-xl font-semibold text-green-600 dark:text-green-400">
                            {{ $promotion->discount_label }}
                        </p>

                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Compra mínima:
                            S/ {{ number_format((float) $promotion->minimum_purchase, 2) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Alcance
                        </p>

                        @if ($promotion->applies_to === 'all')
                            <p class="mt-1 font-medium text-zinc-900 dark:text-white">
                                Toda la tienda
                            </p>
                        @elseif ($promotion->applies_to === 'categories')
                            <p class="mt-1 font-medium text-zinc-900 dark:text-white">
                                {{ $promotion->categories_count }} categorías
                            </p>
                        @else
                            <p class="mt-1 font-medium text-zinc-900 dark:text-white">
                                {{ $promotion->products_count }} productos
                            </p>
                        @endif

                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            Usos:
                            {{ $promotion->used_count }}
                            /
                            {{ $promotion->usage_limit ?? 'Sin límite' }}
                        </p>
                    </div>

                    <div class="space-y-2 text-sm">

                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Inicio:
                            </span>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $promotion->starts_at?->format('d/m/Y H:i') ?? 'Inmediato' }}
                            </p>
                        </div>

                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Final:
                            </span>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $promotion->ends_at?->format('d/m/Y H:i') ?? 'Sin límite' }}
                            </p>
                        </div>

                    </div>

                    <div class="flex flex-wrap gap-2 lg:justify-end">

                        <flux:button
                            size="sm"
                            icon="pencil-square"
                            :href="route('promotions.edit', $promotion)"
                            wire:navigate
                        >
                            Editar
                        </flux:button>

                        <flux:button
                            size="sm"
                            icon="power"
                            wire:click="toggleStatus({{ $promotion->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStatus({{ $promotion->id }})"
                        >
                            {{ $promotion->is_active ? 'Desactivar' : 'Activar' }}
                        </flux:button>

                        <flux:button
                            size="sm"
                            variant="danger"
                            icon="trash"
                            wire:click="delete({{ $promotion->id }})"
                            wire:confirm="¿Seguro que deseas eliminar esta promoción?"
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $promotion->id }})"
                        >
                            Eliminar
                        </flux:button>

                    </div>

                </article>

            @empty

                <div class="px-6 py-14 text-center">

                    <p class="font-medium text-zinc-700 dark:text-zinc-200">
                        No se encontraron promociones.
                    </p>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea una promoción para comenzar.
                    </p>

                    <flux:button
                        class="mt-5"
                        variant="primary"
                        icon="plus"
                        :href="route('promotions.create')"
                        wire:navigate
                    >
                        Crear promoción
                    </flux:button>

                </div>

            @endforelse

        </div>

    </div>

    @if ($promotions->hasPages())
        <div>
            {{ $promotions->links() }}
        </div>
    @endif

</div>