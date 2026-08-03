<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Livewire Settings
    |--------------------------------------------------------------------------
    */

    'class_namespace' => 'App\\Livewire',

    'view_path' => resource_path('views/livewire'),

    'layout' => 'components.layouts.app',


    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Configuración de archivos temporales usados por wire:model.
    | Después de esta etapa el archivo será enviado a Cloudinary.
    |
    */

    'temporary_file_upload' => [

        /*
        |--------------------------------------------------------------------------
        | Disco temporal
        |--------------------------------------------------------------------------
        |
        | Render permite escritura temporal dentro del contenedor.
        | Usamos local para evitar problemas con storage público.
        |
        */

        'disk' => 'local',


        /*
        |--------------------------------------------------------------------------
        | Validación temporal
        |--------------------------------------------------------------------------
        |
        | 10240 KB = 10 MB.
        | El formulario del producto mantiene su propio límite de 3 MB.
        |
        */

        'rules' => [
            'required',
            'file',
            'max:10240',
        ],


        /*
        |--------------------------------------------------------------------------
        | Carpeta temporal
        |--------------------------------------------------------------------------
        */

        'directory' => 'livewire-tmp',


        /*
        |--------------------------------------------------------------------------
        | Middleware
        |--------------------------------------------------------------------------
        |
        | Evita bloqueos adicionales durante la subida temporal.
        |
        */

        'middleware' => null,


        /*
        |--------------------------------------------------------------------------
        | Tipos permitidos para vista previa
        |--------------------------------------------------------------------------
        */

        'preview_mimes' => [
            'png',
            'jpg',
            'jpeg',
            'webp',
            'gif',
            'svg',
        ],


        /*
        |--------------------------------------------------------------------------
        | Tiempo máximo de subida
        |--------------------------------------------------------------------------
        */

        'max_upload_time' => 10,

    ],


    /*
    |--------------------------------------------------------------------------
    | Pagination Theme
    |--------------------------------------------------------------------------
    */

    'pagination_theme' => 'tailwind',

];