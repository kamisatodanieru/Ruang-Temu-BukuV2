<x-app-layout title="Pengguna" header="Kelola Pengguna">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Tambah Akun -->
        <div class="lg:col-span-1">
            <div class="rtb-card rounded-2xl p-6">
                <h2 class="font-serif text-base font-semibold text-slate-900 mb-1">Tambah Pengguna</h2>
                <p class="text-xs text-slate-500 mb-5">Buat akun baru untuk admin, petugas, atau anggota.</p>

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4" novalidate>
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm {{ $errors->has('name') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Alamat Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                               class="mt-1.5 block w-full rounded-xl border bg-slate-50/50 px-3.5 py-2.5 text-sm {{ $errors->has('email') ? 'border-rose-500' : 'border-slate-300' }}">
                        @error('email')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-slate-700">Peran</label>
                        <select id="role" name="role" required
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm bg-white">
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected(old('role', 'petugas') === $roleOption->value)>
                                    {{ $roleOption->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
                        <div class="relative mt-1.5">
                            <input id="password" name="password" type="password" minlength="15" maxlength="64" required
                                   class="block w-full rounded-xl border bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm {{ $errors->has('password') ? 'border-rose-500' : 'border-slate-300' }}">
                            <x-password-toggle target="password" />
                        </div>
                        <p class="mt-1 text-xs text-slate-500">15 sampai 64 karakter.</p>
                        @error('password')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi</label>
                        <div class="relative mt-1.5">
                            <input id="password_confirmation" name="password_confirmation" type="password" minlength="15" maxlength="64" required
                                   class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 pl-3.5 pr-10 py-2.5 text-sm">
                            <x-password-toggle target="password_confirmation" />
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Buat Akun
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Pengguna -->
        <div class="lg:col-span-2 space-y-4">
            <div class="rtb-card rounded-2xl p-5">
                <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label for="search" class="sr-only">Cari nama atau email</label>
                        <input id="search" name="search" type="text" value="{{ $filters['search'] }}" placeholder="Cari nama atau email..."
                               class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-sm">
                    </div>
                    <div>
                        <label for="filter_role" class="sr-only">Pilih Peran</label>
                        <select id="filter_role" name="role" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                            <option value="">Semua Peran</option>
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected($filters['role'] === $roleOption->value)>
                                    {{ $roleOption->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 py-2 px-3.5 rounded-xl text-sm font-medium text-white bg-slate-800 hover:bg-slate-900">
                            Terapkan
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="py-2 px-3.5 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 whitespace-nowrap">
                            Atur Ulang
                        </a>
                    </div>
                </form>
            </div>

            <div class="rtb-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nama &amp; Email</th>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Peran</th>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($users as $item)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900">{{ $item->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->email }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-slate-100 text-slate-800">
                                            {{ $item->role?->name }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($item->isActive())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @can('toggleStatus', $item)
                                            <form method="POST" action="{{ route('admin.users.toggle-status', $item) }}" class="inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin mengubah status akun {{ addslashes($item->name) }}?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition {{ $item->isActive() ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}">
                                                    {{ $item->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Akun Anda</span>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-slate-500">
                                        Tidak ada data pengguna yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
