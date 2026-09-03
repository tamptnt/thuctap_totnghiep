@extends('web.layout.index')
@section('css')
<link href="web_assets/css/listing.css" rel="stylesheet">
@endsection
@section('content')
<main>
	<!-- /top_banner -->
	<div id="stick_here"></div> >
	<div class="container margin_30">
		<div class="row small-gutters">
			@foreach($products as $pro)
				@if(Auth::user()->id == $pro->users_id)
					@foreach($pro->products->ProductsImage as $img)
						@if($loop->first)
						<div class="col-6 col-md-4 col-xl-3">
							<div class="grid_item">
								@if(isset($pro->products->price_new) && isset($pro->products->price) && $pro->products->price != 0 && $pro->products->price_new != 0)
								<span class="ribbon off">-{{round((($pro->products->price - $pro->products->price_new)/$pro->products->price)*100,0) }} %</span>
								@elseif($pro->products->featured_product == 1)
								<span class="ribbon hot">Hot</span>
								@else
								<span class="ribbon new">New</span>
								@endif
								<figure>
									<a href="/detail/{{$pro->products->id}}">
										@if(strstr($img->image,"https") == "")
										<img style="width:290px; height: 290px;" class="img-fluid lazy" data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg" alt="">
										@else
										<img style="width:290px; height: 290px;" class="img-fluid lazy" data-src="{{$img->image}}" alt="">
										@endif
									</a>
									<!-- <div data-countdown="2019/05/15" class="countdown"></div> -->
								</figure>
								<a href="/detail/{{$pro->products->id}}">
									<h3 class="d-block" style="max-width: 270px; height:50px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">{{$pro->products->name}}</h3>
								</a>
								<div class="price_box">
									@if($pro->products->price != 0 && $pro->products->price_new != 0)
									<span class="new_price">{{number_format($pro->products->price_new,0,",",".")}} Vnđ</span>
									<span class="old_price">{{number_format($pro->products->price,0,",",".")}} Vnđ</span>

									@elseif($pro->products->price_new==0 && $pro->products->price!=0)
									<span class="new_price">{{number_format($pro->products->price,0,",",".")}} Vnđ</span>

									@elseif($pro->products->price==0 && $pro->products->price_new!=0)
									<span class="new_price">{{number_format($pro->products->price_new,0,",",".")}} Vnđ</span>

									@else
									<span class="new_price">{{__("Contact")}}</span>

									@endif
								</div>
								<ul>
									<li>
										@if(Auth::check())
										<?php
										$count = $wishlist->countWishlist($pro->products->id);
										?>
										<a href="javascript:void(0)" data-productid="{{$pro->products->id}}" class="tooltip-1 wishlist" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
											@if($count >0)
											<i class="fa-solid fa-heart" style="color:red"></i>
											@else
											<i class="fa-regular fa-heart"></i>
											@endif
											<span>{{__('Add to favorites')}}</span>
										</a>
										@else
										<a href="/signin_signup" data-productid="{{$pro->products->id}}" class="tooltip-1 wishlist" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
											<i class="fa-regular fa-heart"></i>
											<span>{{__('Add to favorites')}}</span>
										</a>
										@endif
									</li>
									<li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to compare')}}"><i class="ti-control-shuffle"></i><span>{{__("Add to compare")}}</span></a></li>
									@if($pro->products->price || $pro->products->price_new)
									<li>
										<form action="/cart" method="post" id="formSubmitCart_{{$pro->products->id}}">
											@csrf
											<a href="javascript:void(0)" onclick="document.getElementById('formSubmitCart_{{$pro->products->id}}').submit();" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to cart')}}"><i class="ti-shopping-cart"></i><span>{{__('Add to cart')}}</span></a>
											<input type="hidden" name="products_id" value="{{$pro->products->id}}" />
											<input type="hidden" name="quantity" value="1" />
										</form>
									</li>
									@endif
								</ul>
							</div>
						</div>
						@endif
					@endforeach
				@endif
			@endforeach
		</div>
	</div>
</main>
@endsection
@section('scripts')
<script src="web_assets/js/sticky_sidebar.min.js"></script>
<script src="web_assets/js/specific_listing.js"></script>
@endsection