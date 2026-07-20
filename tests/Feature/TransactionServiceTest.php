<?php

namespace Tests\Feature;

use App\Models\FnbItem;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_updates_fnb_and_total_costs(): void
    {
        $transaction = Transaction::factory()->create([
            'billiard_cost' => 50000,
            'fnb_cost' => 0,
            'total_cost' => 50000,
        ]);

        $item1 = FnbItem::factory()->create(['price' => 10000]);
        $item2 = FnbItem::factory()->create(['price' => 15000]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'fnb_item_id' => $item1->id,
            'quantity' => 2,
            'price' => 10000,
            'subtotal' => 20000,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'fnb_item_id' => $item2->id,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        TransactionService::recalculate($transaction);

        $this->assertEquals(35000, $transaction->fnb_cost);
        $this->assertEquals(85000, $transaction->total_cost);
    }
}
