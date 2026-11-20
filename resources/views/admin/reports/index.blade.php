<x-app-layout title="Laporan" header="Laporan Peminjaman">
    <div class="space-y-6">
        <!-- Header Khusus Mode Cetak -->
        <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-6">
            <h1 class="font-serif text-xl font-bold text-slate-900">RUANG TEMU BUKU — LAPORAN PEMINJAMAN</h1>
            <p class="text-xs text-slate-600 mt-1 font-mono">
                Dicetak pada: {{ now()->format('d M Y H:i') }}
                @if ($filters['start_date'] || $filters['end_date'])
                    | Periode: {{ $filters['start_date'] ?: 'Awal' }} s/d {{ $filters['end_date'] ?: 'Sekarang' }}
                @endif
            </p>
        </div>

        <!-- Panel Saringan & Ekspor -->
        <div class="print:hidden rtb-card rounded-2xl p-5 sm:p-6 space-y-4">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <div class="lg:col-span-2">
                    <label for="search" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Cari Anggota / Buku</label>
                    <input id="search" name="search" type="text" value="{{ $filters['search'] }}"
                           placeholder="Nama/email anggota atau judul/ISBN buku..."
                           class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-sm">
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Status</label>
                    <select id="status" name="status" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>
                                {{ $statusOption->label() }}
                            </option>
                        @endforeach
                        <option value="overdue" @selected($filters['status'] === 'overdue')>Terlambat</option>
                    </select>
                </div>

                <div>
                    <label for="start_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Dari Tanggal</label>
                    <input id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] }}"
                           class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 font-mono text-sm">
                    @error('start_date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Sampai Tanggal</label>
                    <input id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] }}"
                           class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 font-mono text-sm">
                    @error('end_date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-200/80">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-slate-800 hover:bg-slate-900">
                            Terapkan
                        </button>
                        <a href="{{ route('admin.reports.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
                            Atur Ulang
                        </a>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button type="button" onclick="window.print()"
                                class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50">
                            Cetak Laporan
                        </button>
                        <a href="{{ route('admin.reports.export', request()->query()) }}"
                           class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm">
                            Unduh CSV
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Laporan -->
        <div class="rtb-card rounded-2xl overflow-hidden print:border-slate-400 print:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">ID</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Anggota</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Buku</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tgl Pengajuan</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tgl Pinjam / Tempo</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tgl Kembali</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($loans as $loan)
                            <tr>
                                <td class="px-4 py-3.5 text-xs text-slate-500 font-mono">
                                    #{{ $loan->id }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-slate-900">{{ $loan->member?->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $loan->member?->email }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-serif font-medium text-slate-900">{{ $loan->book?->title ?? '-' }}</div>
                                    @if ($loan->book?->isbn)
                                        <div class="font-mono text-[11px] text-slate-500">ISBN: {{ $loan->book->isbn }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs text-slate-600">
                                    {{ $loan->requested_at?->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs text-slate-600">
                                    <div>Pinjam: {{ $loan->borrowed_at?->format('d M Y') ?? '-' }}</div>
                                    <div>Tempo: {{ $loan->due_at?->format('d M Y') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs text-slate-600">
                                    {{ $loan->returned_at?->format('d M Y H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($loan->isOverdue())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            Terlambat
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                            {{ $loan->status->label() }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                    Tidak ada data transaksi yang sesuai dengan filter laporan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($loans->hasPages())
                <div class="print:hidden px-5 py-3.5 border-t border-slate-200 bg-slate-50">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
