<?php

namespace App\Notifications\User\MoneyOut;

use App\Services\Notifications\LocalizedMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class SenderMail extends Notification
{
    use Queueable;

    public $user;
    public $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user,$data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $user = $this->user;
        $data = $this->data;
        $trx_id = $this->data->trx_id;
        $date = Carbon::now();
        $dateTime = $date->format('Y-m-d h:i:s A');

        $messaging = app(LocalizedMessagingService::class);
        $context = $messaging->resolveUserContext($user);

        $emailCopy = $messaging->emailTemplate('money_out.sender', [
            'amount' => $data->request_amount,
            'reference' => $trx_id,
            'country' => $context['country'] ?? __('messaging.labels.scenario_playbook'),
        ], $context, [
            'subject' => $data->title,
            'intro' => $data->title,
        ]);

        $mail = (new MailMessage)
                    ->greeting(__("Hello")." ".$user->fullname." !")
                    ->subject($emailCopy['subject'])
                    ->line(__("Sender Money Out Email Heading").":");

        if (!empty($emailCopy['intro'])) {
            $mail->line($emailCopy['intro']);
        }

        $mail->line(__("web_trx_id").": " .$trx_id)
            ->line(__("request Amount").": " .$data->request_amount)
            ->line(__("Fees & Charges").": " .$data->charges)
            ->line(__("Total Payable Amount").": " .$data->payable)
            ->line(__("Recipient Received").": " .$data->received_amount)
            ->line(__("Status").": " .$data->status)
            ->line(__("Date And Time").": " .$dateTime);

        if (!empty($emailCopy['footer'])) {
            $mail->line($emailCopy['footer']);
        }

        return $mail->line(__('Thank you for using our application!'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
