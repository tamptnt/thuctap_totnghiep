@extends('web.layout.index')
@section('css')
<link href="web_assets/css/cart.css" rel="stylesheet">
@endsection
@section('content')
<?php

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;

if (Auth::check()) {
    $carts = Cart::instance(Auth::user()->id);
} else {
    $carts = Cart::instance();
}
Cart::setGlobalTax($info->tax);
?>
<style>
    .numbers-cart {
        position: relative;
        width: 100%;
        height: 40px;
        overflow: visible;
        border: 1px solid #dddddd;
        -webkit-border-radius: 3px;
        -moz-border-radius: 3px;
        -ms-border-radius: 3px;
        border-radius: 3px;
        background-color: #fff;
        text-align: left !important;
    }

    .button_cart {
        cursor: pointer;
        position: absolute;
        width: 33px;
        height: 40px;
        line-height: 38px;
        text-align: center;
        z-index: 2;
        font-size: 26px;
        font-weight: 300 !important;
        color: #999;
    }
</style>
<main class="bg_gray">
    <div class="container margin_30">
        <div class="page_header">
            <div class="breadcrumbs">
                <ul>
                    <li><a href="/">{{__("Home")}}</a></li>
                    <li>{{__("Cart")}}</li>
                </ul>
            </div>
            <h1>{{__("Cart")}}</h1>
        </div>
        <!-- /page_header -->

        <table class="table table-striped cart-list">
            <thead>
                <tr>
                    <th>
                        {{__("Products")}}
                    </th>
                    <th>
                        {{__("Unit Price")}}
                    </th>
                    <th>
                        {{__("Quantity")}}
                    </th>
                    <th style="white-space: nowrap;">
                        {{__("Subtotal")}}
                    </th>
                    <th>

                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($carts->content() as $key =>$cart)
                <tr>
                    <td>
                        <div class="thumb_cart">
                            @if(strstr($cart->image,"https") == "")
                            <img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$cart->options->image}}.jpg" class="lazy" alt="Image">
                            @else
                            <img src="{{$cart->options->image}}" class="lazy" alt="Image">
                            @endif
                        </div>
                        <a href="/detail/{{$cart->id}}" style="color:black !important;">
                            <span class="item_cart">{{$cart->name}}</span>
                            <input type="hidden" name="cartId" value="{{$cart->id}}" />
                        </a>
                    </td>
                    <td>
                        <strong>
                            {{number_format($cart->price,0,",",".")}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                        </strong>
                    </td>
                    <td>
                        <div class="numbers-cart">
                            <input type="text" value="{{$cart->qty}}" id="quantity_1" class="qty2" name="quantity">
                            <div class="inc button_cart" data-id="{{$cart->rowId}}" data-productid="{{$cart->id}}">+</div>
                            <div class="dec button_cart" data-id="{{$cart->rowId}}" data-productid="{{$cart->id}}">-</div>
                        </div>
                    </td>
                    <td>
                        <strong id="subtotal_{{$cart->rowId}}">
                            <?php
                            $sum = $cart->price * $cart->qty;
                            ?>
                            {{number_format($sum,0,',','.')}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                        </strong>
                    </td>
                    <td class="options">
                        <a href="javascript:void(0)" class="delete-cart" data-id="{{$cart->rowId}}">
                            <i class="ti-trash"></i>
                        </a>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>

        <div class="row add_top_30 flex-sm-row-reverse cart_actions">
            <div class="col-sm-4 text-end">
            </div>
            <div class="col-sm-8 text-start">
                <div class="apply-coupon">
                    <div class="form-group">
                        <div class="row g-2">
                            @if(Auth::check())
                            <div class="col-md-6">
                                <input type="text" name="code" id="discount" placeholder="{{__('Promo code')}}" class="form-control">
                            </div>
                            <div class="col-md-4" id="formCoupon">
                                <button id="applyCoupon" class="btn_1 outline">{{__("Apply Coupon")}}</button>
                            </div>
                            @else
                            <div class="col-md-6">
                                <input type="text" disabled placeholder="{{__('Promo code')}}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <form action="/signin_signup">
                                    <button type="submit" class="btn_1 outline">{{__("Login")}}</button>
                                </form>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- /cart_actions -->

    </div>
    <!-- /container -->

    <div class="box_cart">
        <div class="container">
            <div class="row justify-content-end">
                <div class="col-xl-4 col-lg-4 col-md-6">
                    <ul>
                        <li>
                            <span>{{__("Subtotal")}}</span>
                            <p id="sumSubtotal">
                                {{$carts->priceTotal(0,',','.');}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                            </p>
                        </li>
                        <li>
                            <span>{{__("Tax")}} ({{$info->tax}}%)</span>
                            <p id="tax">
                                {{$carts->tax(0,',','.')}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                            </p>
                        </li>
                        <li>
                            <span id="discountPercent">{{__("Discount")}} (0%)</span>
                            <p id="valueDiscount">
                                {{$carts->discount(0,',','.')}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                            </p>
                        </li>
                        <li>
                            <span>{{__("Shipping")}}</span>
                            <p>
                                {{__("Free")}}
                            </p>
                        </li>
                        <li>
                            <span>{{__("Total")}}</span>

                            <p id="totalCart">
                                {{$carts->total(0,',','.')}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                            </p>
                        </li>
                    </ul>
                    @if(Auth::check())
                    @if($carts->content()->count()!=0)
                    <form action="/checkout" method="get">
                    <button type="submit" class="btn_1 full-width cart">{{__("Proceed to Checkout")}}</button>
                    <input type="hidden" id="discount_hidden" name="discount_hidden" value="" />
                    </form>
                    @else
                    <a href="/list" class="btn_1 full-width cart">{{__("Please place an order")}}</a>
                    @endif
                    @else
                    <a href="/signin_signup" class="btn_1 full-width cart">{{__("Login")}}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- /box_cart -->

</main>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $(".button_cart").on("click", function() {
            var $button = $(this);
            var oldValue = $button.parent().find("input").val();
            if ($button.text() == "+") {
                var newVal = parseFloat(oldValue) + 1;
                // alert(newVal);
            } else {
                // Don't allow decrementing below zero
                if (oldValue > 1) {
                    var newVal = parseFloat(oldValue) - 1;
                    // alert(newVal);
                } else {
                    newVal = 1;
                }
            }
            $button.parent().find("input").val(newVal);
            var cartId = $(this).data('id');
            var productCartId = $(this).data('productid');
            // alert(productCartId);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    cartId: cartId,
                    qty: newVal,
                    productCartId: productCartId
                },
                url: '/updateCart',
                type: "post",
                success: function(data) {
                    if (data['success']) {
                        $("#subtotal_" + cartId).text(data.subtotal.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#sumSubtotal').text(data.sum.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#tax').text(data.tax.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#totalCart').text(data.total.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#valueDiscount').text(data.discount.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                    }
                    if (data['error']) {
                        $button.parent().find("input").val(data.products_quantity);
                        alert(data.error);
                    }
                }
            });

        });

    });
</script>
<script>
    $(document).ready(function() {
        $('.delete-cart').on('click', function() {
            var Obj = $(this);
            var cartId = $(this).data('id');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: '/deleteCart',
                type: 'DELETE',
                data: {
                    cartId: cartId
                },
                dataType: 'json',
                success: function(data) {
                    Obj.parents('tr').remove();
                    $('#sumSubtotal').text(data.subtotal.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                    $('#totalCart').text(data.total.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                    $('#tax').text(data.tax.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                    $('#valueDiscount').text(data.discount.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                },
                error: function() {
                    alert("error");

                }
            })
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#formCoupon').on('click', '#applyCoupon', function() {
            var discount = $('#discount').val();
            console.log(discount)
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: '/cart/discounts',
                type: 'POST',
                dataType: 'json',
                data: {
                    'discount': discount,
                },
                success: function(data) {
                    if (data['success']) {
                        alert(data.success)
                        $('#discount_hidden').val(discount);
                        $("#discount").prop('disabled', true);
                        $('#discountPercent').text('{{__("Discounts")}} (' + data.percent.toLocaleString('vi-VN') + '%)');
                        $("#applyCoupon").replaceWith('<button id="cancelCoupon" class="btn_1 outline">{{__("Cancel coupon")}}</button>')
                        $('#sumSubtotal').text(data.subtotal.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#totalCart').text(data.total.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#tax').text(data.tax.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#valueDiscount').text(data.discount.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');

                    }
                    if (data['error']) {
                        alert(data.error)
                    }
                }
            });


        });

        $('#formCoupon').on('click', '#cancelCoupon', function() {
            var discount = $('#discount').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: '/cart/canceldiscounts',
                type: 'DELETE',
                dataType: 'json',
                data: {
                    'discount': discount,
                },
                success: function(data) {
                    if (data['success']) {
                        alert(data.success);
                        $("#discount").prop('disabled', false);
                        $("#cancelCoupon").replaceWith('<button id="applyCoupon" class="btn_1 outline">{{__("Apply Coupon")}}</button>')
                        $('#discountPercent').text('{{__("Discounts")}} (0%)');
                        $('#sumSubtotal').text(data.subtotal.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#totalCart').text(data.total.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#tax').text(data.tax.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                        $('#valueDiscount').text(data.discount.toLocaleString('vi-VN')).append('<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>');
                    }
                    if (data['error']) {
                        alert(data.error)
                    }
                }
            });
        });

    });
</script>
@endsection