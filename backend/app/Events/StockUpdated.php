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

    public $branchId;
    public $productId;
    public $quantityOnHand;
    public $sellingPrice;
    public $costPrice;

    /**
     * Create a new event instance.
     */
    public function __construct(\App\Models\Stock $stock)
    {
        $this->branchId = $stock->branch_id;
        $this->productId = $stock->product_id;
        $this->quantityOnHand = $stock->quantity_on_hand;
        $this->sellingPrice = $stock->selling_price;
        $this->costPrice = $stock->cost_price;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('branch.' . $this->branchId . '.stock'),
        ];
    }

    /**
     * Data yang akan dibroadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity_on_hand' => $this->quantityOnHand,
            'selling_price' => $this->sellingPrice,
            'cost_price' => $this->costPrice,
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.updated';
    }
}
