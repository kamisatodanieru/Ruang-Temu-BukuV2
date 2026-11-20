<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Proses registrasi publik.
     * Selalu menetapkan role 'anggota' dan status 'active' dari sisi server.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->safe()->only(['name', 'email', 'password']);

        $user = DB::transaction(function () use ($validated, $request): User {
            $anggotaRole = Role::query()->firstOrCreate([
                'name' => RoleName::Anggota->value,
            ]);

            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Ditetapkan secara eksplisit di server, tidak pernah dari input request
            $user->role_id = $anggotaRole->id;
            $user->status = UserStatus::Active;
            $user->save();

            ActivityLogger::record(
                action: 'user.registered',
                description: "Registrasi akun anggota baru: {$user->email}",
                subject: $user,
                actor: $user,
                request: $request,
            );

            return $user;
        });

        return redirect()->route('login')->with('status', 'Registrasi berhasil! Silakan masuk menggunakan akun baru Anda.');
    }
}
