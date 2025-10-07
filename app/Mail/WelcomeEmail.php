<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Welcome to Kafinta!')
                    ->from('aquadirmuhammad@gmail.com', 'Kafinta') // Use verified sender
                    ->view('emails.welcome')
                    ->with([
                        'user' => $this->user,
                        'appName' => config('app.name'),
                        'appUrl' => config('app.url')
                    ]);
    }
}
