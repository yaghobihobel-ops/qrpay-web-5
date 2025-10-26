<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Services\Support\HelpContentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HelpContentController extends Controller
{
    public function __construct(protected HelpContentService $helpContentService)
    {
    }

    public function show(string $section): JsonResponse
    {
        try {
            $content = $this->helpContentService->getContent($section);
        } catch (InvalidArgumentException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return response()->json([
            'data' => $content,
        ]);
    }
}
