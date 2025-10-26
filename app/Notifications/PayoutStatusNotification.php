<?php

namespace App\Notifications;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Transaction $transaction, protected int $status, protected ?string $message = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusText = $this->status === PaymentGatewayConst::STATUSSUCCESS
            ? __('successful')
            : __('failed');

        $mail = (new MailMessage)
            ->subject(__('Payout status update'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->fullname ?? $notifiable->name ?? __('there')]))
            ->line(__('Your payout request #:reference is now :status.', [
                'reference' => $this->transaction->callback_ref ?? $this->transaction->trx_id,
                'status' => $statusText,
            ]));

        if ($this->message) {
            $mail->line($this->message);
        }

        $mail->line(__('Amount: :amount :currency', [
            'amount' => get_amount($this->transaction->request_amount, $this->transaction->creator_wallet->currency->code ?? ''),
            'currency' => $this->transaction->creator_wallet->currency->code ?? '',
        ]));

        return $mail;
    }
}
