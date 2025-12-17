<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public $pdf;

    public function __construct(Order $order, $pdf)
    {
        $this->order = $order;
        $this->pdf = $pdf;
    }

    public function build()
    {
        return $this->subject('Invoice - '.$this->order->order_number)
            ->view('emails.invoice')
            ->attachData($this->pdf, 'Invoice-'.$this->order->order_number.'.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
