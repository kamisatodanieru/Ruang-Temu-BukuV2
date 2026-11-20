<x-app-layout title="Buku Saya" header="Peminjaman Saya">
    <div class="space-y-6">
        <!-- Ringkasan Kuota & Status -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div style="--stagger: 1" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Kuota Aktif</p>
                <p class="mt-2 font-mono text-2xl font-bold text-slate-900">{{ $activeCount }} / {{ $maxActiveLoans }}</p>
                <p class="mt-1 text-xs text-slate-500">Termasuk yang menunggu persetujuan</p>
            </div>

            <div style="--stagger: 2" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Terlambat</p>
                <p class="mt-2 font-mono text-2xl font-bold {{ $overdueCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ $overdueCount }} buku
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $overdueCount > 0 ? 'Segera kembalikan ke petugas' : 'Tidak ada keterlambatan' }}
                </p>
            </div>

            <div style="--stagger: 3" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pinjam Buku</p>
                    <p class="mt-1 text-xs text-slate-600">Cari buku lain di katalog.</p>
                </div>
                <div class="mt-3">
                    <a href="{{ route('catalog.index') }}"
                       class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Buka Katalog &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Saring Status -->
        <div class="rtb-card rounded-2xl p-5 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('member.loans.index') }}" class="flex flex-wrap items-center gap-3">
                <label for="status" class="text-sm font-medium text-slate-700">Status:</label>
                <select id="status" name="status" class="rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                    <option value="">Semua Transaksi</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected($statusFilter === $statusOption->value)>
                            {{ $statusOption->label() }}
                        </option>
                    @endforeach
                    <option value="overdue" @selected($statusFilter === 'overdue')>Terlambat</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-slate-800 hover:bg-slate-900">
                    Terapkan
                </button>
                @if ($statusFilter !== '')
                    <a href="{{ route('member.loans.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
                        Atur Ulang
                    </a>
                @endif
            </form>
        </div>

        <!-- Daftar Transaksi Peminjaman Anggota -->
        <div class="rtb-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Buku</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tanggal Pengajuan</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Jatuh Tempo</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($loans as $loan)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="font-serif font-semibold text-slate-900">{{ $loan->book?->title ?? 'Buku Tidak Tersedia' }}</div>
                                    <div class="text-xs text-slate-500">{{ $loan->book?->author }}</div>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-600 whitespace-nowrap">
                                    {{ $loan->requested_at?->format('d M Y H:i') }}
                                </td>
                                <td class="px-5 py-4 font-mono text-xs whitespace-nowrap">
                                    @if ($loan->due_at)
                                        <span class="{{ $loan->isOverdue() ? 'font-semibold text-rose-600' : 'text-slate-700' }}">
                                            {{ $loan->due_at->format('d M Y H:i') }}
                                        </span>
                                        @if ($loan->isOverdue())
                                            <div class="text-xs font-sans font-semibold text-rose-600">
                                                Terlambat {{ $loan->overdueDays() }} hari
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($loan->isOverdue())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                            Terlambat
                                        </span>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::Pending)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Menunggu Persetujuan
                                        </span>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::ReturnPending)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Menunggu Pengembalian
                                        </span>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::Borrowed)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Sedang Dipinjam
                                        </span>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::Returned)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Dikembalikan
                                        </span>
                                    @elseif ($loan->status === \App\Enums\LoanStatus::Rejected)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Ditolak
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                            Dibatalkan
                                        </span>
                                    @endif

                                    @if ($loan->fine)
                                        <div class="mt-1 font-mono text-xs font-semibold {{ $loan->fine->isPaid() ? 'text-emerald-600' : 'text-rose-600' }}">
                                            Denda: {{ $loan->fine->formattedAmount() }} &bull; {{ $loan->fine->status->label() }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap space-x-2">
                                    @if ($loan->status === \App\Enums\LoanStatus::Borrowed)
                                        <form method="POST" action="{{ route('member.loans.request-return', $loan) }}" class="inline"
                                              onsubmit="return confirm('Apakah Anda ingin mengajukan pengembalian untuk buku {{ addslashes($loan->book?->title ?? '') }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                                Ajukan Pengembalian
                                            </button>
                                        </form>
                                    @endif

                                    @if (($loan->fine && $loan->fine->isPayable()) || $loan->isOverdue())
                                        <a href="{{ route('member.loans.show', $loan) }}"
                                           class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                            Detail &amp; Bayar Denda
                                        </a>
                                    @else
                                        <a href="{{ route('member.loans.show', $loan) }}"
                                           class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                                            Detail
                                        </a>
                                    @endif

                                    @if ($loan->status === \App\Enums\LoanStatus::Pending)
                                        <form method="POST" action="{{ route('member.loans.cancel', $loan) }}" class="inline"
                                              onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permintaan peminjaman ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200">
                                                Batalkan
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                    Belum ada riwayat atau transaksi peminjaman.
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
