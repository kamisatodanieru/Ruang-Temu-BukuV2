<x-app-layout title="Detail Peminjaman" header="Detail Peminjaman">
    <div class="max-w-4xl space-y-6">
        <div>
            <a href="{{ route('staff.loans.index') }}" class="group inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                <span class="inline-block transform group-hover:-translate-x-1 transition-transform duration-200">&larr;</span>
                <span>Kembali ke Daftar Peminjaman</span>
            </a>
        </div>

        <div class="rtb-card rounded-2xl p-6 sm:p-7 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-200">
                <div>
                    <h2 class="font-serif text-xl font-semibold text-slate-900">{{ $loan->book?->title }}</h2>
                    <p class="text-sm text-slate-600">Peminjam: <strong>{{ $loan->member?->name }}</strong> &bull; {{ $loan->member?->email }}</p>
                </div>

                <div>
                    @if ($loan->isOverdue())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                            Terlambat {{ $loan->overdueDays() }} hari
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                            {{ $loan->status->label() }}
                        </span>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-xl bg-slate-50 border border-slate-200/80 p-5 text-sm">
                <div>
                    <dt class="text-xs font-medium text-slate-500">Status Anggota</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $loan->member?->status?->label() ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Stok Tersedia</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $loan->book?->available_stock ?? 0 }} dari {{ $loan->book?->total_stock ?? 0 }} eksemplar</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Waktu Pengajuan</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $loan->requested_at?->format('d M Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Disetujui Oleh</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $loan->approver?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Waktu Pinjam</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $loan->borrowed_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Jatuh Tempo</dt>
                    <dd class="mt-1 font-mono text-xs font-medium {{ $loan->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-900' }}">
                        {{ $loan->due_at?->format('d M Y H:i') ?? '-' }}
                    </dd>
                </div>
            </dl>

            @if ($loan->status === \App\Enums\LoanStatus::Rejected)
                <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                    <p class="font-semibold">Ditolak oleh {{ $loan->rejector?->name ?? 'Petugas' }} pada {{ $loan->rejected_at?->format('d M Y H:i') }}</p>
                    <p class="mt-1">Alasan: {{ $loan->rejection_reason }}</p>
                </div>
            @endif

            @if ($loan->status === \App\Enums\LoanStatus::Returned)
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                    <p class="font-semibold">
                        Buku telah dikembalikan pada {{ $loan->returned_at?->format('d M Y H:i') }}
                        (Dikonfirmasi oleh: {{ $loan->returnConfirmer?->name ?? 'Petugas' }})
                    </p>
                </div>
            @endif

            @if ($loan->fine || $loan->isOverdue())
                @php
                    $effectiveDays = $loan->effectiveOverdueDays();
                    $fineRate = (int) ($loan->fine?->fine_per_day ?: config('library.fine_per_day', 1000));
                    $effectiveTotal = $loan->effectiveFineTotal();
                    $effectiveRemaining = $loan->effectiveRemainingFine();
                    $paidSoFar = (int) ($loan->fine?->paid_amount ?? 0);
                    $isFineSettled = $effectiveRemaining === 0 && $loan->fine?->isPaid();
                @endphp
                <div class="rounded-2xl border {{ $isFineSettled ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' }} p-5 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="font-serif text-sm font-bold text-slate-900">
                                {{ $loan->fine ? 'Tagihan Denda Keterlambatan' : 'Estimasi Denda Keterlambatan Saat Ini' }}
                            </h3>
                            <p class="text-xs text-slate-600 font-mono">
                                Terlambat {{ $effectiveDays }} hari &times; Rp {{ number_format($fineRate, 0, ',', '.') }}/hari
                            </p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $isFineSettled ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ $isFineSettled ? $loan->fine->status->label() : ($loan->fine && ! $loan->fine->isPaid() ? $loan->fine->status->label() : 'Belum Ditagihkan Final') }}
                        </span>
                    </div>
                    <div class="font-mono text-sm font-bold text-slate-900 space-y-1">
                        <div>
                            Total Denda Kumulatif: Rp {{ number_format($effectiveTotal, 0, ',', '.') }}
                            @if ($isFineSettled && $loan->fine?->paid_at)
                                <span class="text-xs font-sans font-normal text-emerald-700 ml-2">(Lunas pada {{ $loan->fine->paid_at->format('d M Y H:i') }})</span>
                            @endif
                        </div>
                        @if ($paidSoFar > 0 && $effectiveRemaining > 0)
                            <div class="text-xs font-normal text-slate-700">
                                Sudah Dibayar: <span class="font-semibold text-emerald-700">Rp {{ number_format($paidSoFar, 0, ',', '.') }}</span>
                                &bull; Sisa Tagihan: <span class="font-bold text-rose-700">Rp {{ number_format($effectiveRemaining, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($loan->status === \App\Enums\LoanStatus::ReturnPending)
                <div class="pt-6 border-t border-slate-200">
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-200 text-amber-900 uppercase tracking-wider">Pengajuan Masuk</span>
                            <h3 class="mt-1 font-serif text-base font-semibold text-amber-950 dark:text-slate-100">Konfirmasi Pengembalian Buku</h3>
                            <p class="mt-1 text-xs text-amber-800 dark:text-slate-300">
                                Diajukan pada <span class="font-mono font-semibold">{{ $loan->return_requested_at?->format('d M Y H:i') }}</span>. Pastikan buku sudah diterima dengan baik.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('staff.loans.return', $loan) }}" class="shrink-0"
                              onsubmit="return confirm('Konfirmasi bahwa buku telah diterima kembali?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition">
                                Konfirmasi Pengembalian
                            </button>
                        </form>
                    </div>
                </div>
            @elseif ($loan->status === \App\Enums\LoanStatus::Borrowed)
                <div class="pt-6 border-t border-slate-200">
                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="font-serif text-sm font-semibold text-slate-800 dark:text-slate-200">Buku Sedang Dipinjam</h3>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                                Anggota belum mengajukan pengembalian. Konfirmasi langsung jika buku sudah diterima.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('staff.loans.return', $loan) }}" class="shrink-0"
                              onsubmit="return confirm('Konfirmasi pengembalian buku secara langsung?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition shadow-sm">
                                Konfirmasi Langsung
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($loan->status === \App\Enums\LoanStatus::Pending)
                <div class="pt-6 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Panel Setujui -->
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 flex flex-col justify-between">
                        <div>
                            <h3 class="font-serif text-sm font-semibold text-emerald-950 dark:text-emerald-200">Setujui Peminjaman</h3>
                            <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">
                                Status akan menjadi <strong>Sedang Dipinjam</strong>, stok berkurang 1, dengan masa pinjam {{ config('library.loan_duration_days', 7) }} hari.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('staff.loans.approve', $loan) }}" class="mt-4"
                              onsubmit="return confirm('Setujui peminjaman buku ini?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition">
                                Setujui &amp; Serahkan Buku
                            </button>
                        </form>
                    </div>

                    <!-- Panel Tolak Permintaan -->
                    <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5">
                        <h3 class="font-serif text-sm font-semibold text-rose-950 dark:text-rose-200">Tolak Permintaan</h3>
                        <form method="POST" action="{{ route('staff.loans.reject', $loan) }}" class="mt-3 space-y-3" novalidate
                              onsubmit="return confirm('Tolak permintaan peminjaman ini?');">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="rejection_reason" class="block text-xs font-medium text-rose-900 dark:text-rose-200">Alasan Penolakan *</label>
                                <textarea id="rejection_reason" name="rejection_reason" rows="2" required
                                          placeholder="Contoh: Buku sedang dalam perbaikan."
                                          class="mt-1 block w-full rounded-xl border px-3 py-2 text-sm bg-white {{ $errors->has('rejection_reason') ? 'border-rose-500' : 'border-rose-300' }}">{{ old('rejection_reason') }}</textarea>
                                @error('rejection_reason')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="w-full py-2 px-4 rounded-xl text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition">
                                Tolak Permintaan
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
