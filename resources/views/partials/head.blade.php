@php
    $company = null;

    try {
        $company = \App\Models\CompanySetting::query()->first();
    } catch (\Throwable) {
        $company = null;
    }

    $companyName = $company?->trade_name
        ?: config('app.name', 'Shopping Admin');

    $pageTitle = isset($title) && filled($title)
        ? $title . ' · ' . $companyName
        : $companyName;

    $faviconUrl = $company?->favicon
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->favicon)
        : null;
@endphp

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="application-name"
    content="{{ $companyName }}"
>

<meta
    name="description"
    content="{{ $companyName }}"
>

<title>{{ $pageTitle }}</title>

@if ($faviconUrl)

    <link
        rel="icon"
        href="{{ $faviconUrl }}"
        type="image/png"
    >

    <link
        rel="apple-touch-icon"
        href="{{ $faviconUrl }}"
    >

@else

    <link
        rel="icon"
        href="/favicon.ico"
        sizes="any"
    >

    <link
        rel="icon"
        href="/favicon.svg"
        type="image/svg+xml"
    >

    <link
        rel="apple-touch-icon"
        href="/apple-touch-icon.png"
    >

@endif

<link
    rel="preconnect"
    href="https://fonts.bunny.net"
>

<link
    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
    rel="stylesheet"
/>

@vite([
    'resources/css/app.css',
    'resources/js/app.js',
])

@fluxAppearance