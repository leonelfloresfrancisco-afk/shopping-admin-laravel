<?php

use App\Models\Brand;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $perPage = '10';

    public ?string $message = null;

    public string $messageType = 'success';

    /**
     * Reinicia la paginación al buscar.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Valida la cantidad de registros por página.
     */
    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, ['10', '25', '50'], true)) {
            $this->perPage = '10';
        }

        $this->resetPage();
    }

    /**
     * Activa o desactiva una marca.
     */
    public function toggleStatus(int $brandId): void
    {
        $brand = Brand::query()->findOrFail($brandId);

        $brand->update([
            'is_active' => ! $brand->is_active,
        ]);

        $this->messageType = 'success';

        $this->message = $brand->is_active
            ? 'La marca fue activada correctamente.'
            : 'La marca fue desactivada correctamente.';
    }

    /**
     * Elimina una marca que no tenga productos asociados.
     */
    public function delete(int $brandId): void
    {
        $brand = Brand::query()->findOrFail($brandId);

        if ($brand->products()->exists()) {
            $this->messageType = 'error';
            $this->message = 'No se puede eliminar la marca porque tiene productos asociados.';

            return;
        }

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        $this->messageType = 'success';
        $this->message = 'La marca fue eliminada correctamente.';

        $this->resetPage();
    }

    /**
     * Datos enviados a la interfaz.
     */
    public function with(): array
    {
        $search = trim($this->search);

        return [
            'brands' => Brand::query()
                ->withCount('products')
                ->when(
                    $search !== '',
                    function ($query) use ($search): void {
                        $query->where(function ($subQuery) use ($search): void {
                            $subQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                )
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate((int) $this->perPage),

            'totalBrands' => Brand::query()->count(),

            'activeBrands' => Brand::query()
                ->where('is_active', true)
                ->count(),
        ];
    }
};

?>

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Marcas
            </flux:heading>

            <flux:text class="mt-1">
                Administra los fabricantes y marcas disponibles en el catálogo.
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            :href="route('brands.create')"
            wire:navigate
        >
            Nueva marca
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

    <div class="grid gap-4 sm:grid-cols-2">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total de marcas
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $totalBrands }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Marcas activas
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $activeBrands }}
            </p>
        </div>

    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        <div class="grid gap-4 border-b border-zinc-200 p-4 sm:grid-cols-[minmax(0,1fr)_180px] dark:border-zinc-700">

            <flux:input
                label="Buscar marcas"
                placeholder="Nombre, slug o descripción..."
                icon="magnifying-glass"
                wire:model.live.debounce.500ms="search"
            />

            <div>
                <label
                    for="brands-per-page"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Registros
                </label>

                <select
                    id="brands-per-page"
                    wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="10">10 registros</option>
                    <option value="25">25 registros</option>
                    <option value="50">50 registros</option>
                </select>
            </div>

        </div>

        {{-- Vista móvil --}}
        <div class="divide-y divide-zinc-200 md:hidden dark:divide-zinc-700">

            @forelse ($brands as $brand)

                <article
                    wire:key="brand-mobile-{{ $brand->id }}"
                    class="space-y-4 p-4"
                >
                    <div class="flex items-start gap-4">

                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                            @if ($brand->logo)
                                <img
                                    src="{{ $brand->logo_url }}"
                                    alt="{{ $brand->name }}"
                                    class="h-full w-full object-contain"
                                    loading="lazy"
                                >
                            @else
                                <span class="text-xs text-zinc-500">
                                    Sin logo
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">
                                {{ $brand->name }}
                            </h3>

                            <p class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $brand->slug }}
                            </p>

                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $brand->products_count }} productos
                            </p>
                        </div>

                        @if ($brand->is_active)
                            <flux:badge color="green">
                                Activa
                            </flux:badge>
                        @else
                            <flux:badge color="red">
                                Inactiva
                            </flux:badge>
                        @endif

                    </div>

                    <div class="grid gap-2 sm:grid-cols-3">

                        <flux:button
                            size="sm"
                            icon="pencil-square"
                            :href="route('brands.edit', $brand)"
                            wire:navigate
                        >
                            Editar
                        </flux:button>

                        <flux:button
                            size="sm"
                            wire:click="toggleStatus({{ $brand->id }})"
                            wire:loading.attr="disabled"
                        >
                            {{ $brand->is_active ? 'Desactivar' : 'Activar' }}
                        </flux:button>

                        <flux:button
                            size="sm"
                            variant="danger"
                            icon="trash"
                            wire:click="delete({{ $brand->id }})"
                            wire:confirm="¿Seguro que deseas eliminar esta marca?"
                            wire:loading.attr="disabled"
                        >
                            Eliminar
                        </flux:button>

                    </div>

                </article>

            @empty

                <div class="px-6 py-14 text-center">
                    <p class="font-medium text-zinc-700 dark:text-zinc-200">
                        No se encontraron marcas.
                    </p>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea una marca para comenzar.
                    </p>
                </div>

            @endforelse

        </div>

        {{-- Vista de escritorio --}}
        <div class="hidden overflow-x-auto md:block">

            <table class="w-full min-w-200 text-sm">

                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/70">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Marca
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Estado
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-zinc-700 dark:text-zinc-200">
                            Orden
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-zinc-700 dark:text-zinc-200">
                            Productos
                        </th>

                        <th class="px-6 py-4 text-right font-semibold text-zinc-700 dark:text-zinc-200">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse ($brands as $brand)

                        <tr
                            wire:key="brand-desktop-{{ $brand->id }}"
                            class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                        >
                            <td class="px-6 py-4">

                                <div class="flex items-center gap-4">

                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                                        @if ($brand->logo)
                                            <img
                                                src="{{ $brand->logo_url }}"
                                                alt="{{ $brand->name }}"
                                                class="h-full w-full object-contain"
                                                loading="lazy"
                                            >
                                        @else
                                            <span class="text-[10px] text-zinc-500">
                                                Sin logo
                                            </span>
                                        @endif
                                    </div>

                                    <div>
                                        <p class="font-semibold text-zinc-900 dark:text-white">
                                            {{ $brand->name }}
                                        </p>

                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $brand->slug }}
                                        </p>

                                        @if ($brand->website)
                                            <p class="mt-1 max-w-72 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $brand->website }}
                                            </p>
                                        @endif
                                    </div>

                                </div>

                            </td>

                            <td class="px-6 py-4">
                                @if ($brand->is_active)
                                    <flux:badge color="green">
                                        Activa
                                    </flux:badge>
                                @else
                                    <flux:badge color="red">
                                        Inactiva
                                    </flux:badge>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center font-medium text-zinc-700 dark:text-zinc-200">
                                {{ $brand->sort_order }}
                            </td>

                            <td class="px-6 py-4 text-center font-medium text-zinc-700 dark:text-zinc-200">
                                {{ $brand->products_count }}
                            </td>

                            <td class="px-6 py-4">

                                <div class="flex flex-wrap justify-end gap-2">

                                    <flux:button
                                        size="sm"
                                        icon="pencil-square"
                                        :href="route('brands.edit', $brand)"
                                        wire:navigate
                                    >
                                        Editar
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        wire:click="toggleStatus({{ $brand->id }})"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ $brand->is_active ? 'Desactivar' : 'Activar' }}
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="delete({{ $brand->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar esta marca?"
                                        wire:loading.attr="disabled"
                                    >
                                        Eliminar
                                    </flux:button>

                                </div>

                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="5"
                                class="px-6 py-14 text-center"
                            >
                                <p class="font-medium text-zinc-700 dark:text-zinc-200">
                                    No se encontraron marcas.
                                </p>

                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    Crea una marca para comenzar.
                                </p>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if ($brands->hasPages())
        <div>
            {{ $brands->links() }}
        </div>
    @endif

</div>