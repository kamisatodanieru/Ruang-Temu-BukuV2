<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing('role');
        Gate::authorize('updateProfile', $user);

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('updateProfile', $user);

        $validated = $request->safe()->only(['name', 'email', 'password']);

        $oldEmail = $user->email;
        $emailChanged = mb_strtolower($validated['email']) !== mb_strtolower($oldEmail);
        $passwordChanged = ! empty($validated['password']);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($passwordChanged) {
            $user->password = Hash::make($validated['password']);
        }

        if ($emailChanged || $passwordChanged) {
            $user->remember_token = Str::random(60);
        }

        $user->save();

        if ($emailChanged) {
            DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
        }

        if ($emailChanged || $passwordChanged) {
            $request->session()->regenerate();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        ActivityLogger::record(
            action: 'profile.updated',
            description: 'Pengguna memperbarui data profil.',
            subject: $user,
            actor: $user,
            request: $request,
        );

        return redirect()->route('profile.edit')->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
