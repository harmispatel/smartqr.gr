<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorySorting extends Model
{
    use HasFactory;

    protected $table = 'category_sortings';
    // protected $fillable = [
    //     'category_id',
    //     'item_id',
    //     'order_key',
    //     'shop_id',
    // ];

    protected $guarded=[];

    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
