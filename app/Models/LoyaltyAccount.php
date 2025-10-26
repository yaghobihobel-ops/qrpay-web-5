<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyAccount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'points_balance' => 'integer',
        'lifetime_points' => 'integer',
        'redeemed_rewards_count' => 'integer',
        'last_rewarded_at' => 'datetime',
        'preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rewardEvents(): HasMany
    {
        return $this->hasMany(RewardEvent::class);
    }

    public function scopeWithUser($query)
    {
        return $query->with('user');
    }
}
