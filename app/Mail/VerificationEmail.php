<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;
    public $verificationCode;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $verificationUrl, string $verificationCode)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
        $this->verificationCode = $verificationCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Verify Your Email Address')
                    ->from('aquadirmuhammad@gmail.com', 'Kafinta')
                    ->markdown('emails.verification');
    }
} 