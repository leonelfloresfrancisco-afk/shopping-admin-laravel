<?php

use App\Models\CarouselSlide;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $slideId;

    public string $title = '';

    public string $subtitle = '';

    public string $description = '';

    public string $button_text = '';

    public string $button_url = '';

    public bool $open_in_new_tab = false;

    public int $sort_order = 0;

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $is_active = true;

    public ?string $existingDesktopImage = null;

    public ?string $existingMobileImage = null;

    public bool $remove_mobile_image = false;

    public $desktop_image = null;

    public $mobile_image = null;

    public function mount(CarouselSlide $carouselSlide): void
    {
        $this->slideId = $carouselSlide->id;
        $this->title = $carouselSlide->title;
        $this->subtitle = $carouselSlide->subtitle ?? '';
        $this->description = $carouselSlide->description ?? '';
        $this->button_text = $carouselSlide->button_text ?? '';
        $this->button_url = $carouselSlide->button_url ?? '';
        $this->open_in_new_tab = $carouselSlide->open_in_new_tab;
        $this->sort_order = $carouselSlide->sort_order;

        $this->starts_at = $carouselSlide->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $carouselSlide->ends_at?->format('Y-m-d\TH:i') ?? '';

        $this->is_active = $carouselSlide->is_active;
        $this->existingDesktopImage = $carouselSlide->desktop_image;
        $this->existingMobileImage = $carouselSlide->mobile_image;
    }

    protected function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:160',
            ],

            'subtitle' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'button_text' => [
                'nullable',
                'string',
                'max:80',
                'required_with:button_url',
            ],

            'button_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'open_in_new_tab' => [
                'boolean',
            ],

            'desktop_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'mobile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_mobile_image' => [
                'boolean',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],

            'starts_at' => [
                'nullable',
                'date',
            ],

            'ends_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.min' => 'El título debe tener al menos 3 caracteres.',
            'title.max' => 'El título no puede superar los 160 caracteres.',

            'button_text.required_with' => 'Escribe el texto del botón.',
            'button_url.url' => 'El enlace debe ser una URL válida.',

            'desktop_image.image' => 'El archivo de escritorio debe ser una imagen.',
            'desktop_image.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'desktop_image.max' => 'La imagen no puede superar los 5 MB.',

            'mobile_image.image' => 'El archivo móvil debe ser una imagen.',
            'mobile_image.mimes' => 'La imagen móvil debe ser JPG, JPEG, PNG o WEBP.',
            'mobile_image.max' => 'La imagen móvil no puede superar los 5 MB.',

            'sort_order.required' => 'El orden es obligatorio.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden no puede ser negativo.',

            'ends_at.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha inicial.',
        ];
    }

    public function update(): void
    {
        $validated = $this->validate();

        $desktopImage = $validated['desktop_image'] ?? null;
        $mobileImage = $validated['mobile_image'] ?? null;
        $removeMobileImage = (bool) $validated['remove_mobile_image'];

        unset(
            $validated['desktop_image'],
            $validated['mobile_image'],
            $validated['remove_mobile_image']
        );

        $validated['title'] = trim($validated['title']);

        $validated['subtitle'] = filled($validated['subtitle'])
            ? trim($validated['subtitle'])
            : null;

        $validated['description'] = filled($validated['description'])
            ? trim($validated['description'])
            : null;

        $validated['button_text'] = filled($validated['button_text'])
            ? trim($validated['button_text'])
            : null;

        $validated['button_url'] = filled($validated['button_url'])
            ? trim($validated['button_url'])
            : null;

        $validated['starts_at'] = filled($validated['starts_at'])
            ? $validated['starts_at']
            : null;

        $validated['ends_at'] = filled($validated['ends_at'])
            ? $validated['ends_at']
            : null;

        $slide = CarouselSlide::query()->findOrFail(
            $this->slideId
        );

        if ($desktopImage !== null) {
            $newDesktopImage = $desktopImage->store(
                'carousel/desktop',
                'public'
            );

            if ($slide->desktop_image) {
                Storage::disk('public')->delete(
                    $slide->desktop_image
                );
            }

            $validated['desktop_image'] = $newDesktopImage;
        }

        if ($mobileImage !== null) {
            $newMobileImage = $mobileImage->store(
                'carousel/mobile',
                'public'
            );

            if ($slide->mobile_image) {
                Storage::disk('public')->delete(
                    $slide->mobile_image
                );
            }

            $validated['mobile_image'] = $newMobileImage;
        } elseif ($removeMobileImage) {
            if ($slide->mobile_image) {
                Storage::disk('public')->delete(
                    $slide->mobile_image
                );
            }

            $validated['mobile_image'] = null;
        }

        $slide->update($validated);

        session()->flash(
            'success',
            'La diapositiva fue actualizada correctamente.'
        );

        $this->redirectRoute(
            'carousel.index',
            navigate: true
        );
    }
};

