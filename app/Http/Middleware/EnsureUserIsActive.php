<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Pastikan akun pengguna yang sedang login masih berstatus aktif.
     * Jika status berubah menjadi inactive saat session aktif, paksa logout dan hapus session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = $request->user()?->fresh();

            if ($user === null || ! $user->isActive()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'Akun Anda tidak aktif. Silakan hubungi administrator perpustakaan.']);
            }
        }

        return $next($request);
    }
}
