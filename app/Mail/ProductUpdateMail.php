<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $productId;
    public $productName;
    public $username;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($productId, $productName, $username)
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->username = $username;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Bagou450 - New udpate of ' . $this->productName)
            ->view('emails.newupdate')
            ->with([
                'productName' => $this->productId,
                'productId' => $this->productName,
                'username' => $this->username
            ]);
    }

}