?>

<div class="mx-auto w-full max-w-6xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Editar diapositiva
            </flux:heading>

            <flux:text class="mt-1">
                Actualiza el contenido, imágenes y programación del banner.
            </flux:text>
        </div>

        <flux:button
            icon="arrow-left"
            :href="route('carousel.index')"
            wire:navigate
        >
            Volver
        </flux:button>

    </div>

    <form wire:submit="update" class="space-y-6">

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Contenido
            </flux:heading>

            <div class="grid gap-6 md:grid-cols-2">

                <div>
                    <flux:input
                        label="Título"
                        wire:model="title"
                        required
                    />

                    @error('title')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Subtítulo"
                        wire:model="subtitle"
                    />

                    @error('subtitle')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div>
                <flux:textarea
                    label="Descripción"
                    rows="4"
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
                        label="Texto del botón"
                        wire:model="button_text"
                    />

                    @error('button_text')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="url"
                        label="Enlace del botón"
                        wire:model="button_url"
                    />

                    @error('button_url')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:checkbox
                    label="Abrir enlace en una pestaña nueva"
                    wire:model="open_in_new_tab"
                />
            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Imágenes responsive
            </flux:heading>

            <div class="grid gap-8 lg:grid-cols-2">

                <div class="space-y-4">

                    <label
                        for="edit-desktop-image"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Nueva imagen de escritorio
                    </label>

                    <input
                        id="edit-desktop-image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="desktop_image"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    @error('desktop_image')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="flex aspect-video items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">

                        @if ($desktop_image)
                            <img
                                src="{{ $desktop_image->temporaryUrl() }}"
                                alt="Nueva imagen de escritorio"
                                class="h-full w-full object-cover"
                            >
                        @elseif ($existingDesktopImage)
                            <img
                                src="{{ asset('storage/' . $existingDesktopImage) }}"
                                alt="{{ $title }}"
                                class="h-full w-full object-cover"
                            >
                        @endif

                    </div>

                </div>

                <div class="space-y-4">

                    <label
                        for="edit-mobile-image"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Nueva imagen móvil
                    </label>

                    <input
                        id="edit-mobile-image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="mobile_image"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    @error('mobile_image')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    @if ($existingMobileImage)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:checkbox
                                label="Eliminar imagen móvil actual"
                                description="Se eliminará al guardar los cambios."
                                wire:model="remove_mobile_image"
                            />
                        </div>
                    @endif

                    <div class="mx-auto flex aspect-4/5 max-w-72 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">

                        @if ($mobile_image)
                            <img
                                src="{{ $mobile_image->temporaryUrl() }}"
                                alt="Nueva imagen móvil"
                                class="h-full w-full object-cover"
                            >
                        @elseif ($existingMobileImage && ! $remove_mobile_image)
                            <img
                                src="{{ asset('storage/' . $existingMobileImage) }}"
                                alt="{{ $title }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Sin imagen móvil.
                            </p>
                        @endif

                    </div>

                </div>

            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:heading size="lg">
                Programación
            </flux:heading>

            <div class="grid gap-6 md:grid-cols-3">

                <div>
                    <flux:input
                        type="number"
                        min="0"
                        label="Orden"
                        wire:model="sort_order"
                        required
                    />

                    @error('sort_order')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="edit-carousel-starts-at"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Fecha de inicio
                    </label>

                    <input
                        id="edit-carousel-starts-at"
                        type="datetime-local"
                        wire:model="starts_at"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                    >

                    @error('starts_at')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="edit-carousel-ends-at"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Fecha de finalización
                    </label>

                    <input
                        id="edit-carousel-ends-at"
                        type="datetime-local"
                        wire:model="ends_at"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                    >

                    @error('ends_at')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:checkbox
                    label="Diapositiva activa"
                    description="También debe encontrarse dentro del período programado."
                    wire:model="is_active"
                />
            </div>

        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <flux:button
                :href="route('carousel.index')"
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