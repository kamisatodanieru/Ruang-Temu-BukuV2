<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class RejectLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $loan = $this->route('loan');

        return $loan !== null && ($this->user()?->can('reject', $loan) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 3 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ];
    }
}
