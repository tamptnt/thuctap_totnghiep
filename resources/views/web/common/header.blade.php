@if(Request::path() == 'list')
<style>
    .account-slider{
        margin-top: 19px !important;
    }
</style>
@endif
<header class="version_2">
    <div class="layer"></div><!-- Mobile menu overlay mask -->
    <div class="main_header" style=" z-index: 1 !important;">
        <div class="container">
            <div class="row small-gutters">
                <div class="col-xl-3 col-lg-3 d-lg-flex align-items-center">
                    <div id="logo">
                        <a href="/" ><img src="images/favicon/{{isset($info->logo) ? $info->logo : ''}} " alt="" width="100" height="35"></a>
                    </div>
                </div>
                <nav class="col-xl-6 col-lg-7">

                    <!-- Mobile menu button -->
              <div class="main-menu"></div>
                    <!--/main-menu -->
                </nav>
                <div class="col-xl-3 col-lg-2 d-lg-flex align-items-center justify-content-end text-end">
                <a class="phone_top" href="tel://{{isset($info->phone) ? $info->phone : ''}}"><strong><span>{{__("Contact")}}</span>+{{isset($info->phone) ? $info->phone : ''}}</strong></a>
                <a href="{{route('lang')}}" class="nav-link nav-link-lg nav-link-user">
                    <div class="d-lg-inline-block">
                    @if(Session("language") == "en")
                    <img src="images/language/{{__('en')}}.png" style="height: 30px">
                    @else
                    <img src="images/language/{{__('vi')}}.png" style="height: 30px">
                    @endif
                    </div>
                </a>
                </div>
            </div>
            <!-- /row -->
        </div>
    </div>
    <!-- /main_header -->

    <div class="main_nav Sticky">
        <div class="container">
            <div class="row small-gutters">
                <div class="col-xl-3 col-lg-3 col-md-3">
                    <nav class="categories">
                        <ul class="clearfix">
                            <li><span>
                                    <a href="#">
                                        <span class="hamburger hamburger--spin">
                                            <span class="hamburger-box">
                                                <span class="hamburger-inner"></span>
                                            </span>
                                        </span>
                                        {{__("Categories")}}
                                    </a>
                                </span>
                                <div id="menu">
                                    <ul>
                                        @foreach($categories as $cat)
                                        <li><span><a href="/category/{{$cat->id}}">{{$cat->name}}</a></span>
                                            <ul class="fix_long">
                                                @foreach($cat->Subcategories as $sub)
                                                @if($sub->status == 1)
                                                <li><a href="/subcategory/{{$sub->id}}">{{$sub->name}}</a></li>
                                                @endif
                                                @endforeach
                                            </ul>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="col-xl-6 col-lg-7 col-md-6 d-none d-md-block">
                    <div class="custom-search-input">
                        <form action="/search" method="GET">
                            <input type="text" name="search" value="{{Request::get('search')}}" placeholder="{{__('Search over 10.000 products')}}">
                            <button type="submit" style="margin-top: -3px;"><i class="ti-search" style="font-size: 20px;"></i></button>
                        </form>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-2 col-md-3">
                    <ul class="top_tools">
                        <li>
                            <div class="dropdown dropdown-cart mt-1">
                                @if( Request::path() != "cart" && Request::path() != "checkout")
                                <a href="/cart"><i class="ti-shopping-cart" style="font-size: 22px;"></i><strong id="cartCount"> @if(Auth::check()) {{ Cart::instance(Auth::user()->id)->count() }} @else {{ Cart::instance()->count() }} @endif </strong></a>
                                <div class="dropdown-menu">
                                    <ul>
                                        @foreach($carts->content() as $key =>$cart)
                                        <li>
                                            <a href="/detail/{{$cart->id}}">
                                                @if(strstr($cart->image,"https") == "")
                                                <figure><img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$cart->options->image}}.jpg" alt="" width="50" height="50" class="lazy"></figure>
                                                @else
                                                <figure><img src="{{$cart->options->image}}" alt="" width="50" height="50" class="lazy"></figure>
                                                @endif
                                                <strong>
                                                    <span style="max-width: 150px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">{{$cart->qty}} x {{$cart->name}}</span>
                                                    {{number_format($cart->price,0,",",".")}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                                                </strong>
                                            </a>
                                            <a href="javascript:void(0);" data-id="{{$cart->rowId}}" class="action delete-cart"><i class="ti-trash"></i></a>
                                        </li>
                                        @endforeach
                                    </ul>
                                    <div class="total_drop">
                                        <div class="clearfix">
                                            <strong>{{__("Subtotal")}}</strong>
                                            <span id="sumSubtotal">
                                            {{$carts->priceTotal(0,',','.');}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                                            </span>
                                        </div>
                                        <div class="clearfix">
                                            <strong>{{__("Tax")}}</strong>
                                            <span id="tax">
                                            {{$carts->tax(0,',','.');}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                                            </span>
                                        </div>
                                        <div class="clearfix">
                                            <strong>{{__("Total")}}</strong>
                                            <span id="total">
                                            {{$carts->total(0,',','.');}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                                            </span>
                                        </div>
                                        <a href="/cart" class="btn_1 outline">{{__("View Cart")}}</a>
                                        @if(Auth::check())
                                        <a href="/checkout" class="btn_1">{{__("Checkout")}}</a>
                                        @else
                                        <a href="/signin_signup" class="btn_1">{{__("Login")}}</a>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            <!-- /dropdown-cart-->
                        </li>
                        @if(Auth::check())                                    
                        <li>
                            <div class="dropdown dropdown-access mt-1 account-slider">
                            <a href="javascript:void(0)"><i class="ti-unlock" style="font-size: 22px;"></i></a>
                                <div class="dropdown-menu" style="display: none;">
                                    @if(isset(Auth::user()->image))
                                    
                                        @if(strstr(Auth::user()->image,"https") == "")
                                            <img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{ Auth::user()->image }}.jpg"   class=" me-2" style="border-radius:50%!important; width:40px;height:32px">
                                        @else
                                            <img src='{{Auth::user()->image}}'  class=" me-2" style="border-radius:50%!important; width:40px;height:32px">
                                        @endif
                                    @else
                                    <img src='images/avatar/avatar.png'  class=" me-2" style="border-radius:50%!important; width:40px;height:32px">
                                    @endif

                                            @if(Session("language") == "en")
                                                {{Auth::user()->firstname}} {{Auth::user()->lastname}}
                                            @else
                                                {{Auth::user()->lastname}} {{Auth::user()->firstname}}
                                            @endif
                                    <ul>
                                        <li>
                                            <a href="/profile"><i class="ti-user"></i>{{__("My Profile")}}</a>
                                        </li>
                                        <li>
                                            <a href="/myOrder"><i class="ti-package"></i>{{__("My Orders")}}</a>
                                        </li>
                                        <li>
                                            <a href="/trackOrder"><i class="ti-truck"></i>{{__("Track your Order")}}</a>
                                        </li>
                                        <li>
                                            <a href="/logout"><i class="ti-shift-right"></i>{{__("Logout")}}</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- /dropdown-access-->
                        </li>
                        @else
                        <li>
                            <div class="dropdown dropdown-access mt-1 account-slider">
                                <a href="/signin_signup"><i class="ti-lock" style="font-size: 22px;"></i><span>Account</span></a>
                            </div>
                            <!-- /dropdown-access-->
                        </li>
                        @endif
                        <li>
                            <div class="mt-1">
                                @if(Auth::check())
                                    <a href="/wishlist"><i class="fa-solid fa-heart" style="font-size: 21px;color:red"></i><strong id="wishlistCount"></strong></a>
                                @else
                                    <a href="/signin_signup"><i class="fa-regular fa-heart account-slider" style="font-size: 21px;"></i><strong id="wishlistCount"></strong></a>
                                @endif
                            </div>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="btn_search_mob ti-search" ><span>Search</span></a>
                        </li>
                        <li>
                            <a href="#menu" class="btn_cat_mob">
                                <div class="hamburger hamburger--spin" id="hamburger">
                                    <div class="hamburger-box">
                                        <div class="hamburger-inner"></div>
                                    </div>
                                </div>
                                Categories
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- /row -->
        </div>

        <!-- Search mobile -->

        <div class="search_mob_wp">
            <form action="/search" method="get">
                <input type="text" name="search" value="{{Request::get('search')}}" class="form-control" placeholder="{{__('Search over 10.000 products')}}">
                <input type="submit" class="btn_1 full-width" value="Search">
            </form>
        </div>
        <!-- /search_mobile -->
    </div>
    <!-- /main_nav -->
</header>
@section('scripts')
<script>
    $(document).ready(function(){
        $('.delete-cart').on('click',function(){
            var Obj = $(this);
            var cartId = $(this).data('id');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url:'/deleteCart',
                type:'DELETE',
                data:{
                    cartId:cartId
                },
                dataType: 'json',
                success: function(data){
                    Obj.parent().remove();
                    $('#sumSubtotal').text(data.subtotal.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                    $('#total').text(data.total.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                    $('#cartCount').text(data.count.toLocaleString('vi-VN'));
                    $('#tax').text(data.tax.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                },error: function(){
                    alert("error");

                }
            })
        });
    });
</script>
@endsection