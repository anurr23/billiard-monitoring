<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'expected_end_time' => 'datetime',
        'billiard_cost' => 'decimal:2',
        'fnb_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function scopeBilliard($query)
    {
        return $query->where('type', 'billiard');
    }

    public function scopeFnbOnly($query)
    {
        return $query->where('type', 'fnb_only');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
