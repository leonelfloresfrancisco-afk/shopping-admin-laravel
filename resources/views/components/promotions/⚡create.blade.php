<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $code = '';

    public string $description = '';

    public string $discount_type = 'percentage';

    public string $discount_value = '';

    public string $applies_to = 'all';

    public string $minimum_purchase = '0.00';

    public string $usage_limit = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $is_active = true;

    /**
     * @var array<int, string>
     */
    public array $selectedCategories = [];

    /**
     * @var array<int, string>
     */
    public array $selectedProducts = [];

    protected function rules(): array
    {
        $discountRules = [
            'required',
            'numeric',
            'gt:0',
        ];

        if ($this->discount_type === 'percentage') {
            $discountRules[] = 'max:100';
        } else {
            $discountRules[] = 'max:99999999.99';
        }

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:160',
            ],

            'code' => [
                'nullable',
                'string',
                'max:80',
                'unique:promotions,code',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'discount_type' => [
                'required',
                'in:percentage,fixed',
            ],

            'discount_value' => $discountRules,

            'applies_to' => [
                'required',
                'in:all,categories,products',
            ],

            'minimum_purchase' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'usage_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:4294967295',
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

            'selectedCategories' => [
                'array',
                'required_if:applies_to,categories',
            ],

            'selectedCategories.*' => [
                'integer',
                'exists:categories,id',
            ],

            'selectedProducts' => [
                'array',
                'required_if:applies_to,products',
            ],

            'selectedProducts.*' => [
                'integer',
                'exists:products,id',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre de la promoción es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede superar los 160 caracteres.',

            'code.max' => 'El código no puede superar los 80 caracteres.',
            'code.unique' => 'Ya existe una promoción con este código.',

            'description.max' => 'La descripción no puede superar los 5000 caracteres.',

            'discount_type.in' => 'El tipo de descuento no es válido.',
            'discount_value.required' => 'El valor del descuento es obligatorio.',
            'discount_value.numeric' => 'El descuento debe ser un número válido.',
            'discount_value.gt' => 'El descuento debe ser mayor que cero.',
            'discount_value.max' => 'El valor del descuento supera el máximo permitido.',

            'minimum_purchase.required' => 'La compra mínima es obligatoria.',
            'minimum_purchase.numeric' => 'La compra mínima debe ser un número válido.',
            'minimum_purchase.min' => 'La compra mínima no puede ser negativa.',

            'usage_limit.integer' => 'El límite de usos debe ser un número entero.',
            'usage_limit.min' => 'El límite de usos debe ser mayor que cero.',

            'starts_at.date' => 'La fecha de inicio no es válida.',
            'ends_at.date' => 'La fecha de finalización no es válida.',
            'ends_at.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha inicial.',

            'selectedCategories.required_if' => 'Selecciona al menos una categoría.',
            'selectedCategories.*.exists' => 'Una de las categorías seleccionadas no existe.',

            'selectedProducts.required_if' => 'Selecciona al menos un producto.',
            'selectedProducts.*.exists' => 'Uno de los productos seleccionados no existe.',
        ];
    }

    public function updatedAppliesTo(): void
    {
        if ($this->applies_to !== 'categories') {
            $this->selectedCategories = [];
        }

        if ($this->applies_to !== 'products') {
            $this->selectedProducts = [];
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $selectedCategories = $validated['selectedCategories'];
        $selectedProducts = $validated['selectedProducts'];

        unset(
            $validated['selectedCategories'],
            $validated['selectedProducts']
        );

        $validated['name'] = trim($validated['name']);

        $validated['code'] = filled($validated['code'])
            ? Str::upper(trim($validated['code']))
            : null;

        $validated['description'] = filled($validated['description'])
            ? trim($validated['description'])
            : null;

        $validated['usage_limit'] = filled($validated['usage_limit'])
            ? (int) $validated['usage_limit']
            : null;

        $validated['starts_at'] = filled($validated['starts_at'])
            ? $validated['starts_at']
            : null;

        $validated['ends_at'] = filled($validated['ends_at'])
            ? $validated['ends_at']
            : null;

        DB::transaction(function () use (
            $validated,
            $selectedCategories,
            $selectedProducts
        ): void {
            $promotion = Promotion::query()->create($validated);

            if ($promotion->applies_to === 'categories') {
                $promotion->categories()->sync(
                    collect($selectedCategories)
                        ->map(fn ($id): int => (int) $id)
                        ->unique()
                        ->values()
                        ->all()
                );
            }

            if ($promotion->applies_to === 'products') {
                $promotion->products()->sync(
                    collect($selectedProducts)
                        ->map(fn ($id): int => (int) $id)
                        ->unique()
                        ->values()
                        ->all()
                );
            }
        });

        session()->flash(
            'success',
            'La promoción fue creada correctamente.'
        );

        $this->redirectRoute(
            'promotions.index',
            navigate: true
        );
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'products' => Product::query()
                ->with([
                    'category',
                    'brand',
                ])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }
};

