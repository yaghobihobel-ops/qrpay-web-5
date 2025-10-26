<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPointRule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'min_volume' => 'float',
        'max_volume' => 'float',
        'multiplier' => 'float',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProvider($query, ?string $provider)
    {
        if (empty($provider)) {
            return $query->whereNull('provider');
        }

        return $query->where(function ($q) use ($provider) {
            $q->where('provider', $provider)
              ->orWhereNull('provider');
        });
    }
}
