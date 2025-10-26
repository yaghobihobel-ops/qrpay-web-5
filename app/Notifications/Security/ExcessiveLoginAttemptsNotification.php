<?php

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ExcessiveLoginAttemptsNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $identifier,
        protected ?string $ip,
        protected int $attempts,
        protected Carbon $timestamp
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Excessive login attempts detected'))
            ->line(__('Identifier: :identifier', ['identifier' => $this->identifier]))
            ->line(__('IP Address: :ip', ['ip' => $this->ip ?? __('unknown')]))
            ->line(__('Attempts: :attempts', ['attempts' => $this->attempts]))
            ->line(__('Time: :time', ['time' => $this->timestamp->toIso8601String()]));
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->warning()
            ->content(sprintf(
                'Excessive login attempts detected for %s (IP: %s, Attempts: %d, Time: %s)',
                $this->identifier,
                $this->ip ?? 'unknown',
                $this->attempts,
                $this->timestamp->toIso8601String()
            ));
    }
}
