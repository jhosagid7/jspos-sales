<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $paymentModel;
    public $amountPaid;
    public $sale;

    /**
     * Create a new event instance.
     * $paymentModel can be BankPayment, CashPayment, etc.
     */
    public function __construct($paymentModel, $amountPaid, $sale)
    {
        $this->paymentModel = $paymentModel;
        $this->amountPaid = $amountPaid;
        $this->sale = $sale;
    }
}
