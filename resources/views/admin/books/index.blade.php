<x-app-layout title="Kelola Buku" header="Kelola Buku">
    <div class="space-y-5">
        <!-- Pencarian & Tombol Tambah -->
        <div class="rtb-card rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <form method="GET" action="{{ route('admin.books.index') }}" class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="search" class="sr-only">Cari judul, pengarang, atau ISBN</label>
                    <input id="search" name="search" type="text" value="{{ $filters['search'] }}"
                           placeholder="Cari judul, pengarang, ISBN..."
                           class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="category_id" class="sr-only">Pilih Kategori</label>
                    <select id="category_id" name="category_id" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['category_id'] === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-slate-800 hover:bg-slate-900">
                        Terapkan
                    </button>
                    <a href="{{ route('admin.books.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
                        Atur Ulang
                    </a>
                </div>
            </form>

            <div>
                <a href="{{ route('admin.books.create') }}"
                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                    + Tambah Buku
                </a>
            </div>
        </div>

        <!-- Tabel Koleksi Buku -->
        <div class="rtb-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Buku</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Kategori</th>
                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Total Stok</th>
                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Dipinjam</th>
                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Tersedia</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($books as $book)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3.5">
                                        @if ($book->coverUrl())
                                            <div class="book-cover-frame w-10 h-14 rounded-md overflow-hidden shrink-0" style="width: 2.5rem; height: 3.5rem;">
                                                <img src="{{ $book->coverUrl() }}" alt="Sampul {{ $book->title }}" class="w-full h-full object-cover">
                                            </div>
                                        @else
                                            <div class="book-cover-frame w-10 h-14 rounded-md bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center text-[10px] font-serif italic text-slate-400 shrink-0 text-center leading-tight px-0.5" style="width: 2.5rem; height: 3.5rem;">
                                                Tanpa Sampul
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-serif font-semibold text-slate-900">{{ $book->title }}</div>
                                            <div class="text-xs text-slate-600">{{ $book->author }}</div>
                                            @if ($book->isbn)
                                                <div class="font-mono text-[11px] text-slate-400 mt-0.5">ISBN: {{ $book->isbn }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                            {{ $book->category?->name ?? '-' }}
                                        </span>
                                    </div>
                                    @if ($book->tags->isNotEmpty())
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            @foreach ($book->tags as $tag)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-normal bg-slate-100 text-slate-600">
                                                    #{{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center font-mono font-medium text-slate-800">
                                    {{ $book->total_stock }}
                                </td>
                                <td class="px-5 py-4 text-center font-mono font-medium text-amber-700">
                                    {{ $book->borrowed_loans_count }}
                                </td>
                                <td class="px-5 py-4 text-center font-mono">
                                    @if ($book->available_stock > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $book->available_stock }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Habis
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap space-x-1.5">
                                    <a href="{{ route('admin.books.edit', $book) }}"
                                       class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        Ubah
                                    </a>
                                    <form method="POST" action="{{ route('admin.books.destroy', $book) }}" class="inline"
                                          data-confirm="Apakah Anda yakin ingin menghapus buku {{ $book->title }} dari katalog?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                    Belum ada koleksi buku yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($books->hasPages())
                <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50">
                    {{ $books->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
