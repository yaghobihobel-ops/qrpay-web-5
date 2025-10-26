<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class DeveloperPortalController extends Controller
{
    public function guide()
    {
        $path = resource_path('docs/developer-portal.md');

        $document = '';

        $updatedAt = null;

        if (File::exists($path)) {
            $contents = File::get($path);
            $updatedAt = Carbon::createFromTimestamp(File::lastModified($path))->toDateTimeString();

            if (method_exists(Str::class, 'markdown')) {
                $document = Str::markdown($contents)->toHtmlString();
            } else {
                $document = nl2br(e($contents));
            }
        }

        return view('admin.pages.developer-portal-guide', [
            'page_title' => __('Developer Portal Guide'),
            'document' => $document,
            'updatedAt' => $updatedAt,
        ]);
    }
}
