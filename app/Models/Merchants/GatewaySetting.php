<?php

namespace App\Models\Merchants;

use App\Casts\EncryptedJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewaySetting extends Model
{
    use HasFactory;
    protected $guarded = ["id"];
    protected $casts = [
        'id'                    => 'integer',
        'wallet_status'         => 'boolean',
        'virtual_card_status'   => 'boolean',
        'master_visa_status'    => 'boolean',
        'credentials'           => EncryptedJson::class,
        'created_at'            => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function merchant() {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeMerchantAuth($query) {
        $query->where("merchant_id",auth()->user()->id);
    }
}
