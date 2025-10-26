<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrowalletVirtualCardAudit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'strowallet_virtual_card_id' => 'integer',
        'changed_by' => 'integer',
    ];

    public function card()
    {
        return $this->belongsTo(StrowalletVirtualCard::class, 'strowallet_virtual_card_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
