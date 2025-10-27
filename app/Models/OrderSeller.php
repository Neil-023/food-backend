<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSeller extends Model
{
    protected $table = 'order_seller_tbl';
    protected $fillable = ['order_id', 'seller_id', 'status'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }
}
