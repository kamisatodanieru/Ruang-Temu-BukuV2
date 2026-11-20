<x-app-layout title="Tag Buku" header="Tag Buku">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Tambah Tag -->
        <div class="lg:col-span-1">
            <div class="rtb-card rounded-2xl p-6">
                <h2 class="font-serif text-base font-semibold text-slate-900 mb-1">Tambah Tag</h2>
                <p class="text-xs text-slate-500 mb-5">
                    Tambahkan tag untuk memudahkan pencarian buku.
                </p>

                <form method="POST" action="{{ route('admin.tags.store') }}" class="space-y-4" novalidate>
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Nama Tag</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="50"
                               placeholder="Contoh: Fiksi, Sejarah"
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm {{ $errors->has('name') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Simpan Tag
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Tag -->
        <div class="lg:col-span-2">
            <div class="rtb-card rounded-2xl overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <form method="GET" action="{{ route('admin.tags.index') }}" class="flex items-center gap-2 max-w-sm w-full">
                        <label for="search" class="sr-only">Cari tag</label>
                        <input id="search" name="search" type="text" value="{{ $search ?? '' }}"
                               placeholder="Cari nama tag..."
                               class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-sm">
                        <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 transition">
                            Cari
                        </button>
                        @if (!empty($search))
                            <a href="{{ route('admin.tags.index') }}" class="px-3 py-2 rounded-xl text-xs text-slate-600 bg-slate-100 hover:bg-slate-200 whitespace-nowrap">
                                Atur Ulang
                            </a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nama Tag</th>
                                <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Jumlah Buku</th>
                                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($tags as $tag)
                                <tr>
                                    <td class="px-5 py-3.5">
                                        <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <label for="tag-name-{{ $tag->id }}" class="sr-only">Nama Tag</label>
                                            <input id="tag-name-{{ $tag->id }}" name="name" type="text" value="{{ $tag->name }}" required maxlength="50"
                                                   class="block w-full max-w-xs rounded-xl border border-slate-300 px-3 py-1.5 text-sm">
                                            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                                                Simpan
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-mono text-xs font-medium bg-slate-100 text-slate-800">
                                            {{ $tag->books_count }} buku
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" class="inline"
                                              data-confirm="Apakah Anda yakin ingin menghapus tag {{ $tag->name }}? Tag akan dilepas dari seluruh buku.">
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
                                        Belum ada tag buku yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($tags->hasPages())
                    <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50">
                        {{ $tags->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
