<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::with(['transactions' => function($q) {
            $q->where('status', 'active');
        }])->get()->sortBy('name', SORT_NATURAL)->values();
        $packages = \App\Models\Package::all();

        return Inertia::render('Dashboard', [
            'tables' => $tables,
            'packages' => $packages
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
            'customer_name' => 'required|string|max:255',
            'duration_hours' => 'required|numeric|min:0.5',
            'package_id' => 'required'
        ]);

        $table = Table::find($id);
        $package = \App\Models\Package::find($request->package_id);

        if ($table && $package) {
            $table->status = 'active';
            $table->save();
            
            // Create Transaction
            $startTime = Carbon::now();
            $expectedEndTime = Carbon::now()->addMinutes($request->duration_hours * 60);

            \App\Models\Transaction::create([
                'table_id' => $table->id,
                'package_id' => $package->id,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'customer_name' => $request->customer_name,
                'start_time' => $startTime,
                'expected_end_time' => $expectedEndTime,
                'billiard_cost' => $package->price * $request->duration_hours,
                'status' => 'active'
            ]);

            // Execute python script to turn ON relay
            $this->controlRelay($table->relay_channel, 'on');
        }
        
        return back();
    }

    public function stop(Request $request, $id)
    {
        $table = Table::find($id);
        if ($table) {
            $table->status = 'inactive';
            $table->save();

            // Complete Transaction
            $transaction = \App\Models\Transaction::where('table_id', $table->id)
                ->where('status', 'active')
                ->first();
                
            if ($transaction) {
                $transaction->end_time = Carbon::now();
                $transaction->total_cost = $transaction->billiard_cost + $transaction->fnb_cost;
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
