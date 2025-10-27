<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // if your table name doesn’t follow “categories”, point at it explicitly:
    protected $table = 'category_tbl';

    // if your PK is not “id”:
    protected $primaryKey = 'category_id';

    // Eloquent will maintain created_at / updated_at by default
    public $timestamps = true;

    // which columns may be mass-assigned
    protected $fillable = [
      'category_name',
      'icon_name'
    ];

    /**
     * A category has many products.
     */
    public function products()
    {
      // arguments: related model, foreignKey on products, localKey on this table
      return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
