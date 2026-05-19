<?php

namespace Database\Factories;

use App\Domain\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'attachment_path' => 'tickets/'.fake()->uuid().'.pdf',
            'status' => TicketStatus::Pending,
        ];
    }

    public function approvedL1(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::ApprovedL1,
        ]);
    }

    public function approvedL2(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::ApprovedL2,
        ]);
    }

    public function rejectedL1(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::RejectedL1,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Delivered,
        ]);
    }
}
