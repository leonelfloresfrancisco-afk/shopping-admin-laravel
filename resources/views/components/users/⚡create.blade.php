<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'operator';

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->role === 'admin',
            403
        );
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'role' => [
                'required',
                'in:admin,manager,operator',
            ],

            'is_active' => [
                'boolean',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un usuario con este correo.',

            'role.in' => 'Selecciona un rol válido.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        User::query()->forceCreate([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => filled($validated['phone'])
                ? trim($validated['phone'])
                : null,
            'role' => $validated['role'],
            'is_active' => (bool) $validated['is_active'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        session()->flash(
            'success',
            'El usuario fue creado correctamente.'
        );

        $this->redirectRoute(
            'users.index',
            navigate: true
        );
    }
};

?>

<div class="mx-auto w-full max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Nuevo usuario
            </flux:heading>

            <flux:text class="mt-1">
                Crea una cuenta y define su nivel de acceso.
            </flux:text>
        </div>

        <flux:button
            icon="arrow-left"
            :href="route('users.index')"
            wire:navigate
        >
            Volver
        </flux:button>

    </div>

    <form
        wire:submit="save"
        class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div>
            <flux:heading size="lg">
                Información de la cuenta
            </flux:heading>

            <flux:text class="mt-1">
                Datos personales y credenciales de acceso.
            </flux:text>
        </div>

        <div class="grid items-start gap-6 md:grid-cols-2">

            <div>
                <flux:input
                    label="Nombre completo"
                    placeholder="Ejemplo: María Pérez"
                    wire:model="name"
                    required
                />

                @error('name')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <flux:input
                    type="email"
                    label="Correo electrónico"
                    placeholder="usuario@empresa.com"
                    wire:model="email"
                    required
                />

                @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <flux:input
                    label="Teléfono"
                    placeholder="+51 999 999 999"
                    wire:model="phone"
                />

                @error('phone')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="new-user-role"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Rol
                </label>

                <select
                    id="new-user-role"
                    wire:model="role"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
                >
                    <option value="operator">
                        Operador
                    </option>

                    <option value="manager">
                        Gestor
                    </option>

                    <option value="admin">
                        Administrador
                    </option>
                </select>

                @error('role')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <flux:input
                    type="password"
                    label="Contraseña"
                    placeholder="Mínimo 8 caracteres"
                    wire:model="password"
                    required
                    viewable
                />

                @error('password')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <flux:input
                    type="password"
                    label="Confirmar contraseña"
                    wire:model="password_confirmation"
                    required
                    viewable
                />
            </div>

        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:checkbox
                label="Cuenta activa"
                description="El usuario podrá iniciar sesión y utilizar las áreas permitidas."
                wire:model="is_active"
            />
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
            <p class="font-semibold">
                Permisos por rol
            </p>

            <p class="mt-2">
                Administrador: acceso total. Gestor: catálogos y contenido.
                Operador: dashboard y productos.
            </p>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:justify-end dark:border-zinc-700">

            <flux:button
                :href="route('users.index')"
                wire:navigate
            >
                Cancelar
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                icon="check"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    Guardar usuario
                </span>

                <span wire:loading wire:target="save">
                    Guardando...
                </span>
            </flux:button>

        </div>

    </form>

</div>