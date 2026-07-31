<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public int $userId;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'operator';

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $isCurrentUser = false;

    public function mount(User $user): void
    {
        abort_unless(
            auth()->user()?->role === 'admin',
            403
        );

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->role = $user->role;
        $this->is_active = (bool) $user->is_active;
        $this->isCurrentUser = $user->is(auth()->user());
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
                Rule::unique('users', 'email')
                    ->ignore($this->userId),
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
                'nullable',
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
            'email.unique' => 'Ya existe otro usuario con este correo.',

            'role.in' => 'Selecciona un rol válido.',

            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    public function update(): void
    {
        $validated = $this->validate();

        $user = User::query()->findOrFail(
            $this->userId
        );

        /*
         * El administrador conectado no puede quitarse sus propios
         * permisos ni desactivar su propia cuenta.
         */
        if ($user->is(auth()->user())) {
            $validated['role'] = 'admin';
            $validated['is_active'] = true;
        }

        if (
            $user->role === 'admin'
            && (
                $validated['role'] !== 'admin'
                || ! (bool) $validated['is_active']
            )
            && User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->doesntExist()
        ) {
            $this->addError(
                'role',
                'Debe existir al menos un administrador activo.'
            );

            return;
        }

        $data = [
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => filled($validated['phone'])
                ? trim($validated['phone'])
                : null,
            'role' => $validated['role'],
            'is_active' => (bool) $validated['is_active'],
            'email_verified_at' => $user->email_verified_at ?? now(),
        ];

        if (filled($validated['password'])) {
            $data['password'] = Hash::make(
                $validated['password']
            );
        }

        $user->forceFill($data)->save();

        session()->flash(
            'success',
            'El usuario fue actualizado correctamente.'
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
                Editar usuario
            </flux:heading>

            <flux:text class="mt-1">
                Actualiza los datos, permisos y estado de la cuenta.
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
        wire:submit="update"
        class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7 dark:border-zinc-700 dark:bg-zinc-900"
    >
        @if ($isCurrentUser)
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
                Estás editando tu propia cuenta. Por seguridad, no podrás
                desactivarla ni quitarle el rol de administrador.
            </div>
        @endif

        <div>
            <flux:heading size="lg">
                Información de la cuenta
            </flux:heading>
        </div>

        <div class="grid items-start gap-6 md:grid-cols-2">

            <div>
                <flux:input
                    label="Nombre completo"
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
                    for="edit-user-role"
                    class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >
                    Rol
                </label>

                <select
                    id="edit-user-role"
                    wire:model="role"
                    @disabled($isCurrentUser)
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:scheme-dark"
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

        </div>

        <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">

            <flux:heading size="lg">
                Cambiar contraseña
            </flux:heading>

            <flux:text class="mt-1">
                Déjala vacía para conservar la contraseña actual.
            </flux:text>

        </div>

        <div class="grid items-start gap-6 md:grid-cols-2">

            <div>
                <flux:input
                    type="password"
                    label="Nueva contraseña"
                    placeholder="Mínimo 8 caracteres"
                    wire:model="password"
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
                    label="Confirmar nueva contraseña"
                    wire:model="password_confirmation"
                    viewable
                />
            </div>

        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:checkbox
                label="Cuenta activa"
                description="Una cuenta inactiva será expulsada del panel y no podrá utilizarlo."
                wire:model="is_active"
                :disabled="$isCurrentUser"
            />
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
                wire:target="update"
            >
                <span wire:loading.remove wire:target="update">
                    Guardar cambios
                </span>

                <span wire:loading wire:target="update">
                    Actualizando...
                </span>
            </flux:button>

        </div>

    </form>

</div>