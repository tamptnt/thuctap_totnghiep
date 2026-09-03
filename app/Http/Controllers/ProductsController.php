<?php

namespace App\Http\Controllers;

use App\Models\Brands;
use App\Models\Categories;
use App\Models\Products;
use App\Models\ProductsImage;
use App\Models\Subcategories;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    public function index()
    {
        $products = Products::orderBy('id', 'DESC')->get();
        $categories = Categories::all();
        $subcategories = Subcategories::all();
        $brands = Brands::all();
        return view('admin.pages.products.index',
        [
            'products' => $products,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands
        ]);
    }
    public function edit_pages($id){
        $products = Products::find($id);
        $categories = Categories::all();
        $subcategories = Subcategories::all();
        $brands = Brands::all();
        return view('admin.pages.products.edit',[
            'products' => $products,
            'categories'=> $categories,
            'subcategories' => $subcategories,
            'brands' => $brands
        ]);
    }
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(),[
            'name' =>'required'
        ],[
            'name.required'=>__("Name field is required")
        ]);

        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }
            $request['users_id'] = Auth::user()->id;
            if($request->hasFile('ProductsImage'))
            {
                $products = new Products(
                    [
                        'cat_id' => $request->cat_id,
                        'users_id'=>$request->users_id,
                        'brands_id'=>$request->brands_id,
                        'sub_id'=>$request->sub_id,
                        'name'=>$request->name,
                        'youtube_path'=>$request->youtube_path,
                        'price'=>(int)preg_replace("/[,]+/", "", $request->price),
                        'price_new'=>(int)preg_replace("/[,]+/", "", $request->price_new),
                        'quantity'=>$request->quantity,
                        'content'=>$request->content,
                    ]
                );
                $products->save();

                foreach($request->file('ProductsImage') as $file){
                    $cloud = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'products',
                        'format' => 'jpg',
                    ])->getPublicId();
                    ProductsImage::create([
                        'products_id'=>$products->id,
                        'image'=> $cloud
                    ]);
                }
            }else{
                return redirect()->back()->with('toast_error',__("Please choose image"));
            }
        return redirect()->back()->with('toast_success',__("Create successfully"));
    }

    public function edit(Request $request,$id)
    {
        $products = Products::find($id);
        $validate = Validator::make($request->all(),[
            'name' =>'required'
        ],[
            'name.required'=>__("Name field is required")
        ]);

        if($validate->fails()){
            return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }

        $request['users_id'] = Auth::user()->id;

        if($request->hasFile('ProductsImage'))
        {
            foreach($request->file('ProductsImage') as $file){
                $cloud = Cloudinary::upload($file->getRealPath(), [
                    'folder' => 'products',
                    'format' => 'jpg',
                ])->getPublicId();
                ProductsImage::create([
                    'products_id'=>$products->id,
                    'image'=> $cloud
                ]);
            }
        }
        $products->cat_id = $request->cat_id;
        $products->users_id = $request->users_id;
        $products->brands_id = $request->brands_id;
        $products->sub_id = $request->sub_id;
        $products->name = $request->name;
        $products->youtube_path = $request->youtube_path;
        $products->price = (int)preg_replace("/[,]+/", "", $request->price);
        if($request->changeprice == "on"){
            $products->price_new = (int)preg_replace("/[,]+/", "", $request->price_new); 
        }
        $products->quantity = $request->quantity;
        $products->content = $request->content;
        $products->update();
        return redirect('/admin/products')->with('toast_success',__("Update successfully"));
    }

    public function destroy($id)
    {
        $products = Products::find($id);
        $productsImage= ProductsImage::where('products_id',$products->id)->get();
        if($products->status == 0){
            foreach($productsImage as $pro){
                Cloudinary::destroy($pro->image);
            }
            $products->delete();
            return redirect()->back()->with('toast_success',__("Delete Successfully"));
        }else{
            return redirect()->back()->with('toast_warning',__("Please change status to Inactive to delete"));
        }
    }
    public function status(Request $request){
        $products = Products::find($request->status_id);
        $products->status = $request->active;
        $products->save();
        return response('success',200);
    }
    public function featured(Request $request){
        $products = Products::find($request->featured_id);
        $products->featured_product = $request->active;
        $products->save();
        return response('success',200);
    }
    public function getSubCategories($cat_id){
        $subcategories = Subcategories::where('cat_id', $cat_id)->get();
        foreach ($subcategories as $sub) {
            echo '<option value="' . $sub->id . '">' . $sub->name . '</option>';
        }
    }
    public function getSubCategories_edit($cat_id){
        $subcategories = Subcategories::where('cat_id', $cat_id)->get();
        foreach ($subcategories as $sub) {
            echo '<option value="' . $sub->id . '">' . $sub->name . '</option>';
        }
    }
    public function deleteImages($id){
        $productsImage = ProductsImage::find($id);
        Cloudinary::destroy($productsImage->image);
        $productsImage->delete();
        return response()->json(['success'=>"Delete successfully"]);
    }
}
