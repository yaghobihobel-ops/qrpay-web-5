<?php

namespace App\Http\Controllers\Api\Support;

use App\Constants\SupportTicketConst;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Requests\Support\SupportTicketFeedbackRequest;
use App\Models\SupportBotSession;
use App\Models\UserSupportTicket;
use App\Models\UserSupportTicketAttachment;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $data = $request->validated();
        $token = 'ST' . Str::upper(Str::random(10));

        $session = null;
        if (!empty($data['bot_session_id'])) {
            $session = SupportBotSession::where('session_id', $data['bot_session_id'])->first();
        }

        $ticket = UserSupportTicket::create([
            'token' => $token,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'desc' => $data['message'],
            'status' => SupportTicketConst::DEFAULT,
            'support_bot_session_id' => $session?->id,
        ]);

        if (!empty($data['attachments'])) {
            $attachments = [];
            foreach ($data['attachments'] as $attachment) {
                $attachments[] = [
                    'user_support_ticket_id' => $ticket->id,
                    'attachment' => Arr::get($attachment, 'name'),
                    'attachment_info' => json_encode($attachment),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($attachments) {
                UserSupportTicketAttachment::insert($attachments);
            }
        }

        if ($session) {
            $session->handoff_recommended = true;
            $session->last_interaction_at = now();
            $session->save();
        }

        $this->notifyTeam($ticket);

        return response()->json([
            'data' => [
                'token' => $ticket->token,
                'status' => $ticket->stringStatus,
            ],
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        $ticket = UserSupportTicket::where('token', $token)->firstOrFail();

        return response()->json([
            'data' => [
                'token' => $ticket->token,
                'subject' => $ticket->subject,
                'status' => $ticket->stringStatus,
                'created_at' => $ticket->created_at,
                'first_response_at' => $ticket->first_response_at,
                'resolved_at' => $ticket->resolved_at,
                'satisfaction_score' => $ticket->satisfaction_score,
                'satisfaction_comment' => $ticket->satisfaction_comment,
            ],
        ]);
    }

    public function feedback(SupportTicketFeedbackRequest $request, string $token): JsonResponse
    {
        $ticket = UserSupportTicket::where('token', $token)->firstOrFail();
        $data = $request->validated();

        $ticket->update([
            'satisfaction_score' => $data['rating'],
            'satisfaction_comment' => $data['comment'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'token' => $ticket->token,
                'satisfaction_score' => $ticket->satisfaction_score,
                'satisfaction_comment' => $ticket->satisfaction_comment,
            ],
        ]);
    }

    protected function notifyTeam(UserSupportTicket $ticket): void
    {
        $email = config('support.notifications.email');
        $webhook = config('support.notifications.slack_webhook');

        if (!$email && !$webhook) {
            return;
        }

        $notification = new SupportTicketCreatedNotification($ticket);

        if ($email) {
            Notification::route('mail', $email)->notify($notification);
        }

        if ($webhook) {
            Notification::route('slack', $webhook)->notify($notification);
        }
    }
}
