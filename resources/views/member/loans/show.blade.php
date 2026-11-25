<x-app-layout title="Detail Peminjaman" header="Detail Peminjaman">
    <div class="max-w-3xl space-y-6">
        <div>
            <a href="{{ route('member.loans.index') }}" class="group inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                <span class="inline-block transform group-hover:-translate-x-1 transition-transform duration-200">&larr;</span>
                <span>Kembali ke Daftar Peminjaman</span>
            </a>
        </div>

        <div class="rtb-card rounded-2xl p-6 sm:p-7 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-200">
                <div>
                    <h2 class="font-serif text-xl font-semibold text-slate-900">{{ $loan->book?->title ?? 'Buku' }}</h2>
                    <p class="text-sm text-slate-600">Oleh {{ $loan->book?->author }}</p>
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

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-xl bg-slate-50 border border-slate-200/80 p-5 text-sm">
                <div>
                    <dt class="text-xs font-medium text-slate-500">Waktu Pengajuan</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $loan->requested_at?->format('d M Y H:i') }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-slate-500">Waktu Dipinjam</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $loan->borrowed_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-slate-500">Jatuh Tempo</dt>
                    <dd class="mt-1 font-mono text-xs font-medium {{ $loan->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-900' }}">
                        {{ $loan->due_at?->format('d M Y H:i') ?? '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-slate-500">Waktu Dikembalikan</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $loan->returned_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>

                @if ($loan->return_requested_at)
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Pengajuan Pengembalian</dt>
                        <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $loan->return_requested_at->format('d M Y H:i') }}</dd>
                    </div>
                @endif
            </dl>

            @if ($loan->status === \App\Enums\LoanStatus::Rejected && $loan->rejection_reason)
                <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                    <p class="font-semibold">Alasan Penolakan:</p>
                    <p class="mt-1">{{ $loan->rejection_reason }}</p>
                </div>
            @endif

            @if ($loan->isOverdue() && ! $loan->fine)
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Terlambat {{ $loan->overdueDays() }} hari</p>
                    <p class="mt-1">
                        Perkiraan denda saat ini: <strong class="font-mono">Rp {{ number_format($loan->estimatedFineAmount(), 0, ',', '.') }}</strong>
                        (Rp {{ number_format((int) config('library.fine_per_day', 1000), 0, ',', '.') }}/hari).
                        Segera kembalikan buku ke petugas.
                    </p>
                </div>
            @endif

            @if ($loan->fine)
                <div class="pt-4 border-t border-slate-200">
                    <div class="rounded-2xl border {{ $loan->fine->isPaid() ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' }} p-5 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="font-serif text-sm font-bold text-slate-900">Denda Keterlambatan</h3>
                                <p class="text-xs text-slate-600 font-mono">
                                    Terlambat {{ $loan->fine->overdue_days }} hari &times; Rp {{ number_format($loan->fine->fine_per_day, 0, ',', '.') }}/hari
                                </p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $loan->fine->isPaid() ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ $loan->fine->status->label() }}
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-3 border-t border-slate-200/70">
                            <div class="space-y-1">
                                <p class="text-xs text-slate-500">Total Denda Kumulatif</p>
                                <p class="font-mono text-lg font-bold text-slate-900">{{ $loan->fine->formattedAmount() }}</p>
                                @if ($loan->fine->paid_amount > 0 && ! $loan->fine->isPaid())
                                    <p class="text-xs text-slate-700 font-mono">
                                        Terbayar: <span class="font-semibold text-emerald-700">{{ $loan->fine->formattedPaidAmount() }}</span>
                                        &bull; Sisa Tagihan: <span class="font-bold text-rose-700">{{ $loan->fine->formattedRemainingAmount() }}</span>
                                    </p>
                                @endif
                                @if ($loan->fine->paid_at)
                                    <p class="text-xs text-emerald-700 mt-0.5 font-mono">Dibayar pada {{ $loan->fine->paid_at->format('d M Y H:i') }}</p>
                                @endif
                            </div>

                            @if ($loan->fine->isPayable())
                                <button type="button"
                                        id="pay-fine-button"
                                        data-pay-url="{{ route('member.fines.pay', $loan->fine) }}"
                                        class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
                                    {{ $loan->fine->paid_amount > 0 ? 'Bayar Sisa Denda ('.$loan->fine->formattedRemainingAmount().')' : 'Bayar Denda' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($loan->status === \App\Enums\LoanStatus::Borrowed)
                <div class="pt-4 border-t border-slate-200">
                    <div class="rounded-2xl bg-indigo-50 border border-indigo-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="font-serif text-sm font-semibold text-indigo-950">Ajukan Pengembalian</h3>
                            <p class="mt-1 text-xs text-indigo-800">
                                Sudah selesai membaca? Ajukan pengembalian lalu serahkan buku ke petugas.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('member.loans.request-return', $loan) }}"
                              onsubmit="return confirm('Apakah Anda yakin ingin mengajukan pengembalian buku ini?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="shrink-0 px-4 py-2.5 rounded-xl text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
                                Ajukan Pengembalian
                            </button>
                        </form>
                    </div>
                </div>
            @elseif ($loan->status === \App\Enums\LoanStatus::ReturnPending)
                <div class="pt-4 border-t border-slate-200">
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-200 text-amber-900 uppercase tracking-wider">Menunggu Konfirmasi</span>
                        </div>
                        <h3 class="mt-1.5 font-serif text-sm font-semibold text-amber-950">Pengajuan Pengembalian Diterima</h3>
                        <p class="mt-1 text-xs text-amber-800">
                            Diajukan pada <span class="font-mono font-medium">{{ $loan->return_requested_at?->format('d M Y H:i') }}</span>. Silakan serahkan buku ke petugas untuk dikonfirmasi.
                        </p>
                    </div>
                </div>
            @endif

            @if ($loan->status === \App\Enums\LoanStatus::Pending)
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end">
                    <form method="POST" action="{{ route('member.loans.cancel', $loan) }}"
                          onsubmit="return confirm('Batalkan permintaan peminjaman ini?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm font-semibold bg-rose-600 text-white hover:bg-rose-700 transition">
                            Batalkan Permintaan
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @if ($loan->fine && $loan->fine->isPayable())
        <script src="{{ $midtransSnapJsUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const payButton = document.getElementById('pay-fine-button');
                if (!payButton) return;

                payButton.addEventListener('click', async function () {
                    const originalText = payButton.textContent;
                    payButton.disabled = true;
                    payButton.textContent = 'Memproses...';

                    try {
                        const response = await fetch(payButton.dataset.payUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.snap_token) {
                            alert(data.message || 'Gagal membuat transaksi pembayaran.');
                            payButton.disabled = false;
                            payButton.textContent = originalText;
                            return;
                        }

                        if (window.snap) {
                            window.snap.pay(data.snap_token, {
                                onSuccess: function () {
                                    window.location.reload();
                                },
                                onPending: function () {
                                    window.location.reload();
                                },
                                onError: function () {
                                    alert('Pembayaran gagal diproses.');
                                    payButton.disabled = false;
                                    payButton.textContent = originalText;
                                },
                                onClose: function () {
                                    payButton.disabled = false;
                                    payButton.textContent = originalText;
                                }
                            });
                        }
                    } catch (err) {
                        alert('Terjadi kesalahan koneksi saat menghubungi server pembayaran.');
                        payButton.disabled = false;
                        payButton.textContent = originalText;
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
