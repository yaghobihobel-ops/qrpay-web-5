<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpContentFaqLog;
use App\Models\HelpContentView;
use App\Services\HelpContentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HelpContentAnalyticsController extends Controller
{
    public function index(HelpContentService $service)
    {
        $page_title = __('Help Center Analytics');

        $sectionsMeta = collect($service->getSections());
        $sectionLookup = $sectionsMeta->keyBy('id');

        $totalViews = HelpContentView::count();
        $averageRead = (float) round(HelpContentView::avg('duration_seconds') ?? 0, 2);
        $uniqueViewers = HelpContentView::select(DB::raw("COUNT(DISTINCT COALESCE(CONCAT(viewer_type,'-',viewer_id), session_id)) as total"))->value('total') ?? 0;
        $topLanguageRow = HelpContentView::select('language', DB::raw('count(*) as total'))
            ->whereNotNull('language')
            ->groupBy('language')
            ->orderByDesc('total')
            ->first();
        $topLanguage = $topLanguageRow?->language;

        $sectionStats = HelpContentView::select(
            'section_id',
            DB::raw('COUNT(*) as total_views'),
            DB::raw('AVG(duration_seconds) as avg_duration'),
            DB::raw('MAX(created_at) as last_viewed')
        )
            ->groupBy('section_id')
            ->orderByDesc('total_views')
            ->get()
            ->map(function ($row) use ($sectionLookup) {
                $meta = $sectionLookup->get($row->section_id, []);
                return [
                    'section_id' => $row->section_id,
                    'title' => $meta['title'] ?? $row->section_id,
                    'summary' => $meta['summary'] ?? null,
                    'views' => (int) $row->total_views,
                    'avg_duration' => round((float) $row->avg_duration, 2),
                    'last_viewed' => $row->last_viewed ? Carbon::parse($row->last_viewed) : null,
                    'languages' => $meta['available_languages'] ?? [],
                ];
            });

        $faqStats = HelpContentFaqLog::select(
            'section_id',
            'faq_id',
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('section_id', 'faq_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) use ($service) {
                $faq = $service->resolveFaq($row->section_id, $row->faq_id);
                return [
                    'section_id' => $row->section_id,
                    'faq_id' => $row->faq_id,
                    'question' => $faq['question'] ?? $row->faq_id,
                    'total' => (int) $row->total,
                ];
            });

        $trendStart = now()->subDays(13)->startOfDay();
        $trendData = HelpContentView::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $trendStart)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('total', 'date');

        $trendSeries = collect(range(0, 13))->map(function ($offset) use ($trendStart, $trendData) {
            $date = $trendStart->copy()->addDays($offset);
            $key = $date->toDateString();
            return [
                'label' => $date->format('M d'),
                'value' => (int) ($trendData[$key] ?? 0),
            ];
        });

        return view('admin.sections.help.analytics', compact(
            'page_title',
            'totalViews',
            'averageRead',
            'uniqueViewers',
            'topLanguage',
            'sectionStats',
            'faqStats',
            'trendSeries'
        ));
    }
}
