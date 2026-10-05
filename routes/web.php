<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('lang',function(){
    $language = Session("language",config("app.locale"));
    if($language == "en") {
        $language = "vi";
    }else
    {
        $language = "en";
    }
    Session::put('language',$language);
    return redirect()->back();
})->name("lang");

Route::middleware('language')->group(function(){
    require 'admin.php';
    Route::get('/errors/404',function(){
        return view('errors.404');
    });
    Route::get('/', [WebController::class, 'index']);

    //! Authentication
    Route::get('/signin_signup', [WebController::class, 'signin_signup']);
    Route::post('/handle_login',[WebController::class, 'handle_login']);
    Route::post('/register',[WebController::class, 'register']);
    Route::get('/logout',[WebController::class, 'logout']);
    Route::get('/reset-password',[WebController::class,'reset_password']);
    Route::post('/reset-password',[WebController::class,'handle_reset_password']);
    Route::post('/forgotPassword',[WebController::class, 'forgotPassword']);

    //TODO Products
    Route::get('/list',[WebController::class, 'list']);
    Route::get('/detail/{id}',[WebController::class, 'detail']);
    Route::get('/search',[WebController::class, 'search']);
    Route::post('/reviews',[WebController::class, 'reviews']);
    Route::get('/sortCategories',[WebController::class, 'sortCategories']);
    Route::get('/sortBrands',[WebController::class, 'sortBrands']);
    Route::get('/sortBySelect',[WebController::class, 'sortBySelect']);
    Route::get('/filterPrice',[WebController::class, 'filterPrice']);

    //! Categories
    Route::get('/category/{id}',[WebController::class, 'category']);
    Route::get('/subcategory/{id}',[WebController::class, 'subcategory']);
    Route::get('/brands/{id}',[WebController::class, 'brands']);

    //TODO Profile
    Route::get('/profile',[WebController::class, 'profile']);
    Route::post('/profile',[WebController::class, 'editProfile']);
    Route::post('/imageProfile',[WebController::class, 'imageProfile']);
    Route::get('/myOrder',[WebController::class, 'myOrder']);
    Route::get('/trackOrder',[WebController::class, 'trackOrder']);
    Route::post('/trackOrder',[WebController::class, 'handle_trackOrder']);
    Route::get('/detail-track-order',[WebController::class, 'detailTrackOrder']);

    //! Carts
    Route::get('/cart',[WebController::class, 'cart']);
    Route::post('/cart',[WebController::class, 'handle_cart']);
    Route::post('/updateCart',[WebController::class, 'update']);
    Route::post('/cart/discounts',[WebController::class, 'discounts']);
    Route::delete('/cart/canceldiscounts',[WebController::class, 'cancelDiscounts']);
    Route::delete('/deleteCart',[WebController::class, 'deleteCart']);
    Route::get('/checkout',[WebController::class, 'checkout']);
    Route::get('/verify-email',[WebController::class,'verify_email']);
   
    //TODO News
    Route::get('/news/list',[WebController::class, 'newsList']);
    Route::get('/news/{id}',[WebController::class, 'newsDetail']);

    //? Payment
    Route::post('/vnpay_payment',[PaymentController::class, 'vnpay_payment']);
    Route::get('/handle_payment',[PaymentController::class, 'handle_payment']);
    Route::post('/send_mail_orders',[PaymentController::class, 'sendMail']);

    //! Wishlist
    Route::get('/wishlist',[WishlistController::class, 'index']);
    Route::post('/wishlist',[WishlistController::class, 'wishlist']);
    Route::get('/count_wishlist',[WishlistController::class, 'count_wishlist']);




});