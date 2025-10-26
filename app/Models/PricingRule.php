<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingRule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'spread_bps' => 'decimal:4',
        'status' => 'boolean',
        'conditions' => 'array',
    ];

    public function feeTiers(): HasMany
    {
        return $this->hasMany(FeeTier::class)->orderBy('priority');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
