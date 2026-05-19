<?php

namespace Database\Factories;

use App\Domain\Enums\Decision;
use App\Models\Ticket;
use App\Models\TicketDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketDecisionFactory extends Factory
{
    protected $model = TicketDecision::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'admin_id' => User::factory(),
            'decision' => fake()->randomElement([Decision::Approved, Decision::Rejected]),
            'note' => fake()->sentence(2),
            'level' => fake()->randomElement([1, 2]),
        ];
    }
}
