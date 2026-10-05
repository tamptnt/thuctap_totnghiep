<div class="container margin_60_35">
				<div class="main_title mb-4">
				<a href="/list">
					<h2>{{__("New Products")}}</h2>
					<span>{{__("New Products")}}</span>
				</a>
					<!-- <p>Cum doctus civibus efficiantur in imperdiet deterruisset.</p> -->
				</div>
				<div class="isotope-wrapper">
					<div class="row small-gutters">
						@foreach($products as $new)
							@foreach($new->ProductsImage as $img)
								@if($loop ->first)
									<div class="col-6 col-md-4 col-xl-3 isotope-item popular" style="max-height:440px">
										<div class="grid_item">
											<span class="ribbon new">New</span>
											<figure>
												<a href="/detail/{{$new->id}}">
													@if(strstr($img->image,"https") == "")
														<img style="width:270px; height:250px" class="img-fluid lazy" data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg" alt="">
														<img style="width:270px; height:250px" class="img-fluid lazy" data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg" alt="">
													@else
														<img style="width:270px; height:250px" class="img-fluid lazy" data-src="{{$img->image}}" alt="">
														<img style="width:270px; height:250px" class="img-fluid lazy" data-src="{{$img->image}}" alt="">
													@endif
												</a>
											</figure>
											<!-- <div class="rating"><i class="icon-star voted"></i><i class="icon-star voted"></i><i class="icon-star voted"></i><i class="icon-star voted"></i><i class="icon-star"></i></div> -->
											<a href="/detail/{{$new->id}}" >
												<h3 class="d-block" style="max-width: 270px; height:50px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">{{$img->name}}</h3>
											</a>
											<div class="price_box ">
												@if($new->price_new !=0 && $new->price !=0)
												<span class="new_price">{{number_format($new->price_new,0,",",".")}} Vnđ</span>
												<span class="old_price">{{number_format($new->price,0,",",".")}} Vnđ</span>

												@elseif($new->price_new == 0 && $new->price !=0)
												<span class="new_price">{{number_format($new->price,0,",",".")}} Vnđ</span>

												@elseif($new->price == 0 && $new->price_new !=0)
												<span class="new_price">{{number_format($new->price_new,0,",",".")}} Vnđ</span>

												@else
												<span class="new_price">{{__("Contact")}}</span>
												
												@endif
											</div>
											<ul>
												<li>
												@if(Auth::check())
													<?php 
														$count = $wishlist->countWishlist($new->id);
													?>
													<a href="javascript:void(0)" data-productid="{{$new->id}}" class="tooltip-1 wishlist" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
														@if($count >0)
															<i class="fa-solid fa-heart" style="color:red"></i>
														@else
															<i class="fa-regular fa-heart"></i>
														@endif	
														<span>{{__('Add to favorites')}}</span>
													</a>
												@else
													<a href="/signin_signup" data-productid="{{$new->id}}" class="tooltip-1 wishlist" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}">
														<i class="fa-regular fa-heart"></i>
														<span>{{__('Add to favorites')}}</span>
													</a>
												@endif
												</li>
												<li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to compare')}}"><i class="ti-control-shuffle"></i><span>{{__('Add to compare')}}</span></a></li>
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
						<!-- /col -->
					</div>
					<!-- /row -->
				</div>
				<!-- /isotope-wrapper -->
			</div>
			