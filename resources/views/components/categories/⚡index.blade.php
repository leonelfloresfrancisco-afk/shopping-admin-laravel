<?php

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $perPage = '10';

    public ?string $feedback = null;

    public string $feedbackType = 'success';

    /**
     * Reinicia la paginación cuando cambia la búsqueda.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando cambia la cantidad de registros.
     */
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * NUEVO: activa o desactiva una categoría.
     */
    public function toggleStatus(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        $this->feedbackType = 'success';

        $this->feedback = $category->is_active
            ? 'La categoría fue activada correctamente.'
            : 'La categoría fue desactivada correctamente.';
    }

    /**
     * NUEVO: elimina una categoría sin productos asociados.
     */
    public function delete(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $hasProducts = Product::query()
            ->where('category_id', $category->id)
            ->exists();

        if ($hasProducts) {
            $this->feedbackType = 'error';
            $this->feedback = 'No se puede eliminar la categoría porque tiene productos asociados.';

            return;
        }

        $category->delete();

        $this->feedbackType = 'success';
        $this->feedback = 'La categoría fue eliminada correctamente.';

        $this->resetPage();
    }

    /**
     * Datos utilizados por la vista.
     */
    public function with(): array
    {
        $search = trim($this->search);

        return [
            'categories' => Category::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subquery) use ($search) {
                        $subquery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate((int) $this->perPage),

            'totalCategories' => Category::query()->count(),

            'activeCategories' => Category::query()
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
                Categorías
            </flux:heading>

            <flux:text class="mt-1">
                Administra la organización del catálogo de productos.
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            :href="route('categories.create')"
            wire:navigate
        >
            Nueva categoría
        </flux:button>

    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($feedback)
        <div
            @class([
                'rounded-xl border px-4 py-3 text-sm font-medium',
                'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200' => $feedbackType === 'success',
                'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200' => $feedbackType === 'error',
            ])
        >
            {{ $feedback }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total de categorías
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $totalCategories }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Categorías activas
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $activeCategories }}
            </p>
        </div>

    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        <div class="flex flex-col gap-4 border-b border-zinc-200 p-4 sm:flex-row sm:items-end sm:justify-between dark:border-zinc-700">

            <div class="w-full sm:max-w-md">
                <flux:input
                    label="Buscar categorías"
                    placeholder="Nombre, slug o descripción..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.500ms="search"
                />
            </div>

            <div class="w-full sm:w-44">
                <label
                    for="categories-per-page"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Registros
                </label>

                <select
                    id="categories-per-page"
                    wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:focus:ring-zinc-700"
                >
                    <option value="10">10 registros</option>
                    <option value="25">25 registros</option>
                    <option value="50">50 registros</option>
                </select>
            </div>

        </div>

        {{-- Vista responsive para móviles --}}
        <div class="divide-y divide-zinc-200 md:hidden dark:divide-zinc-700">

            @forelse ($categories as $category)

                <article
                    wire:key="category-mobile-{{ $category->id }}"
                    class="space-y-4 p-4"
                >
                    <div class="flex items-start justify-between gap-4">

                        <div class="min-w-0">
                            <h3 class="truncate font-semibold text-zinc-900 dark:text-white">
                                {{ $category->name }}
                            </h3>

                            <p class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $category->slug }}
                            </p>
                        </div>

                        @if ($category->is_active)
                            <flux:badge color="green">
                                Activa
                            </flux:badge>
                        @else
                            <flux:badge color="red">
                                Inactiva
                            </flux:badge>
                        @endif

                    </div>

                    @if ($category->description)
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $category->description }}
                        </p>
                    @endif

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">
                            Orden
                        </span>

                        <span class="font-medium text-zinc-900 dark:text-white">
                            {{ $category->sort_order }}
                        </span>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-3">

                        <flux:button
                            size="sm"
                            icon="pencil-square"
                            :href="route('categories.edit', $category)"
                            wire:navigate
                        >
                            Editar
                        </flux:button>

                        <flux:button
                            size="sm"
                            icon="power"
                            wire:click="toggleStatus({{ $category->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStatus({{ $category->id }})"
                        >
                            {{ $category->is_active ? 'Desactivar' : 'Activar' }}
                        </flux:button>

                        <flux:button
                            size="sm"
                            variant="danger"
                            icon="trash"
                            wire:click="delete({{ $category->id }})"
                            wire:confirm="¿Seguro que deseas eliminar esta categoría?"
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $category->id }})"
                        >
                            Eliminar
                        </flux:button>

                    </div>

                </article>

            @empty

                <div class="px-6 py-12 text-center">
                    <p class="font-medium text-zinc-700 dark:text-zinc-200">
                        No se encontraron categorías.
                    </p>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea una categoría o cambia la búsqueda.
                    </p>
                </div>

            @endforelse

        </div>

        {{-- Vista de escritorio --}}
        <div class="hidden overflow-x-auto md:block">

            <table class="w-full min-w-212.5 text-sm">

                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/70">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Categoría
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                            Estado
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-zinc-700 dark:text-zinc-200">
                            Orden
                        </th>

                        <th class="px-6 py-4 text-right font-semibold text-zinc-700 dark:text-zinc-200">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse ($categories as $category)

                        <tr
                            wire:key="category-desktop-{{ $category->id }}"
                            class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                        >
                            <td class="px-6 py-4">

                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $category->name }}
                                </div>

                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $category->slug }}
                                </div>

                                @if ($category->description)
                                    <div class="mt-2 max-w-xl truncate text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $category->description }}
                                    </div>
                                @endif

                            </td>

                            <td class="px-6 py-4">
                                @if ($category->is_active)
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
                                {{ $category->sort_order }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">

                                    <flux:button
                                        size="sm"
                                        icon="pencil-square"
                                        :href="route('categories.edit', $category)"
                                        wire:navigate
                                    >
                                        Editar
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        icon="power"
                                        wire:click="toggleStatus({{ $category->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleStatus({{ $category->id }})"
                                    >
                                        {{ $category->is_active ? 'Desactivar' : 'Activar' }}
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar esta categoría?"
                                        wire:loading.attr="disabled"
                                        wire:target="delete({{ $category->id }})"
                                    >
                                        Eliminar
                                    </flux:button>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="4"
                                class="px-6 py-12 text-center"
                            >
                                <p class="font-medium text-zinc-700 dark:text-zinc-200">
                                    No se encontraron categorías.
                                </p>

                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    Crea una categoría o cambia la búsqueda.
                                </p>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if ($categories->hasPages())
        <div>
            {{ $categories->links() }}
        </div>
    @endif

</div>