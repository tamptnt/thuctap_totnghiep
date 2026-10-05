<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'products_id',
        'image'
    ];
    public function products()
    {
        return $this->belongsTo(Products::class,'products_id','id');
    }
}
