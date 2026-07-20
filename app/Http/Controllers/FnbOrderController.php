<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Inertia\Inertia;
use App\Models\FnbItem;

class FnbOrderController extends Controller
{
    public function index()
    {
        $fnbItems = FnbItem::orderBy('name')->get();
        $fnbOrders = Transaction::fnbOnly()
            ->where('status', 'active')
            ->with('items.fnbItem')
            ->latest()
            ->get();

        $fnbHistory = Transaction::fnbOnly()
            ->whereIn('status', ['completed', 'cancelled'])
            ->with('items.fnbItem')
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('Fnb/Index', [
            'fnbItems' => $fnbItems,
            'fnbOrders' => $fnbOrders,
            'fnbHistory' => $fnbHistory,
        ]);
    }

    /**
     * Create a standalone F&B order (no table session).
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'items' => 'nullable|array',
            'items.*.fnb_item_id' => 'required|exists:fnb_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $transaction = Transaction::create([
            'type' => 'fnb_only',
            'table_id' => null,
            'package_id' => null,
            'user_id' => Auth::id(),
            'customer_name' => $request->customer_name,
            'start_time' => Carbon::now(),
            'billiard_cost' => 0,
            'fnb_cost' => 0,
            'total_cost' => 0,
            'status' => 'active',
        ]);

            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $fnbItem = \App\Models\FnbItem::find($item['fnb_item_id']);
                    if (!$fnbItem) continue;

                    $transaction->items()->create([
                        'fnb_item_id' => $fnbItem->id,
                        'price' => $fnbItem->price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $fnbItem->price * $item['quantity'],
                    ]);
                }
                \App\Services\TransactionService::recalculate($transaction);
            }

        return back()->with('success', 'Pesanan F&B berhasil dibuat.');
    }

    /**
     * Checkout / complete a standalone F&B order.
     */
    public function checkout(Transaction $transaction)
    {
        if ($transaction->status !== 'active') {
            return back()->with('error', 'Transaksi sudah tidak aktif.');
        }

        if ($transaction->type !== 'fnb_only') {
            return back()->with('error', 'Gunakan checkout meja untuk transaksi biliar.');
        }

        // Recalculate costs from items
        $transaction->end_time = Carbon::now();
        $transaction->status = 'completed';
        $transaction->save();
        
        \App\Services\TransactionService::recalculate($transaction);

        return back()->with('success', 'Pesanan F&B berhasil di-checkout.');
    }
}
