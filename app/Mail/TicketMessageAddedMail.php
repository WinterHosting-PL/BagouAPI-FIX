<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Ticket;
use App\Models\TicketMessage;

class TicketMessageAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $message;

    public function __construct(Ticket $ticket, TicketMessage $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;
    }

    public function build()
    {
        return $this->view('emails.ticket_message_added')
            ->subject('Bagou450 - New Message in Ticket');
    }
}
