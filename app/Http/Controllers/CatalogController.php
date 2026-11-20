<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Book::class);

        $search = trim((string) $request->query('search', ''));
        $categoryId = (string) $request->query('category', '');
        $tagSlug = trim((string) $request->query('tag', ''));
        $availability = (string) $request->query('availability', '');

        $books = Book::query()
            ->select([
                'id',
                'category_id',
                'title',
                'author',
                'publisher',
                'publication_year',
                'total_stock',
                'available_stock',
                'cover_path',
            ])
            ->with(['category:id,name', 'tags:id,name,slug'])
            ->when($search !== '', function ($query) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $query->where(function ($subQuery) use ($escaped) {
                    $subQuery->where('title', 'like', "%{$escaped}%")
                        ->orWhere('author', 'like', "%{$escaped}%");
                });
            })
            ->when($categoryId !== '', function ($query) use ($categoryId) {
                $query->where('category_id', (int) $categoryId);
            })
            ->when($tagSlug !== '', function ($query) use ($tagSlug) {
                $query->whereHas('tags', function ($subQuery) use ($tagSlug) {
                    $subQuery->where('slug', $tagSlug);
                });
            })
            ->when($availability === 'available', function ($query) {
                $query->where('available_stock', '>', 0);
            })
            ->when($availability === 'unavailable', function ($query) {
                $query->where('available_stock', 0);
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();
        $tags = Tag::query()->select(['id', 'name', 'slug'])->has('books')->orderBy('name')->get();

        return view('catalog.index', [
            'books' => $books,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => [
                'search' => $search,
                'category' => $categoryId,
                'tag' => $tagSlug,
                'availability' => $availability,
            ],
        ]);
    }

    public function show(Book $book): View
    {
        Gate::authorize('view', $book);

        $book->loadMissing(['category:id,name', 'tags:id,name,slug']);

        return view('catalog.show', [
            'book' => $book,
        ]);
    }
}
