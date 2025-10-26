<?php

namespace App\Http\Controllers;

use App\Models\HelpContentCompletion;
use App\Models\HelpContentFaqLog;
use App\Models\HelpContentFeedback;
use App\Models\HelpContentView;
use App\Services\HelpContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpContentController extends Controller
{
    public function __construct(protected HelpContentService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $language = $request->query('lang');
        $query = $request->query('q');
        $sections = $this->service->getSections($language, $query);

        return response()->json([
            'data' => $sections,
        ]);
    }

    public function show(string $section, Request $request): JsonResponse
    {
        $language = $request->query('lang');
        $version = $request->query('version');

        $content = $this->service->getContent($section, $language, $version);

        if (!$content) {
            return response()->json([
                'message' => __('Requested help content was not found.'),
            ], 404);
        }

        return response()->json([
            'data' => $content,
        ]);
    }

    public function track(string $section, Request $request): JsonResponse
    {
        $payload = $request->validate([
            'version' => ['nullable', 'string', 'max:32'],
            'language' => ['nullable', 'string', 'max:12'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'array'],
        ]);

        $content = $this->service->getContent($section, $payload['language'] ?? null, $payload['version'] ?? null);

        if (!$content) {
            return response()->json([
                'message' => __('Requested help content was not found.'),
            ], 404);
        }

        [$viewerType, $viewerId] = $this->resolveViewer();

        HelpContentView::create([
            'section_id' => $section,
            'version' => $content['version'],
            'language' => $content['language'],
            'viewer_type' => $viewerType,
            'viewer_id' => $viewerId,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'duration_seconds' => $payload['duration_seconds'] ?? 0,
            'meta' => $payload['meta'] ?? null,
        ]);

        return response()->json([
            'message' => __('Help center view stored successfully.'),
        ], 201);
    }

    public function complete(string $section, Request $request): JsonResponse
    {
        $payload = $request->validate([
            'version' => ['nullable', 'string', 'max:32'],
            'language' => ['nullable', 'string', 'max:12'],
            'total_steps' => ['required', 'integer', 'min:1'],
            'completed_steps' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
            'meta' => ['nullable', 'array'],
        ]);

        $content = $this->service->getContent($section, $payload['language'] ?? null, $payload['version'] ?? null);

        if (!$content) {
            return response()->json([
                'message' => __('Requested help content was not found.'),
            ], 404);
        }

        [$viewerType, $viewerId] = $this->resolveViewer();

        $status = $payload['status'] ?? ($payload['completed_steps'] >= $payload['total_steps'] ? 'completed' : 'in_progress');

        HelpContentCompletion::create([
            'section_id' => $section,
            'version' => $content['version'],
            'language' => $content['language'],
            'viewer_type' => $viewerType,
            'viewer_id' => $viewerId,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'total_steps' => $payload['total_steps'],
            'completed_steps' => min($payload['completed_steps'], $payload['total_steps']),
            'status' => $status,
            'meta' => $payload['meta'] ?? null,
        ]);

        return response()->json([
            'message' => __('Training progress stored successfully.'),
        ], 201);
    }

    public function feedback(string $section, Request $request): JsonResponse
    {
        $payload = $request->validate([
            'version' => ['nullable', 'string', 'max:32'],
            'language' => ['nullable', 'string', 'max:12'],
            'rating' => ['required', 'string', 'in:positive,negative'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'meta' => ['nullable', 'array'],
        ]);

        $content = $this->service->getContent($section, $payload['language'] ?? null, $payload['version'] ?? null);

        if (!$content) {
            return response()->json([
                'message' => __('Requested help content was not found.'),
            ], 404);
        }

        [$viewerType, $viewerId] = $this->resolveViewer();

        HelpContentFeedback::create([
            'section_id' => $section,
            'version' => $content['version'],
            'language' => $content['language'],
            'viewer_type' => $viewerType,
            'viewer_id' => $viewerId,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'rating' => $payload['rating'],
            'comment' => $payload['comment'] ?? null,
            'meta' => $payload['meta'] ?? null,
        ]);

        return response()->json([
            'message' => __('Feedback submitted. Thank you!'),
        ], 201);
    }

    public function faq(string $section, Request $request): JsonResponse
    {
        $payload = $request->validate([
            'faq_id' => ['required', 'string', 'max:191'],
            'version' => ['nullable', 'string', 'max:32'],
            'language' => ['nullable', 'string', 'max:12'],
            'action' => ['nullable', 'string', 'max:32'],
            'meta' => ['nullable', 'array'],
        ]);

        $faq = $this->service->resolveFaq($section, $payload['faq_id'], $payload['language'] ?? null);

        if (!$faq) {
            return response()->json([
                'message' => __('The requested FAQ could not be matched.'),
            ], 404);
        }

        [$viewerType, $viewerId] = $this->resolveViewer();

        $meta = array_merge([
            'question' => $faq['question'],
        ], $payload['meta'] ?? []);

        HelpContentFaqLog::create([
            'section_id' => $section,
            'faq_id' => $payload['faq_id'],
            'version' => $faq['version'],
            'language' => $payload['language'] ?? $faq['language'] ?? null,
            'viewer_type' => $viewerType,
            'viewer_id' => $viewerId,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'action' => $payload['action'] ?? 'view',
            'meta' => $meta,
        ]);

        return response()->json([
            'message' => __('FAQ interaction recorded.'),
        ], 201);
    }

    protected function resolveViewer(): array
    {
        $guards = ['web', 'admin', 'merchant', 'agent'];

        foreach ($guards as $guard) {
            $user = auth($guard)->user();

            if ($user) {
                return [get_class($user), $user->getKey()];
            }
        }

        return [null, null];
    }
}
