<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportBotMessage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'confidence' => 'float',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SupportBotSession::class, 'support_bot_session_id');
    }
}
