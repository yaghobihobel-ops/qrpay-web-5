<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions')->withTimestamps();
    }

    public static function findByName(string $name, ?string $guardName = null): ?self
    {
        $guard = $guardName ?? config('auth.defaults.guard', 'web');

        return static::query()
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->first();
    }

    public static function findOrCreate(string $name, ?string $guardName = null, array $attributes = []): self
    {
        $guard = $guardName ?? config('auth.defaults.guard', 'web');

        $permission = static::findByName($name, $guard);

        if ($permission) {
            return $permission;
        }

        $attributes = Arr::only($attributes, ['description']);

        return static::create(array_merge([
            'name' => $name,
            'guard_name' => $guard,
        ], $attributes));
    }

    public function scopeGuard($query, ?string $guardName = null)
    {
        return $query->where('guard_name', $guardName ?? config('auth.defaults.guard', 'web'));
    }
}
