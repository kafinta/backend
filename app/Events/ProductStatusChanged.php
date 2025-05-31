<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $oldStatus;
    public $newStatus;
    public $denialReason;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param string $oldStatus
     * @param string $newStatus
     * @param string $denialReason
     */
    public function __construct(Product $product, string $oldStatus, string $newStatus, string $denialReason = '')
    {
        $this->product = $product;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->denialReason = $denialReason;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
