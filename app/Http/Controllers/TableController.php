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
        $fnbItems = FnbItem::orderBy('category')->orderBy('name')->get();
        $fnbOrders = Transaction::fnbOnly()
            ->where('status', 'active')
            ->with('items.fnbItem')
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'tables' => $tables,
            'packages' => $packages,
            'fnbItems' => $fnbItems,
            'fnbOrders' => $fnbOrders,
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
                $fnbCost = 0;
                foreach ($request->items as $item) {
                    $fnbItem = \App\Models\FnbItem::find($item['fnb_item_id']);
                    if (!$fnbItem) continue;

                    $subtotal = $fnbItem->price * $item['quantity'];
                    $transaction->items()->create([
                        'fnb_item_id' => $fnbItem->id,
                        'price' => $fnbItem->price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal,
                    ]);
                    $fnbCost += $subtotal;
                }
                $transaction->fnb_cost = $fnbCost;
                $transaction->total_cost = $transaction->billiard_cost + $fnbCost;
                $transaction->save();
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
        ]);

        $transaction = Transaction::where('id', $transaction_id)
            ->where('table_id', $id)
            ->where('status', 'active')
            ->firstOrFail();

        $package = \App\Models\Package::find($request->package_id);

        if ($transaction && $package) {
            // Update package
            $transaction->package_id = $package->id;

            // Recalculate end time based on original start time
            $originalStartTime = Carbon::parse($transaction->start_time);
            $newExpectedEndTime = $originalStartTime->copy()->addMinutes($request->duration_hours * 60);
            
            $transaction->expected_end_time = $newExpectedEndTime;

            // Recalculate billiard cost
            $transaction->billiard_cost = $package->price * $request->duration_hours;
            $transaction->total_cost = $transaction->billiard_cost + $transaction->fnb_cost;
            
            $transaction->save();
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
                // Recalculate fnb_cost from actual items
                $fnbCost = $transaction->items()->sum('subtotal');
                $transaction->fnb_cost = $fnbCost;
                $transaction->end_time = Carbon::now();
                $transaction->total_cost = $transaction->billiard_cost + $fnbCost;
                $transaction->status = 'completed';
                $transaction->save();
            }
            
            // Execute python script to turn OFF relay
            $this->controlRelay($table->relay_channel, 'off');
        }
        
        return back();
    }
    
    private function controlRelay($channel, $state)
    {
        $scriptPath = base_path('app/Services/relay_controller.py');
        // Warning: shell_exec can be dangerous if inputs are not sanitized, but here they are hardcoded integers/strings
        $command = escapeshellcmd("python \"$scriptPath\" $channel $state");
        shell_exec($command);
    }
}
