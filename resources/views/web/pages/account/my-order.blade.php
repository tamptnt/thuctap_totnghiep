@extends('web.layout.index')
@section('css')
<link href="web_assets/css/checkout.css" rel="stylesheet">
@endsection
@section('content')
<main class="bg_gray">
    <div class="container margin_30">
        <div class="page_header">
            <div class="breadcrumbs">
                <ul>
                    <li><a href="/">{{__("Home")}}</a></li>
                    <li>{{__("Your Order")}}</li>
                </ul>
            </div>
            <!-- <h1>{{__("Your Order")}}</h1> -->
        </div>
        <!-- /page_header -->

        <div class="row">
            @foreach($orders as $key => $order)
            <div class="col-lg-12 col-md-12">
                <div class="step first">
                    <form action="/detail-track-order" id="formSubmitTrackOrder_{{$order->id}}" method="GET">
                        @csrf
                        <h3>
                            <a href="javascript:void(0)" onclick="document.getElementById('formSubmitTrackOrder_{{$order->id}}').submit();">
                                <span style="color:white;">{{__("Click to see order details")}}</span>
                            </a>
                        </h3>
                        <input type="hidden" name="id" value="{{$order->id}}" />
                    </form>
                    <div class="box_general summary">
                        <ul>
                            @foreach($order->products as $orderPro)
                            <li class="clearfix">
                                <a href="/detail/{{$orderPro->id}}" style="color:black !important;">
                                    <em
                                        style="max-width: 300px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">
                                        <img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$orderPro->ProductsImage->first()->image}}.jpg"
                                            width="50" height="50" class="lazy" alt="Image">
                                        {{$orderPro->pivot->quantity}} x {{$orderPro->name}}
                                    </em>
                                </a>
                                <span style="margin-top:15px">
                                    @if($orderPro->price_new)
                                    {{number_format($orderPro->price_new,0,',','.')}}<sup
                                        style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                                    @else
                                    {{number_format($orderPro->price,0,',','.')}}<sup
                                        style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
                                    @endif
                                </span>
                            </li>
                            @endforeach
                        </ul>
                        <ul>
                            <li class="clearfix">
                                <em>
                                    <strong>{{__("Order Number")}}</strong>
                                </em>
                                <span>{{$order->id}}</span>
                            </li>
                            <li class="clearfix">
                                <em>
                                    <strong>{{__("Quantity")}}</strong>
                                </em>
                                <span>
                                    <?php 
                                        $sum=0;
                                        foreach($order->products as $orderPro){
                                            $sum+=$orderPro->pivot->quantity;
                                        }
                                        echo $sum;
                                    ?>
                                    /{{__("piece")}}
                                </span>
                            </li>

                            <li class="clearfix">
                                <em>
                                    <strong>{{__("Subtotal")}}</strong>
                                </em>
                                <span>{{number_format($order->subtotal,0,',','.')}}<sup
                                        style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup></span>
                            </li>

                            <li class="clearfix">
                                <em>
                                    <strong>{{__("Tax")}} ({{(($order->total-($order->subtotal-$order->discount))/($order->subtotal-$order->discount))*100}}%)</strong>
                                </em>
                                <span>{{number_format($order->tax,0,',','.')}}<sup
                                        style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup></span>
                            </li>

                            <li class="clearfix">
                                <em>
                                    <strong>{{__("Discount")}} ({{(($order->subtotal-($order->total-$order->tax))/$order->subtotal)*100}}%)</strong>
                                </em>
                                <span>{{number_format($order->discount,0,',','.')}}<sup
                                        style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup></span>
                            </li>

                            <li class="clearfix">
                                <em>
                                    <strong>{{__("Shipping")}}</strong>
                                </em>
                                <span>{{__("Free")}}</span>
                            </li>

                        </ul>
                        <div class="total clearfix">{{__("Total")}}
                            <span>{{number_format($order->total,0,',','.')}}<sup
                                    style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup></span>
                        </div>
                    </div>
                    <!-- /box_general -->
                </div>
                <!-- /step -->
            </div>

            @endforeach
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{$orders->links()}}
        </div>

        <!-- /row -->
    </div>
    <!-- /container -->
</main>
@endsection