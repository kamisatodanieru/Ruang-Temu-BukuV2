<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'role' => ['required', 'string', Rule::in([RoleName::Admin->value, RoleName::Petugas->value, RoleName::Anggota->value])],
            'password' => ['required', 'string', 'confirmed', 'min:15', 'max:64', Password::min(15)->max(64)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 15 karakter.',
            'password.max' => 'Kata sandi maksimal 64 karakter.',
        ];
    }
}
