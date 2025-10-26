<?php

namespace App\Notifications\Loyalty;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class LoyaltyCampaignNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $subject,
        protected string $message,
        protected array $data = [],
        protected string $channel = 'email'
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        if ($this->channel === 'email') {
            $channels[] = 'mail';
        }

        if ($this->channel === 'sms') {
            $channels[] = 'vonage';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $ctaUrl = $this->data['cta_url'] ?? url('/');
        $ctaLabel = $this->data['cta_label'] ?? __('View details');

        return (new MailMessage)
            ->subject($this->subject)
            ->greeting(__('Hello :name!', ['name' => $notifiable->fullname ?? $notifiable->name ?? __('there')]))
            ->line($this->message)
            ->action($ctaLabel, $ctaUrl);
    }

    public function toArray($notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
            'data' => $this->data,
            'channel' => $this->channel,
        ];
    }

    public function toVonage($notifiable): VonageMessage
    {
        return (new VonageMessage())
            ->content($this->message);
    }
}
