<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  int  $userId  ID do usuário que está digitando
     * @param  string  $userName  Nome do usuário (para exibição)
     * @param  string  $channelId  UUID do canal onde o evento ocorre
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $userName,
        public readonly string $channelId,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Transmite para o canal de presença do canal, onde os membros online
     * recebem o evento de "usuário está digitando".
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel.'.$this->channelId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * Get the data to broadcast with the event.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'channel_id' => $this->channelId,
        ];
    }
}
