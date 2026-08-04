<?php

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $settingId;

    public string $trade_name = '';

    public string $legal_name = '';

    public string $tax_id = '';

    public string $website = '';

    public string $email = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $country = 'Perú';

    public string $postal_code = '';

    public string $currency_code = 'PEN';

    public string $timezone = 'America/Lima';

    public string $tax_rate = '0.00';

    public string $invoice_prefix = 'FAC';

    public string $invoice_next_number = '1';

    public string $facebook_url = '';

    public string $instagram_url = '';

    public string $tiktok_url = '';

    public string $youtube_url = '';

    public bool $store_enabled = true;

    public string $maintenance_message = '';

    public ?string $existingLogo = null;

    public ?string $existingFavicon = null;

    public bool $remove_logo = false;

    public bool $remove_favicon = false;

    public $logo = null;

    public $favicon = null;

    /**
     * Carga la configuración actual.
     */
    public function mount(): void
    {
        $setting = CompanySetting::current();

        $this->settingId = $setting->id;
        $this->trade_name = $setting->trade_name;
        $this->legal_name = $setting->legal_name ?? '';
        $this->tax_id = $setting->tax_id ?? '';
        $this->website = $setting->website ?? '';
        $this->email = $setting->email ?? '';
        $this->phone = $setting->phone ?? '';
        $this->whatsapp = $setting->whatsapp ?? '';
        $this->address = $setting->address ?? '';
        $this->city = $setting->city ?? '';
        $this->state = $setting->state ?? '';
        $this->country = $setting->country;
        $this->postal_code = $setting->postal_code ?? '';
        $this->currency_code = $setting->currency_code;
        $this->timezone = $setting->timezone;
        $this->tax_rate = (string) $setting->tax_rate;
        $this->invoice_prefix = $setting->invoice_prefix;
        $this->invoice_next_number = (string) $setting->invoice_next_number;
        $this->facebook_url = $setting->facebook_url ?? '';
        $this->instagram_url = $setting->instagram_url ?? '';
        $this->tiktok_url = $setting->tiktok_url ?? '';
        $this->youtube_url = $setting->youtube_url ?? '';
        $this->store_enabled = $setting->store_enabled;
        $this->maintenance_message = $setting->maintenance_message ?? '';
        $this->existingLogo = $setting->logo;
        $this->existingFavicon = $setting->favicon;
    }

    /**
     * Completa automáticamente la razón social mientras esté vacía.
     */
    public function updatedTradeName(string $value): void
    {
        if (trim($this->legal_name) === '') {
            $this->legal_name = trim($value);
        }
    }

    /**
     * Copia automáticamente el teléfono a WhatsApp mientras esté vacío.
     */
    public function updatedPhone(string $value): void
    {
        if (trim($this->whatsapp) === '') {
            $this->whatsapp = trim($value);
        }
    }

    /**
     * Completa rápidamente la configuración básica local.
     */
    public function useQuickDefaults(): void
    {
        $this->country = 'Perú';
        $this->currency_code = 'PEN';
        $this->timezone = 'America/Lima';

        if (trim($this->invoice_prefix) === '') {
            $this->invoice_prefix = 'FAC';
        }

        if (
            trim($this->invoice_next_number) === ''
            || (int) $this->invoice_next_number < 1
        ) {
            $this->invoice_next_number = '1';
        }
    }

    /**
     * Copia manualmente el teléfono a WhatsApp.
     */
    public function copyPhoneToWhatsApp(): void
    {
        $this->whatsapp = trim($this->phone);
    }

    protected function rules(): array
    {
        return [
            'trade_name' => [
                'required',
                'string',
                'min:2',
                'max:160',
            ],

            'legal_name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'tax_id' => [
                'nullable',
                'string',
                'max:30',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'whatsapp' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'nullable',
                'string',
                'max:120',
            ],

            'state' => [
                'nullable',
                'string',
                'max:120',
            ],

            'country' => [
                'required',
                'string',
                'max:120',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'currency_code' => [
                'required',
                'in:PEN,USD,EUR',
            ],

            'timezone' => [
                'required',
                'in:America/Lima,America/Bogota,America/Mexico_City,America/Santiago,America/Argentina/Buenos_Aires,America/New_York,Europe/Madrid',
            ],

            'tax_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'invoice_prefix' => [
                'required',
                'string',
                'max:20',
            ],

            'invoice_next_number' => [
                'required',
                'integer',
                'min:1',
            ],

            'facebook_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'instagram_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'tiktok_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'youtube_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'store_enabled' => [
                'boolean',
            ],

            'maintenance_message' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'favicon' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:1024',
            ],

            'remove_logo' => [
                'boolean',
            ],

            'remove_favicon' => [
                'boolean',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'trade_name.required' => 'El nombre comercial es obligatorio.',
            'trade_name.min' => 'El nombre comercial debe tener al menos 2 caracteres.',
            'trade_name.max' => 'El nombre comercial no puede superar los 160 caracteres.',

            'website.url' => 'El sitio web debe ser una URL válida.',
            'email.email' => 'Ingresa un correo electrónico válido.',

            'country.required' => 'El país es obligatorio.',
            'currency_code.in' => 'Selecciona una moneda válida.',
            'timezone.in' => 'Selecciona una zona horaria válida.',

            'tax_rate.required' => 'El impuesto es obligatorio.',
            'tax_rate.numeric' => 'El impuesto debe ser un número válido.',
            'tax_rate.min' => 'El impuesto no puede ser negativo.',
            'tax_rate.max' => 'El impuesto no puede superar el 100 %.',

            'invoice_prefix.required' => 'El prefijo de facturación es obligatorio.',
            'invoice_next_number.required' => 'El siguiente número de factura es obligatorio.',
            'invoice_next_number.integer' => 'El número de factura debe ser entero.',
            'invoice_next_number.min' => 'El número de factura debe ser mayor que cero.',

            'facebook_url.url' => 'La dirección de Facebook no es válida.',
            'instagram_url.url' => 'La dirección de Instagram no es válida.',
            'tiktok_url.url' => 'La dirección de TikTok no es válida.',
            'youtube_url.url' => 'La dirección de YouTube no es válida.',

            'logo.image' => 'El logotipo debe ser una imagen.',
            'logo.mimes' => 'El logotipo debe ser JPG, JPEG, PNG o WEBP.',
            'logo.max' => 'El logotipo no puede superar los 3 MB.',

            'favicon.image' => 'El favicon debe ser una imagen.',
            'favicon.mimes' => 'El favicon debe ser JPG, JPEG, PNG o WEBP.',
            'favicon.max' => 'El favicon no puede superar 1 MB.',
        ];
    }

    /**
     * Guarda la configuración empresarial.
     */
    public function save(): void
    {
        $validated = $this->validate();

        $uploadedLogo = $validated['logo'] ?? null;
        $uploadedFavicon = $validated['favicon'] ?? null;

        $removeLogo = (bool) $validated['remove_logo'];
        $removeFavicon = (bool) $validated['remove_favicon'];

        unset(
            $validated['logo'],
            $validated['favicon'],
            $validated['remove_logo'],
            $validated['remove_favicon']
        );

        $nullableFields = [
            'legal_name',
            'tax_id',
            'website',
            'email',
            'phone',
            'whatsapp',
            'address',
            'city',
            'state',
            'postal_code',
            'facebook_url',
            'instagram_url',
            'tiktok_url',
            'youtube_url',
            'maintenance_message',
        ];

        foreach ($nullableFields as $field) {
            $validated[$field] = filled($validated[$field])
                ? trim($validated[$field])
                : null;
        }

        $validated['trade_name'] = trim($validated['trade_name']);
        $validated['country'] = trim($validated['country']);

        $validated['invoice_prefix'] = strtoupper(
            trim($validated['invoice_prefix'])
        );

        $validated['invoice_next_number'] = (int) $validated['invoice_next_number'];

        $setting = CompanySetting::query()->findOrFail(
            $this->settingId
        );

        if ($uploadedLogo !== null) {
            $newLogo = $uploadedLogo->store(
                'company',
                'public'
            );

            if ($setting->logo) {
                Storage::disk('public')->delete(
                    $setting->logo
                );
            }

            $validated['logo'] = $newLogo;
        } elseif ($removeLogo) {
            if ($setting->logo) {
                Storage::disk('public')->delete(
                    $setting->logo
                );
            }

            $validated['logo'] = null;
        }

        if ($uploadedFavicon !== null) {
            $newFavicon = $uploadedFavicon->store(
                'company',
                'public'
            );

            if ($setting->favicon) {
                Storage::disk('public')->delete(
                    $setting->favicon
                );
            }

            $validated['favicon'] = $newFavicon;
        } elseif ($removeFavicon) {
            if ($setting->favicon) {
                Storage::disk('public')->delete(
                    $setting->favicon
                );
            }

            $validated['favicon'] = null;
        }

        $setting->update($validated);

        session()->flash(
            'success',
            'La configuración y la identidad visual fueron actualizadas.'
        );

        /*
         * NUEVO:
         * La redirección completa vuelve a cargar el sidebar, el logo,
         * el nombre comercial y el favicon inmediatamente.
         */
        $this->redirectRoute('company.edit');
    }
};

