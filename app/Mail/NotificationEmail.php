<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $emailSubject;
    public $template;
    public $data;

    public function __construct(User $user, string $subject, string $template, array $data = [])
    {
        $this->user = $user;
        $this->emailSubject = $subject;
        $this->template = $template;
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject($this->emailSubject)
                    ->from('aquadirmuhammad@gmail.com', 'Kafinta')
                    ->view($this->template)
                    ->with(array_merge($this->data, [
                        'user' => $this->user,
                        'appName' => config('app.name'),
                        'appUrl' => config('app.url')
                    ]));
    }
}
