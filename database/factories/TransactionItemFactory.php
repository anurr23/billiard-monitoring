<?php

namespace Database\Factories;

use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => \App\Models\Transaction::factory(),
            'fnb_item_id' => \App\Models\FnbItem::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => function (array $attributes) {
                return \App\Models\FnbItem::find($attributes['fnb_item_id'])->price;
            },
            'subtotal' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['price'];
            },
            'status' => 'pending',
        ];
    }
}
