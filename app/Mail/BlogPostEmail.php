<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BlogPostEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $slug;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($title, $slug)
    {
        $this->title = $title;
        $this->slug = $slug;

    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $logo_url = 'https://cdn.bagou450.com/assets/img/logo_full_colored.png';

        return $this->subject('Bagou450 - ' . $this->title)
            ->view('emails.blogpost')

            ->with([
                'logo_url' => $logo_url,
                'BlogTitle' => $this->title,
                'blogSlug' => $this->slug,
            ]);
    }

}