<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $code;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $code
     */
    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Two-Factor Authentication Code - ' . config('app.name'))
                    ->view('emails.two-factor-code')
                    ->with([
                        'username' => $this->user->username,
                        'code' => $this->code,
                        'appName' => config('app.name'),
                        'expiresIn' => '10 minutes',
                    ]);
    }
}

