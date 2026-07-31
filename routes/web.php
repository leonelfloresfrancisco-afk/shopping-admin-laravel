<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tienda pública
|--------------------------------------------------------------------------
*/

Route::livewire('/', 'store.home')
    ->name('home');

/*
|--------------------------------------------------------------------------
| Detalle público del producto
|--------------------------------------------------------------------------
|
| El producto se resuelve por su slug:
| /producto/tablet-samsung-galaxy
|
*/

Route::livewire(
    '/producto/{product:slug}',
    'store.product-show'
)->name('store.product.show');

/*
|--------------------------------------------------------------------------
| Panel administrativo
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    EnsureUserIsActive::class,
])->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::livewire('/dashboard', 'dashboard.index')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Productos
    |--------------------------------------------------------------------------
    | Administrador, gestor y operador.
    */

    Route::middleware(
        EnsureUserHasRole::class . ':admin,manager,operator'
    )->group(function (): void {
        Route::livewire('/products', 'products.index')
            ->name('products.index');

        Route::livewire('/products/create', 'products.create')
            ->name('products.create');

        Route::livewire('/products/{product}/edit', 'products.edit')
            ->name('products.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Catálogos y contenido
    |--------------------------------------------------------------------------
    | Administrador y gestor.
    */

    Route::middleware(
        EnsureUserHasRole::class . ':admin,manager'
    )->group(function (): void {
        /*
        |--------------------------------------------------------------------------
        | Categorías
        |--------------------------------------------------------------------------
        */

        Route::livewire('/categories', 'categories.index')
            ->name('categories.index');

        Route::livewire('/categories/create', 'categories.create')
            ->name('categories.create');

        Route::livewire('/categories/{category}/edit', 'categories.edit')
            ->name('categories.edit');

        /*
        |--------------------------------------------------------------------------
        | Marcas
        |--------------------------------------------------------------------------
        */

        Route::livewire('/brands', 'brands.index')
            ->name('brands.index');

        Route::livewire('/brands/create', 'brands.create')
            ->name('brands.create');

        Route::livewire('/brands/{brand}/edit', 'brands.edit')
            ->name('brands.edit');

        /*
        |--------------------------------------------------------------------------
        | Carrusel
        |--------------------------------------------------------------------------
        */

        Route::livewire('/carousel', 'carousel.index')
            ->name('carousel.index');

        Route::livewire('/carousel/create', 'carousel.create')
            ->name('carousel.create');

        Route::livewire(
            '/carousel/{carouselSlide}/edit',
            'carousel.edit'
        )->name('carousel.edit');

        /*
        |--------------------------------------------------------------------------
        | Promociones
        |--------------------------------------------------------------------------
        */

        Route::livewire('/promotions', 'promotions.index')
            ->name('promotions.index');

        Route::livewire('/promotions/create', 'promotions.create')
            ->name('promotions.create');

        Route::livewire(
            '/promotions/{promotion}/edit',
            'promotions.edit'
        )->name('promotions.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Administración
    |--------------------------------------------------------------------------
    | Solo administradores.
    */

    Route::middleware(
        EnsureUserHasRole::class . ':admin'
    )->group(function (): void {
        /*
        |--------------------------------------------------------------------------
        | Empresa
        |--------------------------------------------------------------------------
        */

        Route::livewire('/company', 'company.edit')
            ->name('company.edit');

        /*
        |--------------------------------------------------------------------------
        | Usuarios
        |--------------------------------------------------------------------------
        */

        Route::livewire('/users', 'users.index')
            ->name('users.index');

        Route::livewire('/users/create', 'users.create')
            ->name('users.create');

        Route::livewire('/users/{user}/edit', 'users.edit')
            ->name('users.edit');
    });
});

/*
|--------------------------------------------------------------------------
| Configuración de cuenta
|--------------------------------------------------------------------------
*/

require __DIR__ . '/settings.php';