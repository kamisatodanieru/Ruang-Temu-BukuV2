<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagRequest;
use App\Models\Tag;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Tag::class);

        $search = trim((string) $request->query('search', ''));

        $tags = Tag::query()
            ->select(['id', 'name', 'slug', 'created_at'])
            ->withCount('books')
            ->when($search !== '', function ($query) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $query->where('name', 'like', "%{$escaped}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tags.index', [
            'tags' => $tags,
            'search' => $search,
        ]);
    }

    public function store(TagRequest $request): RedirectResponse
    {
        Gate::authorize('create', Tag::class);

        $validated = $request->safe()->only(['name']);

        DB::transaction(function () use ($validated, $request): void {
            $name = trim($validated['name']);
            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = 'tag-'.Str::random(6);
            }

            $tag = Tag::query()->create([
                'name' => $name,
                'slug' => $slug,
            ]);

            ActivityLogger::record(
                action: 'tag.created',
                description: "Menambahkan tag buku: {$tag->name}",
                subject: $tag,
                actor: $request->user(),
                request: $request,
            );
        });

        return redirect()->route('admin.tags.index')->with('success', 'Tag baru berhasil ditambahkan.');
    }

    public function update(TagRequest $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('update', $tag);

        $validated = $request->safe()->only(['name']);

        DB::transaction(function () use ($tag, $validated, $request): void {
            $oldName = $tag->name;
            $name = trim($validated['name']);
            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = 'tag-'.Str::random(6);
            }

            $tag->update([
                'name' => $name,
                'slug' => $slug,
            ]);

            ActivityLogger::record(
                action: 'tag.updated',
                description: "Mengubah tag dari '{$oldName}' menjadi '{$tag->name}'",
                subject: $tag,
                actor: $request->user(),
                request: $request,
            );
        });

        return redirect()->route('admin.tags.index')->with('success', 'Tag berhasil diperbarui.');
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        DB::transaction(function () use ($tag, $request): void {
            $lockedTag = Tag::query()->whereKey($tag->id)->lockForUpdate()->firstOrFail();
            $tagName = $lockedTag->name;
            $tagId = $lockedTag->id;

            $lockedTag->delete();

            ActivityLogger::record(
                action: 'tag.deleted',
                description: "Menghapus tag buku: {$tagName} (ID #{$tagId})",
                subject: null,
                actor: $request->user(),
                request: $request,
            );
        });

        return redirect()->route('admin.tags.index')->with('success', 'Tag berhasil dihapus.');
    }
}
