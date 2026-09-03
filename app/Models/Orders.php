<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;
    protected $fillable = [
        'users_id',
        'lastname',
        'firstname',
        'email',
        'phone',
        'address',
        'district',
        'city',
        'content',
        'tax',
        'subtotal',
        'total',
        'discount',
        'lastname_sender',
        'firstname_sender',
        'phone_sender',
        'status'
    ];
    public function user(){
        return $this->belongsTo(User::class,'users_id','id');
    }
    public function products(){
        return $this->belongsToMany(Products::class,'orders__details','orders_id','products_id')->withPivot('quantity');
    }
}
