<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    use HasFactory;
    protected $fillable=[
        'products_id',
        'users_id',
        'rate',
        'content'
    ];
    public function users()
    {
        return $this->belongsTo(User::class,'users_id','id');
    }
    public function products()
    {
        return $this->belongsTo(Products::class,'products_id');
    }
}
