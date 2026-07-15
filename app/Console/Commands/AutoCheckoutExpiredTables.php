<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Carbon\Carbon;

class AutoCheckoutExpiredTables extends Command
{
    protected $signature = 'tables:auto-checkout';
    protected $description = 'Auto-checkout tables whose session has expired';

    public function handle(): int
    {
        $expired = Transaction::with('table')
            ->where('status', 'active')
            ->whereNotNull('expected_end_time')
            ->where('expected_end_time', '<=', Carbon::now())
            ->get();

        if ($expired->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($expired as $transaction) {
            $table = $transaction->table;

            if (!$table) {
                continue;
            }

            // Complete the transaction
            $transaction->end_time = $transaction->expected_end_time;
            $transaction->total_cost = $transaction->billiard_cost + $transaction->fnb_cost;
            $transaction->status = 'completed';
            $transaction->save();

            // Deactivate the table
            $table->status = 'inactive';
            $table->save();

            // Turn off the relay
            $this->controlRelay($table->relay_channel, 'off');

            $this->info("Auto-checkout: {$table->name} ({$transaction->customer_name})");
        }

        return self::SUCCESS;
    }

    private function controlRelay($channel, $state): void
    {
        $scriptPath = base_path('app/Services/relay_controller.py');
        $command = escapeshellcmd("python \"$scriptPath\" $channel $state");
        shell_exec($command);
    }
}
