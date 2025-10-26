<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCampaign extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'channels' => 'array',
        'audience_filters' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_special_offer' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(LoyaltyCampaignRun::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(LoyaltyCampaignTest::class);
    }
}
