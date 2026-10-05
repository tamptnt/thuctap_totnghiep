<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Orders;
use Illuminate\Support\Facades\View;
use App\Models\Brands;
use App\Models\Categories;
use App\Models\Info;
use App\Models\Subcategories;
use App\Models\Wishlist;
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public function __construct(){
        $new_orders = Orders::orderBy('id', 'DESC')->take(3)->get();
        $categories = Categories::all()->where('status',1);
        $subcategories = Subcategories::all()->where('status',1);
        $brands = Brands::all()->where('status',1);
        $info = Info::find(1);
        $wishlist = new Wishlist;
        view()->share('categories',$categories);
        view()->share('subcategories',$subcategories);
        view()->share('brands',$brands);
        view()->share('info',$info);
        view()->share('wishlist',$wishlist);
        View()->share('new_orders',$new_orders);
    }
}
