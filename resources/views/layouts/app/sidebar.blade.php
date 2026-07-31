<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

@php
    $authenticatedUser = auth()->user();
    $userRole = $authenticatedUser?->role ?? 'operator';

    $isAdministrator = $userRole === 'admin';
    $isManager = $userRole === 'manager';

    $canManageCatalogs = $isAdministrator || $isManager;
    $canManageContent = $isAdministrator || $isManager;
@endphp

<body class="min-h-screen bg-zinc-100 dark:bg-zinc-900">

    <flux:sidebar
        sticky
        collapsible="mobile"
        class="border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950"
    >
        <flux:sidebar.header>
            <x-app-logo
                :sidebar="true"
                href="{{ route('dashboard') }}"
                wire:navigate
            />

            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>

            <flux:sidebar.group heading="INICIO" class="grid">

                <flux:sidebar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                >
                    Dashboard
                </flux:sidebar.item>

            </flux:sidebar.group>

            <flux:sidebar.group heading="CATÁLOGOS" class="grid">

                @if ($canManageCatalogs)
                    <flux:sidebar.item
                        icon="tag"
                        :href="route('categories.index')"
                        :current="request()->routeIs('categories.*')"
                        wire:navigate
                    >
                        Categorías
                    </flux:sidebar.item>
                @endif

                <flux:sidebar.item
                    icon="shopping-bag"
                    :href="route('products.index')"
                    :current="request()->routeIs('products.*')"
                    wire:navigate
                >
                    Productos
                </flux:sidebar.item>

                @if ($canManageCatalogs)
                    <flux:sidebar.item
                        icon="building-storefront"
                        :href="route('brands.index')"
                        :current="request()->routeIs('brands.*')"
                        wire:navigate
                    >
                        Marcas
                    </flux:sidebar.item>
                @endif

            </flux:sidebar.group>

            @if ($canManageContent)
                <flux:sidebar.group heading="CONTENIDO" class="grid">

                    <flux:sidebar.item
                        icon="photo"
                        :href="route('carousel.index')"
                        :current="request()->routeIs('carousel.*')"
                        wire:navigate
                    >
                        Carrusel
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="megaphone"
                        :href="route('promotions.index')"
                        :current="request()->routeIs('promotions.*')"
                        wire:navigate
                    >
                        Promociones
                    </flux:sidebar.item>

                </flux:sidebar.group>
            @endif

            @if ($isAdministrator)
                <flux:sidebar.group heading="CONFIGURACIÓN" class="grid">

                    <flux:sidebar.item
                        icon="building-office"
                        :href="route('company.edit')"
                        :current="request()->routeIs('company.*')"
                        wire:navigate
                    >
                        Empresa
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="users"
                        :href="route('users.index')"
                        :current="request()->routeIs('users.*')"
                        wire:navigate
                    >
                        Usuarios
                    </flux:sidebar.item>

                </flux:sidebar.group>
            @endif

        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>

            <flux:sidebar.group heading="SISTEMA" class="grid">

                <flux:sidebar.item
                    icon="cog"
                    :href="route('profile.edit')"
                    :current="request()->routeIs('profile.edit')"
                    wire:navigate
                >
                    Mi Perfil
                </flux:sidebar.item>

            </flux:sidebar.group>

        </flux:sidebar.nav>

        <x-desktop-user-menu
            class="hidden lg:block"
            :name="$authenticatedUser->name"
        />
    </flux:sidebar>

    <flux:header class="lg:hidden">

        <flux:sidebar.toggle
            class="lg:hidden"
            icon="bars-2"
            inset="left"
        />

        <flux:spacer />

        <flux:dropdown
            position="top"
            align="end"
        >
            <flux:profile
                :initials="$authenticatedUser->initials()"
                icon-trailing="chevron-down"
            />

            <flux:menu>

                <div class="px-3 py-3">

                    <flux:heading>
                        {{ $authenticatedUser->name }}
                    </flux:heading>

                    <flux:text>
                        {{ $authenticatedUser->email }}
                    </flux:text>

                    <p class="mt-2 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        @switch($userRole)
                            @case('admin')
                                Administrador
                                @break

                            @case('manager')
                                Gestor
                                @break

                            @default
                                Operador
                        @endswitch
                    </p>

                </div>

                <flux:menu.separator />

                <flux:menu.item
                    :href="route('profile.edit')"
                    icon="cog"
                    wire:navigate
                >
                    Mi Perfil
                </flux:menu.item>

                <flux:menu.separator />

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <flux:menu.item
                        as="button"
                        type="submit"
                        icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer"
                    >
                        Cerrar sesión
                    </flux:menu.item>
                </form>

            </flux:menu>
        </flux:dropdown>

    </flux:header>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts

</body>

</html>