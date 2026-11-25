<x-app-layout title="Peminjaman" header="Kelola Peminjaman">
    <div class="space-y-6">
        <!-- Ringkasan Antrean -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div style="--stagger: 1" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu Persetujuan</p>
                <p class="mt-2 font-mono text-2xl font-bold text-amber-600">{{ $pendingCount }} permintaan</p>
            </div>
            <div style="--stagger: 2" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu Pengembalian</p>
                <p class="mt-2 font-mono text-2xl font-bold text-emerald-600">{{ $returnPendingCount ?? 0 }} transaksi</p>
            </div>
            <div style="--stagger: 3" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sedang Dipinjam</p>
                <p class="mt-2 font-mono text-2xl font-bold text-indigo-600">{{ $borrowedCount }} transaksi</p>
            </div>
        </div>

        <!-- Pencarian & Saring -->
        <div class="rtb-card rounded-2xl p-5">
            <form method="GET" action="{{ route('staff.loans.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2">
                    <label for="search" class="sr-only">Cari anggota atau buku</label>
                    <input id="search" name="search" type="text" value="{{ $filters['search'] }}"
                           placeholder="Cari nama/email anggota atau judul/ISBN buku..."
                           class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="status" class="sr-only">Pilih Status</label>
                    <select id="status" name="status" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="">Semua Status</option>
                        <option value="returns" @selected($filters['status'] === 'returns')>Antrean Pengembalian</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>
                                {{ $statusOption->label() }}
                            </option>
                        @endforeach
                        <option value="overdue" @selected($filters['status'] === 'overdue')>Terlambat</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 px-3.5 rounded-xl text-sm font-medium text-white bg-slate-800 hover:bg-slate-900">
                        Terapkan
                    </button>
                    <a href="{{ route('staff.loans.index') }}" class="py-2 px-3.5 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
                        Atur Ulang
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabel Transaksi -->
        <div class="rtb-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Anggota</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Buku</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tanggal</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($loans as $loan)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-900">{{ $loan->member?->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $loan->member?->email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-serif font-semibold text-slate-900">{{ $loan->book?->title }}</div>
                                    <div class="text-xs text-slate-500">
                                        Stok tersedia: <span class="font-mono font-medium">{{ $loan->book?->available_stock ?? 0 }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-mono text-xs text-slate-600">
                                    <div>Diajukan: {{ $loan->requested_at?->format('d M Y H:i') }}</div>
                                    @if ($loan->due_at)
                                        <div class="{{ $loan->isOverdue() ? 'font-bold text-rose-600' : 'text-slate-600' }}">
                                            Tempo: {{ $loan->due_at->format('d M Y H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($loan->isOverdue())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                            Terlambat {{ $loan->overdueDays() }} hari
                                        </span>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::ReturnPending)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            {{ $loan->status->label() }}
                                        </span>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::Borrowed)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ $loan->status->label() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                            {{ $loan->status->label() }}
                                        </span>
                                    @endif

                                    @if ($loan->fine || $loan->isOverdue())
                                        @php
                                            $remainingFine = $loan->effectiveRemainingFine();
                                            $totalFine = $loan->effectiveFineTotal();
                                            $isFineSettled = $remainingFine === 0 && $loan->fine?->isPaid();
                                        @endphp
                                        <div class="mt-1 font-mono text-xs font-semibold {{ $isFineSettled ? 'text-emerald-600' : 'text-rose-600' }}">
                                            Denda: Rp {{ number_format($isFineSettled ? $totalFine : $remainingFine, 0, ',', '.') }}
                                            &bull; {{ $isFineSettled ? $loan->fine->status->label() : ($loan->fine?->status->label() ?? 'Estimasi') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap space-x-1.5">
                                    @if ($loan->status === \App\Enums\LoanStatus::Pending)
                                        <form method="POST" action="{{ route('staff.loans.approve', $loan) }}" class="inline"
                                              data-confirm="Setujui peminjaman dan serahkan buku kepada {{ $loan->member?->name ?? '' }}?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                                Setujui
                                            </button>
                                        </form>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::ReturnPending)
                                        <form method="POST" action="{{ route('staff.loans.return', $loan) }}" class="inline"
                                              data-confirm="Konfirmasi bahwa buku {{ $loan->book?->title ?? '' }} telah diterima kembali dari {{ $loan->member?->name ?? '' }}?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                                Konfirmasi Kembali
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('staff.loans.show', $loan) }}"
                                       class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                    Tidak ada data transaksi peminjaman yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($loans->hasPages())
                <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
