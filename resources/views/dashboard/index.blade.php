<x-app-layout title="Beranda" header="Beranda {{ ucfirst($user->role?->name ?? '') }}">
    <div class="space-y-6">
        <!-- Banner Sambutan -->
        <div class="rtb-card relative overflow-hidden rounded-2xl p-6 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div class="pointer-events-none absolute -right-12 -top-12 w-48 h-48 rounded-full bg-indigo-600/[0.04] blur-2xl"></div>
            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-terracotta-600 dark:text-terracotta-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-terracotta-500"></span>
                    Perpustakaan Digital
                </span>
                <h2 class="mt-1.5 font-serif text-xl sm:text-2xl font-semibold text-slate-900">
                    Selamat datang, {{ $user->name }}
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Masuk sebagai <span class="font-semibold text-indigo-600">{{ ucfirst($user->role?->name ?? '') }}</span>.
                </p>
            </div>
            <div class="relative z-10 shrink-0">
                <a href="{{ route('catalog.index') }}"
                   class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition">
                    <span>Lihat Katalog</span>
                    <span class="inline-block transform group-hover:translate-x-1 transition-transform duration-200">&rarr;</span>
                </a>
            </div>
        </div>

        {{-- ==============================================================
             1. BERANDA ADMIN
             ============================================================== --}}
        @if ($user->isAdmin())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div style="--stagger: 1" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Judul Buku</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-slate-900">{{ $stats['total_books'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Koleksi di katalog</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                </div>

                <div style="--stagger: 2" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Stok Tersedia</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-indigo-600">
                            {{ $stats['available_stock'] }} <span class="text-sm font-normal text-slate-500">/ {{ $stats['total_stock'] }}</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Eksemplar siap dipinjam</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    </span>
                </div>

                <div style="--stagger: 3" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Anggota Aktif</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-emerald-600">{{ $stats['active_members'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Akun anggota aktif</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" /></svg>
                    </span>
                </div>

                <div style="--stagger: 4" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu Persetujuan</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-amber-600">{{ $stats['pending_loans'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Perlu ditinjau petugas</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>

                <div style="--stagger: 5" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sedang Dipinjam</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-indigo-600">{{ $stats['borrowed_loans'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Peminjaman berjalan</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg>
                    </span>
                </div>

                <div style="--stagger: 6" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Terlambat</p>
                        <p class="mt-2 font-mono text-3xl font-bold {{ $stats['overdue_loans'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $stats['overdue_loans'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Melewati jatuh tempo</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl {{ $stats['overdue_loans'] > 0 ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-slate-100 border-slate-200 text-slate-500' }} border flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Transaksi Terbaru -->
                <div style="--stagger: 7" class="rtb-reveal rtb-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="font-serif text-base font-semibold text-slate-900">Transaksi Peminjaman Terbaru</h3>
                        <a href="{{ route('staff.loans.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                            Lihat Semua &rarr;
                        </a>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse ($recentLoans as $loan)
                            <div class="px-6 py-4 flex items-center justify-between text-sm hover:bg-slate-50/70 transition">
                                <div>
                                    <p class="font-serif font-medium text-slate-900">{{ $loan->book?->title ?? 'Buku' }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $loan->member?->name }} &bull; <span class="font-mono">{{ $loan->requested_at?->format('d M Y H:i') }}</span></p>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    @if ($loan->isOverdue())
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">Terlambat</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">{{ $loan->status->label() }}</span>
                                    @endif
                                    <a href="{{ route('staff.loans.show', $loan) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">Detail</a>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                Belum ada transaksi peminjaman.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Aktivitas Terbaru -->
                <div style="--stagger: 8" class="rtb-reveal rtb-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="font-serif text-base font-semibold text-slate-900">Aktivitas Sistem Terbaru</h3>
                        <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                            Lihat Semua &rarr;
                        </a>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse ($recentLogs as $log)
                            <div class="px-6 py-4 flex items-center justify-between text-sm hover:bg-slate-50/70 transition">
                                <div>
                                    <span class="font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</span>
                                    <span class="text-slate-600">— {{ $log->description ?? $log->action }}</span>
                                </div>
                                <span class="font-mono text-xs text-slate-500 whitespace-nowrap ml-4">
                                    {{ $log->created_at?->format('d M H:i') }}
                                </span>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                Belum ada catatan aktivitas.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- ==============================================================
             2. BERANDA PETUGAS
             ============================================================== --}}
        @if ($user->isPetugas())
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div style="--stagger: 1" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu Persetujuan</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-amber-600">{{ $stats['pending_loans'] }}</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <div style="--stagger: 2" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sedang Dipinjam</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-indigo-600">{{ $stats['borrowed_loans'] }}</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg>
                    </span>
                </div>
                <div style="--stagger: 3" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Terlambat</p>
                        <p class="mt-2 font-mono text-3xl font-bold {{ $stats['overdue_loans'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $stats['overdue_loans'] }}</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl {{ $stats['overdue_loans'] > 0 ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-emerald-50 border-emerald-200 text-emerald-600' }} border flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Antrean Persetujuan -->
                <div style="--stagger: 4" class="rtb-reveal rtb-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="font-serif text-base font-semibold text-slate-900">Permintaan Menunggu Persetujuan</h3>
                        <a href="{{ route('staff.loans.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                            Lihat Semua &rarr;
                        </a>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse ($pendingQueue as $loan)
                            <div class="px-6 py-4 flex items-center justify-between text-sm gap-4 hover:bg-slate-50/70 transition">
                                <div>
                                    <p class="font-serif font-semibold text-slate-900">{{ $loan->book?->title }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $loan->member?->name }} &bull; Stok tersedia: <span class="font-mono font-semibold">{{ $loan->book?->available_stock ?? 0 }}</span></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <form method="POST" action="{{ route('staff.loans.approve', $loan) }}"
                                          onsubmit="return confirm('Setujui peminjaman ini?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                                            Setujui
                                        </button>
                                    </form>
                                    <a href="{{ route('staff.loans.show', $loan) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                                        Detail
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                Belum ada permintaan peminjaman.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Antrean Pengembalian -->
                <div style="--stagger: 5" class="rtb-reveal rtb-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="font-serif text-base font-semibold text-slate-900">Pengembalian Perlu Dikonfirmasi</h3>
                        <a href="{{ route('staff.loans.index', ['status' => 'return_pending']) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                            Lihat Semua &rarr;
                        </a>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse ($returnQueue as $loan)
                            <div class="px-6 py-4 flex items-center justify-between text-sm gap-4 hover:bg-slate-50/70 transition">
                                <div>
                                    <p class="font-serif font-semibold text-slate-900">{{ $loan->book?->title }}</p>
                                    <p class="text-xs mt-0.5 {{ $loan->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                        {{ $loan->member?->name }} &bull; Tempo: <span class="font-mono">{{ $loan->due_at?->format('d M Y') }}</span>
                                        @if ($loan->isOverdue())
                                            &bull; Terlambat {{ $loan->overdueDays() }} hari
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <form method="POST" action="{{ route('staff.loans.return', $loan) }}"
                                          onsubmit="return confirm('Konfirmasi pengembalian buku ini?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition">
                                            Konfirmasi Kembali
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                Belum ada pengajuan pengembalian.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- ==============================================================
             3. BERANDA ANGGOTA
             ============================================================== --}}
        @if ($user->isAnggota())
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div style="--stagger: 1" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu Persetujuan</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-amber-600">{{ $stats['pending_count'] }}</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <div style="--stagger: 2" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sedang Dipinjam</p>
                        <p class="mt-2 font-mono text-3xl font-bold text-indigo-600">{{ $stats['borrowed_count'] }}</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg>
                    </span>
                </div>
                <div style="--stagger: 3" class="rtb-reveal rtb-card rtb-card-hover rounded-2xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Terlambat</p>
                        <p class="mt-2 font-mono text-3xl font-bold {{ $stats['overdue_count'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $stats['overdue_count'] }}</p>
                    </div>
                    <span class="w-10 h-10 rounded-xl {{ $stats['overdue_count'] > 0 ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-emerald-50 border-emerald-200 text-emerald-600' }} border flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
            </div>

            <!-- Daftar Buku Aktif -->
            <div style="--stagger: 4" class="rtb-reveal rtb-card rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <h3 class="font-serif text-base font-semibold text-slate-900">Buku Aktif Saya</h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-mono font-medium bg-slate-100 text-slate-600">
                            {{ $activeLoans->count() }} / {{ $stats['max_active_loans'] }}
                        </span>
                    </div>
                    <a href="{{ route('member.loans.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                        Lihat Semua &rarr;
                    </a>
                </div>
                <div class="divide-y divide-slate-200">
                    @forelse ($activeLoans as $loan)
                        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition">
                            <div class="flex items-start gap-4">
                                @if ($loan->book?->coverUrl())
                                    <div class="book-cover-frame w-14 h-20 rounded-lg overflow-hidden shrink-0" style="width: 3.5rem; height: 5rem;">
                                        <img src="{{ $loan->book->coverUrl() }}" alt="Sampul {{ $loan->book->title }}" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="book-cover-frame w-14 h-20 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-[11px] font-serif italic text-slate-400 overflow-hidden shrink-0" style="width: 3.5rem; height: 5rem;">
                                        Buku
                                    </div>
                                @endif
                                <div>
                                    <h4 class="font-serif text-base font-semibold text-slate-900">{{ $loan->book?->title }}</h4>
                                    <p class="text-xs text-slate-600 mt-0.5">{{ $loan->book?->author }}</p>
                                    <div class="mt-2.5 flex flex-wrap items-center gap-3 text-xs text-slate-500 font-mono">
                                        @if ($loan->borrowed_at)
                                            <span>Dipinjam: {{ $loan->borrowed_at->format('d M Y') }}</span>
                                        @else
                                            <span>Diajukan: {{ $loan->requested_at?->format('d M Y') }}</span>
                                        @endif

                                        @if ($loan->due_at)
                                            <span class="{{ $loan->isOverdue() ? 'font-bold text-rose-600' : 'text-slate-700' }}">
                                                Jatuh Tempo: {{ $loan->due_at->format('d M Y H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                @if ($loan->isOverdue())
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                        Terlambat {{ $loan->overdueDays() }} hari
                                    </span>
                                @elseif ($loan->status === \App\Enums\LoanStatus::Pending)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        Menunggu Persetujuan
                                    </span>
                                @elseif ($loan->status === \App\Enums\LoanStatus::ReturnPending)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        Menunggu Pengembalian
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Sedang Dipinjam
                                    </span>
                                @endif

                                <a href="{{ route('member.loans.show', $loan) }}"
                                   class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                                    Detail
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada buku yang sedang dipinjam.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Riwayat Peminjaman Terbaru -->
            <div style="--stagger: 5" class="rtb-reveal rtb-card rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-serif text-base font-semibold text-slate-900">Riwayat Peminjaman Terbaru</h3>
                    <a href="{{ route('member.loans.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                        Lihat Semua &rarr;
                    </a>
                </div>
                <div class="divide-y divide-slate-200">
                    @forelse ($recentHistory as $item)
                        <div class="px-6 py-4 flex items-center justify-between text-sm hover:bg-slate-50/60 transition">
                            <div>
                                <p class="font-serif font-medium text-slate-900">{{ $item->book?->title }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $item->book?->author }} &bull;
                                    <span class="font-mono">
                                        @if ($item->returned_at)
                                            Dikembalikan {{ $item->returned_at->format('d M Y') }}
                                        @else
                                            Diajukan {{ $item->requested_at?->format('d M Y') }}
                                        @endif
                                    </span>
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $item->status->label() }}
                                </span>
                                <a href="{{ route('member.loans.show', $item) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                    Detail
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada riwayat peminjaman.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
