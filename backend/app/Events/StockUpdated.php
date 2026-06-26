<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stock;

    /**
     * Create a new event instance.
     */
    public function __construct(\App\Models\Stock $stock)
    {
        $this->stock = $stock;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('branch.' . $this->stock->branch_id . '.stock'),
        ];
    }

    /**
     * Data yang akan dibroadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->stock->product_id,
            'quantity_on_hand' => $this->stock->quantity_on_hand,
            'selling_price' => $this->stock->selling_price,
            'cost_price' => $this->stock->cost_price,
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.updated';
    }
}
