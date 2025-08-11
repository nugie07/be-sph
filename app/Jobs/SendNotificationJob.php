<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\SphNotificationMail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $recipients;
    protected $mailData;
    protected $fileUrl;
    protected $subject;
    protected $view;

    public function __construct(array $recipients, array $mailData, $fileUrl = null, $subject = '', $view = 'emails.default_notification')
    {
        $this->recipients = $recipients;
        $this->mailData = $mailData;
        $this->fileUrl = $fileUrl;
        $this->subject = $subject;
        $this->view = $view;
    }

    public function handle()
    {
        foreach ($this->recipients as $recipient) {
            $personalized = array_merge($this->mailData, [
                'fullname' => $recipient['name']
            ]);

            Mail::to($recipient['email'])->queue(
                new SphNotificationMail($personalized, $this->fileUrl, $this->subject, $this->view)
            );
        }
    }
}