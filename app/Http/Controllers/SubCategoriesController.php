<?php

namespace App\Http\Controllers;

use App\Models\Subcategories;
use App\Models\Categories;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subcategories = Subcategories::all();
        $categories = Categories::all();
        return view('admin.pages.subcategories.index',['subcategories' => $subcategories,'categories'=>$categories]);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(),[
            'name' =>'required|unique:subcategories'
        ],[
            'name.required'=>__("Subcategory field is required"),
            'name.unique' =>__("Subcategory already exists")
        ]);
        $subcategories = new Subcategories([
            'name'=> $request->name,
            'cat_id'=>$request->cat_id
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        $subcategories->save();
        return redirect()->back()->with('toast_success',__("Create successfully"));
    }

    public function edit(Request $request, $id)
    {
        $subcategories = Subcategories::find($id);
        $validate = Validator::make($request->all(),[
            'name' =>'required'
        ],[
            'name.required'=>__("Subcategory field is required")
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        $subcategories->name= $request->name;
        $subcategories->cat_id= $request->cat_id;
        $subcategories->update();
        return redirect()->back()->with('toast_success',__("Update successfully"));
    }

    public function destroy($id)
    {
        $subcategories = SubCategories::find($id);
        $check = count(Products::where('sub_id',$id)->get());
        if($check ==0){
            $subcategories::destroy($id);
            return redirect()->back()->with('toast_success',__("Delete Successfully"));
        }else{
            return redirect()->back()->with('toast_error',__("Can't delete because there are products in subcategory"));
        }
       
    }
    public function status(Request $request)
    {
        $subcategories = SubCategories::find($request->status_id);
        $subcategories->status = $request->active;
        $subcategories->save();
        return response('success',200);
    }
}
