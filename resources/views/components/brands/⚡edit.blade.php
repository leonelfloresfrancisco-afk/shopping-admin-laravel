<?php

use App\Models\Brand;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $brandId;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $website = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    public ?string $existingLogo = null;

    public bool $remove_logo = false;

    public $logo = null;

    /**
     * Carga la marca desde la ruta.
     */
    public function mount(Brand $brand): void
    {
        $this->brandId = $brand->id;
        $this->name = $brand->name;
        $this->slug = $brand->slug;
        $this->description = $brand->description ?? '';
        $this->website = $brand->website ?? '';
        $this->sort_order = $brand->sort_order;
        $this->is_active = $brand->is_active;
        $this->existingLogo = $brand->logo;
    }

    /**
     * Reglas de validación.
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('brands', 'name')
                    ->ignore($this->brandId),
            ],

            'slug' => [
                'required',
                'string',
                'max:170',
                Rule::unique('brands', 'slug')
                    ->ignore($this->brandId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],

            'is_active' => [
                'boolean',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'remove_logo' => [
                'boolean',
            ],
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
     * Actualiza la marca.
     */
    public function update(): void
    {
        $validated = $this->validate();

        $uploadedLogo = $validated['logo'] ?? null;
        $removeLogo = (bool) $validated['remove_logo'];

        unset(
            $validated['logo'],
            $validated['remove_logo']
        );

        $validated['name'] = trim($validated['name']);
        $validated['slug'] = Str::slug($validated['slug']);

        $validated['description'] = filled($validated['description'])
            ? trim($validated['description'])
            : null;

        $validated['website'] = filled($validated['website'])
            ? trim($validated['website'])
            : null;

        $brand = Brand::query()->findOrFail($this->brandId);

        if ($uploadedLogo !== null) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }

            $validated['logo'] = $uploadedLogo->store(
                'brands',
                'public'
            );
        } elseif ($removeLogo) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }

            $validated['logo'] = null;
        }

        $brand->update($validated);

        session()->flash(
            'success',
            'La marca fue actualizada correctamente.'
        );

        $this->redirectRoute(
            'brands.index',
            navigate: true
        );
    }
};

?>

<div class="mx-auto w-full max-w-5xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Editar marca
            </flux:heading>

            <flux:text class="mt-1">
                Actualiza la información y el logotipo de la marca.
            </flux:text>
        </div>

        <flux:button
            icon="arrow-left"
            :href="route('brands.index')"
            wire:navigate
        >
            Volver
        </flux:button>

    </div>

    <form wire:submit="update" class="space-y-6">

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Información principal
            </flux:heading>

            <div class="grid gap-6 md:grid-cols-2">

                <div>
                    <flux:input
                        label="Nombre"
                        wire:model.live.debounce.400ms="name"
                        required
                    />

                    @error('name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Slug"
                        wire:model="slug"
                        required
                    />

                    @error('slug')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div>
                <flux:textarea
                    label="Descripción"
                    rows="5"
                    wire:model="description"
                />

                @error('description')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-2">

                <div>
                    <flux:input
                        type="url"
                        label="Sitio web"
                        placeholder="https://www.ejemplo.com"
                        wire:model="website"
                    />

                    @error('website')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="number"
                        min="0"
                        label="Orden de visualización"
                        wire:model="sort_order"
                        required
                    />

                    @error('sort_order')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Logotipo y estado
            </flux:heading>

            <div class="grid gap-6 lg:grid-cols-2">

                <div class="space-y-4">

                    <div>
                        <label
                            for="brand-logo"
                            class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Nuevo logotipo
                        </label>

                        <input
                            id="brand-logo"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            wire:model="logo"
                            class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                        >

                        @error('logo')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @if ($existingLogo)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:checkbox
                                label="Eliminar logotipo actual"
                                description="El archivo se eliminará al guardar los cambios."
                                wire:model="remove_logo"
                            />
                        </div>
                    @endif

                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:checkbox
                            label="Marca activa"
                            description="La marca podrá utilizarse en productos."
                            wire:model="is_active"
                        />
                    </div>

                </div>

                <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                    @if ($logo)
                        <img
                            src="{{ $logo->temporaryUrl() }}"
                            alt="Nuevo logotipo"
                            class="h-52 w-full object-contain p-4"
                        >
                    @elseif ($existingLogo && ! $remove_logo)
                        <img
                            src="{{ asset('storage/' . $existingLogo) }}"
                            alt="{{ $name }}"
                            class="h-52 w-full object-contain p-4"
                        >
                    @else
                        <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Marca sin logotipo.
                        </p>
                    @endif

                </div>

            </div>

        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <flux:button
                :href="route('brands.index')"
                wire:navigate
            >
                Cancelar
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                icon="check"
                wire:loading.attr="disabled"
                wire:target="update"
            >
                <span wire:loading.remove wire:target="update">
                    Guardar cambios
                </span>

                <span wire:loading wire:target="update">
                    Actualizando...
                </span>
            </flux:button>

        </div>

    </form>

</div>