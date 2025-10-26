<?php

namespace App\Notifications\Monitoring;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class ServiceAlertNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private array $result, private string $severity)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->routeNotificationFor('mail')) {
            $channels[] = 'mail';
        }

        if ($notifiable->routeNotificationFor('slack')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->subject())
            ->line($this->summaryLine());

        foreach ($this->contextLines() as $line) {
            $message->line($line);
        }

        return $message;
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->{$this->slackMethod()}('['.$this->severityLabel().'] '.$this->result['service_name'])
            ->content($this->summaryLine())
            ->attachment(function ($attachment) {
                $attachment->fields($this->slackFields());
            });
    }

    /**
     * Get the subject for notifications.
     */
    protected function subject(): string
    {
        return sprintf('[%s] %s service alert', strtoupper($this->severityLabel()), ucfirst($this->result['service_name']));
    }

    /**
     * Human readable summary line.
     */
    protected function summaryLine(): string
    {
        return $this->result['message'];
    }

    /**
     * Detail lines for email context.
     */
    protected function contextLines(): array
    {
        $meta = $this->result['meta'] ?? [];
        $lines = [
            sprintf('Status: %s', strtoupper($this->result['status'])),
        ];

        if ($this->result['latency_ms']) {
            $lines[] = sprintf('Latency: %s ms', $this->result['latency_ms']);
        }

        if (!empty($meta['error_rate'])) {
            $lines[] = sprintf('Error rate: %.2f%%', $meta['error_rate']);
        }

        if (!empty($meta['fee'])) {
            $lines[] = sprintf('Fee: %.2f', $meta['fee']);
        }

        if ($this->result['error_message']) {
            $lines[] = 'Error: '.$this->result['error_message'];
        }

        return $lines;
    }

    /**
     * Slack fields payload.
     */
    protected function slackFields(): array
    {
        $fields = [
            'Status' => strtoupper($this->result['status']),
        ];

        if ($this->result['latency_ms']) {
            $fields['Latency (ms)'] = $this->result['latency_ms'];
        }

        $meta = $this->result['meta'] ?? [];
        if (!empty($meta['error_rate'])) {
            $fields['Error rate'] = $meta['error_rate'].'%';
        }

        if (!empty($meta['fee'])) {
            $fields['Fee'] = $meta['fee'];
        }

        if (!empty($this->result['error_message'])) {
            $fields['Error'] = $this->result['error_message'];
        }

        $fields['Checked at'] = $this->result['checked_at']->toDateTimeString();

        return $fields;
    }

    /**
     * Slack severity method mapping.
     */
    protected function slackMethod(): string
    {
        return match ($this->severity) {
            'critical' => 'error',
            'warning' => 'warning',
            default => 'success',
        };
    }

    /**
     * Severity label mapping.
     */
    protected function severityLabel(): string
    {
        return match ($this->severity) {
            'critical' => 'critical',
            'warning' => 'warning',
            default => 'recovered',
        };
    }
}
