@extends('web.layout.index')
@section('css')
<link href="web_assets/css/listing.css" rel="stylesheet">
@endsection
@section('content')
<main>
	<div class="container margin_30">
		<div class="row small-gutters">
			@if(count($products)>0 )
			@foreach($products as $pro)
			@foreach($pro->ProductsImage as $img)
			@if($loop->first)
			<div class="col-6 col-md-4 col-xl-3">
				<div class="grid_item">
					@if(isset($pro->price_new) && isset($pro->price) && $pro->price != 0 && $pro->price_new != 0)
					<span class="ribbon off">-{{round((($pro->price - $pro->price_new)/$pro->price)*100,0) }} %</span>
					@elseif($pro->featured_product == 1)
					<span class="ribbon hot">Hot</span>
					@else
					<span class="ribbon new">New</span>
					@endif
					<figure>
						<a href="/detail/{{$pro->id}}">
							@if(strstr($img->image,"https") == "")
							<img style="width:290px; height: 290px;" class="img-fluid lazy" data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg" alt="">
							@else
							<img style="width:290px; height: 290px;" class="img-fluid lazy" data-src="{{$img->image}}" alt="">
							@endif
						</a>
						<!-- <div data-countdown="2019/05/15" class="countdown"></div> -->
					</figure>
					<a href="/detail/{{$pro->id}}">
						<h3 class="d-block" style="max-width: 270px; height:50px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">{{$pro->name}}</h3>
					</a>
					<div class="price_box">
						@if(isset($pro->price_new) && isset($pro->price))
						<span class="new_price">{{number_format($pro->price_new,0,",",".")}} Vnđ</span>
						<span class="old_price">{{number_format($pro->price,0,",",".")}} Vnđ</span>

						@elseif(!isset($pro->price_new) && isset($pro->price))
						<span class="new_price">{{number_format($pro->price,0,",",".")}} Vnđ</span>

						@elseif(!isset($pro->price) && isset($pro->price_new))
						<span class="new_price">{{number_format($pro->price_new,0,",",".")}} Vnđ</span>

						@else
						<span class="new_price">{{__("Contact")}}</span>

						@endif
					</div>
					<ul>
						<li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to favorites')}}"><i class="ti-heart"></i><span>{{__("Add to favorites")}}</span></a></li>
						<li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to compare')}}"><i class="ti-control-shuffle"></i><span>{{__("Add to compare")}}</span></a></li>
						<li><a href="#0" class="tooltip-1" data-bs-toggle="tooltip" data-bs-placement="left" title="{{__('Add to cart')}}"><i class="ti-shopping-cart"></i><span>{{__("Add to cart")}}</span></a></li>
					</ul>
				</div>
			</div>

			@endif
			@endforeach
			@endforeach
			@else
			<h1 class="text-center">{{__("No Products!")}}</h1>
			@endif
			<!-- /col -->
		</div>
		<!-- /row -->

		<div class="pagination__wrapper">
			<ul class="pagination">
				<li>
					{{$products->links()}}
				</li>
			</ul>
		</div>

	</div>
	<!-- /container -->
</main>
@endsection
@section('scripts')
<script src="web_assets/js/sticky_sidebar.min.js"></script>

<script src="web_assets/js/specific_listing.js"></script>
@endsection