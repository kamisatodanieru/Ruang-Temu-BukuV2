<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Proses penyimpanan password baru menggunakan token broker Laravel.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'confirmed', 'min:15', 'max:64', Rules\Password::min(15)->max(64)],
        ], [
            'token.required' => 'Token atur ulang kata sandi tidak valid.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 15 karakter.',
            'password.max' => 'Kata sandi maksimal 64 karakter.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request): void {
                $user->forceFill([
                    'password' => Hash::make((string) $request->string('password')),
                    'remember_token' => Str::random(60),
                ])->save();

                // Akhiri seluruh session aktif pengguna untuk mencegah persistensi akses tidak sah
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));

                ActivityLogger::record(
                    action: 'auth.password_reset',
                    description: 'Password akun berhasil diatur ulang melalui token.',
                    subject: $user,
                    actor: $user,
                    request: $request,
                );
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Kata sandi Anda berhasil diperbarui. Silakan masuk dengan kata sandi baru.');
        }

        throw ValidationException::withMessages([
            'email' => ['Token atur ulang kata sandi tidak valid atau sudah kedaluwarsa.'],
        ]);
    }
}