?>

<div class="mx-auto w-full max-w-7xl space-y-6">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

        <div>
            <flux:heading size="xl">
                Empresa
            </flux:heading>

            <flux:text class="mt-1">
                Completa primero los datos esenciales. El resto de opciones es opcional.
            </flux:text>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">

            <flux:button
                icon="bolt"
                wire:click="useQuickDefaults"
                type="button"
            >
                Completar datos básicos
            </flux:button>

            <flux:button
                type="submit"
                form="company-settings-form"
                variant="primary"
                icon="check"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    Guardar
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </flux:button>

        </div>

    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <form
        id="company-settings-form"
        wire:submit="save"
        class="space-y-6"
    >

        {{-- Datos esenciales --}}
        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Datos esenciales
                </flux:heading>

                <flux:text class="mt-1">
                    Esta información identifica a tu tienda y permite contactarte.
                </flux:text>
            </div>

            <div class="grid items-start gap-6 md:grid-cols-2">

                <div>
                    <flux:input
                        label="Nombre comercial"
                        placeholder="Ejemplo: Shopping Leo"
                        wire:model.live.debounce.400ms="trade_name"
                        required
                    />

                    @error('trade_name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Razón social"
                        placeholder="Se completa con el nombre comercial"
                        wire:model="legal_name"
                    />

                    @error('legal_name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="RUC / Identificación fiscal"
                        placeholder="20123456789"
                        wire:model="tax_id"
                    />

                    @error('tax_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="email"
                        label="Correo electrónico"
                        placeholder="ventas@empresa.com"
                        wire:model="email"
                    />

                    @error('email')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Teléfono"
                        placeholder="+51 999 999 999"
                        wire:model.live.debounce.500ms="phone"
                    />

                    @error('phone')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">

                    <flux:input
                        label="WhatsApp"
                        placeholder="+51 999 999 999"
                        wire:model="whatsapp"
                    />

                    <flux:button
                        size="sm"
                        type="button"
                        wire:click="copyPhoneToWhatsApp"
                    >
                        Usar el mismo teléfono
                    </flux:button>

                    @error('whatsapp')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <div class="md:col-span-2">
                    <flux:input
                        label="Dirección"
                        placeholder="Avenida, calle, número y referencia"
                        wire:model="address"
                    />

                    @error('address')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="País"
                        wire:model="country"
                        required
                    />

                    @error('country')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </section>

        {{-- Identidad visual --}}
        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Logotipo de la tienda
                </flux:heading>

                <flux:text class="mt-1">
                    Al guardar, reemplazará automáticamente la identidad predeterminada de Laravel.
                </flux:text>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">

                <div class="space-y-4">

                    <label
                        for="company-logo"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Logotipo principal
                    </label>

                    <input
                        id="company-logo"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="logo"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        PNG transparente o WEBP recomendado. Máximo 3 MB.
                    </p>

                    @error('logo')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    @if ($existingLogo)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:checkbox
                                label="Eliminar logotipo actual"
                                wire:model="remove_logo"
                            />
                        </div>
                    @endif

                    <div
                        wire:loading
                        wire:target="logo"
                        class="text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        Procesando logotipo...
                    </div>

                </div>

                <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                    @if ($logo)
                        <img
                            src="{{ $logo->temporaryUrl() }}"
                            alt="Nuevo logotipo"
                            class="h-44 w-full object-contain p-4"
                        >
                    @elseif ($existingLogo && ! $remove_logo)
                        <img
                            src="{{ $existingLogo }}"
                            alt="{{ $trade_name }}"
                            class="h-44 w-full object-contain p-4"
                        >
                    @else
                        <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Todavía no has cargado un logotipo.
                        </p>
                    @endif

                </div>

            </div>

            <div class="grid gap-8 border-t border-zinc-200 pt-6 lg:grid-cols-2 dark:border-zinc-700">

                <div class="space-y-4">

                    <label
                        for="company-favicon"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Ícono de la pestaña
                    </label>

                    <input
                        id="company-favicon"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="favicon"
                        class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-sm text-zinc-900 file:me-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700"
                    >

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Recomendado: imagen cuadrada de 512 × 512 px.
                    </p>

                    @error('favicon')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    @if ($existingFavicon)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:checkbox
                                label="Eliminar favicon actual"
                                wire:model="remove_favicon"
                            />
                        </div>
                    @endif

                </div>

                <div class="flex min-h-40 items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">

                    @if ($favicon)
                        <img
                            src="{{ $favicon->temporaryUrl() }}"
                            alt="Nuevo favicon"
                            class="h-24 w-24 rounded-xl object-contain"
                        >
                    @elseif ($existingFavicon && ! $remove_favicon)
                        <img
                            src="{{ $existingFavicon }}"
                            alt="Favicon"
                            class="h-24 w-24 rounded-xl object-contain"
                        >
                    @else
                        <p class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Sin favicon personalizado.
                        </p>
                    @endif

                </div>

            </div>

        </section>

        {{-- Opciones avanzadas --}}
        <details class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <summary class="cursor-pointer list-none p-5 sm:p-7">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <flux:heading size="lg">
                            Ubicación y sitio web
                        </flux:heading>

                        <flux:text class="mt-1">
                            Ciudad, departamento, código postal y dirección web.
                        </flux:text>
                    </div>

                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        Opcional
                    </span>

                </div>

            </summary>

            <div class="grid items-start gap-6 border-t border-zinc-200 p-5 md:grid-cols-2 sm:p-7 dark:border-zinc-700">

                <div>
                    <flux:input
                        type="url"
                        label="Sitio web"
                        placeholder="https://www.empresa.com"
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
                        label="Ciudad"
                        wire:model="city"
                    />

                    @error('city')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Departamento / Estado"
                        wire:model="state"
                    />

                    @error('state')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Código postal"
                        wire:model="postal_code"
                    />

                    @error('postal_code')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </details>

        <details class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <summary class="cursor-pointer list-none p-5 sm:p-7">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <flux:heading size="lg">
                            Facturación y moneda
                        </flux:heading>

                        <flux:text class="mt-1">
                            Ya contiene valores predeterminados; modifícalos solo cuando sea necesario.
                        </flux:text>
                    </div>

                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        Avanzado
                    </span>

                </div>

            </summary>

            <div class="grid items-start gap-6 border-t border-zinc-200 p-5 md:grid-cols-2 xl:grid-cols-4 sm:p-7 dark:border-zinc-700">

                <div>
                    <label
                        for="company-currency"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Moneda
                    </label>

                    <select
                        id="company-currency"
                        wire:model="currency_code"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                    >
                        <option value="PEN">Soles (PEN)</option>
                        <option value="USD">Dólares (USD)</option>
                        <option value="EUR">Euros (EUR)</option>
                    </select>

                    @error('currency_code')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="company-timezone"
                        class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Zona horaria
                    </label>

                    <select
                        id="company-timezone"
                        wire:model="timezone"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                    >
                        <option value="America/Lima">Lima</option>
                        <option value="America/Bogota">Bogotá</option>
                        <option value="America/Mexico_City">Ciudad de México</option>
                        <option value="America/Santiago">Santiago</option>
                        <option value="America/Argentina/Buenos_Aires">Buenos Aires</option>
                        <option value="America/New_York">Nueva York</option>
                        <option value="Europe/Madrid">Madrid</option>
                    </select>

                    @error('timezone')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        label="Impuesto (%)"
                        wire:model="tax_rate"
                        required
                    />

                    @error('tax_rate')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        label="Prefijo de factura"
                        wire:model="invoice_prefix"
                        required
                    />

                    @error('invoice_prefix')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="number"
                        min="1"
                        label="Siguiente factura"
                        wire:model="invoice_next_number"
                        required
                    />

                    @error('invoice_next_number')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </details>

        <details class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <summary class="cursor-pointer list-none p-5 sm:p-7">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <flux:heading size="lg">
                            Redes sociales
                        </flux:heading>

                        <flux:text class="mt-1">
                            Enlaces opcionales de las redes oficiales.
                        </flux:text>
                    </div>

                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        Opcional
                    </span>

                </div>

            </summary>

            <div class="grid items-start gap-6 border-t border-zinc-200 p-5 md:grid-cols-2 sm:p-7 dark:border-zinc-700">

                <div>
                    <flux:input
                        type="url"
                        label="Facebook"
                        placeholder="https://facebook.com/empresa"
                        wire:model="facebook_url"
                    />

                    @error('facebook_url')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="url"
                        label="Instagram"
                        placeholder="https://instagram.com/empresa"
                        wire:model="instagram_url"
                    />

                    @error('instagram_url')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="url"
                        label="TikTok"
                        placeholder="https://tiktok.com/@empresa"
                        wire:model="tiktok_url"
                    />

                    @error('tiktok_url')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        type="url"
                        label="YouTube"
                        placeholder="https://youtube.com/@empresa"
                        wire:model="youtube_url"
                    />

                    @error('youtube_url')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </details>

        <details class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <summary class="cursor-pointer list-none p-5 sm:p-7">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <flux:heading size="lg">
                            Estado de la tienda
                        </flux:heading>

                        <flux:text class="mt-1">
                            Habilitación general y mensaje de mantenimiento.
                        </flux:text>
                    </div>

                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        Avanzado
                    </span>

                </div>

            </summary>

            <div class="space-y-6 border-t border-zinc-200 p-5 sm:p-7 dark:border-zinc-700">

                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:checkbox
                        label="Tienda habilitada"
                        description="Permite que los clientes accedan normalmente al catálogo."
                        wire:model="store_enabled"
                    />
                </div>

                <div>
                    <flux:textarea
                        label="Mensaje de mantenimiento"
                        placeholder="Estamos realizando mejoras. Volveremos pronto."
                        rows="4"
                        wire:model="maintenance_message"
                    />

                    @error('maintenance_message')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </details>

        <div class="sticky bottom-4 z-20 flex justify-end">

            <div class="rounded-xl border border-zinc-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">

                <flux:button
                    type="submit"
                    variant="primary"
                    icon="check"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">
                        Guardar configuración
                    </span>

                    <span wire:loading wire:target="save">
                        Guardando...
                    </span>
                </flux:button>

            </div>

        </div>

    </form>

</div>