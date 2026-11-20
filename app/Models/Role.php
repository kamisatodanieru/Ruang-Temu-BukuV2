<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function findByName(RoleName|string $role): ?self
    {
        $name = $role instanceof RoleName ? $role->value : $role;

        return static::query()->where('name', $name)->first();
    }
}
