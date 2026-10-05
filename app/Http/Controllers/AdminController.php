<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Orders;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AdminController extends Controller
{

    public function index(){
        $sum = 0;
        $sum_today = 0;
        $orders = Orders::all()->where('status',3);
        $new_orders = Orders::orderBy('id', 'DESC')->take(3)->get();
        $orders_today = Orders::whereDate('created_at', Carbon::today())->get();

        foreach ($orders as $order) {
            $sum += $order->total;
        }

        foreach($orders_today->where('status',3) as $today){
            $sum_today += $today->total;
        }
        return view('admin.pages.home.index',[
            'orders' => $orders,
            'sum' => $sum,
            'orders_today'=>$orders_today,
            'sum_today'=>$sum_today,
            'new_orders'=>$new_orders
        ]);
    }

    public function login(){
        return view('admin.pages.auth.login');
    }

    public function handle_login(Request $request){
        $credentials = Validator::make($request->all(),[
            'username' => 'required',
            'password' => 'required',
        ],
        [
            'username.required'=>__("the username field is required"),
            'password.required'=>__("the passwords field is required")
        ]);
        if($credentials->fails()){
            return back()->with('toast_error', $credentials->messages()->all()[0])->withInput();
        }
        if(Auth::attempt(['username' => $request['username'], 'password' => $request['password']])){
            toast(__("Login Successfully"),'success');
            return redirect('admin');
        }
        return back()->with('toast_error',__("Wrong username or password. Please try again"));
    }

    public function logout(){
        Auth::logout();
        return redirect('admin/login');
    }

    public function clients(){
        $user = User::role('client')->orderBy('id', 'DESC')->get();
        return view('admin.pages.clients.index',[
            'user'=>$user
        ]);
    }
    public function profile(){
        $user = Auth::user();
        if(Auth::check()){
            return view('admin.pages.account.profile',[
                'user'=>$user
            ]);
        }
        abort(404);
    }

    public function editProfile(Request $request){
        $validate = Validator::make($request->all(),[
            'phone' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:12|nullable',
        ],[
            'phone.regex' => __("Phone numbers are from 0 to 9 and do not include characters"),
            'phone.min' => __("Phone number at least 10 digits"),
            'phone.max' => __("Phone number maximum 20 digits"),
        ]);
        $user = User::find(Auth::user()->id);
        $email = User::where('email', "=", $request->email)->first();
        $phone = User::where('phone', "=", $request->phone)->first();

        if ($phone && $user->phone != $phone->phone) {
            if (!isset($request->phone)) {
                return redirect()->back()->with('toast_error',__("the phone field is required"));
            }
            //            dd($user->phone);
            return redirect()->back()->with('toast_warning', __("The phone is already exists"));
        }
        if ($email && $user->email != $email->email) {
            if (!isset($request->email)) {
                return redirect()->back()->with('toast_error',__("the phone field is required"));
            }
            return redirect()->back()->with('toast_warning', __("the email is already exists"));
        }
        if($validate->fails()){
            return redirect()->back()->with('toast_error', $validate->messages()->all()[0])->withInput();
        }

        if($request->changepassword == 'on'){
            $validate = Validator::make($request->all(),[
                'password' => 'required|min:6',
                'repassword'=>'required|same:password',
            ],[
                'password.required'=>__("the passwords field is required"),
                'password.min'=>__("The password must be at least 6 characters"),
                'repassword.required'=>__("the repassword field is required"),
                'repassword.same'=>__("the repassword is incorrect")
            ]);

            if($validate->fails()){
                return back()->with('toast_error', $validate->messages()->all()[0])->withInput();
            }

            if(Hash::check($request->password,$user->password)){
                return redirect()->back()->with('warning',__("The new password matches the old password !"));
            }
            $request->password = Hash::make($request->password);
            $user->password = $request->password;

            if($user->isDirty('password')){
                $user->save();
                Auth::logout();
                return redirect('/admin')->with('success',__("Change password successfully. Please re-login to continue using the website."));
            }
        }

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->district = $request->district;
        $user->city = $request->city;

        $user->save();
        return redirect()->back()->with('toast_success',__("Update successfully"));
    }

    public function imageProfile(Request $request){
        $user = User::find(Auth::user()->id);
        if ($request->hasFile('Image')) {
            $img = $request->file('Image');
            if($user->image !=''){
                Cloudinary::destroy($user->image);
            }
            $cloud = Cloudinary::upload($img->getRealPath(), [
                'folder' => 'user',
                'format' => 'jpg',

            ])->getPublicId();
            $user->image= $cloud;
        }
        $user->save();
        return redirect()->back()->with('toast_success',__("Update successfully"));
    }
    public function status_clients(Request $request){
        $user = User::find($request->status_id);
        $user->status = $request->active;
        $user->save();
        return response('success',200);
    }
    public function filter_by_date(Request $request){
        $start_time = Carbon::createFromFormat('Y-m-d', $request->from)->startOfDay();
        $end_time = Carbon::createFromFormat('Y-m-d',  $request->to)->endOfDay(); // lấy ngày cuối cùng

        $filters = Orders::whereBetween('updated_at',[$start_time,$end_time])->where('status',3)->orderBy('updated_at','ASC')->get();
        $filters_first = $filters->first();
        $filters_last = $filters->last();

        $current_date = date('d-m-Y', strtotime($filters_first->updated_at));

        $total = 0;
        $chart_data = [];
          
        foreach($filters as $filter){
           if($current_date == date('d-m-Y',strtotime($filter->updated_at))){
            $total+= $filter['total'];
           }else{
                $data = [
                    'date'=>$current_date,
                    'total'=>$total
                ];
                $current_date = date("d-m-Y", strtotime($filter->updated_at));
                $total = $filter->total;
                array_push($chart_data,$data);
            }
           if($filters_last->id == $filter->id){
            $data = [
                'date'=>date("d-m-Y", strtotime($filter->updated_at)),
                'total'=>$total
            ];
            array_push($chart_data,$data);

           }
        }
        return response()->json([
            'success'=>__("Successfully !"),
            'chart_data'=>$chart_data,
            'current_date'=>$current_date
        ]);
    }
    public function sort_by(Request $request){
        $now = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
        $week = Carbon::now('Asia/Ho_Chi_Minh')->subDays(7)->startOfDay()->toDateString();//7
        $this_month = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();//1-31
        $start_last_month = Carbon::now('Asia/Ho_Chi_Minh')->subMonth()->startOfMonth()->toDateString();//1
        $end_last_month = Carbon::now('Asia/Ho_Chi_Minh')->subMonth()->endOfMonth()->toDateString();//30 hoặc 31
        $year = Carbon::now('Asia/Ho_Chi_Minh')->subDays(365)->startOfYear()->toDateString();

        if($request->statistical == 'week'){
            $statistical = Orders::whereBetween('updated_at',[$week,$now])->where('status',3)->orderBy('updated_at','ASC')->get();
            $statistical_first = $statistical->first();
            $statistical_last = $statistical->last();
            $current_date = date('d-m-Y',strtotime($statistical_first->updated_at));
        }
        if($request->statistical == 'last_month'){
            $statistical = Orders::whereBetween('updated_at',[$start_last_month,$end_last_month])->where('status',3)->orderBy('updated_at','ASC')->get();
            $statistical_first = $statistical->first();
            $statistical_last = $statistical->last();
            $current_date = date('d-m-Y',strtotime($statistical_first->updated_at));
        }
        if($request->statistical == 'this_month'){
            $statistical = Orders::whereBetween('updated_at',[$this_month,$now])->where('status',3)->orderBy('updated_at','ASC')->get();
            $statistical_first = $statistical->first();
            $statistical_last = $statistical->last();
            $current_date = date('d-m-Y',strtotime($statistical_first->updated_at));
        }
        if($request->statistical == 'year'){
            $statistical = Orders::whereBetween('updated_at',[$year,$now])->where('status',3)->orderBy('updated_at','ASC')->get();
            $statistical_first = $statistical->first();
            $statistical_last = $statistical->last();
            $current_date = date('m-Y',strtotime($statistical_first->updated_at));
        }

        function date_statistical($option, $date)
        {
            if ($option == 'year') {
                return date("m-Y", strtotime($date));
            } else {
                return date("d-m-Y", strtotime($date));
            }
        }
        $total =0;
        $chart_data = [];

        foreach($statistical as $statis){
            if($current_date == date_statistical($request->statistical,$statis->updated_at)) {
                $total += $statis->total;
            }else{
                $data = [
                    'date'=>$current_date,
                    'total'=>$total
                ];
                $current_date = date_statistical($request->statistical,$statis->updated_at);
                $total = $statis->total;
                array_push($chart_data,$data);
            }
            if($statistical_last->id == $statis->id){
                $data =[
                    'date'=>date_statistical($request->statistical,$statis->updated_at),
                    'total'=>$total
                ];
                array_push($chart_data,$data);
            }
        }
        return response()->json([
            'success' => __("Successfully !"),
            'chart_data'=>$chart_data,
            'statistical'=>$statistical
        ]);
    }
}
