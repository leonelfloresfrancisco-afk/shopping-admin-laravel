<?php

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $categoryId;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    public ?string $existingImage = null;

    public bool $remove_image = false;

    public $image = null;

    /**
     * Recibe la categoría desde el parámetro de la ruta.
     */
    public function mount(Category $category): void
    {
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
        $this->existingImage = $category->image;
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
                'min:3',
                'max:150',
                Rule::unique('categories', 'name')
                    ->ignore($this->categoryId),
            ],

            'slug' => [
                'required',
                'string',
                'max:170',
                Rule::unique('categories', 'slug')
                    ->ignore($this->categoryId),
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

            'remove_image' => [
                'boolean',
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
     * Mensajes de validación.
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'name.unique' => 'Ya existe otra categoría con este nombre.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.max' => 'El slug no puede superar los 170 caracteres.',
            'slug.unique' => 'Ya existe otra categoría con este slug.',

            'description.max' => 'La descripción no puede superar los 5000 caracteres.',

            'image.image' => 'El archivo seleccionado debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen no puede superar los 4 MB.',

            'sort_order.required' => 'El orden es obligatorio.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden no puede ser negativo.',
            'sort_order.max' => 'El valor del orden es demasiado grande.',
        ];
    }

    /**
     * Actualiza automáticamente el slug.
     */
    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    /**
     * Desmarca la eliminación cuando se selecciona una imagen nueva.
     */
    public function updatedImage(): void
    {
        $this->remove_image = false;
    }

    /**
     * Quita la nueva imagen temporal.
     */
    public function removeSelectedImage(): void
    {
        $this->reset('image');
        $this->resetValidation('image');
    }

    /**
     * Actualiza la categoría.
     */
    public function update(): void
    {
        $validated = $this->validate();

        $uploadedImage = $validated['image'] ?? null;

        $removeImage = (bool) (
            $validated['remove_image'] ?? false
        );

        unset(
            $validated['image'],
            $validated['remove_image']
        );

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

        $category = Category::query()->findOrFail(
            $this->categoryId
        );

        $newImagePath = null;
        $oldImagePath = null;

        try {
            if ($uploadedImage !== null) {
                $newImagePath = $uploadedImage->store(
                    'categories',
                    'public'
                );

                $validated['image'] = $newImagePath;
                $oldImagePath = $category->image;
            } elseif ($removeImage) {
                $validated['image'] = null;
                $oldImagePath = $category->image;
            }

            $category->update(
                $validated
            );
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete(
                    $newImagePath
                );
            }

            report($exception);

            $this->addError(
                'image',
                'No se pudo actualizar la categoría. Intenta nuevamente.'
            );

            return;
        }

        if ($oldImagePath !== null) {
            Storage::disk('public')->delete(
                $oldImagePath
            );
        }

        session()->flash(
            'success',
            'La categoría fue actualizada correctamente.'
        );

        $this->redirectRoute(
            'categories.index',
            navigate: true
        );
    }
};

?>

<div class="mx-auto w-full max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Editar categoría
            </flux:heading>

            <flux:text class="mt-1">
                Actualiza la información y el estado de la categoría.
            </flux:text>
        </div>

        <flux:button
            icon="arrow-left"
            :href="route('categories.index')"
            wire:navigate
        >
            Volver
        </flux:button>

    </div>

    <form
        wire:submit="update"
        class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900"
    >

        <div class="grid gap-6 md:grid-cols-2">

            <div>
                <flux:input
                    label="Nombre"
                    placeholder="Ejemplo: Electrónica"
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
                placeholder="Describe los productos que pertenecen a esta categoría."
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

            <div class="space-y-4">

                <div>
                    <label
                        for="category-image"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Nueva imagen
                    </label>

                    <input
                        id="category-image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="image"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        La nueva imagen reemplazará la actual. Máximo 4 MB.
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
                </div>

                @if ($image)
                    <button
                        type="button"
                        wire:click="removeSelectedImage"
                        class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Cancelar nueva imagen
                    </button>
                @endif

                @if ($existingImage)
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                        <flux:checkbox
                            label="Eliminar imagen actual"
                            description="La imagen se eliminará cuando guardes los cambios."
                            wire:model.live="remove_image"
                        />

                    </div>
                @endif

            </div>

            <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                @if ($image)
                    <img
                        src="{{ $image->temporaryUrl() }}"
                        alt="Nueva imagen de {{ $name }}"
                        class="h-52 w-full object-cover"
                    >
                @elseif ($existingImage && ! $remove_image)
                    <img
                        src="{{ asset('storage/' . $existingImage) }}"
                        alt="{{ $name }}"
                        class="h-52 w-full object-cover"
                    >
                @else
                    <div class="p-6 text-center">

                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                            Categoría sin imagen
                        </p>

                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Selecciona una imagen para mostrarla en la tienda.
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

            <div class="flex items-center rounded-xl border border-zinc-200 px-4 py-4 dark:border-zinc-700">

                <flux:checkbox
                    label="Categoría activa"
                    description="Permite utilizar esta categoría en la tienda."
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
                wire:target="update,image"
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