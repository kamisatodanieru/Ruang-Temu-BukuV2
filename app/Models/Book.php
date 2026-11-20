<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Atribut yang diizinkan untuk mass assignment.
     * available_stock sengaja dikecualikan agar selalu dihitung secara otoritatif oleh server.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'author',
        'publisher',
        'isbn',
        'publication_year',
        'total_stock',
        'cover_path',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'total_stock' => 'integer',
            'available_stock' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function borrowedLoans(): HasMany
    {
        return $this->hasMany(Loan::class)
            ->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
            ->whereNull('returned_at');
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)
            ->whereIn('status', [
                LoanStatus::Pending->value,
                LoanStatus::Borrowed->value,
                LoanStatus::ReturnPending->value,
            ]);
    }

    public function isAvailable(): bool
    {
        return ! $this->trashed() && $this->available_stock > 0;
    }

    public function coverUrl(): ?string
    {
        if (empty($this->cover_path)) {
            return null;
        }

        return asset('storage/'.ltrim($this->cover_path, '/'));
    }
}
