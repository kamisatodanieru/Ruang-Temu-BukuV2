<x-app-layout :title="$book->title" header="Detail Buku">
    <div class="max-w-4xl space-y-6">
        <div>
            <a href="{{ route('catalog.index') }}" class="group inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-indigo-600 transition">
                <span class="inline-block transform group-hover:-translate-x-1 transition-transform duration-200">&larr;</span>
                <span>Kembali ke Katalog</span>
            </a>
        </div>

        <div class="rtb-card rounded-2xl overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 p-6 sm:p-8">
                <!-- Sampul Buku -->
                <div class="md:col-span-4">
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <div class="book-cover-frame aspect-[3/4] rounded-xl bg-slate-100 overflow-hidden flex items-center justify-center">
                            @if ($book->coverUrl())
                                <img src="{{ $book->coverUrl() }}" alt="Sampul {{ $book->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="text-center p-6 text-slate-400 flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span class="text-xs font-serif italic">Tanpa Sampul</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Informasi Buku -->
                <div class="md:col-span-8 flex flex-col justify-between space-y-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold tracking-wide bg-indigo-50 text-indigo-700 border border-indigo-200/70">
                                {{ $book->category?->name ?? 'Umum' }}
                            </span>
                            @foreach ($book->tags as $tag)
                                <a href="{{ route('catalog.index', ['tag' => $tag->slug]) }}"
                                   class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 hover:bg-indigo-100 hover:text-indigo-800 transition"
                                   title="Lihat buku dengan tag #{{ $tag->name }}">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                            @if ($book->available_stock > 0)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Tersedia {{ $book->available_stock }} dari {{ $book->total_stock }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Stok Habis
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-4 font-serif text-2xl sm:text-3xl font-semibold text-slate-900 leading-tight">
                            {{ $book->title }}
                        </h2>
                        <p class="mt-1.5 text-base font-medium text-slate-600">
                            Oleh <span class="text-slate-800">{{ $book->author }}</span>
                        </p>

                        <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-xl bg-slate-50 border border-slate-200/80 p-4 text-sm">
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Penerbit</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $book->publisher ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">ISBN</dt>
                                <dd class="mt-1 font-mono text-xs font-medium text-slate-900">{{ $book->isbn ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tahun Terbit</dt>
                                <dd class="mt-1 font-mono font-medium text-slate-900">{{ $book->publication_year ?: '-' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-6">
                            <h3 class="font-serif text-sm font-semibold uppercase tracking-wider text-slate-800">Sinopsis</h3>
                            <p class="mt-2.5 text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $book->description ?: 'Belum ada sinopsis untuk buku ini.' }}</p>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="pt-5 border-t border-slate-200">
                        @guest
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                                <p class="text-sm text-slate-700">
                                    Masuk dengan akun anggota untuk meminjam buku ini.
                                </p>
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition shrink-0">
                                    Masuk untuk Meminjam
                                </a>
                            </div>
                        @else
                            @if (auth()->user()->isAnggota())
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                                    <div>
                                        <p class="font-serif text-sm font-semibold text-slate-900">Pinjam Buku</p>
                                        <p class="mt-0.5 text-xs text-slate-600">
                                            Masa pinjam {{ config('library.loan_duration_days', 7) }} hari, maksimal {{ config('library.max_active_loans', 3) }} buku aktif.
                                        </p>
                                    </div>
                                    @if ($book->available_stock > 0)
                                        <form method="POST" action="{{ route('member.loans.store') }}"
                                              data-confirm="Ajukan peminjaman untuk buku {{ $book->title }}?">
                                            @csrf
                                            <input type="hidden" name="book_id" value="{{ $book->id }}">
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition shrink-0">
                                                Ajukan Pinjam
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled
                                                class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 bg-slate-200 cursor-not-allowed shrink-0">
                                            Stok Habis
                                        </button>
                                    @endif
                                </div>
                            @elseif (auth()->user()->isAdmin())
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500">Pengelolaan Buku</span>
                                    <a href="{{ route('admin.books.edit', $book) }}"
                                       class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold bg-slate-800 text-white hover:bg-slate-900 transition">
                                        Ubah Buku
                                    </a>
                                </div>
                            @endif
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
