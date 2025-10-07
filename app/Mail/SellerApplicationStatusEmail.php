<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerApplicationStatusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $status;
    public $reason;

    public function __construct(User $user, string $status, string $reason = null)
    {
        $this->user = $user;
        $this->status = $status;
        $this->reason = $reason;
    }

    public function build()
    {
        $subject = $this->status === 'approved' 
            ? 'Seller Application Approved - Kafinta'
            : 'Seller Application Update - Kafinta';

        return $this->subject($subject)
                    ->from('aquadirmuhammad@gmail.com', 'Kafinta')
                    ->view('emails.seller-application-status')
                    ->with([
                        'user' => $this->user,
                        'status' => $this->status,
                        'reason' => $this->reason,
                        'appName' => config('app.name'),
                        'appUrl' => config('app.url')
                    ]);
    }
}
