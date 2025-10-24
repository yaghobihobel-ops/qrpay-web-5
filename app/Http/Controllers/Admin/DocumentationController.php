<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\File;

class DocumentationController extends Controller
{
    /**
     * نمایش راهنمای توسعه سیستم.
     */
    public function index()
    {
        $guidePath = resource_path('docs/system-guide.md');

        if (! File::exists($guidePath)) {
            abort(404, __('System guide not found.'));
        }

        $markdown = File::get($guidePath);
        $documentation = app(Markdown::class)->parse($markdown);

        $page_title = __('راهنمای توسعه سیستم');
        $quickLinks = [
            'module-payment' => __('پرداخت'),
            'module-exchange' => __('اکسچنج'),
            'module-withdrawal' => __('برداشت'),
            'module-kyc' => __('احراز هویت (KYC)'),
            'module-card' => __('کارت و کیف پول مجازی'),
        ];

        return view('admin.documentation', compact('page_title', 'documentation', 'quickLinks'));
    }
}
