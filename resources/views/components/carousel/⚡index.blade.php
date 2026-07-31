<?php

use App\Models\CarouselSlide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

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

        $this->resetPage();
    }

    /**
     * Activa o desactiva una diapositiva.
     */
    public function toggleStatus(int $slideId): void
    {
        $slide = CarouselSlide::query()->findOrFail($slideId);

        $slide->update([
            'is_active' => ! $slide->is_active,
        ]);

        $this->messageType = 'success';

        $this->message = $slide->is_active
            ? 'La diapositiva fue activada correctamente.'
            : 'La diapositiva fue desactivada correctamente.';
    }

    /**
     * Elimina la diapositiva y sus imágenes.
     */
    public function delete(int $slideId): void
    {
        $slide = CarouselSlide::query()->findOrFail($slideId);

        Storage::disk('public')->delete(
            array_filter([
                $slide->desktop_image,
                $slide->mobile_image,
            ])
        );

        $slide->delete();

        $this->messageType = 'success';
        $this->message = 'La diapositiva fue eliminada correctamente.';

        $this->resetPage();
    }

    /**
     * Datos utilizados por la vista.
     */
    public function with(): array
    {
        $search = trim($this->search);
        $now = now();

        return [
            'slides' => CarouselSlide::query()
                ->when(
                    $search !== '',
                    function (Builder $query) use ($search): void {
                        $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where('title', 'like', "%{$search}%")
                                ->orWhere('subtitle', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                )
                ->when(
                    $this->statusFilter === 'visible',
                    function (Builder $query) use ($now): void {
                        $query
                            ->where('is_active', true)
                            ->where(function (Builder $dateQuery) use ($now): void {
                                $dateQuery
                                    ->whereNull('starts_at')
                                    ->orWhere('starts_at', '<=', $now);
                            })
                            ->where(function (Builder $dateQuery) use ($now): void {
                                $dateQuery
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $now);
                            });
                    }
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
                    $this->statusFilter === 'inactive',
                    fn (Builder $query) => $query
                        ->where('is_active', false)
                )
                ->orderBy('sort_order')
                ->latest('id')
                ->paginate((int) $this->perPage),

            'totalSlides' => CarouselSlide::query()->count(),

            'visibleSlides' => CarouselSlide::query()
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
                ->count(),

            'scheduledSlides' => CarouselSlide::query()
                ->where('is_active', true)
                ->where('starts_at', '>', $now)
                ->count(),

            'inactiveSlides' => CarouselSlide::query()
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
                Carrusel
            </flux:heading>

            <flux:text class="mt-1">
                Administra los banners principales y su programación de publicación.
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            :href="route('carousel.create')"
            wire:navigate
        >
            Nueva diapositiva
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
                {{ $totalSlides }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Visibles actualmente
            </p>

            <p class="mt-2 text-3xl font-semibold text-green-600 dark:text-green-400">
                {{ $visibleSlides }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Programadas
            </p>

            <p class="mt-2 text-3xl font-semibold text-blue-600 dark:text-blue-400">
                {{ $scheduledSlides }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Inactivas
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $inactiveSlides }}
            </p>
        </div>

    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        <div class="grid gap-4 border-b border-zinc-200 p-4 md:grid-cols-[minmax(0,1fr)_220px_160px] dark:border-zinc-700">

            <flux:input
                label="Buscar"
                placeholder="Título, subtítulo o descripción..."
                icon="magnifying-glass"
                wire:model.live.debounce.500ms="search"
            />

            <div>
                <label
                    for="carousel-status-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Estado
                </label>

                <select
                    id="carousel-status-filter"
                    wire:model.live="statusFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="">Todos</option>
                    <option value="visible">Visibles</option>
                    <option value="scheduled">Programadas</option>
                    <option value="expired">Finalizadas</option>
                    <option value="inactive">Inactivas</option>
                </select>
            </div>

            <div>
                <label
                    for="carousel-per-page"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Registros
                </label>

                <select
                    id="carousel-per-page"
                    wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            @if ($search !== '' || $statusFilter !== '')
                <div class="md:col-span-3">
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

            @forelse ($slides as $slide)

                <article
                    wire:key="carousel-slide-{{ $slide->id }}"
                    class="grid gap-5 p-4 transition lg:grid-cols-[260px_minmax(0,1fr)_180px_auto] lg:items-center sm:p-5 dark:hover:bg-zinc-800/50"
                >
                    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">

                        <img
                            src="{{ asset('storage/' . $slide->desktop_image) }}"
                            alt="{{ $slide->title }}"
                            class="aspect-video w-full object-cover"
                            loading="lazy"
                        >

                    </div>

                    <div class="min-w-0">

                        <div class="flex flex-wrap items-center gap-2">

                            <h3 class="font-semibold text-zinc-900 dark:text-white">
                                {{ $slide->title }}
                            </h3>

                            @if ($slide->status === 'visible')
                                <flux:badge color="green">
                                    Visible
                                </flux:badge>
                            @elseif ($slide->status === 'scheduled')
                                <flux:badge color="blue">
                                    Programada
                                </flux:badge>
                            @elseif ($slide->status === 'expired')
                                <flux:badge color="amber">
                                    Finalizada
                                </flux:badge>
                            @else
                                <flux:badge color="red">
                                    Inactiva
                                </flux:badge>
                            @endif

                        </div>

                        @if ($slide->subtitle)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $slide->subtitle }}
                            </p>
                        @endif

                        @if ($slide->button_text)
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Botón: {{ $slide->button_text }}
                            </p>
                        @endif

                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            Orden: {{ $slide->sort_order }}
                        </p>

                    </div>

                    <div class="space-y-2 text-sm">

                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Inicio:
                            </span>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $slide->starts_at?->format('d/m/Y H:i') ?? 'Inmediato' }}
                            </p>
                        </div>

                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Final:
                            </span>

                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $slide->ends_at?->format('d/m/Y H:i') ?? 'Sin límite' }}
                            </p>
                        </div>

                    </div>

                    <div class="flex flex-wrap gap-2 lg:justify-end">

                        <flux:button
                            size="sm"
                            icon="pencil-square"
                            :href="route('carousel.edit', $slide)"
                            wire:navigate
                        >
                            Editar
                        </flux:button>

                        <flux:button
                            size="sm"
                            icon="power"
                            wire:click="toggleStatus({{ $slide->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStatus({{ $slide->id }})"
                        >
                            {{ $slide->is_active ? 'Desactivar' : 'Activar' }}
                        </flux:button>

                        <flux:button
                            size="sm"
                            variant="danger"
                            icon="trash"
                            wire:click="delete({{ $slide->id }})"
                            wire:confirm="¿Seguro que deseas eliminar esta diapositiva?"
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $slide->id }})"
                        >
                            Eliminar
                        </flux:button>

                    </div>

                </article>

            @empty

                <div class="px-6 py-14 text-center">

                    <p class="font-medium text-zinc-700 dark:text-zinc-200">
                        No se encontraron diapositivas.
                    </p>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea una diapositiva para comenzar.
                    </p>

                    <flux:button
                        class="mt-5"
                        variant="primary"
                        icon="plus"
                        :href="route('carousel.create')"
                        wire:navigate
                    >
                        Crear diapositiva
                    </flux:button>

                </div>

            @endforelse

        </div>

    </div>

    @if ($slides->hasPages())
        <div>
            {{ $slides->links() }}
        </div>
    @endif

</div>