?>

<div class="mx-auto w-full max-w-6xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Nueva promoción
            </flux:heading>

            <flux:text class="mt-1">
                Configura el descuento, alcance, restricciones y programación.
            </flux:text>
        </div>

        <flux:button
            icon="arrow-left"
            :href="route('promotions.index')"
            wire:navigate
        >
            Volver
        </flux:button>

    </div>

    <form wire:submit="save" class="space-y-6">

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Información principal
                </flux:heading>

                <flux:text class="mt-1">
                    Identificación y descripción de la promoción.
                </flux:text>
            </div>

            {{-- AJUSTE: ambos campos comienzan exactamente a la misma altura. --}}
            <div class="grid items-start gap-6 md:grid-cols-2">

                <div class="space-y-2">
                    <flux:input
                        label="Nombre"
                        placeholder="Ejemplo: Oferta de verano"
                        wire:model="name"
                        required
                    />

                    @error('name')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input
                        label="Código promocional"
                        placeholder="VERANO20"
                        wire:model="code"
                    />

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Déjalo vacío para que la promoción se aplique automáticamente.
                    </p>

                    @error('code')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div class="space-y-2">
                <flux:textarea
                    label="Descripción"
                    placeholder="Describe las condiciones principales de la promoción."
                    rows="4"
                    wire:model="description"
                />

                @error('description')
                    <p class="text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Descuento y condiciones
                </flux:heading>

                <flux:text class="mt-1">
                    Define el beneficio económico y los requisitos de compra.
                </flux:text>
            </div>

            {{-- AJUSTE: todos los controles quedan alineados desde la parte superior. --}}
            <div class="grid items-start gap-6 md:grid-cols-2 xl:grid-cols-4">

                <div class="space-y-2">
                    <label
                        for="promotion-discount-type"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Tipo de descuento
                    </label>

                    <select
                        id="promotion-discount-type"
                        wire:model.live="discount_type"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:ring-zinc-700"
                    >
                        <option value="percentage">
                            Porcentaje
                        </option>

                        <option value="fixed">
                            Monto fijo
                        </option>
                    </select>

                    @error('discount_type')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0.01"
                        label="{{ $discount_type === 'percentage' ? 'Porcentaje' : 'Monto del descuento' }}"
                        placeholder="{{ $discount_type === 'percentage' ? '20' : '50.00' }}"
                        wire:model="discount_value"
                        required
                    />

                    @error('discount_value')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0"
                        label="Compra mínima"
                        wire:model="minimum_purchase"
                        required
                    />

                    @error('minimum_purchase')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input
                        type="number"
                        min="1"
                        label="Límite de usos"
                        placeholder="Sin límite"
                        wire:model="usage_limit"
                    />

                    @error('usage_limit')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Alcance
                </flux:heading>

                <flux:text class="mt-1">
                    Selecciona dónde se aplicará el descuento.
                </flux:text>
            </div>

            <div class="space-y-2">
                <label
                    for="promotion-applies-to"
                    class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Aplicar a
                </label>

                <select
                    id="promotion-applies-to"
                    wire:model.live="applies_to"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 md:max-w-md dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:ring-zinc-700"
                >
                    <option value="all">
                        Toda la tienda
                    </option>

                    <option value="categories">
                        Categorías seleccionadas
                    </option>

                    <option value="products">
                        Productos seleccionados
                    </option>
                </select>

                @error('applies_to')
                    <p class="text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            @if ($applies_to === 'categories')
                <div>
                    <p class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Selecciona las categorías
                    </p>

                    <div class="grid max-h-80 gap-3 overflow-y-auto rounded-xl border border-zinc-200 p-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-zinc-700">

                        @forelse ($categories as $category)

                            <label
                                wire:key="promotion-category-{{ $category->id }}"
                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 p-3 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $category->id }}"
                                    wire:model="selectedCategories"
                                    class="mt-1 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
                                >

                                <span>
                                    <span class="block font-medium text-zinc-900 dark:text-white">
                                        {{ $category->name }}
                                    </span>

                                    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $category->slug }}
                                    </span>
                                </span>
                            </label>

                        @empty

                            <p class="text-sm text-zinc-500 sm:col-span-2 lg:col-span-3 dark:text-zinc-400">
                                No hay categorías activas disponibles.
                            </p>

                        @endforelse

                    </div>

                    @error('selectedCategories')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif

            @if ($applies_to === 'products')
                <div>
                    <p class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Selecciona los productos
                    </p>

                    <div class="grid max-h-96 gap-3 overflow-y-auto rounded-xl border border-zinc-200 p-4 md:grid-cols-2 dark:border-zinc-700">

                        @forelse ($products as $product)

                            <label
                                wire:key="promotion-product-{{ $product->id }}"
                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 p-3 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $product->id }}"
                                    wire:model="selectedProducts"
                                    class="mt-1 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
                                >

                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-zinc-900 dark:text-white">
                                        {{ $product->name }}
                                    </span>

                                    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                        SKU: {{ $product->sku }}
                                    </span>

                                    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $product->category?->name ?? 'Sin categoría' }}
                                        ·
                                        {{ $product->brand?->name ?? 'Sin marca' }}
                                    </span>
                                </span>
                            </label>

                        @empty

                            <p class="text-sm text-zinc-500 md:col-span-2 dark:text-zinc-400">
                                No hay productos activos disponibles.
                            </p>

                        @endforelse

                    </div>

                    @error('selectedProducts')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif

        </section>

        <section class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900">

            <div>
                <flux:heading size="lg">
                    Programación
                </flux:heading>

                <flux:text class="mt-1">
                    Define el período de validez de la promoción.
                </flux:text>
            </div>

            <div class="grid items-start gap-6 md:grid-cols-2">

                <div class="space-y-2">
                    <label
                        for="promotion-starts-at"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Fecha de inicio
                    </label>

                    <input
                        id="promotion-starts-at"
                        type="datetime-local"
                        wire:model="starts_at"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:ring-zinc-700"
                    >

                    @error('starts_at')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label
                        for="promotion-ends-at"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Fecha de finalización
                    </label>

                    <input
                        id="promotion-ends-at"
                        type="datetime-local"
                        wire:model="ends_at"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark dark:focus:ring-zinc-700"
                    >

                    @error('ends_at')
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:checkbox
                    label="Promoción activa"
                    description="También debe cumplir las fechas y el límite de usos configurado."
                    wire:model="is_active"
                />
            </div>

        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <flux:button
                :href="route('promotions.index')"
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
                    Guardar promoción
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </flux:button>

        </div>

    </form>

</div>