<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public const GENERIC_RESET_LINK_MESSAGE = 'Jika alamat email tersebut terdaftar, tautan atur ulang kata sandi telah dikirimkan.';

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Proses permintaan tautan reset password dengan respons generik
     * agar tidak membocorkan keberadaan akun (mencegah user enumeration).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', self::GENERIC_RESET_LINK_MESSAGE);
    }
}
