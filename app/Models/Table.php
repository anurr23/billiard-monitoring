<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Table extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function recentTransactions()
    {
        return $this->hasMany(Transaction::class)
            ->where(function($q) {
                $q->where('status', 'active')
                  ->orWhere('created_at', '>=', \Carbon\Carbon::now()->subHours(48))
                  ->orWhere('end_time', '>=', \Carbon\Carbon::now()->subHours(48));
            })
            ->with(['package', 'items.fnbItem'])
            ->latest();
    }
}
