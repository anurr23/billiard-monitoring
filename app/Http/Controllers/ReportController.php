<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Table;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function fnbSales(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $sales = TransactionItem::select(
                'fnb_item_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('fnb_items', 'transaction_items.fnb_item_id', '=', 'fnb_items.id')
            ->where('transactions.status', 'completed')
            ->whereBetween(DB::raw("date(transactions.created_at)"), [$startDate, $endDate])
            ->groupBy('fnb_item_id')
            ->with('fnbItem')
            ->orderByDesc('total_revenue')
            ->get();

        $summary = TransactionItem::select(
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereBetween(DB::raw("date(transactions.created_at)"), [$startDate, $endDate])
            ->first();

        return Inertia::render('Master/Reports/FnbSales', [
            'sales' => $sales,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function tableTransactions(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $transactions = Transaction::billiard()
            ->with(['table', 'package', 'user'])
            ->where('transactions.status', 'completed')
            ->whereBetween(DB::raw("date(transactions.created_at)"), [$startDate, $endDate])
            ->orderByDesc('transactions.created_at')
            ->get();

        $summary = Transaction::billiard()
            ->where('status', 'completed')
            ->whereBetween(DB::raw("date(created_at)"), [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(billiard_cost) as total_billiard'),
                DB::raw('SUM(fnb_cost) as total_fnb'),
                DB::raw('SUM(total_cost) as total_revenue')
            )
            ->first();

        return Inertia::render('Master/Reports/TableTransactions', [
            'transactions' => $transactions,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function revenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $dailyRevenue = Transaction::where('status', 'completed')
            ->whereBetween(DB::raw("date(created_at)"), [$startDate, $endDate])
            ->select(
                DB::raw("date(created_at) as date"),
                DB::raw('SUM(billiard_cost) as billiard_revenue'),
                DB::raw('SUM(fnb_cost) as fnb_revenue'),
                DB::raw('SUM(total_cost) as total_revenue'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw("date(created_at)"))
            ->orderByDesc('date')
            ->get();

        $summary = Transaction::where('status', 'completed')
            ->whereBetween(DB::raw("date(created_at)"), [$startDate, $endDate])
            ->select(
                DB::raw('SUM(billiard_cost) as total_billiard'),
                DB::raw('SUM(fnb_cost) as total_fnb'),
                DB::raw('SUM(total_cost) as total_revenue'),
                DB::raw('COUNT(*) as total_transactions')
            )
            ->first();

        return Inertia::render('Master/Reports/Revenue', [
            'dailyRevenue' => $dailyRevenue,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function analytics(Request $request)
    {
        // For the graph to be meaningful when filtering by 'Hari Ini' (today),
        // we default to fetching the current month's data if no dates are provided.
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $completedTransactions = Transaction::where('status', 'completed')
            ->whereBetween(DB::raw("date(created_at)"), [$startDate, $endDate])
            ->get();

        $hourlyStats = $completedTransactions->groupBy(fn($t) => $t->created_at->format('H'))->map(function ($group) {
            $count = $group->count();
            $revenue = $group->sum('total_cost');
            $avgDuration = $group->avg(fn($t) => $t->ended_at ? $t->created_at->diffInMinutes($t->ended_at) : 0);
            return [
                'hour' => $group->first()->created_at->format('H:00'),
                'transactions' => $count,
                'revenue' => $revenue,
                'avg_duration' => round($avgDuration ?? 0),
            ];
        })->sortKeys()->values();

        $tableUtilization = Table::with(['transactions' => function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
              ->whereBetween(DB::raw("date(created_at)"), [$startDate, $endDate]);
        }])->get()->map(function ($table) {
            $txns = $table->transactions;
            $totalMinutes = $txns->sum(fn($t) => $t->ended_at ? $t->created_at->diffInMinutes($t->ended_at) : 0);
            $totalHours = round($totalMinutes / 60, 1);
            return [
                'table_number' => $table->table_number,
                'table_name' => $table->name,
                'transactions' => $txns->count(),
                'total_hours' => $totalHours,
                'revenue' => $txns->sum('total_cost'),
            ];
        })->sortByDesc('revenue')->values();

        $packageStats = Package::with(['transactions' => function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
              ->whereBetween(DB::raw("date(created_at)"), [$startDate, $endDate]);
        }])->get()->map(function ($pkg) {
            $txns = $pkg->transactions;
            return [
                'name' => $pkg->name,
                'price_per_hour' => $pkg->price_per_hour,
                'transactions' => $txns->count(),
                'revenue' => $txns->sum('billiard_cost'),
                'total_hours' => round($txns->sum(fn($t) => $t->ended_at ? $t->created_at->diffInMinutes($t->ended_at) : 0) / 60, 1),
            ];
        })->sortByDesc('revenue')->values();

        // Group transactions by date
        $dailyTransactions = $completedTransactions->groupBy(fn($t) => $t->created_at->format('Y-m-d'));
        
        // Ensure all dates in range are represented, even if 0
        $dailyStatsList = collect();
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            $group = $dailyTransactions->get($dateStr, collect());
            
            $dailyStatsList->push([
                'date' => $dateStr,
                'transactions' => $group->count(),
                'revenue' => $group->sum('total_cost'),
                'billiard_revenue' => $group->sum('billiard_cost'),
                'fnb_revenue' => $group->sum('fnb_cost'),
                'avg_duration' => round($group->avg(fn($t) => $t->ended_at ? $t->created_at->diffInMinutes($t->ended_at) : 0) ?? 0),
            ]);
            
            $currentDate->addDay();
        }
        $dailyStats = $dailyStatsList->values();

        $summary = [
            'total_transactions' => $completedTransactions->count(),
            'total_revenue' => $completedTransactions->sum('total_cost'),
            'total_billiard' => $completedTransactions->sum('billiard_cost'),
            'total_fnb' => $completedTransactions->sum('fnb_cost'),
            'avg_transaction_value' => $completedTransactions->avg('total_cost') ?? 0,
            'avg_duration' => round($completedTransactions->avg(fn($t) => $t->ended_at ? $t->created_at->diffInMinutes($t->ended_at) : 0) ?? 0),
            'unique_tables_used' => $completedTransactions->pluck('table_id')->filter()->unique()->count(),
        ];

        return Inertia::render('Master/Reports/Analytics', [
            'hourlyStats' => $hourlyStats,
            'tableUtilization' => $tableUtilization,
            'packageStats' => $packageStats,
            'dailyStats' => $dailyStats,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
