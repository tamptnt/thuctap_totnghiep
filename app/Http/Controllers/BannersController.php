<?php

namespace App\Http\Controllers;

use App\Models\Banners;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class BannersController extends Controller
{
    public function index(){
        $banners = Banners::all();
        return view('admin.pages.banners.index',['banners' => $banners]);
    }
    public function create(Request $request){
        if ($request->hasFile('Image')) {

            foreach($request->file('Image') as $file){
                $img = $file;
                $cloud = Cloudinary::upload($img->getRealPath(), [
                    'folder' => 'banners',
                    'format' => 'jpg',
                ])->getPublicId();

                $banners = new Banners(
                    [
                        'image' => $cloud
                    ]
                );
                $banners->save();
            }
           
        }
       
        return redirect()->back()->with('toast_success',__("Create successfully"));
    }
    public function edit(Request $request,$id){
        $banners = Banners::find($id);
        if ($request->hasFile('Image')) {
            $img = $request->file('Image');
            if($banners->image !=''){
                Cloudinary::destroy($banners->image);
            }
            $cloud = Cloudinary::upload($img->getRealPath(), [
                'folder' => 'banners',
                'format' => 'jpg',

            ])->getPublicId();
            $banners->image= $cloud;
            $banners->save();
        }
        return redirect()->back()->with('toast_success',__("Update successfully"));
    }
    public function destroy($id){
        $banners = Banners::find($id);
        Cloudinary::destroy($banners->image);
        $banners->delete();
        return redirect()->back()->with('toast_success',__("Delete Successfully"));
    }
    public function status(Request $request){
        $banners = Banners::find($request->status_id);
        $banners->status = $request->active;
        $banners->save();
        return response('success',200);
    }
}
