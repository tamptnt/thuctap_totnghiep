<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Info extends Model
{
    use HasFactory;
    protected $fillable = [
        'logo',
        'name',
        'phone',
        'email',
        'facebook',
        'twitter',
        'youtube',
        'address',
        'worktime',
        'copyright',
        'tax'
    ];
}
