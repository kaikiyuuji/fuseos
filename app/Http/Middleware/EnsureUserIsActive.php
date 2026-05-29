<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * AUTH-06 — Bloqueia contas desativadas, mesmo portando token válido.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            abort(403, 'Conta desativada. Contate a equipe de TI.');
        }

        return $next($request);
    }
}
