<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\FnbItem;
use App\Services\TransactionService;
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

        TransactionService::recalculate($transaction);

        return back()->with('success', "{$fnbItem->name} berhasil ditambahkan.");
    }

    /**
     * Update item quantity by a change value (default +1).
     * If quantity reaches 0, the item is removed.
     */
    public function updateQuantity(Request $request, TransactionItem $item)
    {
        $transaction = $item->transaction;

        if ($transaction->status !== 'active') {
            return back()->with('error', 'Transaksi sudah tidak aktif.');
        }

        $change = (int) $request->input('change', 1);
        $newQty = $item->quantity + $change;

        if ($newQty <= 0) {
            $item->delete();
            TransactionService::recalculate($transaction);
            return back()->with('success', 'Item berhasil dihapus.');
        }

        $item->quantity = $newQty;
        $item->subtotal = $item->price * $item->quantity;
        $item->save();

        TransactionService::recalculate($transaction);

        return back()->with('success', 'Jumlah berhasil diubah.');
    }

    /**
     * Remove an item from a transaction entirely.
     */
    public function destroy(TransactionItem $item)
    {
        $transaction = $item->transaction;

        if ($transaction->status !== 'active') {
            return back()->with('error', 'Transaksi sudah tidak aktif.');
        }

        $item->delete();
        TransactionService::recalculate($transaction);

        return back()->with('success', 'Item berhasil dihapus.');
    }
}
