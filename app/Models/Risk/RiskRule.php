<?php

namespace App\Models\Risk;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskRule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'stop_on_match' => 'boolean',
    ];

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param Builder<self> $query
     * @param string $eventType
     * @return Builder<self>
     */
    public function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->whereIn('event_type', [$eventType, 'any']);
    }
}
