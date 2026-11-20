<x-app-layout title="Cadangan Data" header="Cadangan Data">
    <div class="space-y-6">
        {{-- Ringkasan Pengaturan Cadangan --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div style="--stagger: 1" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jadwal Otomatis</p>
                    <p class="mt-2 font-serif text-xl font-semibold text-slate-900">Setiap Hari</p>
                    <p class="mt-1 text-xs text-slate-500">Berjalan otomatis pukul 02:00 WIB.</p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>

            <div style="--stagger: 2" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Keamanan Arsip</p>
                    <p class="mt-2 font-serif text-xl font-semibold text-slate-900">
                        @if ($policy['encryption_enabled'])
                            <span class="text-emerald-700">Terenkripsi</span>
                        @else
                            <span>Standar</span>
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        @if ($policy['encryption_enabled'])
                            Arsip dilindungi kata sandi.
                        @else
                            Enkripsi arsip belum diaktifkan.
                        @endif
                    </p>
                </div>
                <span class="w-10 h-10 rounded-xl {{ $policy['encryption_enabled'] ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : 'bg-slate-100 border-slate-200 text-slate-600' }} border flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </span>
            </div>

            <div style="--stagger: 3" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pemeriksaan File</p>
                    <p class="mt-2 font-serif text-xl font-semibold text-slate-900">Tersedia</p>
                    <p class="mt-1 text-xs text-slate-500">Keutuhan arsip dapat diperiksa kapan saja.</p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>

            <div style="--stagger: 4" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Batas Penyimpanan</p>
                    <p class="mt-2 font-serif text-xl font-semibold text-slate-900">
                        Maks. {{ $policy['max_backups'] }} Arsip
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Disimpan hingga {{ $policy['retention_days'] }} hari.
                    </p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </span>
            </div>
        </div>

        {{-- Panel Buat Cadangan Baru --}}
        <div style="--stagger: 5" class="rtb-reveal rtb-card rounded-2xl p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="font-serif text-base sm:text-lg font-semibold text-slate-900">
                        Buat Cadangan Baru
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-slate-600">
                        Simpan salinan data perpustakaan beserta sampul buku.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <form method="POST" action="{{ route('admin.backups.store') }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="full">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span>Cadangan Lengkap</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.backups.store') }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="db">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" />
                            </svg>
                            <span>Basis Data Saja</span>
                        </button>
                    </form>

                    @if (count($backups) > 0)
                        <form method="POST" action="{{ route('admin.backups.prune') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50"
                                    title="Hapus arsip lama yang melebihi batas penyimpanan">
                                <span>Bersihkan Arsip Lama</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabel Daftar Arsip Cadangan --}}
        <div style="--stagger: 6" class="rtb-reveal rtb-card rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-serif text-base font-semibold text-slate-900">
                        Daftar Arsip Cadangan
                    </h3>
                    <p class="text-xs text-slate-500">
                        Unduh salinan arsip secara berkala untuk disimpan di perangkat lain.
                    </p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 shrink-0">
                    {{ count($backups) }} / {{ $policy['max_backups'] }} Arsip
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nama Arsip</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Jenis</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Keamanan</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Isi Data</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Ukuran</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($backups as $backup)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-semibold text-slate-900">
                                        {{ $backup['filename'] }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $backup['created_at']->format('d M Y H:i:s') }} WIB &bull; oleh {{ $backup['created_by'] }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($backup['backup_type'] === 'full')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/70">
                                            Lengkap
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            Basis Data
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($backup['encrypted'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Terenkripsi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                            Standar
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-600">
                                    <div>{{ number_format($backup['total_records']) }} data</div>
                                    @if ($backup['backup_type'] === 'full')
                                        <div class="text-slate-500">{{ $backup['cover_files_count'] }} sampul</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-mono text-xs text-slate-600">
                                    {{ $backup['size_formatted'] }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.backups.verify', $backup['filename']) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200"
                                                    title="Periksa keutuhan arsip">
                                                Periksa
                                            </button>
                                        </form>

                                        <a href="{{ route('admin.backups.download', $backup['filename']) }}"
                                           class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200"
                                           title="Unduh arsip cadangan">
                                            Unduh
                                        </a>

                                        <form method="POST" action="{{ route('admin.backups.destroy', $backup['filename']) }}"
                                              class="inline"
                                              onsubmit="return confirm('Hapus arsip {{ $backup['filename'] }} secara permanen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium text-slate-700">Belum ada arsip cadangan.</p>
                                    <p class="mt-1 text-xs text-slate-500">Pilih tombol di atas untuk membuat cadangan pertama.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
