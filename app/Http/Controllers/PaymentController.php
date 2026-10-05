<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Discounts;
use App\Models\Info;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Subcategories;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    //TODO:Payment
    public function vnpay_payment(Request $request)
    {
        //    dd($request->discount);
        $credentials = Validator::make(
            $request->all(),
            [
                'lastname' => 'required',
                'firstname' => 'required',
                'email' => 'required',
                'address' => 'required',
                'district' => 'required',
                'phone' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:12|required',
            ],
            [
                'lastname.required' => __("the last name field is required"),
                'firstname.required' => __("the first name field is required"),
                'email.required' => __("the email field is required"),
                'address.required' => __("the address field is required"),
                'district.required' => __("the district field is required"),
                'phone.required' => __("the phone field is required"),
                'phone.regex' => __("Phone numbers are from 0 to 9 and do not include characters"),
                'phone.min' => __("Phone number at least 10 characters"),
                'phone.max' => __("Phone number maximum 20 characters")
            ]
        );

        if ($credentials->fails()) {
            return back()->with('toast_error', $credentials->messages()->all()[0])->withInput();
        }
        if ($request->city == null) {
            return back()->with('toast_error', __('Please choose a city'));
        }
        // dd($request->discount);
        $cart = Cart::instance(Auth::user()->id);
        $user = Auth::user()->id;
        $orders = new Orders([
            'users_id' => $user,
            'lastname' => $request->lastname,
            'firstname' => $request->firstname,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'district' => $request->district,
            'city' => $request->city,
            'content' => $request->content,
            'tax' => (int)preg_replace("/[,]+/", "", $cart->tax(0)),
            'subtotal' => (int)preg_replace("/[,]+/", "", $cart->priceTotal(0)),
            'total' => (int)preg_replace("/[,]+/", "", $cart->total(0)),
            'discount' => (int)preg_replace("/[,]+/", "", $cart->discount(0)),
            'lastname_sender' => $request->lastname_sender,
            'firstname_sender' => $request->firstname_sender,
            'phone_sender' => $request->phone_sender,
            'hold' => true
        ]);
        $orders->save();
        foreach ($cart->content() as $carts) {
            $products = Products::find($carts->id);
            if ($carts->qty > $products->quantity) {
                $orders->delete();
                return back()->with('toast_error', __("The product just ran out of stock !"));
                break;
            }
        }

        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = $request->getSchemeAndHttpHost() . "/handle_payment?orders=" . $orders->id . "&discount=" . $request->discount;
        $vnp_TmnCode = "P24RMPW5"; //Mã website tại VNPAY 
        $vnp_HashSecret = "CTI6LRY7BKCJ9Y8CRN8GA61FLPQ6O7ZI"; //Chuỗi bí mật

        $vnp_TxnRef = $orders->id; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_Amount = $orders->total * 100;
        $vnp_Locale = 'vn';
        $vnp_BankCode = 'NCB';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        //Add Params of 2.0.1 Version
        // $vnp_ExpireDate = $_POST['txtexpire'];

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh toan GD" . $vnp_TxnRef,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            // "vnp_ExpireDate" => $vnp_ExpireDate

        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        //var_dump($inputData);
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //  
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return redirect($vnp_Url);
    }

    public function handle_payment(Request $request)
    {
        if ($request->discount != null)
            $discount = Discounts::where('code', $request->discount)->first();
        else
            $discount = null;
        // dd($request->all());
        $orders = Orders::find($request->orders);
        // dd($request->vnp_TransactionStatus == 00);//Mã phản hồi kết quả thanh toán. Quy định mã trả lời 00 ứng với kết quả Thành công cho tất cả các API
        if ($request->vnp_TransactionStatus == 00) {
            $orders->hold = 0;
            $orders->save();
            if ($discount != null) {
                $discount->quantity--;
                $discount->save();
            }
            $cart = Cart::instance(Auth::user()->id);
            foreach ($cart->content() as $carts) {
                $products = Products::find($carts->id);
                if ($carts->qty <= $products->quantity) {
                    $products->quantity = $products->quantity - $carts->qty;
                    $products->update();
                    $orders->products()->attach($carts->id, ['quantity' => $carts->qty]);
                } else {
                    $orders->delete();
                    return back()->with('toast_error', __('Out Stock'));
                    break;
                }
            }
            Orders::where('hold', 1)->delete();
            //   $discount_id = Discounts::find($discounts->id);
            // $discount_id->quantity--;
            // $discount_id->save();
            // dd($discount_id);
            $categories = Categories::all()->where('status', 1);
            $subcategories = Subcategories::all()->where('status', 1);
            $info = Info::find(1);
            return view('web.pages.cart.confirm', ['orders' => $orders, 'categories' => $categories, 'subcategories' => $subcategories, 'info' => $info]);
        } else {
            $orders->delete();
        }
    }
    public function sendMail(Request $request)
    {
        if (Auth::check()) {
            $cart = Cart::instance(Auth::user()->id);
            $orders = Orders::find($request->orders);

            $email_cur = $orders->email;
            $name = Auth::user()->firstname;
            if (isset($orders->email)) {
                Mail::send('web.pages.cart.cart_mail', [
                    'name' => $name,
                    'orders' => $orders,
                    'cart' => $cart
                ], function ($email) use ($email_cur) {
                    $email->subject(__("Shopping Cart Information"));
                    $email->to($email_cur);
                });
            }
            $cart->destroy();
            return response(200);
        } else {
            return response(500);
        }
    }
}
