<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi dan regenerasi session.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        ActivityLogger::record(
            action: 'auth.login',
            description: 'Pengguna berhasil masuk.',
            subject: $user,
            actor: $user,
            request: $request,
        );

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Akhiri session yang terautentikasi (wajib melalui metode POST).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            ActivityLogger::record(
                action: 'auth.logout',
                description: 'Pengguna keluar dari sistem.',
                subject: $user,
                actor: $user,
                request: $request,
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar.');
    }
}
