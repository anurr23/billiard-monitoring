<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionService
{
    public static function recalculate(Transaction $transaction): void
    {
        $transaction->fnb_cost = $transaction->items()->sum('subtotal');
        $transaction->total_cost = $transaction->billiard_cost + $transaction->fnb_cost;
        $transaction->save();
    }
}
