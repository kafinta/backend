<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;
    public $resetCode;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $resetUrl, string $resetCode)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->resetCode = $resetCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Reset Your Password')
                    ->markdown('emails.password-reset');
    }
} 