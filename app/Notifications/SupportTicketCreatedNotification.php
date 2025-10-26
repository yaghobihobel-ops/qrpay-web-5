<?php

namespace App\Notifications;

use App\Models\UserSupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly UserSupportTicket $ticket)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('New support ticket: :subject', ['subject' => $this->ticket->subject]))
            ->greeting(__('Hello support team,'))
            ->line(__('A new support ticket has been created by :name (:email).', [
                'name' => $this->ticket->name,
                'email' => $this->ticket->email,
            ]))
            ->line(__('Subject: :subject', ['subject' => $this->ticket->subject]))
            ->line(__('Message: :message', ['message' => $this->ticket->desc]))
            ->line(__('Ticket token: :token', ['token' => $this->ticket->token]));
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->success()
            ->content('New support ticket received')
            ->attachment(function ($attachment) {
                $attachment
                    ->title($this->ticket->subject)
                    ->fields([
                        'Token' => $this->ticket->token,
                        'From' => sprintf('%s <%s>', $this->ticket->name, $this->ticket->email),
                        'Status' => ucfirst($this->ticket->stringStatus->value ?? 'pending'),
                    ])
                    ->content($this->ticket->desc ?? '');
            });
    }

    public function toArray($notifiable): array
    {
        return [
            'token' => $this->ticket->token,
            'subject' => $this->ticket->subject,
        ];
    }
}
