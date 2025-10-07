<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $order;

    public function __construct(User $user, $order)
    {
        $this->user = $user;
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('Order Confirmation #' . $this->order->id . ' - Kafinta')
                    ->from('aquadirmuhammad@gmail.com', 'Kafinta')
                    ->view('emails.order-confirmation')
                    ->with([
                        'user' => $this->user,
                        'order' => $this->order,
                        'appName' => config('app.name'),
                        'appUrl' => config('app.url')
                    ]);
    }
}
