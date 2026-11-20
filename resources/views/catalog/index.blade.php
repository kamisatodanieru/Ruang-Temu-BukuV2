<x-app-layout title="Katalog Buku" header="Katalog Buku">
    <div class="space-y-7">
        <!-- Pencarian & Saringan -->
        <div class="rtb-card rounded-2xl p-5 sm:p-6">
            <div class="mb-4 flex flex-col sm:flex-row sm:items-end justify-between gap-2">
                <div>
                    <h2 class="font-serif text-xl sm:text-2xl font-semibold text-slate-900">
                        Cari Buku
                    </h2>
                </div>
                <p class="text-xs text-slate-500 font-mono">
                    Menampilkan {{ $books->count() }} dari {{ $books->total() }} buku
                </p>
            </div>

            <form method="GET" action="{{ route('catalog.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-5 relative">
                    <label for="search" class="sr-only">Cari judul atau pengarang</label>
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <input id="search" name="search" type="text" value="{{ $filters['search'] }}"
                           placeholder="Cari judul buku atau nama pengarang..."
                           class="block w-full rounded-xl border border-slate-300 bg-slate-50/60 pl-10 pr-3.5 py-2.5 text-sm placeholder:text-slate-400">
                </div>

                <div class="sm:col-span-3">
                    <label for="category" class="sr-only">Filter Kategori</label>
                    <select id="category" name="category" class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['category'] === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-4 flex gap-2">
                    <div class="flex-1">
                        <label for="availability" class="sr-only">Filter Ketersediaan</label>
                        <select id="availability" name="availability" class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                            <option value="">Semua Status</option>
                            <option value="available" @selected($filters['availability'] === 'available')>Tersedia</option>
                            <option value="unavailable" @selected($filters['availability'] === 'unavailable')>Stok Habis</option>
                        </select>
                    </div>
                    @if (!empty($filters['tag']))
                        <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
                    @endif
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Cari
                    </button>
                </div>
            </form>

            @if ($tags->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-slate-200/80 flex flex-wrap items-center gap-1.5 text-xs">
                    <span class="font-medium text-slate-500 mr-1.5">Tag Populer:</span>
                    @if (!empty($filters['tag']))
                        <a href="{{ route('catalog.index', array_filter(array_merge(request()->query(), ['tag' => null]))) }}"
                           class="inline-flex items-center px-3 py-1 rounded-full font-medium bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition">
                            Hapus filter tag &times;
                        </a>
                    @endif
                    @foreach ($tags as $tagItem)
                        <a href="{{ route('catalog.index', array_merge(request()->query(), ['tag' => $tagItem->slug])) }}"
                           class="inline-flex items-center px-3 py-1 rounded-full font-medium transition {{ $filters['tag'] === $tagItem->slug ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 border border-slate-200/60' }}">
                            #{{ $tagItem->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Daftar Buku Grid -->
        @if ($books->isEmpty())
            <div class="rtb-card rounded-2xl p-14 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 mx-auto mb-4 flex items-center justify-center text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <p class="font-serif text-lg font-semibold text-slate-800">Buku tidak ditemukan</p>
                <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain atau hapus pilihan kategori.</p>
                @if ($filters['search'] !== '' || $filters['category'] !== '' || $filters['availability'] !== '')
                    <div class="mt-5">
                        <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200">
                            Atur Ulang
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($books as $book)
                    <article style="--stagger: {{ $loop->index }}"
                             class="rtb-reveal rtb-card rtb-card-hover group rounded-2xl p-3.5 flex flex-col">
                        <a href="{{ route('catalog.show', $book) }}" class="book-cover-frame block aspect-[3/4] rounded-xl bg-slate-100 overflow-hidden relative">
                            @if ($book->coverUrl())
                                <img src="{{ $book->coverUrl() }}"
                                     alt="Sampul {{ $book->title }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover transform group-hover:scale-[1.045] transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-between p-5 text-center bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200/70 dark:from-slate-800 dark:via-slate-900 dark:to-slate-950">
                                    <span class="text-[10px] font-mono uppercase tracking-widest text-slate-400">
                                        {{ $book->category?->name ?? 'Koleksi' }}
                                    </span>
                                    <div class="my-auto flex flex-col items-center">
                                        <svg class="w-10 h-10 mb-2 text-slate-400/80 group-hover:scale-110 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        <span class="text-xs font-serif italic text-slate-500">Tanpa Sampul</span>
                                    </div>
                                    <span class="w-8 h-[1px] bg-slate-300 dark:bg-slate-700"></span>
                                </div>
                            @endif

                            <div class="absolute top-2.5 right-2.5 z-20">
                                @if ($book->available_stock > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-950/80 text-emerald-100 backdrop-blur-md border border-emerald-400/25 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Tersedia {{ $book->available_stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-950/85 text-rose-100 backdrop-blur-md border border-rose-400/25 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        Habis
                                    </span>
                                @endif
                            </div>
                        </a>

                        <div class="pt-4 px-1.5 pb-1 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-terracotta-600 dark:text-terracotta-300">
                                    {{ $book->category?->name ?? 'Umum' }}
                                </span>
                                <h2 class="mt-1 font-serif text-base font-semibold text-slate-900 line-clamp-2 leading-snug">
                                    <a href="{{ route('catalog.show', $book) }}" class="hover:text-indigo-600 transition-colors">
                                        {{ $book->title }}
                                    </a>
                                </h2>
                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $book->author }}</p>

                                @if ($book->tags->isNotEmpty())
                                    <div class="mt-2.5 flex flex-wrap gap-1">
                                        @foreach ($book->tags as $tag)
                                            <a href="{{ route('catalog.index', array_merge(request()->query(), ['tag' => $tag->slug])) }}"
                                               class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-normal bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                                #{{ $tag->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-200/70 flex items-center justify-between">
                                <span class="font-mono text-xs text-slate-500">
                                    {{ $book->publication_year ?? '-' }}
                                </span>
                                <a href="{{ route('catalog.show', $book) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                    <span>Lihat Detail</span>
                                    <span class="inline-block transform group-hover:translate-x-1 transition-transform duration-300">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($books->hasPages())
                <div class="pt-4">
                    {{ $books->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
