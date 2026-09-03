<?php

namespace App\Http\Controllers;

use App\Models\BannersFeatured;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Validator;

class BannersFeaturedController extends Controller
{
    public function index(){
        $bannersfeatured = BannersFeatured::all();
        return view('admin.pages.bannersfeatured.index',['bannersfeatured' => $bannersfeatured]);
    }
    public function create(Request $request){
        $validate = Validator::make($request->all(),[
            'name' =>'required',
            'link'=>'required'
        ],[
            'name.required'=>__("Name field is required"),
            'link.required'=>__("Link field is required")
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }

        if ($request->hasFile('Image')) {
            $img = $request->file('Image');
            $cloud = Cloudinary::upload($img->getRealPath(), [
                'folder' => 'bannersfeatured',
                'format' => 'jpg',

            ])->getPublicId();
            $bannersfeatured = new BannersFeatured(
                [
                    'name'=>$request->name,
                    'link'=>$request->link,
                    'image' => $cloud
                ]
            );
            $bannersfeatured->save();   
            return redirect()->back()->with('toast_success',__("Create successfully"));
        }else{
            return redirect()->back()->with('toast_error',__("Please choose image"));
        }
    }
    public function edit(Request $request,$id){
        $bannersfeatured = BannersFeatured::find($id);
        $validate = Validator::make($request->all(),[
            'name' =>'required',
            'link'=>'required'
        ],[
            'name.required'=>__("Name field is required"),
            'link.required'=>__("Link field is required")
        ]);

        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }

        if ($request->hasFile('Image')) {
            $img = $request->file('Image');
            if($bannersfeatured->image !=''){
                Cloudinary::destroy($bannersfeatured->image);
            }
            $cloud = Cloudinary::upload($img->getRealPath(), [
                'folder' => 'bannersfeatured',
                'format' => 'jpg',

            ])->getPublicId();
       
            $bannersfeatured->image= $cloud;
           
        }

        $bannersfeatured->name = $request->name;
        $bannersfeatured->link = $request->link;
        $bannersfeatured->save();
        return redirect()->back()->with('toast_success',__("Update successfully"));
    }
    public function destroy($id){
        $bannersfeatured = BannersFeatured::find($id);
        Cloudinary::destroy($bannersfeatured->image);
        $bannersfeatured->delete();
        return redirect()->back()->with('toast_success',__("Delete Successfully"));
    }
    public function status(Request $request){
        $bannersfeatured = BannersFeatured::find($request->status_id);
        $bannersfeatured->status = $request->active;
        $bannersfeatured->save();
        return response('success',200);
    }
}
