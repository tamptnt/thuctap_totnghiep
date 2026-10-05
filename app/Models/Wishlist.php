<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Wishlist extends Model
{
    use HasFactory;
    protected $fillable = [
        'products_id',
        'users_id'
    ];
    public function products()
    {
        return $this->belongsTo(Products::class,'products_id','id');
    }
    public function users()
    {
        return $this->belongsTo(User::class,'users_id','id');
    }
    public function countWishlist($products_id)
    {
        if(Auth::check())
        {
            $countWishlist = Wishlist::where(['users_id' => Auth::user()->id, 'products_id' => $products_id])->count();
            return $countWishlist;
        }
    } 
}
