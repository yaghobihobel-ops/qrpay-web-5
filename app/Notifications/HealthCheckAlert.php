<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HealthCheckAlert extends Notification
{
    use Queueable;

    public function __construct(protected array $provider, protected array $result)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusLabel = strtoupper($this->result['status']);
        $latency = $this->result['latency_ms'];
        $checkedAt = $this->result['checked_at'];

        $mail = (new MailMessage)
            ->subject(__('Service health alert: :provider is :status', [
                'provider' => $this->provider['name'],
                'status' => $statusLabel,
            ]))
            ->line(__('Provider: :provider', ['provider' => $this->provider['name']]))
            ->line(__('Status: :status', ['status' => $statusLabel]))
            ->line(__('Latency: :latency ms', ['latency' => $latency ?? __('n/a')]))
            ->line(__('Checked at: :time', ['time' => $checkedAt]))
            ->line(__('Endpoint: :url', ['url' => $this->provider['url']]))
            ->salutation(__('Stay vigilant.'));

        if (! empty($this->result['message'])) {
            $mail->line(__('Message: :message', ['message' => $this->result['message']]));
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'provider' => $this->provider['slug'],
            'name' => $this->provider['name'],
            'status' => $this->result['status'],
            'latency_ms' => $this->result['latency_ms'],
            'checked_at' => $this->result['checked_at'],
            'message' => $this->result['message'],
        ];
    }
}
