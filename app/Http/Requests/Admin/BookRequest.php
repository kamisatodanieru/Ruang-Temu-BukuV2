<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tags')) {
            $raw = $this->input('tags');
            $tagsList = [];

            if (is_string($raw)) {
                $tagsList = array_map('trim', explode(',', $raw));
            } elseif (is_array($raw)) {
                foreach ($raw as $item) {
                    if (is_string($item)) {
                        $tagsList[] = trim($item);
                    }
                }
            }

            $tagsList = array_values(array_filter($tagsList, fn ($t) => $t !== ''));

            // Dedup case-insensitively
            $unique = [];
            foreach ($tagsList as $item) {
                $lower = mb_strtolower($item);
                if (! isset($unique[$lower])) {
                    $unique[$lower] = $item;
                }
            }

            $this->merge([
                'tags' => array_values($unique),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $bookId = $this->route('book')?->id;
        $maxYear = (int) date('Y') + 1;

        return [
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:150'],
            'publisher' => ['nullable', 'string', 'max:150'],
            'isbn' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique(Book::class, 'isbn')->ignore($bookId),
            ],
            'publication_year' => ['nullable', 'integer', 'min:1000', "max:{$maxYear}"],
            'total_stock' => ['required', 'integer', 'min:0', 'max:10000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['required', 'string', 'min:1', 'max:50'],
            'cover' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:min_width=50,min_height=50,max_width=4000,max_height=4000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori buku wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'title.required' => 'Judul buku wajib diisi.',
            'title.max' => 'Judul buku maksimal 255 karakter.',
            'author.required' => 'Nama pengarang wajib diisi.',
            'author.max' => 'Nama pengarang maksimal 150 karakter.',
            'isbn.unique' => 'Nomor ISBN sudah digunakan oleh buku lain.',
            'publication_year.integer' => 'Tahun terbit harus berupa angka tahun yang valid.',
            'publication_year.min' => 'Tahun terbit minimal 1000.',
            'total_stock.required' => 'Total stok wajib diisi.',
            'total_stock.integer' => 'Total stok harus berupa bilangan bulat.',
            'total_stock.min' => 'Total stok tidak boleh bernilai negatif.',
            'tags.max' => 'Buku maksimal hanya boleh memiliki 5 tag.',
            'tags.*.required' => 'Nama tag tidak boleh kosong.',
            'tags.*.max' => 'Setiap tag maksimal 50 karakter.',
            'cover.image' => 'Berkas sampul harus berupa gambar yang valid.',
            'cover.mimes' => 'Format sampul hanya boleh JPG, JPEG, PNG, atau WEBP.',
            'cover.mimetypes' => 'Isi berkas sampul harus berupa gambar JPEG, PNG, atau WEBP.',
            'cover.max' => 'Ukuran berkas sampul maksimal 2 MB.',
            'cover.dimensions' => 'Dimensi gambar sampul minimal 50x50 piksel dan maksimal 4000x4000 piksel.',
        ];
    }
}
