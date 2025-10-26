<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportBotSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'handoff_recommended' => 'boolean',
        'last_interaction_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportBotMessage::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(UserSupportTicket::class);
    }
}
