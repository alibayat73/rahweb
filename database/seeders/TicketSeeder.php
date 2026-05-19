<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ticket::factory()
            ->count(20)
            ->create();

        Ticket::factory()
            ->count(5)
            ->approvedL1()
            ->create();

        Ticket::factory()
            ->count(3)
            ->approvedL2()
            ->create();

        Ticket::factory()
            ->count(2)
            ->rejectedL1()
            ->create();

        Ticket::factory()
            ->count(2)
            ->delivered()
            ->create();
    }
}
