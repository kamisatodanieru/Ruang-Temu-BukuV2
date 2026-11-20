<x-app-layout title="Ubah Buku" header="Ubah Buku">
    <div class="max-w-3xl">
        <div class="rtb-card rounded-2xl p-6 sm:p-7">
            <div class="mb-6 pb-4 border-b border-slate-200">
                <h2 class="font-serif text-lg font-semibold text-slate-900">Ubah Data Buku</h2>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi buku <strong>{{ $book->title }}</strong>.</p>
            </div>

            <form method="POST" action="{{ route('admin.books.update', $book) }}" enctype="multipart/form-data" class="space-y-5" novalidate>
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label for="title" class="block text-sm font-medium text-slate-700">Judul Buku *</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $book->title) }}" required
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm {{ $errors->has('title') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('title')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="author" class="block text-sm font-medium text-slate-700">Pengarang *</label>
                        <input id="author" name="author" type="text" value="{{ old('author', $book->author) }}" required
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm {{ $errors->has('author') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('author')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700">Kategori *</label>
                        <select id="category_id" name="category_id" required
                                class="mt-1.5 block w-full rounded-xl border px-3.5 py-2.5 text-sm bg-white {{ $errors->has('category_id') ? 'border-rose-500' : 'border-slate-300' }}">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $book->category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="tags" class="block text-sm font-medium text-slate-700">
                            Tag Buku
                        </label>
                        <p class="text-xs text-slate-500 mb-1.5">
                            Pisahkan dengan koma, maksimal 5 tag.
                        </p>
                        <input id="tags" name="tags" type="text"
                               value="{{ old('tags') !== null ? (is_array(old('tags')) ? implode(', ', old('tags')) : old('tags')) : $book->tags->pluck('name')->implode(', ') }}"
                               placeholder="Contoh: Novel, Remaja, Misteri"
                               class="mt-1 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm {{ $errors->has('tags') || $errors->has('tags.*') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('tags')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('tags.*')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror

                        @if ($availableTags->isNotEmpty())
                            <div class="mt-2.5">
                                <span class="text-xs font-medium text-slate-500">Pilih cepat:</span>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($availableTags as $availableTag)
                                        <button type="button"
                                                data-tag-name="{{ $availableTag->name }}"
                                                onclick="toggleTag(this.dataset.tagName)"
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 border border-slate-200 transition cursor-pointer">
                                            + {{ $availableTag->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="publisher" class="block text-sm font-medium text-slate-700">Penerbit</label>
                        <input id="publisher" name="publisher" type="text" value="{{ old('publisher', $book->publisher) }}"
                               class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm">
                        @error('publisher')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="isbn" class="block text-sm font-medium text-slate-700">ISBN</label>
                        <input id="isbn" name="isbn" type="text" value="{{ old('isbn', $book->isbn) }}"
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 font-mono text-sm {{ $errors->has('isbn') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('isbn')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="publication_year" class="block text-sm font-medium text-slate-700">Tahun Terbit</label>
                        <input id="publication_year" name="publication_year" type="number" value="{{ old('publication_year', $book->publication_year) }}" min="1000" max="{{ date('Y') + 1 }}"
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 font-mono text-sm {{ $errors->has('publication_year') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('publication_year')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="total_stock" class="block text-sm font-medium text-slate-700">Total Stok *</label>
                        <input id="total_stock" name="total_stock" type="number" value="{{ old('total_stock', $book->total_stock) }}" min="{{ $book->borrowed_loans_count }}" required
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 font-mono text-sm {{ $errors->has('total_stock') ? 'border-rose-500' : 'border-slate-300' }}">
                        <p class="mt-1 text-xs text-slate-500">
                            Dipinjam: <strong class="font-mono">{{ $book->borrowed_loans_count }}</strong> &bull; Tersedia: <strong class="font-mono">{{ $book->available_stock }}</strong>.
                        </p>
                        @error('total_stock')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="cover" class="block text-sm font-medium text-slate-700">Ganti Sampul Buku</label>
                        <p class="mt-0.5 text-xs text-slate-500">Format JPG, PNG, atau WEBP, maks. 2 MB.</p>
                        @if ($book->coverUrl())
                            <div class="my-2.5">
                                <div class="book-cover-frame w-20 h-28 rounded-lg overflow-hidden" style="width: 5rem; height: 7rem;">
                                    <img src="{{ $book->coverUrl() }}" alt="Sampul {{ $book->title }}" class="w-full h-full object-cover">
                                </div>
                            </div>
                        @endif
                        <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp"
                               class="mt-1.5 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('cover')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700">Sinopsis</label>
                        <textarea id="description" name="description" rows="4"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm">{{ old('description', $book->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.books.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200">
                        Batal
                    </a>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function toggleTag(tagName) {
            const input = document.getElementById('tags');
            if (!input) return;

            let current = input.value.split(',').map(s => s.trim()).filter(Boolean);
            const lowerList = current.map(s => s.toLowerCase());
            const targetLower = tagName.toLowerCase();

            const index = lowerList.indexOf(targetLower);
            if (index > -1) {
                current.splice(index, 1);
            } else {
                if (current.length >= 5) {
                    alert('Buku maksimal memiliki 5 tag.');
                    return;
                }
                current.push(tagName);
            }

            input.value = current.join(', ');
        }
    </script>
</x-app-layout>
