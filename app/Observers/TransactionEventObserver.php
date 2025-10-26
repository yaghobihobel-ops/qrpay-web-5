<?php

namespace App\Observers;

use App\Constants\PaymentGatewayConst;
use App\DataTransferObjects\EventStreamMessage;
use App\Models\Transaction;
use App\Services\Messaging\EventStreamPublisher;

class TransactionEventObserver
{
    public function __construct(protected EventStreamPublisher $publisher)
    {
    }

    public function created(Transaction $transaction): void
    {
        $eventType = $this->determineEventType($transaction);
        if ($eventType === null) {
            return;
        }

        $destination = config("eventstream.events.$eventType.destination");
        $message = EventStreamMessage::forTransaction($transaction, $eventType, $destination);
        $this->publisher->publish($message);
    }

    protected function determineEventType(Transaction $transaction): ?string
    {
        return match ($transaction->type) {
            PaymentGatewayConst::TYPEMAKEPAYMENT, PaymentGatewayConst::MERCHANTPAYMENT => 'transactions.payment.completed',
            PaymentGatewayConst::TYPEMONEYEXCHANGE => 'transactions.exchange.completed',
            PaymentGatewayConst::TYPEMONEYOUT => 'transactions.withdrawal.completed',
            default => null,
        };
    }
}
