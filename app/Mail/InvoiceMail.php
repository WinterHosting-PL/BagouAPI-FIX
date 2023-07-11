<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice_number;
    public $customer;
    public $items;
    public $invoice_date;
    public $due_date;
    public $pdf;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($invoice_number, $customer, $items, $invoice_date, $due_date, $pdf)
    {
        $this->invoice_number = $invoice_number;
        $this->customer = $customer;
        $this->items = $items;
        $this->invoice_date = $invoice_date;
        $this->due_date = $due_date;
        $this->pdf = $pdf;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $logo_url = 'https://cdn.bagou450.com/assets/img/logo_full_colored.png';

        return $this->subject('Bagou450 - Invoice for order #' . $this->invoice_number)
            ->view('emails.invoice')
            ->attachData($this->pdf->output(), "Invoice #$this->invoice_number - Bagou450.pdf", [
                'mime' => 'application/pdf',
            ])
            ->with([
                'logo_url' => $logo_url,
                'sincerely' => 'Sincerely, Bagou450 Team',
            ]);
    }

}