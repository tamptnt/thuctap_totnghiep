<?php

namespace App\Http\Controllers;

use App\Models\Discounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountsController extends Controller
{

    public function index(){
        $discounts = Discounts::orderBy('id', 'DESC')->Paginate(15);
        return view('admin.pages.discounts.index',[
            'discounts' => $discounts
        ]);
    }

    public function create(Request $request){
        $validate = Validator::make($request->all(),[
            'name'=>'required',
            'code' => 'required|unique:discounts',
            'percent'=>'numeric|min:0|max:100',
            'quantity'=>'required'
        ], [
            'name.required'=>__("Name field is required"),
            'code.required' => __("Code field is required"),
            'code.unique' =>__("Code is already exists"),
            'percent.max'=>__("Maximum discount code is 100"),
            'percent.min'=>__("Minimum discount code is 0"),
            'quantity.required'=>__("Quantity field is required")
        ]);
        $discounts = new Discounts([
            'name'=>$request->name,
            'code'=>$request->code,
            'percent'=>$request->percent,
            'quantity'=>$request->quantity
        ]);

        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        $discounts->save();
        return redirect()->back()->with('toast_success', 'Added Successfully!');
    }

    public function edit(Request $request,$id){
        $discounts = Discounts::find($id);
        $validate = Validator::make($request->all(),[
            'percent'=>'numeric|min:0|max:100',
            'quantity'=>'required'
        ], [
            'percent.max'=>__("Maximum discount code is 100"),
            'percent.min'=>__("Minimum discount code is 0"),
            'quantity.required'=>__("Quantity field is required")
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }

        $discounts->name = $request->name;
        $discounts->code = $request->code;
        $discounts->percent = $request->percent;
        $discounts->quantity = $request->quantity;
        $discounts->update();

        return redirect()->back()->with('toast_success',__("Update successfully"));
    }

    public function destroy($id)
    {
        $discounts = Discounts::find($id);
        $discounts::destroy($id);
        return redirect()->back()->with('toast_success',__("Delete Successfully"));
    }
    public function status(Request $request)
    {
        $discounts = Discounts::find($request->status_id);
        $discounts->status = $request->active;
        $discounts->save();
        return response('success',200);
    }
}
