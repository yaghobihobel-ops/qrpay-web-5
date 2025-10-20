<?php

namespace App\Models\Merchants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeveloperApiCredential extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'merchant_id'   => 'integer',
        'name'          => 'string',
        'client_id'     => 'string',
        'client_secret' => 'string',
        'mode'          => 'string',
        'status'        => 'boolean'
    ];

    public function merchant() {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
    
    public function scopeAuth($query) {
        $query->where("merchant_id",auth()->user()->id);
    }

}
