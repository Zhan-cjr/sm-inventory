<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $branchId;
    public $transactionData;

    /**
     * Create a new event instance.
     */
    public function __construct($branchId, $transactionData)
    {
        $this->branchId = $branchId;
        $this->transactionData = $transactionData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('branch.' . $this->branchId . '.transactions'),
        ];
    }

    /**
     * Data yang akan dibroadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'transaction' => $this->transactionData,
        ];
    }
}
