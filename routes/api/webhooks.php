<?php

use App\Http\Controllers\Webhooks\ProviderWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('{country}/{channel}/{provider}', [ProviderWebhookController::class, 'handle'])
    ->name('api.webhooks.handle');
