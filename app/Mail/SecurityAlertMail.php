<?php

namespace App\Mail;

use App\Services\Monitoring\DomainOperationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SecurityAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DomainOperationContext $context,
        public Throwable $exception,
        public int $failureCount,
        public int $threshold
    ) {
    }

    public function build(): self
    {
        return $this->subject(__('Security alert: :domain service issues', ['domain' => ucfirst($this->context->domain)]))
            ->markdown('emails.security-alert', [
                'context'      => $this->context,
                'exception'    => $this->exception,
                'failureCount' => $this->failureCount,
                'threshold'    => $this->threshold,
            ]);
    }
}
