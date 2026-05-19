<?php

namespace Database\Seeders;

use App\Domain\Enums\AdminRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Level 1',
            'email' => 'admin_l1@example.com',
            'password' => bcrypt('password'),
            'role' => AdminRole::AdminL1,
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'name' => 'Admin Level 2',
            'email' => 'admin_l2@example.com',
            'password' => bcrypt('password'),
            'role' => AdminRole::AdminL2,
            'email_verified_at' => now(),
        ]);
    }
}
