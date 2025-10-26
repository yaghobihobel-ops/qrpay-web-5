<?php

namespace App\Models;

use App\Casts\EncryptedJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardApi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'admin_id' => 'integer',
        'image' => 'string',
        'config' => EncryptedJson::class,
        'card_details' => 'string',
        'card_limit' => 'integer'
    ];
}
