<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración Livewire
    |--------------------------------------------------------------------------
    */

    'class_namespace' => 'App\\Livewire',

    'view_path' => resource_path('views/livewire'),

    'layout' => 'components.layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Subida temporal de archivos
    |--------------------------------------------------------------------------
    |
    | Esta configuración controla la primera subida que hace Livewire
    | antes de guardar en Cloudinary.
    |
    */

    'temporary_file_upload' => [

        'disk' => 'local',

        'rules' => [
            'required',
            'file',
            'max:10240',
        ],

        'directory' => 'livewire-tmp',

        'middleware' => null,

        'preview_mimes' => [
            'png',
            'gif',
            'bmp',
            'svg',
            'wav',
            'mp4',
            'mov',
            'avi',
            'webm',
            'jpg',
            'jpeg',
            'webp',
        ],

        'max_upload_time' => 5,

    ],

];