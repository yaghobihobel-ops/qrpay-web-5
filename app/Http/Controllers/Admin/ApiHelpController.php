<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HelpContentService;
use Illuminate\Http\Request;

class ApiHelpController extends Controller
{
    public function __construct(protected HelpContentService $helpContentService)
    {
    }

    public function index(Request $request)
    {
        $searchTerm = trim((string) $request->get('q', ''));

        $categories = $this->helpContentService->getApiCategories($searchTerm);
        $postmanCollectionUrl = asset($this->helpContentService->getPostmanCollectionPath());
        $videoUrl = $this->helpContentService->getApiOverviewVideoUrl();

        return view('admin.api-help', [
            'page_title' => __('API Help Center'),
            'categories' => $categories,
            'searchTerm' => $searchTerm,
            'postmanCollectionUrl' => $postmanCollectionUrl,
            'videoUrl' => $videoUrl,
        ]);
    }
}
