<?php

namespace App\Http\Controllers;

use App\Models\Brands;
use App\Models\Products;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Validator;

class BrandsController extends Controller
{
    public function index(){
        $brands = Brands::all();
        return view('admin.pages.brands.index',['brands' => $brands]);
    }
    public function create(Request $request){
        $validate = Validator::make($request->all(),[
            'name' =>'required'
        ],[
            'name.required'=>__("Name field is required")
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        if ($request->hasFile('Image')) {
            $img = $request->file('Image');
            $cloud = Cloudinary::upload($img->getRealPath(), [
                'folder' => 'brands',
                'format' => 'jpg',

            ])->getPublicId();
            $brands = new Brands(
                [
                    'name'=>$request->name,
                    'image' => $cloud
                ]
            );
            $brands->save();
        }
        else
        {
            $brands = new Brands(
                [
                    'name'=>$request->name
                ]
            );
            $brands->save();
            return redirect()->back()->with('toast_success',__("Create successfully"));
        }
       
        return redirect()->back()->with('toast_success',__("Create successfully"));
    }
    public function edit(Request $request,$id){
        $brands = Brands::find($id);
        $validate = Validator::make($request->all(),[
            'name' =>'required'
        ],[
            'name.required'=>__("Name field is required")
        ]);

        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }

        if ($request->hasFile('Image')) {
            $img = $request->file('Image');
            if($brands->image !=''){
                Cloudinary::destroy($brands->image);
            }
            $cloud = Cloudinary::upload($img->getRealPath(), [
                'folder' => 'brands',
                'format' => 'jpg',

            ])->getPublicId();
            $brands->image= $cloud;
        }
        
        $brands->name = $request->name;
        $brands->save();
        return redirect()->back()->with('toast_success',__("Update successfully"));
    }
    public function destroy($id){
        $brands = Brands::find($id);
        $check = count(Products::where('brands_id',$id)->get());
        if($check == 0 )
        {
            if($brands->image != null){
                Cloudinary::destroy($brands->image);
            }
            $brands->delete();
            return redirect()->back()->with('toast_success',__("Delete Successfully"));
        }else{
            return redirect()->back()->with('toast_error',__("Can't delete because there are products in brand"));
        }
       
    }
    public function status(Request $request){
        $brands = Brands::find($request->status_id);
        $brands->status = $request->active;
        $brands->save();
        return response('success',200);
    }
}
