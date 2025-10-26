<?php

namespace App\Http\Controllers\Admin\Support;

use App\Constants\SupportTicketConst;
use App\Http\Controllers\Controller;
use App\Models\SupportBotSession;
use App\Models\UserSupportTicket;
use Illuminate\Support\Carbon;

class SupportAnalyticsController extends Controller
{
    public function index()
    {
        $page_title = __('Support analytics');

        $totalTickets = UserSupportTicket::count();
        $openTickets = UserSupportTicket::where('status', '!=', SupportTicketConst::SOLVED)->count();
        $solvedTickets = UserSupportTicket::where('status', SupportTicketConst::SOLVED)->count();

        $averageFirstResponseSeconds = UserSupportTicket::whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg')
            ->value('avg');
        $averageResolutionSeconds = UserSupportTicket::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) as avg')
            ->value('avg');

        $firstResponseMinutes = $averageFirstResponseSeconds ? round($averageFirstResponseSeconds / 60, 2) : null;
        $resolutionHours = $averageResolutionSeconds ? round($averageResolutionSeconds / 3600, 2) : null;

        $slaFirstTarget = (int) config('support.sla.first_response_minutes', 30);
        $slaResolutionTarget = (int) config('support.sla.resolution_minutes', 1440);

        $firstResponseBreaches = UserSupportTicket::where(function ($query) use ($slaFirstTarget) {
            $query->whereNull('first_response_at')
                ->orWhereRaw('TIMESTAMPDIFF(MINUTE, created_at, first_response_at) > ?', [$slaFirstTarget]);
        })->count();

        $resolutionBreaches = UserSupportTicket::where(function ($query) use ($slaResolutionTarget) {
            $query->whereNull('resolved_at')
                ->orWhereRaw('TIMESTAMPDIFF(MINUTE, created_at, resolved_at) > ?', [$slaResolutionTarget]);
        })->count();

        $averageSatisfaction = UserSupportTicket::whereNotNull('satisfaction_score')->avg('satisfaction_score');

        $sessionCount = SupportBotSession::count();
        $handoffSessions = SupportBotSession::where('handoff_recommended', true)->count();
        $ticketsFromBot = UserSupportTicket::whereNotNull('support_bot_session_id')->count();
        $deflectionRate = null;
        if ($sessionCount > 0) {
            $deflectionRate = round(100 - (($handoffSessions / $sessionCount) * 100), 1);
        }

        $trendData = UserSupportTicket::selectRaw('DATE(created_at) as label, COUNT(*) as total')
            ->where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => Carbon::parse($row->label)->format('M d'),
                    'value' => (int) $row->total,
                ];
            });

        $recentTickets = UserSupportTicket::latest()->with('supportBotSession')->take(10)->get();

        return view('admin.sections.support.analytics', compact(
            'page_title',
            'totalTickets',
            'openTickets',
            'solvedTickets',
            'firstResponseMinutes',
            'resolutionHours',
            'slaFirstTarget',
            'slaResolutionTarget',
            'firstResponseBreaches',
            'resolutionBreaches',
            'averageSatisfaction',
            'sessionCount',
            'handoffSessions',
            'ticketsFromBot',
            'deflectionRate',
            'trendData',
            'recentTickets'
        ));
    }
}
