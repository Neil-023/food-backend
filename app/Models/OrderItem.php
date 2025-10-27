<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $table      = 'order_items_tbl';
    protected $primaryKey = 'order_items_id';

    // you have no created_at / updated_at on this table:
    public    $timestamps = false;

    protected $fillable   = [
        'order_id',
        'product_id',
        'quantity',
        'price_each',
    ];

    /** the order this item belongs to */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    /** the product that was ordered */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
