<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class ContactEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $firstname;
    public $lastname;
    public $email;
    public $phone;
    public $messages;
    public $society;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $firstname, String $lastname, String $email, String $phone, String $messages, String $society)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->phone = $phone;
        $this->messages = $messages;
        $this->society = $society;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.contactmail')->subject('New Support contact');
    }
}
