# Shopping Admin Platform

Sistema ecommerce desarrollado con Laravel y Livewire.

## Descripción

Plataforma web para administrar un catálogo de productos y gestionar
una tienda pública moderna.

Incluye panel administrativo, gestión de productos, categorías,
marcas, promociones y configuración empresarial.

## Tecnologías

- Laravel 13
- PHP 8.4
- Livewire 4
- Tailwind CSS
- MySQL
- Vite
- Laravel Fortify

## Funcionalidades

### Panel administrativo

- Dashboard con estadísticas
- Gestión de productos
- Gestión de categorías
- Gestión de marcas
- Carrusel de portada
- Promociones
- Usuarios y roles
- Configuración de empresa

### Tienda pública

- Catálogo responsive
- Filtros por categorías
- Vista detalle de producto
- Galería de imágenes
- Diseño adaptado para móvil, tablet y escritorio

## Arquitectura

- MVC Laravel
- Eloquent ORM
- Livewire Components
- Blade Templates
- Middleware de autorización
- Migrations

## Instalación

```bash
git clone URL_DEL_REPOSITORIO

composer install

npm install

cp .env.example .env

php artisan key:generate

php artisan migrate

npm run build

php artisan serve