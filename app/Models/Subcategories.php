<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategories extends Model
{
    use HasFactory;
    protected $fillable =[
        'name',
        'cat_id',
        'status'
    ];
    public function categories()
    {
        return $this->belongsTo(Categories::class,'cat_id','id');
    }
    public function products()
    {
        return $this->hasMany(Products::class,'sub_id','id');
    }
}
