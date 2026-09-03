<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    public function index(){
        $news = News::orderBy('id', 'DESC')->get();
        return view('admin.pages.news.index',['news' => $news]);
    }
    public function create(Request $request){
        $validate = Validator::make($request->all(),[
            'title' =>'required'
        ],[
            'title.required'=>__("Title field is required")
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        if ($request->hasFile('Image')) {
                $img = $request->file('Image');
                $cloud = Cloudinary::upload($img->getRealPath(), [
                    'folder' => 'news',
                    'format' => 'jpg',
                ])->getPublicId();
                
                $news = new News(
                    [
                        'title'=>$request->title,
                        'image' => $cloud,
                        'content'=>$request->content,
                        'users_id'=>Auth::user()->id
                    ]
                );
                $news->save();
        }else{
            return redirect()->back()->with('toast_warning',__("Please choose image"));
        }
       
        return redirect()->back()->with('toast_success',__("Create successfully"));
    }
    public function edit_pages($id){
        $news = News::find($id);
        return view('admin.pages.news.edit',[
            'news'=>$news
        ]);
    }
    public function edit(Request $request,$id){
        $validate = Validator::make($request->all(),[
            'title' =>'required'
        ],[
            'title.required'=>__("Title field is required")
        ]);
        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
        $news = News::find($id);
        if ($request->hasFile('Image')) {
            $img = $request->file('Image');
            if($news->image !=''){
                Cloudinary::destroy($news->image);
            }
            $cloud = Cloudinary::upload($img->getRealPath(), [
                'folder' => 'news',
                'format' => 'jpg',

            ])->getPublicId();
            $news->image= $cloud;
        }
        $news->title = $request->title;
        $news->content = $request->content;
        $news->users_id = Auth::user()->id;
        $news->save();
        return redirect('/admin/news')->with('toast_success',__("Update successfully"));
    }
    public function destroy($id){
        $news = News::find($id);
        Cloudinary::destroy($news->image);
        $news->delete();
        return redirect()->back()->with('toast_success',__("Delete Successfully"));
    }
    public function status(Request $request){
        $news = News::find($request->status_id);
        $news->status = $request->active;
        $news->save();
        return response('success',200);
    }
}
