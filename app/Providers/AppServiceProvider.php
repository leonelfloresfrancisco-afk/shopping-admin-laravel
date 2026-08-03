<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ImageStorage;
use App\Models\CompanySetting;
use App\Services\ImageStorage\CloudinaryStorage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        | Almacenamiento de imágenes
        |--------------------------------------------------------------------------
        |
        | Permite solicitar ImageStorage desde cualquier componente o servicio.
        | Actualmente utiliza Cloudinary como proveedor.
        |
        */

        $this->app->bind(
            ImageStorage::class,
            CloudinaryStorage::class
        );

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
        /*
        |--------------------------------------------------------------------------
        | HTTPS en producción
        |--------------------------------------------------------------------------
        |
        | Render entrega la aplicación mediante HTTPS.
        | Esto obliga a Laravel a generar enlaces seguros para recursos y rutas.
        |
        */

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

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