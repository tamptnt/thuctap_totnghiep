<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'status'
    ];
    public function Subcategories()
    {
        return $this->hasMany(Subcategories::class,'cat_id','id');
    }
    public function Products()
    {
        return $this->hasMany(Products::class,'cat_id','id');
    }
}
