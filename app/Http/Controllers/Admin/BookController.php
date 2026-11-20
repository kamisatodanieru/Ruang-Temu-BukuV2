<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\Tag;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage', Book::class);

        $search = trim((string) $request->query('search', ''));
        $categoryId = (string) $request->query('category_id', '');

        $books = Book::query()
            ->select([
                'id',
                'category_id',
                'title',
                'author',
                'publisher',
                'isbn',
                'publication_year',
                'total_stock',
                'available_stock',
                'cover_path',
                'created_at',
            ])
            ->with(['category:id,name', 'tags:id,name,slug'])
            ->withCount('borrowedLoans')
            ->when($search !== '', function ($query) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $query->where(function ($subQuery) use ($escaped) {
                    $subQuery->where('title', 'like', "%{$escaped}%")
                        ->orWhere('author', 'like', "%{$escaped}%")
                        ->orWhere('isbn', 'like', "%{$escaped}%");
                });
            })
            ->when($categoryId !== '', function ($query) use ($categoryId) {
                $query->where('category_id', (int) $categoryId);
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();

        return view('admin.books.index', [
            'books' => $books,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Book::class);

        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();
        $availableTags = Tag::query()->select(['id', 'name', 'slug'])->orderBy('name')->get();

        return view('admin.books.create', [
            'categories' => $categories,
            'availableTags' => $availableTags,
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        Gate::authorize('create', Book::class);

        $validated = $request->safe()->except(['cover']);
        $coverPath = null;

        if ($request->hasFile('cover')) {
            $coverPath = $this->storeCoverSafely($request->file('cover'));
        }

        DB::transaction(function () use ($validated, $coverPath, $request): void {
            $totalStock = (int) $validated['total_stock'];

            $book = new Book([
                'category_id' => (int) $validated['category_id'],
                'title' => $validated['title'],
                'author' => $validated['author'],
                'publisher' => $validated['publisher'] ?? null,
                'isbn' => $validated['isbn'] ?? null,
                'publication_year' => isset($validated['publication_year']) ? (int) $validated['publication_year'] : null,
                'total_stock' => $totalStock,
                'cover_path' => $coverPath,
                'description' => $validated['description'] ?? null,
            ]);

            // available_stock selalu dihitung dan ditetapkan secara otoritatif oleh server
            $book->available_stock = $totalStock;
            $book->save();

            if (isset($validated['tags'])) {
                $this->syncTags($book, (array) $validated['tags']);
            }

            ActivityLogger::record(
                action: 'book.created',
                description: "Menambahkan buku baru: {$book->title} (Total stok: {$book->total_stock})",
                subject: $book,
                actor: $request->user(),
                request: $request,
            );
        });

        return redirect()->route('admin.books.index')->with('success', 'Data buku baru berhasil ditambahkan.');
    }

    public function edit(Book $book): View
    {
        Gate::authorize('update', $book);

        $book->loadCount('borrowedLoans');
        $book->load('tags:id,name,slug');
        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();
        $availableTags = Tag::query()->select(['id', 'name', 'slug'])->orderBy('name')->get();

        return view('admin.books.edit', [
            'book' => $book,
            'categories' => $categories,
            'availableTags' => $availableTags,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        Gate::authorize('update', $book);

        $validated = $request->safe()->except(['cover']);
        $newCoverPath = null;

        if ($request->hasFile('cover')) {
            $newCoverPath = $this->storeCoverSafely($request->file('cover'));
        }

        $oldCoverToDelete = null;

        try {
            DB::transaction(function () use ($book, $validated, $newCoverPath, &$oldCoverToDelete, $request): void {
                $lockedBook = Book::query()->whereKey($book->id)->lockForUpdate()->firstOrFail();

                $borrowedCount = $lockedBook->borrowedLoans()->count();
                $newTotalStock = (int) $validated['total_stock'];

                if ($newTotalStock < $borrowedCount) {
                    throw ValidationException::withMessages([
                        'total_stock' => "Total stok tidak boleh lebih kecil dari jumlah buku yang sedang dipinjam ({$borrowedCount} eksemplar).",
                    ]);
                }

                $lockedBook->fill([
                    'category_id' => (int) $validated['category_id'],
                    'title' => $validated['title'],
                    'author' => $validated['author'],
                    'publisher' => $validated['publisher'] ?? null,
                    'isbn' => $validated['isbn'] ?? null,
                    'publication_year' => isset($validated['publication_year']) ? (int) $validated['publication_year'] : null,
                    'total_stock' => $newTotalStock,
                    'description' => $validated['description'] ?? null,
                ]);

                if ($newCoverPath !== null) {
                    $oldCoverToDelete = $lockedBook->cover_path;
                    $lockedBook->cover_path = $newCoverPath;
                }

                // Rumus otoritatif PRD 5.5: available_stock = total_stock - borrowed_count
                $lockedBook->available_stock = $newTotalStock - $borrowedCount;
                $lockedBook->save();

                if (isset($validated['tags'])) {
                    $this->syncTags($lockedBook, (array) $validated['tags']);
                }

                ActivityLogger::record(
                    action: 'book.updated',
                    description: "Memperbarui buku: {$lockedBook->title} (Total stok: {$lockedBook->total_stock}, Tersedia: {$lockedBook->available_stock})",
                    subject: $lockedBook,
                    actor: $request->user(),
                    request: $request,
                );
            });
        } catch (ValidationException $e) {
            if ($newCoverPath !== null) {
                Storage::disk('public')->delete($newCoverPath);
            }

            throw $e;
        }

        if ($oldCoverToDelete !== null) {
            Storage::disk('public')->delete($oldCoverToDelete);
        }

        return redirect()->route('admin.books.index')->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        Gate::authorize('delete', $book);

        $deleted = DB::transaction(function () use ($book, $request): bool {
            $lockedBook = Book::query()->whereKey($book->id)->lockForUpdate()->firstOrFail();

            if ($lockedBook->activeLoans()->exists()) {
                return false;
            }

            $lockedBook->delete();

            ActivityLogger::record(
                action: 'book.deleted',
                description: "Menghapus (soft-delete) buku: {$lockedBook->title}",
                subject: $lockedBook,
                actor: $request->user(),
                request: $request,
            );

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('admin.books.index')
                ->with('error', 'Buku tidak dapat dihapus karena masih memiliki transaksi peminjaman yang sedang dipinjam atau menunggu persetujuan.');
        }

        return redirect()->route('admin.books.index')->with('success', 'Buku berhasil dihapus dari katalog.');
    }

    /**
     * Sinkronisasi tag buku (dibatasi maksimal 5 tag per buku).
     *
     * @param  list<string>|array<int, string>  $tags
     */
    private function syncTags(Book $book, array $tags): void
    {
        $tagIds = [];
        $sliced = array_slice($tags, 0, 5);

        foreach ($sliced as $tagInput) {
            $tagName = trim((string) $tagInput);
            if ($tagName === '') {
                continue;
            }

            $tag = Tag::createWithSlug($tagName);

            $tagIds[] = $tag->id;
        }

        $book->tags()->sync($tagIds);
    }

    /**
     * Simpan file cover dengan nama acak berdasarkan MIME type terverifikasi server.
     * Mencegah path traversal dan eksekusi script.
     */
    private function storeCoverSafely(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $randomFilename = Str::uuid()->toString().'.'.$extension;

        return (string) $file->storeAs('covers', $randomFilename, 'public');
    }
}
