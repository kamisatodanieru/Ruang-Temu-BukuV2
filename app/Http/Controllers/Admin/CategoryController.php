<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->select(['id', 'name', 'created_at'])
            ->withCount('books')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $validated = $request->safe()->only(['name']);

        DB::transaction(function () use ($validated, $request): void {
            $category = Category::query()->create([
                'name' => $validated['name'],
            ]);

            ActivityLogger::record(
                action: 'category.created',
                description: "Menambahkan kategori buku: {$category->name}",
                subject: $category,
                actor: $request->user(),
                request: $request,
            );
        });

        return redirect()->route('admin.categories.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->safe()->only(['name']);

        DB::transaction(function () use ($category, $validated, $request): void {
            $oldName = $category->name;
            $category->update([
                'name' => $validated['name'],
            ]);

            ActivityLogger::record(
                action: 'category.updated',
                description: "Mengubah nama kategori dari '{$oldName}' menjadi '{$category->name}'",
                subject: $category,
                actor: $request->user(),
                request: $request,
            );
        });

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $deleted = DB::transaction(function () use ($category, $request): bool {
            $lockedCategory = Category::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();

            if ($lockedCategory->books()->withTrashed()->exists()) {
                return false;
            }

            $categoryName = $lockedCategory->name;
            $categoryId = $lockedCategory->id;
            $lockedCategory->delete();

            ActivityLogger::record(
                action: 'category.deleted',
                description: "Menghapus kategori buku: {$categoryName} (ID #{$categoryId})",
                subject: null,
                actor: $request->user(),
                request: $request,
            );

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih direferensikan oleh buku. Pindahkan buku ke kategori lain terlebih dahulu.');
        }

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
