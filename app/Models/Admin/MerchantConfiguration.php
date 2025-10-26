<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\View\QrPayGatewayViewModelService;

class MerchantConfiguration extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function () {
            QrPayGatewayViewModelService::forgetMerchantConfiguration();
        });

        static::deleted(function () {
            QrPayGatewayViewModelService::forgetMerchantConfiguration();
        });
    }
}
