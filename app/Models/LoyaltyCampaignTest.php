<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyCampaignTest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sample_size' => 'integer',
        'deliveries' => 'integer',
        'conversions' => 'integer',
        'metrics' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCampaign::class, 'loyalty_campaign_id');
    }

    public function registerDelivery(): void
    {
        $this->increment('deliveries');
    }

    public function registerConversion(): void
    {
        $this->increment('conversions');
    }
}
