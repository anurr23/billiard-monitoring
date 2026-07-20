<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'billiard',
            'customer_name' => $this->faker->name(),
            'start_time' => now(),
            'status' => 'active',
            'billiard_cost' => 0,
            'fnb_cost' => 0,
            'total_cost' => 0,
            'table_id' => \App\Models\Table::factory(),
            'package_id' => \App\Models\Package::factory(),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
