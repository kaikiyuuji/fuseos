<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Message types available in the system.
     */
    private array $types = ['text', 'file', 'system', 'ai'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'type' => 'text',
        ];
    }

    /**
     * Indicate the message is from the AI assistant.
     */
    public function ai(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'type' => 'ai',
            'content' => 'Resumo gerado pela IA: '.fake()->paragraph(),
        ]);
    }

    /**
     * Indicate the message is a system notification.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'type' => 'system',
            'content' => fake()->randomElement([
                'Canal criado.',
                'Usuário entrou no canal.',
                'Usuário saiu do canal.',
            ]),
        ]);
    }
}
