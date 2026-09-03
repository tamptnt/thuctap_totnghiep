@extends('web.layout.index')
@section('css')
<link href="web_assets/css/product_page.css" rel="stylesheet">
<link href="web_assets/css/fonts/fontawesome/css/all.css" rel="stylesheet">
<link href="web_assets/css/leave_review.css" rel="stylesheet">
@endsection
@section('content')
<style>
	#collapse-A img {
		width: 100%;
	}

	#reviews a {
		color: black;
	}

	#reviews a:hover {
		color: #004dda;
	}
</style>
<main>
	<div class="container margin_30">
		<div class="row">
			<div class="col-md-6">
				<div class="all">
					<div class="slider">
						<div class="owl-carousel owl-theme main">
							@foreach($products->ProductsImage as $img)
							@if(strstr($img->image,"https") == "")
							<div style="background-image: url(https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg);" class="item-box"></div>
							@else
							<div style="background-image: url($img->image);" class="item-box"></div>
							@endif
							@endforeach
						</div>
						<div class="left nonl"><i class="ti-angle-left"></i></div>
						<div class="right"><i class="ti-angle-right"></i></div>
					</div>
					<div class="slider-two">
						<div class="owl-carousel owl-theme thumbs">
							@foreach($products->ProductsImage as $img)
							@if(strstr($img->image,"https") == "")
							<div style="background-image: url(https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg);" class="item active"></div>
							@else
							<div style="background-image: url($img->image);" class="item active"></div>
							@endif
							@endforeach
						</div>
						@if(count($products->ProductsImage)>5)
						<div class="left-t nonl-t"></div>
						<div class="right-t"></div>
						@endif
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="breadcrumbs">
					<ul>
						<li><a href="/">{{__("Home")}}</a></li>
						<li><a href="/category/{{$products->cat_id}}">{{$products->categories->name}}</a></li>
						<li><a href="/subcategory/{{$products->sub_id}}">{{$products->subcategories->name}}</a></li>
					</ul>
				</div>
				<!-- /page_header -->
				<form action="/cart" method="post">
					@csrf
					<div class="prod_info">
						<h1>{{$products->name}}</h1>
						<span class="rating">
							<?php
							$count = count($reviews);
							$sum = 0;
							foreach ($reviews as $review) {
								$sum += $review->rate;
							}
							if ($count != 0) {
								$average = round($sum / $count, 1);
							} else {
								$average = 0;
							}
							for ($i = 0; $i < 5; $i++) {
								if (($average - $i) > 0.5) {
							?>
									<i class="fa-solid fa-star" style="color:#d0011b"></i>
								<?php

								} else if (($average - $i) == 0.5) {
								?>
									<i class="fa-solid fa-star-half-stroke" style="color:#d0011b"></i>
								<?php

								} else if (($average - $i) < 0.5) {
								?>
									<i class="fa-regular fa-star" style="color:#d0011b"></i>
							<?php
								}
							}
							?>
							<em>{{$count}} reviews</em>
						</span>
						<p><small>{{$products->categories->name}}: {{$products->subcategories->name}}-{{$products->id}}</small>
							<!-- <br>Sed ex labitur adolescens scriptorem. Te saepe verear tibique sed. Et wisi ridens vix, lorem iudico blandit mel cu. Ex vel sint zril oportere, amet wisi aperiri te cum. -->
						</p>
						@if($products->price!=0 || $products->price_new!=0)
						<div class="prod_options">
							<div class="row">
								<label class="col-xl-5 col-lg-5  col-md-6 col-6"><strong>{{__("Quantity")}}</strong></label>
								<div class="col-xl-4 col-lg-5 col-md-6 col-6">
									<div class="numbers-row">
										<input type="text" value="1" id="quantity_1" class="qty2" name="quantity">
									</div>
								</div>
							</div>
						</div>
						@endif
						<div class="row">
							<div class="col-lg-5 col-md-6">
								@if($products->status == 1)
									@if($products->price != 0 && $products->price_new != 0)
									<div class="price_main">
										<span class="new_price">{{number_format($products->price_new,0,",",".")}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
										</span>
										<span class="percentage">-{{round((($products->price - $products->price_new)/$products->price)*100,0) }}%</span>
										<span class="old_price">{{number_format($products->price,0,",",".")}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
										</span>
									</div>
									
									@elseif($products->price == 0 && $products->price_new !=0)
									<div class="price_main">
										<span class="new_price">{{number_format($products->price_new,0,",",".")}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
										</span>
									</div>
									@elseif($products->price != 0 && $products->price_new == 0)
									<div class="price_main">
										<span class="new_price">{{number_format($products->price,0,",",".")}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
										</span>
									</div>
									@else
									<div class="price_main">
										<span class="new_price" style="color: red !important">{{__("Contact")}}</span>
									</div>
									@endif
									@else
									<div class="price_main">
										<span class="new_price" style="color: red !important">{{__("Out Stock")}}</span>
									</div>
									@endif
									<label class="col-xl-5 col-lg-5  col-md-6 col-6 mt-4">
										<strong>{{__("Inventory")}}:
											@if($products->quantity != 0)
											{{$products->quantity}}
											@else
											<br/><span style="color:red">{{__("Out Stock")}}</span>
											@endif
										</strong>
									</label>
							</div>
							@if($products->status == 1)
								@if($products->price != 0 || $products->price_new != 0)
								<div class="col-lg-4 col-md-6">
									<div class="btn_add_to_cart"><button type="submit" class="btn_1">{{__("Add to Cart")}}</button></div>
								</div>
								<input type="hidden" name="products_id" value="{{$products->id}}" />
								@endif
							@endif
						</div>
					</div>
				</form>
				<!-- /prod_info -->
				<div class="row">
					<div class="product_actions">
						<ul>
							<li>
								@if(Auth::check())
								<?php 
									$count = $wishlist->countWishlist($products->id);
								?>
								<a href="javascript:void(0)" data-productid="{{$products->id}}" class="tooltip-1 wishlistDetail" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
									@if($count >0)
									<i class="fa-solid fa-heart" style="color:red"></i>
									@else
									<i class="fa-regular fa-heart"></i>
									@endif	
									<span>{{__('Add to favorites')}}</span>
								</a>
								@else
								<a href="/signin_signup" data-productid="{{$products->id}}" class="tooltip-1 wishlist" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
									<i class="fa-regular fa-heart"></i>
									<span>{{__('Add to favorites')}}</span>
								</a>
								@endif
							</li>
							<li>
								<a href="#"><i class="ti-control-shuffle"></i><span>{{__("Add to Compare")}}</span></a>
							</li>
						</ul>
					</div>
					@if(Auth::check())
					<div id="reviews" class="col-lg-5 col-md-6 mt-3">
						<a href="#Reviews" data-bs-toggle="collapse" data-bs-target="#Reviews" aria-expanded="false" aria-controls="Reviews">
							<i class="ti-star"></i><span> {{__("Leave a review")}}</span>
						</a>
					</div>
					@include('web.pages.products.reviews')
					@endif
				</div>
				<!-- /product_actions -->
			</div>
		</div>
		<!-- /row -->
	</div>
	<!-- /container -->

	<div class="tabs_product">
		<div class="container">
			<ul class="nav nav-tabs" role="tablist">
				<li class="nav-item">
					<a id="tab-A" href="#pane-A" class="nav-link active" data-bs-toggle="tab" role="tab">{{__("Description")}}</a>
				</li>
				<li class="nav-item">
					<a id="tab-B" href="#pane-B" class="nav-link" data-bs-toggle="tab" role="tab">{{__("Reviews")}}</a>
				</li>
			</ul>
		</div>
	</div>
	<!-- /tabs_product -->
	<div class="tab_content_wrapper">
		<div class="container">
			<div class="tab-content" role="tablist">
				<div id="pane-A" class="card tab-pane fade active show" role="tabpanel" aria-labelledby="tab-A">
					<div class="card-header" role="tab" id="heading-A">
						<h5 class="mb-0">
							<a class="collapsed" data-bs-toggle="collapse" href="#collapse-A" aria-expanded="false" aria-controls="collapse-A">
								Description
							</a>
						</h5>
					</div>
					<div id="collapse-A" class="collapse" role="tabpanel" aria-labelledby="heading-A">
						<div class="card-body">
							<div class="row justify-content-center ">

								{!! $products->content !!}
								@if(isset($products->youtube_path))
								<iframe style="height: 700px;" width="100%" src="https://www.youtube.com/embed/{{$products->youtube_path}} " title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
								@endif
							</div>
						</div>
					</div>
				</div>
				<!-- /TAB A -->


				<!-- Review -->
				<div id="pane-B" class="card tab-pane fade" role="tabpanel" aria-labelledby="tab-B">
					<div class="card-header" role="tab" id="heading-B">
						<h5 class="mb-0">
							<a class="collapsed" data-bs-toggle="collapse" href="#collapse-B" aria-expanded="false" aria-controls="collapse-B">
								{{__("Reviews")}}
							</a>
						</h5>
					</div>
					@if(count($reviews)!=0)
					@foreach($reviews as $review)
					<div id="collapse-B" class="collapse" role="tabpanel" aria-labelledby="heading-B" style="margin-top: -30px;margin-left: 15px;">
						<div class="card-body">
							<div class="row">
								<div class="col-lg-12">
									<div class="review_content">
										<span>{{$review->users->firstname}}</span>
										<div class="clearfix add_bottom_10">
											<span>
												<?php
												for ($i = 0; $i < 5; $i++) {
													if (($review->rate - $i) > 0.5) {
												?>
														<i class="fa-solid fa-star" style="color:#d0011b"></i>
													<?php

													} else if (($review->rate - $i) == 0.5) {
													?>
														<i class="fa-solid fa-star-half-stroke" style="color:#d0011b"></i>
													<?php

													} else if (($review->rate - $i) < 0.5) {
													?>
														<i class="fa-regular fa-star" style="color:#d0011b"></i>
												<?php
													}
												}
												?>
												<i> {{$review->rate}}/5</i>
											</span>
											<em>{{$review->updated_at->format('d-m-Y - H:i A')}}</em>
										</div>
										<p>{{$review->content}}</p>
										<hr />
									</div>
								</div>
							</div>
						</div>
					</div>
					@endforeach
					@else
					<div id="collapse-B" class="collapse" role="tabpanel" aria-labelledby="heading-B">
						<div class="card-body">
							<h1 class="text-center">
								{{__("There are no reviews for this product yet")}}
							</h1>
						</div>
					</div>
					@endif
				</div>
				<!-- /tab B -->
			</div>
			<!-- /tab-content -->
		</div>
		<!-- /container -->
	</div>
	<!-- /tab_content_wrapper -->

	<div class="container margin_60_35">
		<div class="main_title">
			<h2>{{__("Related")}}</h2>
			<span>{{__("Products")}}</span>
		</div>
		<div class="owl-carousel owl-theme products_carousel">
			@foreach($related as $re)
			@foreach($re->ProductsImage as $img)
			@if($loop->first)
			<div class="item">
				<div class="grid_item">
					@if(isset($re->price_new) && isset($re->price) && $re->price != 0 && $re->price_new != 0)
					<span class="ribbon off">-{{round((($re->price - $re->price_new)/$re->price)*100,0) }} %</span>
					@elseif($re->featured_product == 1)
					<span class="ribbon hot">Hot</span>
					@else
					<span class="ribbon new">New</span>
					@endif
					<figure>
						<a href="/detail/{{$re->id}}">
							@if(strstr($img->image,"https") == "")
							<img class="img-fluid lazy" style="width:290px; height: 290px;" data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg" alt="">
							@else
							<img class="img-fluid lazy" style="width:290px; height: 290px;" data-src="{{$img->image}}" alt="">
							@endif
						</a>
					</figure>
					<!-- <div class="rating"><i class="icon-star voted"></i><i class="icon-star voted"></i><i class="icon-star voted"></i><i class="icon-star voted"></i><i class="icon-star"></i></div> -->
					<a href="product-detail-1.html">
						<h3 class="d-block" style="max-width: 270px; height:50px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">{{$re->name}}</h3>
					</a>
					<div class="price_box">
						@if($re->price_new!=0 && $re->price!=0)
						<span class="new_price">{{number_format($re->price_new,0,",",".")}} Vnđ</span>
						<span class="old_price">{{number_format($re->price,0,",",".")}} Vnđ</span>

						@elseif($re->price_new==0 && $re->price!=0)
						<span class="new_price">{{number_format($re->price,0,",",".")}} Vnđ</span>

						@elseif($re->price==0 && $re->price_new!=0)
						<span class="new_price">{{number_format($re->price_new,0,",",".")}} Vnđ</span>

						@else
						<span class="new_price">{{__("Contact")}}</span>

						@endif
					</div>
					<ul>
						<li>
							@if(Auth::check())
							<?php 
								$count = $wishlist->countWishlist($re->id);
							?>
							<a href="javascript:void(0)" data-productid="{{$re->id}}" class="tooltip-1 wishlist" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
								@if($count >0)
								<i class="fa-solid fa-heart" style="color:red"></i>
								@else
								<i class="fa-regular fa-heart"></i>
								@endif	
								<span>{{__('Add to favorites')}}</span>
							</a>
							@else
							<a href="/signin_signup" data-productid="{{$re->id}}" class="tooltip-1 wishlist" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
								<i class="fa-regular fa-heart"></i>
								<span>{{__('Add to favorites')}}</span>
							</a>
							@endif
						</li>
						<li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Add to compare"><i class="ti-control-shuffle"></i><span>Add to compare</span></a></li>
						@if($re->price || $re->price_new)
							<li>
								<form action="/cart" method="post" id="formSubmitCart_{{$re->id}}">
									@csrf
									<a href="javascript:void(0)"  onclick="document.getElementById('formSubmitCart_{{$re->id}}').submit();" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to cart')}}"><i class="ti-shopping-cart"></i><span>{{__('Add to cart')}}</span></a>
									<input type="hidden" name="products_id" value="{{$re->id}}"/>
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
			<!-- /item -->
		</div>
		<!-- /products_carousel -->
	</div>
	<!-- /container -->

</main>
@endsection
@section('scripts')
<script src="web_assets/js/carousel_with_thumbs.js"></script>
<script>
		$('.wishlistDetail').click(function(){
			$.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
			var user_id = "{{Auth::id()}}"
			var product_id = $(this).data('productid');
			$.ajax({
				type: 'POST',
				url: '/wishlist',
				data:{
					product_id:product_id,
					user_id:user_id
				},
				success: function (data) {
					if(data.action == 'add'){
						totalWishlist();
                        $('a[data-productid=' + product_id + ']').html('<i class="fa-solid fa-heart" style="color:red"></i>').append(`<span>{{__('Add to favorites')}}</span>`);
					}else if (data.action == 'delete'){
						totalWishlist();
                        $('a[data-productid=' + product_id + ']').html('<i class="fa-regular fa-heart"></i>').append(`<span>{{__('Add to favorites')}}</span>`);
						
					}
				}
			})
		});
</script>
@endsection