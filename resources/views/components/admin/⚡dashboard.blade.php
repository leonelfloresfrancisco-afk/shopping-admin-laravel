<x-layouts::app :title="__('Dashboard')">

    <div class="space-y-8">

        <div>

            <h1 class="text-3xl font-bold">
                Shopping Admin
            </h1>

            <p class="text-zinc-500 mt-2">
                Bienvenido al panel administrativo.
            </p>

        </div>


        <div class="grid gap-6 md:grid-cols-4">

            <div class="rounded-2xl border bg-white p-6 shadow-sm">

                <p class="text-sm text-zinc-500">
                    Productos
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    0
                </h2>

            </div>


            <div class="rounded-2xl border bg-white p-6 shadow-sm">

                <p class="text-sm text-zinc-500">
                    Categorías
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    0
                </h2>

            </div>


            <div class="rounded-2xl border bg-white p-6 shadow-sm">

                <p class="text-sm text-zinc-500">
                    Usuarios
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    1
                </h2>

            </div>


            <div class="rounded-2xl border bg-white p-6 shadow-sm">

                <p class="text-sm text-zinc-500">
                    Ventas
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    $0
                </h2>

            </div>

        </div>

    </div>

</x-layouts::app>