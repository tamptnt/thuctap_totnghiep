<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function index(){
        $orders = Orders::orderBy('id', 'DESC')->get();
        return view('admin.pages.orders.index',[
            'orders' => $orders
        ]);
    }
    
    public function orders(){

    }
    public function status(Request $request){
        $orders = Orders::find($request->status_id);
        $orders->status = $request->active;
        $orders->save();
        return response('success',200);
    }
}
