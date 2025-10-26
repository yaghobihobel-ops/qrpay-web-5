<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProviderOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'provider',
        'key',
        'value',
        'is_active',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected $casts = [
        'value'     => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    public function scopeForProvider(Builder $query, ?string $provider): Builder
    {
        return $query->when($provider, function (Builder $builder, string $value) {
            $builder->where(function (Builder $inner) use ($value) {
                $inner->where('provider', $value)->orWhereNull('provider');
            });
        }, function (Builder $builder) {
            $builder->whereNull('provider');
        })->orderByRaw('provider IS NULL');
    }

    public function getScalarValue(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->value;
        }

        return data_get($this->value, $key, $default);
    }
}
