@extends('web.layout.index')
@section('css')
<link href="web_assets/css/error_track.css" rel="stylesheet">
@endsection
@section('content')
<main class="bg_gray">
		<div id="track_order">
			<div class="container">
				<div class="row justify-content-center text-center">
					<div class="col-xl-7 col-lg-9">
						<img src="web_assets/img/track_order.svg" alt="" class="img-fluid add_bottom_15" width="200" height="177">
						<p>{{__('Quick Tracking Order')}}</p>
						<form action="/detail-track-order" method="GET">
                            @csrf
							<div class="search_bar">
								<input type="text" name="id" class="form-control" placeholder="{{__('Invoice ID')}}">
								<input type="submit" value="{{__('Search')}}">
							</div>
						</form>
					</div>
				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /track_order -->
		<div class="bg_white">
		<div class="container margin_60_35">
			<div class="main_title">
                <a href="/list">
                    <h2>{{__('New Entries')}}</h2>
                    <span>{{__("Products")}}</span>
                </a>
			</div>
			<div class="owl-carousel owl-theme products_carousel">
                @foreach($products as $new)
					@foreach($new->ProductsImage as $img)
						@if($loop ->first)
                        <div class="item">
                            <div class="grid_item">
                                <span class="ribbon new">New</span>
                                <figure>
                                    <a href="/detail/{{$new->id}}">
                                        <img class="img-fluid lazy" src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg" >
                                    </a>
                                </figure>
                                <a href="/detail/{{$new->id}}">
                                    <h3 class="d-block" style="max-width: 270px; height:50px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">{{$new->name}}</h3>
                                </a>
                                <div class="price_box">
                                    @if(isset($new->price_new) && isset($new->price))

                                        <span class="new_price">{{number_format($new->price_new,0,",",".")}} Vnđ</span>
                                        <span class="old_price">{{number_format($new->price,0,",",".")}} Vnđ</span>

                                        @elseif(!isset($new->price_new) && isset($new->price))
                                        <span class="new_price">{{number_format($new->price,0,",",".")}} Vnđ</span>

                                        @elseif(!isset($new->price) && isset($new->price_new))
                                        <span class="new_price">{{number_format($new->price_new,0,",",".")}} Vnđ</span>

                                        @else
                                        <span class="new_price">{{__("Contact")}}</span>
                                    
                                    @endif
                                </div>
                                <ul>
                                    <li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Add to favorites"><i class="ti-heart"></i><span>Add to favorites</span></a></li>
                                    <li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Add to compare"><i class="ti-control-shuffle"></i><span>Add to compare</span></a></li>
                                    @if($new->price || $new->price_new)
                                    <li>
                                        <form action="/cart" method="post" id="formSubmitCart_{{$new->id}}">
                                            @csrf
                                            <a href="javascript:void(0)"  onclick="document.getElementById('formSubmitCart_{{$new->id}}').submit();" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to cart')}}"><i class="ti-shopping-cart"></i><span>{{__('Add to cart')}}</span></a>
                                            <input type="hidden" name="products_id" value="{{$new->id}}"/>
                                            <input type="hidden" name="quantity" value="1"/>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                            <!-- /grid_item -->
                        </div>
                        @endif
                    @endforeach
                @endforeach
			</div>
			<!-- /products_carousel -->
		</div>
		<!-- /container -->
		</div>
		<!-- /bg_white -->
	</main>
@endsection