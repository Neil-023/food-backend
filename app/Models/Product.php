<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'product_tbl';
    protected $primaryKey = 'product_id';
    protected $fillable = [
        'product_id',
        'category_id',
        'product_name',
        'product_desc',
        'product_img',
        'price',
        'avail_stocks',
        'seller_id',
    ];

    protected $casts = [
        'product_img' => 'string',
    ];

    public function getProductImgAttribute($value)
    {
        if (!$value) return null;
        return url('storage/' . $value); // Ensure full URL is returned
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }
}
