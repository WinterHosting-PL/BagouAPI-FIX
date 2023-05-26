<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;
    public $username;
    public $addon;
    public $licenses;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $username, String $addon, Array $licenses)
    {
        $this->username = $username;
        $this->addon = $addon;
        $this->licenses = $licenses;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.license')->subject('Bagou450 - Your licenses');
    }
}
