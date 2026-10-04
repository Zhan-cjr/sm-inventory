<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PpobStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $branchId;
    public $ppobData;

    /**
     * Create a new event instance.
     */
    public function __construct($branchId, $ppobData)
    {
        $this->branchId = $branchId;
        $this->ppobData = $ppobData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('branch.' . $this->branchId . '.ppob'),
        ];
    }

    /**
     * Data yang akan dibroadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'ppob' => $this->ppobData,
        ];
    }
}
