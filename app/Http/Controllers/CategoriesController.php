<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Products;
use App\Models\Subcategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Categories::all();
        return view('admin.pages.categories.index',['categories' => $categories]);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(),[
            'name' =>'required|unique:categories'
        ],[
            'name.required'=>__("Category field is required"),
            'name.unique' =>__("Category already exists")
        ]);
        $categories = new Categories([
            'name'=> $request->name,
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        $categories->save();
        return redirect()->back()->with('toast_success',__("Create successfully"));
    }

    public function edit(Request $request, $id) 
    {
        $categories = Categories::find($id);
        $validate = Validator::make($request->all(),[
            'name' =>'required|unique:categories'
        ],[
            'name.required'=>__("Category field is required"),
            'name.unique' =>__("Category already exists")
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        $categories->name = $request->name;
        $categories->update();
        
        return redirect()->back()->with('toast_success',__("Update successfully"));
    }
    public function destroy($id)
    {
       
        $categories = Categories::find($id);
        $subcategories = count(Subcategories::where('cat_id',$id)->get());
        if($subcategories ==0){
            $categories::destroy($id);
            return redirect()->back()->with('toast_success',__("Delete Successfully"));
        }else{
            return redirect()->back()->with('toast_error',__("Can't delete because there are subcategories in category"));
        } 
    }
    public function status(Request $request)
    {
        $categories = Categories::find($request->status_id);
        $categories->status = $request->active;
        $categories->save();
        return response('success',200);
    }
}
