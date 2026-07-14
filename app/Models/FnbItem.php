<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnbItem extends Model
{
    protected $guarded = [];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class, 'fnb_item_id');
    }
}
