<?php

namespace App\Http\Requests\Admin;

use App\Models\Tag;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tagId = $this->route('tag')?->id;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique(Tag::class, 'name')->ignore($tagId),
                function (string $attribute, mixed $value, Closure $fail) use ($tagId): void {
                    $slug = Str::slug(trim((string) $value));
                    if ($slug === '') {
                        return;
                    }

                    $exists = Tag::query()
                        ->where('slug', $slug)
                        ->when($tagId !== null, fn ($query) => $query->whereKeyNot($tagId))
                        ->exists();

                    if ($exists) {
                        $fail('Nama tag menghasilkan slug yang sudah digunakan oleh tag lain.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tag wajib diisi.',
            'name.min' => 'Nama tag minimal 2 karakter.',
            'name.max' => 'Nama tag maksimal 50 karakter.',
            'name.unique' => 'Nama tag sudah terdaftar dalam sistem.',
        ];
    }
}
