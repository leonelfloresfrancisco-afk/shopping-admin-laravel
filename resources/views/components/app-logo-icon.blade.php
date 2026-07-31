@php
    $company = null;

    try {
        $company = \App\Models\CompanySetting::query()->first();
    } catch (\Throwable) {
        $company = null;
    }

    $brandName = $company?->trade_name
        ?: config('app.name', 'Shopping Admin');

    $logoUrl = $company?->logo
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo)
        : null;
@endphp

@if ($logoUrl)

    <img
        src="{{ $logoUrl }}"
        alt="{{ $brandName }}"
        {{ $attributes->class([
            'object-contain',
        ]) }}
    >

@else

    <svg
        {{ $attributes->merge([
            'viewBox' => '0 0 24 24',
            'fill' => 'none',
            'aria-hidden' => 'true',
        ]) }}
        xmlns="http://www.w3.org/2000/svg"
    >
        <path
            d="M4 9.5V20h16V9.5"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <path
            d="M3 9.5 5.5 4h13L21 9.5"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <path
            d="M3 9.5c0 1.38 1.12 2.5 2.5 2.5S8 10.88 8 9.5c0 1.38 1.12 2.5 2.5 2.5S13 10.88 13 9.5c0 1.38 1.12 2.5 2.5 2.5S18 10.88 18 9.5c0 1.38 1.12 2.5 2.5 2.5"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <path
            d="M9 20v-5h6v5"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>

@endif