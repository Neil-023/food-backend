<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    // your table is non‑standard named:
    protected $table      = 'orders_tbl';

    // your PK is order_id, not id:
    protected $primaryKey = 'order_id';

    // you have "ordered_at" & "updated_at" instead of Laravel's created_at/updated_at:
    public    $timestamps = true;
    const     CREATED_AT  = 'ordered_at';
    const     UPDATED_AT  = 'updated_at';

    // which columns may be mass‑assigned via ::create([...])
    protected $fillable   = [
        'buyer_id',
        'status',
        'total_price',
        'ordered_at',
        'updated_at',
    ];

    // relationships

    /** the buyer (user) who placed this order */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    /** the items in this order */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function sellerStatuses()
    {
        return $this->hasMany(OrderSeller::class, 'order_id', 'order_id');
    }
}
