<?php

namespace App\Models\Risk;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanctionList extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'identifiers' => 'array',
        'is_active' => 'boolean',
        'listed_at' => 'date',
    ];

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
