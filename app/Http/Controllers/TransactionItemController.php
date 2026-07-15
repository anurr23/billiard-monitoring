<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\FnbItem;
use Illuminate\Http\Request;

class TransactionItemController extends Controller
{
    /**
     * Add F&B item to an active transaction.
     * If the same item already exists, increment its quantity.
     */
    public function store(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'active') {
            return back()->with('error', 'Transaksi sudah tidak aktif.');
        }

        $request->validate([
            'fnb_item_id' => 'required|exists:fnb_items,id',
            'quantity' => 'integer|min:1',
        ]);

        $fnbItem = FnbItem::findOrFail($request->fnb_item_id);
        $quantity = $request->input('quantity', 1);

        // Check if item already exists in this transaction
        $existingItem = $transaction->items()
            ->where('fnb_item_id', $fnbItem->id)
            ->first();

        if ($existingItem) {
            // Increment quantity
            $existingItem->quantity += $quantity;
            $existingItem->subtotal = $existingItem->price * $existingItem->quantity;
            $existingItem->save();
        } else {
            // Create new item
            $transaction->items()->create([
                'fnb_item_id' => $fnbItem->id,
                'quantity' => $quantity,
                'price' => $fnbItem->price,
                'subtotal' => $fnbItem->price * $quantity,
                'status' => 'pending',
            ]);
        }

        // Recalculate transaction costs
        $this->recalculateCosts($transaction);

        return back()->with('success', "{$fnbItem->name} berhasil ditambahkan.");
    }

    /**
     * Increment item quantity by 1.
     * Items cannot be removed or decreased — only added.
     */
    public function updateQuantity(TransactionItem $item)
    {
        $transaction = $item->transaction;

        if ($transaction->status !== 'active') {
            return back()->with('error', 'Transaksi sudah tidak aktif.');
        }

        $item->quantity += 1;
        $item->subtotal = $item->price * $item->quantity;
        $item->save();

        $this->recalculateCosts($transaction);

        return back()->with('success', 'Jumlah berhasil ditambah.');
    }

    /**
     * Recalculate fnb_cost and total_cost on the transaction.
     */
    private function recalculateCosts(Transaction $transaction): void
    {
        $fnbCost = $transaction->items()->sum('subtotal');
        $transaction->fnb_cost = $fnbCost;
        $transaction->total_cost = $transaction->billiard_cost + $fnbCost;
        $transaction->save();
    }
}
