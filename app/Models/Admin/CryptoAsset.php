<?php

namespace App\Models\Admin;

use App\Casts\EncryptedJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoAsset extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'credentials' => EncryptedJson::class,
    ];


    public function gateway() {
        return $this->belongsTo(PaymentGateway::class,'payment_gateway_id');
    }
}
