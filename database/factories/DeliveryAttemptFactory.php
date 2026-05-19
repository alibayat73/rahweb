<?php

namespace Database\Factories;

use App\Models\DeliveryAttempt;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Response;

class DeliveryAttemptFactory extends Factory
{
    protected $model = DeliveryAttempt::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'status' => fake()->randomElement(['success', 'failed']),
            'response_code' => fake()->randomElement([Response::HTTP_OK, Response::HTTP_INTERNAL_SERVER_ERROR]),
            'response_message' => fake()->sentence(1),
            'attempted_at' => fake()->dateTime(),
        ];
    }
}
