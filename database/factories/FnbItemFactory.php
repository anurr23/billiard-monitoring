<?php

namespace Database\Factories;

use App\Models\FnbItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FnbItem>
 */
class FnbItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'category' => $this->faker->randomElement(['Makanan', 'Minuman', 'Snack']),
            'price' => $this->faker->randomElement([5000, 10000, 15000, 20000]),
        ];
    }
}
