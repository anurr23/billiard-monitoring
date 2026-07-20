<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\FnbItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::with([
            'transactions' => function($q) {
                $q->where('status', 'active')->with('items.fnbItem');
            },
            'recentTransactions'
        ])->get()->sortBy('name', SORT_NATURAL)->values();
        $packages = \App\Models\Package::all();
        $fnbItems = FnbItem::orderBy('name')->get();

        return Inertia::render('Dashboard', [
            'tables' => $tables,
            'packages' => $packages,
            'fnbItems' => $fnbItems,
        ]);
    }

    // Master Data Methods
    public function masterIndex()
    {
        return Inertia::render('Master/Tables', [
            'tables' => Table::all()->sortBy('name', SORT_NATURAL)->values()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relay_channel' => 'required|integer|min:1|max:16'
        ]);

        Table::create($validated);

        return back()->with('success', 'Meja berhasil ditambahkan.');
    }

    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relay_channel' => 'required|integer|min:1|max:16'
        ]);

        $table->update($validated);

        return back()->with('success', 'Meja berhasil diupdate.');
    }

    public function destroy(Table $table)
    {
        $table->delete();
        return back()->with('success', 'Meja berhasil dihapus.');
    }

    public function start(Request $request, $id)
    {
        $request->validate([
            'customer_name' => 'required',
            'duration_hours' => 'required|numeric|min:0.5',
            'package_id' => 'required',
            'items' => 'nullable|array',
            'items.*.fnb_item_id' => 'required|exists:fnb_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $table = Table::find($id);
        $package = \App\Models\Package::find($request->package_id);

        if ($table && $package) {
            $table->status = 'active';
            $table->save();
            
            // Create Transaction
            $startTime = Carbon::now();
            $expectedEndTime = Carbon::now()->addMinutes($request->duration_hours * 60);

            $transaction = Transaction::create([
                'type' => 'billiard',
                'table_id' => $table->id,
                'package_id' => $package->id,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'customer_name' => $request->customer_name,
                'start_time' => $startTime,
                'expected_end_time' => $expectedEndTime,
                'billiard_cost' => $package->price * $request->duration_hours,
                'fnb_cost' => 0,
                'total_cost' => $package->price * $request->duration_hours,
                'status' => 'active'
            ]);

            // Add F&B Items if provided
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

            // Execute python script to turn ON relay
            $this->controlRelay($table->relay_channel, 'on');
        }
        
        return back();
    }

    public function updateSession(Request $request, $id, $transaction_id)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'duration_hours' => 'required|numeric|min:0.5',
            'items' => 'nullable|array',
            'items.*.fnb_item_id' => 'required|exists:fnb_items,id',
            'items.*.quantity' => 'required|integer|min:0',
        ]);

        $transaction = Transaction::where('id', $transaction_id)
            ->where('table_id', $id)
            ->where('status', 'active')
            ->firstOrFail();

        // VALIDATION: Ensure new duration is not less than the elapsed time
        $elapsedMinutes = Carbon::now()->diffInMinutes(Carbon::parse($transaction->start_time));
        $elapsedHours = $elapsedMinutes / 60;
        
        if ($request->duration_hours < $elapsedHours) {
            return back()->withErrors([
                'duration_hours' => 'Durasi pesanan tidak boleh kurang dari waktu yang sudah berjalan (' . number_format($elapsedHours, 1) . ' jam).'
            ]);
        }

        $package = \App\Models\Package::find($request->package_id);

        if ($transaction && $package) {
            // Update package
            $transaction->package_id = $package->id;

            // Recalculate end time based on original start time
            $originalStartTime = Carbon::parse($transaction->start_time);
            $newExpectedEndTime = $originalStartTime->copy()->addMinutes($request->duration_hours * 60);
            
            $transaction->expected_end_time = $newExpectedEndTime;

            // Handle items update
            if ($request->has('items') && is_array($request->items)) {
                // Delete items that have quantity 0 or were removed
                $receivedItemIds = collect($request->items)->pluck('fnb_item_id')->toArray();
                $transaction->items()->whereNotIn('fnb_item_id', $receivedItemIds)->delete();
                $transaction->items()->where('quantity', '<=', 0)->delete();

                foreach ($request->items as $item) {
                    if ($item['quantity'] <= 0) continue;
                    
                    $fnbItem = \App\Models\FnbItem::find($item['fnb_item_id']);
                    if (!$fnbItem) continue;

                    $transaction->items()->updateOrCreate(
                        ['fnb_item_id' => $fnbItem->id],
                        [
                            'price' => $fnbItem->price,
                            'quantity' => $item['quantity'],
                            'subtotal' => $fnbItem->price * $item['quantity'],
                        ]
                    );
                }
            } else {
                $transaction->items()->delete();
            }

            $transaction->billiard_cost = $package->price * $request->duration_hours;
            $transaction->save();
            \App\Services\TransactionService::recalculate($transaction);
        }

        return back()->with('success', 'Sesi berhasil diperbarui.');
    }

    public function stop(Request $request, $id)
    {
        $table = Table::find($id);
        if ($table) {
            $table->status = 'inactive';
            $table->save();

            // Complete Transaction
            $transaction = Transaction::where('table_id', $table->id)
                ->where('status', 'active')
                ->with('items.fnbItem')
                ->first();
                
            if ($transaction) {
                $transaction->end_time = Carbon::now();
                $transaction->status = 'completed';
                $transaction->save();
                \App\Services\TransactionService::recalculate($transaction);
            }
            
            // Execute python script to turn OFF relay
            $this->controlRelay($table->relay_channel, 'off');
        }
        
        return back();
    }
    
    private function controlRelay($channel, $state)
    {
        $scriptPath = base_path('app/Services/relay_controller.py');
        // Use 'py' instead of 'python' on Windows environments if python command is not recognized, or check if py exists
        $pyCmd = 'py';
        $channelArg = escapeshellarg($channel);
        $stateArg = escapeshellarg($state);
        $scriptPathArg = escapeshellarg($scriptPath);
        
        $command = escapeshellcmd("$pyCmd $scriptPathArg $channelArg $stateArg");
        shell_exec($command);
    }
}
