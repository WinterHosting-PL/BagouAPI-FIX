<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public $pdf;
    public $items;
    public $invoice_number;
    public $invoice_date;
    public $due_date;
    public $customer;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($pdf, $items, $invoice_number, $invoice_date, $due_date, $customer)
    {
        $this->pdf = $pdf;
        $this->items = $items;
        $this->invoice_number = $invoice_number;
        $this->invoice_date = $invoice_date;
        $this->due_date = $due_date;
        $this->customer = $customer;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $logo_url = 'https://cdn.bagou450.com/assets/img/logo_full_colored.png';

        return $this->subject('Bagou450 - Order Confirmation #' . $this->invoice_number)
            ->view('emails.invoice')
            ->attachData($this->pdf->output(), "Invoice #$this->invoice_number - Bagou450.pdf", [
                'mime' => 'application/pdf',
            ])
            ->with([
                'logo_url' => $logo_url,
                'items' => $this->items,
                'invoice_number' => $this->invoice_number,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'customer' => $this->customer,
            ]);
    }

}