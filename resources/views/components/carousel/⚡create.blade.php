<?php

use App\Models\CarouselSlide;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

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

    public $desktop_image = null;

    public $mobile_image = null;

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
                'required',
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

            'subtitle.max' => 'El subtítulo no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',

            'button_text.required_with' => 'Escribe el texto del botón.',
            'button_url.url' => 'El enlace del botón debe ser una URL válida.',

            'desktop_image.required' => 'La imagen de escritorio es obligatoria.',
            'desktop_image.image' => 'El archivo de escritorio debe ser una imagen.',
            'desktop_image.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'desktop_image.max' => 'La imagen no puede superar los 5 MB.',

            'mobile_image.image' => 'El archivo móvil debe ser una imagen.',
            'mobile_image.mimes' => 'La imagen móvil debe ser JPG, JPEG, PNG o WEBP.',
            'mobile_image.max' => 'La imagen móvil no puede superar los 5 MB.',

            'sort_order.required' => 'El orden es obligatorio.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden no puede ser negativo.',

            'starts_at.date' => 'La fecha de inicio no es válida.',
            'ends_at.date' => 'La fecha de finalización no es válida.',
            'ends_at.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha inicial.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $desktopImage = $validated['desktop_image'];
        $mobileImage = $validated['mobile_image'] ?? null;

        unset(
            $validated['desktop_image'],
            $validated['mobile_image']
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

        $validated['desktop_image'] = $desktopImage->store(
            'carousel/desktop',
            'public'
        );

        if ($mobileImage !== null) {
            $validated['mobile_image'] = $mobileImage->store(
                'carousel/mobile',
                'public'
            );
        }

        CarouselSlide::query()->create($validated);

        session()->flash(
            'success',
            'La diapositiva fue creada correctamente.'
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
                Nueva diapositiva
            </flux:heading>

            <flux:text class="mt-1">
                Crea un banner responsive para la página principal de la tienda.
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

    <form wire:submit="save" class="space-y-6">

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Contenido
                </flux:heading>

                <flux:text class="mt-1">
                    Texto que aparecerá sobre el banner.
                </flux:text>
            </div>

            <div class="grid gap-6 md:grid-cols-2">

                <div>
                    <flux:input
                        label="Título"
                        placeholder="Ejemplo: Ofertas de temporada"
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
                        placeholder="Hasta 40 % de descuento"
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
                    placeholder="Texto complementario del banner."
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
                        placeholder="Ver productos"
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
                        placeholder="https://tienda.com/productos"
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

            <div>
                <flux:heading size="lg">
                    Imágenes responsive
                </flux:heading>

                <flux:text class="mt-1">
                    Usa una imagen horizontal para escritorio y otra vertical o cuadrada para móviles.
                </flux:text>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">

                <div class="space-y-3">

                    <label
                        for="desktop-image"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Imagen de escritorio
                    </label>

                    <input
                        id="desktop-image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="desktop_image"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Recomendado: 1920 × 700 px. Máximo 5 MB.
                    </p>

                    @error('desktop_image')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="flex aspect-video items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                        @if ($desktop_image)
                            <img
                                src="{{ $desktop_image->temporaryUrl() }}"
                                alt="Vista previa de escritorio"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Vista previa de escritorio.
                            </p>
                        @endif

                    </div>

                </div>

                <div class="space-y-3">

                    <label
                        for="mobile-image"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Imagen móvil
                    </label>

                    <input
                        id="mobile-image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="mobile_image"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Opcional. Recomendado: 900 × 1100 px.
                    </p>

                    @error('mobile_image')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="mx-auto flex aspect-4/5 max-w-72 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                        @if ($mobile_image)
                            <img
                                src="{{ $mobile_image->temporaryUrl() }}"
                                alt="Vista previa móvil"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Vista previa móvil.
                            </p>
                        @endif

                    </div>

                </div>

            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Programación
                </flux:heading>

                <flux:text class="mt-1">
                    Define el orden y el período durante el cual estará visible.
                </flux:text>
            </div>

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
                        for="carousel-starts-at"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Fecha de inicio
                    </label>

                    <input
                        id="carousel-starts-at"
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
                        for="carousel-ends-at"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Fecha de finalización
                    </label>

                    <input
                        id="carousel-ends-at"
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
                    description="También debe encontrarse dentro del período programado para mostrarse."
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
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    Guardar diapositiva
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </flux:button>

        </div>

    </form>

</div>