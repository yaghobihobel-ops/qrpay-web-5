<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class ThrottleLimitExceeded extends Notification
{
    use Queueable;

    protected string $service;
    protected string $dimension;
    protected string $identifier;
    protected int $maxAttempts;
    protected int $retryAfter;
    protected array $channels;

    public function __construct(
        string $service,
        string $dimension,
        string $identifier,
        int $maxAttempts,
        int $retryAfter,
        array $channels
    ) {
        $this->service = $service;
        $this->dimension = $dimension;
        $this->identifier = $identifier;
        $this->maxAttempts = $maxAttempts;
        $this->retryAfter = $retryAfter;
        $this->channels = $channels;
    }

    public function via($notifiable): array
    {
        return $this->channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Throttle limit exceeded for :service', ['service' => $this->service]))
            ->line(__('The :service service exceeded its :dimension quota for identifier :identifier.', [
                'service' => $this->service,
                'dimension' => str_replace('_', ' ', $this->dimension),
                'identifier' => $this->identifier,
            ]))
            ->line(__('Maximum attempts: :max', ['max' => $this->maxAttempts]))
            ->line(__('Retry after: :seconds seconds', ['seconds' => $this->retryAfter]));
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->warning()
            ->content(__('Throttle limit exceeded for :service', ['service' => $this->service]))
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'Dimension' => $this->dimension,
                    'Identifier' => $this->identifier,
                    'Max Attempts' => $this->maxAttempts,
                    'Retry After (s)' => $this->retryAfter,
                ]);
            });
    }
}
