<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Products extends Model
{
    use HasFactory;
    protected $fillable = [
        'cat_id',
        'users_id',
        'brands_id',
        'sub_id',
        'name',
        'youtube_path',
        'price',
        'price_new',
        'quantity',
        'content',
        'featured_product',
        'status'
    ];
    public function categories()
    {
        return $this->belongsTo(Categories::class,'cat_id','id');
    }
    public function users()
    {
        return $this->belongsTo(User::class,'users_id','id');
    }
    public function brands()
    {
        return $this->belongsTo(Brands::class,'brands_id','id');
    }
    public function subcategories() {
        return $this->belongsTo(Subcategories::class, 'sub_id', 'id');
    }
    public function ProductsImage()
    {
        return $this->hasMany(ProductsImage::class,'products_id','id');
    }
    public function reviews()
    {
        return $this->hasMany(Reviews::class,'products_id','id');
    }

}
