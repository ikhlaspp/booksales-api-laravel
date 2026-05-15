<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'order_number',
        'customer_id',
        'book_id',
        'total_amount',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'status',
        'snap_token',
        'payment_type'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
