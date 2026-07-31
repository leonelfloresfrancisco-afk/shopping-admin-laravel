<?php

use App\Models\Brand;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $website = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    public $logo = null;

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
                'unique:brands,name',
            ],

            'slug' => [
                'required',
                'string',
                'max:170',
                'unique:brands,slug',
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
        ];
    }

    /**
     * Mensajes de validación.
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.unique' => 'Ya existe una marca con este nombre.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe una marca con este slug.',

            'website.url' => 'El sitio web debe ser una URL válida.',

            'sort_order.required' => 'El orden es obligatorio.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden no puede ser negativo.',

            'logo.image' => 'El archivo seleccionado debe ser una imagen.',
            'logo.mimes' => 'El logotipo debe ser JPG, JPEG, PNG o WEBP.',
            'logo.max' => 'El logotipo no puede superar los 2 MB.',
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
     * Guarda la nueva marca.
     */
    public function save(): void
    {
        $validated = $this->validate();

        $uploadedLogo = $validated['logo'] ?? null;

        unset($validated['logo']);

        $validated['name'] = trim($validated['name']);
        $validated['slug'] = Str::slug($validated['slug']);

        $validated['description'] = filled($validated['description'])
            ? trim($validated['description'])
            : null;

        $validated['website'] = filled($validated['website'])
            ? trim($validated['website'])
            : null;

        if ($uploadedLogo !== null) {
            $validated['logo'] = $uploadedLogo->store(
                'brands',
                'public'
            );
        }

        Brand::query()->create($validated);

        session()->flash(
            'success',
            'La marca fue creada correctamente.'
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
                Nueva marca
            </flux:heading>

            <flux:text class="mt-1">
                Registra la información comercial y el logotipo de la marca.
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

    <form wire:submit="save" class="space-y-6">

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Información principal
            </flux:heading>

            <div class="grid gap-6 md:grid-cols-2">

                <div>
                    <flux:input
                        label="Nombre"
                        placeholder="Ejemplo: Samsung"
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
                        placeholder="samsung"
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
                    placeholder="Información general de la marca."
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

                <div>
                    <label
                        for="brand-logo"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Logotipo
                    </label>

                    <input
                        id="brand-logo"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="logo"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        JPG, PNG o WEBP. Máximo 2 MB.
                    </p>

                    @error('logo')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    <div
                        wire:loading
                        wire:target="logo"
                        class="mt-2 text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        Procesando logotipo...
                    </div>
                </div>

                <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                    @if ($logo)
                        <img
                            src="{{ $logo->temporaryUrl() }}"
                            alt="Vista previa del logotipo"
                            class="h-52 w-full object-contain p-4"
                        >
                    @else
                        <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            La vista previa aparecerá aquí.
                        </p>
                    @endif

                </div>

            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:checkbox
                    label="Marca activa"
                    description="La marca podrá seleccionarse al administrar productos."
                    wire:model="is_active"
                />
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
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    Guardar marca
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </flux:button>

        </div>

    </form>

</div>