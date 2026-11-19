<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $productData;

    /**
     * Create a new event instance.
     */
    public function __construct($product)
    {
        $this->productData = $product instanceof \Illuminate\Database\Eloquent\Model
            ? $product->toArray()
            : (array) $product;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast on a global public channel for the organization, or just 'catalog'
        return [
            new Channel('catalog'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'product' => $this->productData,
        ];
    }

    public function broadcastAs(): string
    {
        return 'product.updated';
    }
}
