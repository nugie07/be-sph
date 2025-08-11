<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SphNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;
    public $fileUrl;
    public $subjectLine;
    public $view;

public function __construct($data, $fileUrl, $subjectLine , $view )
{
    $this->data = $data;
    $this->fileUrl = $fileUrl;
    $this->subjectLine = $subjectLine;
    $this->view = $view;
}   

    public function build()
    {
    return $this->subject($this->subjectLine)
    ->view($this->view)
    ->with([
        'data' => $this->data,
        'fileUrl' => $this->fileUrl,
    ]);
    }
}
