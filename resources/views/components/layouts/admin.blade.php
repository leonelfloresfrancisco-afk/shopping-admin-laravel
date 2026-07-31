<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Admin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <aside class="w-64 bg-white border-r">

        <div class="p-6 text-xl font-bold">
            Shopping Admin
        </div>

        <nav class="px-4 space-y-2">

            <a href="/admin"
               class="block rounded-lg px-4 py-3 hover:bg-gray-100">
                Dashboard
            </a>

            <a href="#"
               class="block rounded-lg px-4 py-3 hover:bg-gray-100">
                Productos
            </a>

            <a href="#"
               class="block rounded-lg px-4 py-3 hover:bg-gray-100">
                Categorías
            </a>

            <a href="#"
               class="block rounded-lg px-4 py-3 hover:bg-gray-100">
                Configuración
            </a>

        </nav>

    </aside>


    <main class="flex-1">

        <header class="h-16 bg-white border-b flex items-center justify-between px-8">

            <h2 class="font-semibold">
                Panel Administrativo
            </h2>

            <span>
                Admin
            </span>

        </header>


        <section class="p-8">

            {{ $slot }}

        </section>

    </main>

</div>


@livewireScripts

</body>

</html>