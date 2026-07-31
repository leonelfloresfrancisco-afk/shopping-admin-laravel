<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public string $perPage = '10';

    public ?string $message = null;

    public string $messageType = 'success';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->role === 'admin',
            403
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, ['10', '25', '50'], true)) {
            $this->perPage = '10';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = '';

        $this->resetPage();
    }

    /**
     * Activa o desactiva una cuenta.
     */
    public function toggleStatus(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $authenticatedUser = auth()->user();

        if ($user->is($authenticatedUser)) {
            $this->messageType = 'error';
            $this->message = 'No puedes desactivar tu propia cuenta.';

            return;
        }

        $newStatus = ! (bool) $user->is_active;

        if (
            ! $newStatus
            && $user->role === 'admin'
            && User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->doesntExist()
        ) {
            $this->messageType = 'error';
            $this->message = 'Debe existir al menos un administrador activo.';

            return;
        }

        $user->is_active = $newStatus;
        $user->save();

        $this->messageType = 'success';

        $this->message = $user->is_active
            ? 'El usuario fue activado correctamente.'
            : 'El usuario fue desactivado correctamente.';
    }

    /**
     * Datos enviados a la interfaz.
     */
    public function with(): array
    {
        $search = trim($this->search);

        return [
            'users' => User::query()
                ->when(
                    $search !== '',
                    function (Builder $query) use ($search): void {
                        $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    }
                )
                ->when(
                    $this->roleFilter !== '',
                    fn (Builder $query) => $query->where(
                        'role',
                        $this->roleFilter
                    )
                )
                ->when(
                    $this->statusFilter === 'active',
                    fn (Builder $query) => $query->where(
                        'is_active',
                        true
                    )
                )
                ->when(
                    $this->statusFilter === 'inactive',
                    fn (Builder $query) => $query->where(
                        'is_active',
                        false
                    )
                )
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate((int) $this->perPage),

            'totalUsers' => User::query()->count(),

            'activeUsers' => User::query()
                ->where('is_active', true)
                ->count(),

            'administratorUsers' => User::query()
                ->where('role', 'admin')
                ->count(),

            'inactiveUsers' => User::query()
                ->where('is_active', false)
                ->count(),
        ];
    }
};

?>

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Usuarios
            </flux:heading>

            <flux:text class="mt-1">
                Administra cuentas, roles, permisos y acceso al panel.
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            :href="route('users.create')"
            wire:navigate
        >
            Nuevo usuario
        </flux:button>

    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($message)
        <div
            @class([
                'rounded-xl border px-4 py-3 text-sm font-medium',
                'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200' => $messageType === 'success',
                'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200' => $messageType === 'error',
            ])
        >
            {{ $message }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total de usuarios
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $totalUsers }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Usuarios activos
            </p>

            <p class="mt-2 text-3xl font-semibold text-green-600 dark:text-green-400">
                {{ $activeUsers }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Administradores
            </p>

            <p class="mt-2 text-3xl font-semibold text-blue-600 dark:text-blue-400">
                {{ $administratorUsers }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Usuarios inactivos
            </p>

            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ $inactiveUsers }}
            </p>
        </div>

    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        <div class="grid gap-4 border-b border-zinc-200 p-4 md:grid-cols-2 xl:grid-cols-5 dark:border-zinc-700">

            <div class="md:col-span-2">
                <flux:input
                    label="Buscar usuarios"
                    placeholder="Nombre, correo o teléfono..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.500ms="search"
                />
            </div>

            <div>
                <label
                    for="users-role-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Rol
                </label>

                <select
                    id="users-role-filter"
                    wire:model.live="roleFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="">Todos los roles</option>
                    <option value="admin">Administradores</option>
                    <option value="manager">Gestores</option>
                    <option value="operator">Operadores</option>
                </select>
            </div>

            <div>
                <label
                    for="users-status-filter"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Estado
                </label>

                <select
                    id="users-status-filter"
                    wire:model.live="statusFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>

            <div>
                <label
                    for="users-per-page"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Registros
                </label>

                <select
                    id="users-per-page"
                    wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="10">10 registros</option>
                    <option value="25">25 registros</option>
                    <option value="50">50 registros</option>
                </select>
            </div>

            @if (
                $search !== ''
                || $roleFilter !== ''
                || $statusFilter !== ''
            )
                <div class="md:col-span-2 xl:col-span-5">
                    <flux:button
                        size="sm"
                        icon="x-mark"
                        wire:click="clearFilters"
                    >
                        Limpiar filtros
                    </flux:button>
                </div>
            @endif

        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">

            @forelse ($users as $user)

                @php
                    $roleLabel = match ($user->role) {
                        'admin' => 'Administrador',
                        'manager' => 'Gestor',
                        default => 'Operador',
                    };

                    $roleColor = match ($user->role) {
                        'admin' => 'blue',
                        'manager' => 'amber',
                        default => 'zinc',
                    };

                    $isCurrentUser = $user->is(auth()->user());
                @endphp

                <article
                    wire:key="user-{{ $user->id }}"
                    class="grid gap-5 p-4 transition lg:grid-cols-[minmax(0,2fr)_160px_150px_auto] lg:items-center sm:p-5 dark:hover:bg-zinc-800/50"
                >
                    <div class="flex min-w-0 items-center gap-4">

                        <flux:avatar
                            :name="$user->name"
                            :initials="$user->initials()"
                        />

                        <div class="min-w-0">

                            <div class="flex flex-wrap items-center gap-2">

                                <h3 class="truncate font-semibold text-zinc-900 dark:text-white">
                                    {{ $user->name }}
                                </h3>

                                @if ($isCurrentUser)
                                    <flux:badge color="green">
                                        Tu cuenta
                                    </flux:badge>
                                @endif

                            </div>

                            <p class="mt-1 truncate text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $user->email }}
                            </p>

                            @if ($user->phone)
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $user->phone }}
                                </p>
                            @endif

                        </div>

                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Rol
                        </p>

                        <flux:badge :color="$roleColor">
                            {{ $roleLabel }}
                        </flux:badge>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Estado
                        </p>

                        @if ($user->is_active)
                            <flux:badge color="green">
                                Activo
                            </flux:badge>
                        @else
                            <flux:badge color="red">
                                Inactivo
                            </flux:badge>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2 lg:justify-end">

                        <flux:button
                            size="sm"
                            icon="pencil-square"
                            :href="route('users.edit', $user)"
                            wire:navigate
                        >
                            Editar
                        </flux:button>

                        <flux:button
                            size="sm"
                            icon="power"
                            wire:click="toggleStatus({{ $user->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStatus({{ $user->id }})"
                            :disabled="$isCurrentUser"
                        >
                            {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                        </flux:button>

                    </div>

                </article>

            @empty

                <div class="px-6 py-14 text-center">

                    <p class="font-medium text-zinc-700 dark:text-zinc-200">
                        No se encontraron usuarios.
                    </p>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea un usuario o modifica los filtros.
                    </p>

                    <flux:button
                        class="mt-5"
                        variant="primary"
                        icon="plus"
                        :href="route('users.create')"
                        wire:navigate
                    >
                        Crear usuario
                    </flux:button>

                </div>

            @endforelse

        </div>

    </div>

    @if ($users->hasPages())
        <div>
            {{ $users->links() }}
        </div>
    @endif

</div>