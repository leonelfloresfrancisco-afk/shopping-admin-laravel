<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Permite el acceso únicamente a los roles indicados en la ruta.
     *
     * Ejemplo:
     * EnsureUserHasRole::class . ':admin,manager'
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        abort_unless(
            $user !== null
            && in_array($user->role, $roles, true),
            403,
            'No tienes autorización para acceder a esta sección.'
        );

        return $next($request);
    }
}