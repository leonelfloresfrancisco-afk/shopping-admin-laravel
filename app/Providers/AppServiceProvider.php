<?php

namespace App\Providers;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LogoutResponse;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios de la aplicación.
     */
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Redirección después de cerrar sesión
        |--------------------------------------------------------------------------
        |
        | La página "/" será la tienda pública.
        | Sin embargo, después de cerrar sesión queremos enviar al usuario
        | directamente al formulario de acceso.
        |
        */

        $this->app->instance(
            LogoutResponse::class,
            new class implements LogoutResponse
            {
                public function toResponse($request)
                {
                    return redirect()->route('login');
                }
            }
        );
    }

    /**
     * Inicializa servicios de la aplicación.
     */
    public function boot(): void
    {
        $companySettings = null;

        try {
            if (Schema::hasTable('company_settings')) {
                $companySettings = CompanySetting::current();
            }
        } catch (Throwable) {
            $companySettings = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Configuración compartida
        |--------------------------------------------------------------------------
        |
        | Permite utilizar $companySettings en el panel, login, tienda pública,
        | footer, encabezados, favicon y demás vistas.
        |
        */

        View::share(
            'companySettings',
            $companySettings
        );
    }
}