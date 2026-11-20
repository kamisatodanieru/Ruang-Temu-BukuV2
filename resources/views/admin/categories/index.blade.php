<x-app-layout title="Kategori" header="Kategori Buku">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Tambah Kategori -->
        <div class="lg:col-span-1">
            <div class="rtb-card rounded-2xl p-6">
                <h2 class="font-serif text-base font-semibold text-slate-900 mb-1">Tambah Kategori</h2>
                <p class="text-xs text-slate-500 mb-5">Tambahkan kategori baru untuk mengelompokkan buku.</p>

                <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4" novalidate>
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Nama Kategori</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="100"
                               placeholder="Contoh: Teknologi Informasi"
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm {{ $errors->has('name') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Simpan Kategori
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Kategori -->
        <div class="lg:col-span-2">
            <div class="rtb-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nama Kategori</th>
                                <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Jumlah Buku</th>
                                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="px-5 py-3.5">
                                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <label for="category-name-{{ $category->id }}" class="sr-only">Nama Kategori</label>
                                            <input id="category-name-{{ $category->id }}" name="name" type="text" value="{{ $category->name }}" required maxlength="100"
                                                   class="block w-full max-w-xs rounded-xl border border-slate-300 px-3 py-1.5 text-sm">
                                            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                                                Simpan
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-mono text-xs font-medium bg-slate-100 text-slate-800">
                                            {{ $category->books_count }} buku
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline"
                                              data-confirm="Apakah Anda yakin ingin menghapus kategori {{ $category->name }}?">
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
                                    <td colspan="3" class="px-5 py-10 text-center text-slate-500">
                                        Belum ada kategori buku yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($categories->hasPages())
                    <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50">
                        {{ $categories->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
