<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PassKeyEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $passkey_token;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $passkey_token)
    {

        $this->passkey_token = $passkey_token;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.passkeymail')->subject('Bagou450 - Add new PassKey');
    }
}
