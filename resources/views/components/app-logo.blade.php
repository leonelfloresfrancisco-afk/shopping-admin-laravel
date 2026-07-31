@props([
    'sidebar' => false,
])

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

<a
    {{ $attributes->class([
        'flex min-w-0 items-center gap-3 rounded-lg',
        'w-full' => $sidebar,
    ]) }}
>
    <span
        @class([
            'flex shrink-0 items-center justify-center overflow-hidden rounded-lg',
            'h-10 w-10' => $sidebar,
            'h-12 w-12' => ! $sidebar,
            'border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900',
        ])
    >
        @if ($logoUrl)
            <img
                src="{{ $logoUrl }}"
                alt="{{ $brandName }}"
                class="h-full w-full object-contain p-1"
            >
        @else
            <x-app-logo-icon
                @class([
                    'text-zinc-900 dark:text-white',
                    'h-7 w-7' => $sidebar,
                    'h-8 w-8' => ! $sidebar,
                ])
            />
        @endif
    </span>

    <span
        @class([
            'min-w-0 truncate font-semibold text-zinc-900 dark:text-white',
            'text-sm' => $sidebar,
            'text-base' => ! $sidebar,
        ])
    >
        {{ $brandName }}
    </span>
</a>