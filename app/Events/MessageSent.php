<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\Friendship;
use App\Models\Message;

use Illuminate\Support\Facades\Log;


class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $message; 
    public $receiver_id;
    /**
     * Create a new event instance.
     */
    public function __construct($message, $receiver_id)
    {
        $this->message = $message;
        $this->receiver_id = $receiver_id;
        $this->broadcastOn();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        Log::Info($this->message);
            if($this->message->destination_type == "group"){
                    return [
                    new PrivateChannel('group.message.'.$this->message->destination_id),
                ];
            }
            else{

                return [
                    //change to presence chaneel
                   
                    new PrivateChannel('message.'.$this->receiver_id),
                ];
            }        
    }
}
