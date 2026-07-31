<?php

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    public $image = null;

    /**
     * Reglas de validación para crear la categoría.
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:150',
                'unique:categories,name',
            ],

            'slug' => [
                'required',
                'string',
                'max:170',
                'unique:categories,slug',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'name.unique' => 'Ya existe una categoría con este nombre.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.max' => 'El slug no puede superar los 170 caracteres.',
            'slug.unique' => 'Ya existe una categoría con este slug.',

            'description.max' => 'La descripción no puede superar los 5000 caracteres.',

            'image.image' => 'El archivo seleccionado debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen no puede superar los 4 MB.',

            'sort_order.required' => 'El orden es obligatorio.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden no puede ser negativo.',
            'sort_order.max' => 'El orden indicado es demasiado grande.',
        ];
    }

    /**
     * Genera automáticamente el slug cuando cambia el nombre.
     */
    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    /**
     * Quita la imagen temporal seleccionada.
     */
    public function removeSelectedImage(): void
    {
        $this->reset('image');
        $this->resetValidation('image');
    }

    /**
     * Guarda la categoría en la base de datos.
     */
    public function save(): void
    {
        $validated = $this->validate();

        $uploadedImage = $validated['image'] ?? null;

        unset($validated['image']);

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['slug'] = Str::slug(
            $validated['slug']
        );

        $validated['description'] = filled(
            $validated['description']
        )
            ? trim($validated['description'])
            : null;

        $storedImage = null;

        try {
            if ($uploadedImage !== null) {
                $storedImage = $uploadedImage->store(
                    'categories',
                    'public'
                );

                $validated['image'] = $storedImage;
            }

            Category::query()->create(
                $validated
            );
        } catch (\Throwable $exception) {
            if ($storedImage !== null) {
                Storage::disk('public')->delete(
                    $storedImage
                );
            }

            report($exception);

            $this->addError(
                'image',
                'No se pudo guardar la categoría. Intenta nuevamente.'
            );

            return;
        }

        session()->flash(
            'success',
            'La categoría fue creada correctamente.'
        );

        $this->redirectRoute(
            'categories.index',
            navigate: true
        );
    }
};

?>

<div class="mx-auto w-full max-w-4xl space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Nueva categoría
            </flux:heading>

            <flux:text class="mt-1">
                Registra una categoría para organizar los productos de la tienda.
            </flux:text>
        </div>

        <flux:button
            :href="route('categories.index')"
            icon="arrow-left"
            wire:navigate
        >
            Volver
        </flux:button>

    </div>

    <form
        wire:submit="save"
        class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900"
    >

        <div class="grid gap-6 md:grid-cols-2">

            <div>
                <flux:input
                    label="Nombre"
                    placeholder="Ejemplo: Electrónica"
                    wire:model.live.debounce.300ms="name"
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
                    placeholder="electronica"
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
                placeholder="Describe brevemente los productos que pertenecen a esta categoría."
                rows="5"
                wire:model="description"
            />

            @error('description')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Imagen de la categoría --}}
        <div class="grid gap-6 md:grid-cols-2">

            <div>
                <label
                    for="category-image"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Imagen de la categoría
                </label>

                <input
                    id="category-image"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    wire:model="image"
                    class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                >

                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    Campo opcional. JPG, PNG o WEBP. Máximo 4 MB.
                </p>

                @error('image')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror

                <div
                    wire:loading
                    wire:target="image"
                    class="mt-2 text-sm text-zinc-500 dark:text-zinc-400"
                >
                    Procesando imagen...
                </div>

                @if ($image)
                    <button
                        type="button"
                        wire:click="removeSelectedImage"
                        class="mt-3 inline-flex items-center justify-center rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Quitar imagen
                    </button>
                @endif
            </div>

            <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                @if ($image)
                    <img
                        src="{{ $image->temporaryUrl() }}"
                        alt="Vista previa de {{ $name }}"
                        class="h-52 w-full object-cover"
                    >
                @else
                    <div class="p-6 text-center">

                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                            Vista previa
                        </p>

                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            La imagen seleccionada aparecerá aquí.
                        </p>

                    </div>
                @endif

            </div>

        </div>

        <div class="grid gap-6 md:grid-cols-2">

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

            <div class="flex items-center rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">

                <flux:checkbox
                    label="Categoría activa"
                    description="La categoría estará disponible para utilizarse en la tienda."
                    wire:model="is_active"
                />

            </div>

        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:justify-end dark:border-zinc-700">

            <flux:button
                :href="route('categories.index')"
                wire:navigate
            >
                Cancelar
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                icon="check"
                wire:loading.attr="disabled"
                wire:target="save,image"
            >
                <span wire:loading.remove wire:target="save">
                    Guardar categoría
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </flux:button>

        </div>

    </form>

</div>