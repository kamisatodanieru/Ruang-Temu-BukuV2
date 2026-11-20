<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Periksa apakah pengguna yang login memiliki salah satu role yang diizinkan.
     * Prinsip: deny by default.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $user->loadMissing('role');

        if (! $user->hasRole(...$roles)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki kewenangan untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
