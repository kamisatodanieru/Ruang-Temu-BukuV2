<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public static function createWithSlug(string $name): self
    {
        $cleanName = trim($name);
        $slug = Str::slug($cleanName);
        if ($slug === '') {
            $slug = 'tag-'.Str::random(6);
        }

        $existing = static::query()
            ->where('slug', $slug)
            ->orWhere('name', $cleanName)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return static::query()->create([
            'name' => $cleanName,
            'slug' => $slug,
        ]);
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class)->withTimestamps();
    }
}
