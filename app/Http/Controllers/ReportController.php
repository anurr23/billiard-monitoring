<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function fnbSales(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

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
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

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
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

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
}
