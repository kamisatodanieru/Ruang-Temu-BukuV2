<?php

namespace App\Http\Requests\Loan;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Loan::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.required' => 'Buku yang akan dipinjam wajib dipilih.',
            'book_id.integer' => 'ID buku tidak valid.',
        ];
    }
}
