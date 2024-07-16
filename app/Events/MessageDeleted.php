<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\Message;
use App\Models\Friendship;
class MessageDeleted implements shouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->broadcastOn();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if($this->message->destination_type == "group"){
            return [
            new PrivateChannel('group.message.'.$this->message->destination_id),
        ];
    }
    else{
        
        $friendship = Friendship::find($this->message->destination_id);
        $recipientId = $this->message->sender_id == $friendship->user_id ? $friendship->friend_id:$friendship->user_id;
        return [
            //change to presence chaneel
            new PrivateChannel('message.'.$recipientId),
        ];
    }     
    }
}
