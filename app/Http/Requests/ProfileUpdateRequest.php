<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $isChangingEmail = $user !== null
            && $this->filled('email')
            && mb_strtolower(trim((string) $this->input('email'))) !== mb_strtolower($user->email);

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                Rule::unique(User::class, 'email')->ignore($user?->id),
            ],
            'current_password' => [
                $isChangingEmail ? 'required' : 'nullable',
                'required_with:password',
                'current_password',
            ],
            'password' => ['nullable', 'string', 'confirmed', 'min:15', 'max:64', Password::min(15)->max(64)],
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
            'email.unique' => 'Alamat email sudah digunakan oleh akun lain.',
            'current_password.required' => 'Kata sandi saat ini wajib diisi untuk mengubah alamat email.',
            'current_password.required_with' => 'Kata sandi saat ini wajib diisi untuk mengganti kata sandi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal 15 karakter.',
            'password.max' => 'Kata sandi baru maksimal 64 karakter.',
        ];
    }
}
