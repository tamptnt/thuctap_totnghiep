<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index(){
    $products = Wishlist::all();
       return view('web.pages.wishlist.index',[
        'products' => $products
       ]);
    }
    public function wishlist(Request $request){
        $product_id = $request->product_id;
        $user_id = $request->user_id;
        $wishlist = new Wishlist;
        $count = $wishlist->countWishlist($product_id);
        if($count == 0){//count to see if the product already exists in wishlist, if it dosen't exist then == 0 and add it to wishlist
            $wishlist['products_id'] = $product_id;
            $wishlist['users_id'] = $user_id;
            $wishlist->save();
            return response()->json([
                'action' => 'add',
                'success'=>__("Added successfully to wishlist")
            ]);
        }else{//if it exist then == 1 and remove it from wishlist
            Wishlist::where(['users_id'=> Auth::user()->id,'products_id'=>$request->product_id])->delete();
            return response()->json([
                'action' => 'delete',
                'success'=>__("Deleted successfully from wishlist")
            ]);
        }
    }
    public function count_wishlist(){
        $count = Wishlist::where(['users_id' => Auth::user()->id])->count();
        return response()->json(['count' => $count]);
    }
}
