<style>
	#carousel-home .owl-carousel .owl-slide, #carousel-home-2 .owl-carousel .owl-slide {
  height: 730px;
  position: relative;
}
</style>
<div id="carousel-home">
			<div class="owl-carousel owl-theme">
				@foreach($banners as $banner)
				<div class="owl-slide cover"
				@if($banner->image == NULL)
					style="background-image:url('web_assets/img/slides/slide_home_2.jpg')"
				@else
					@if(strstr($banner->image,"https") == "")
						style="background-image:url('https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$banner->image}}.jpg')"
					@elseif(strstr($banner->image,"https") != "")
						style="background-image:url('{{$banner->image}}')"
					@endif
				@endif
				>
				</div>
				@endforeach
			</div>
			<div id="icon_drag_mobile"></div>
		</div>
		<!--/carousel-->

		<ul id="banners_grid" class="clearfix">
			@foreach($bannersfeatured as $bf)
			<li>
				<a href="{{$bf->link}}" class="img_container">
					@if(strstr($banner->image,"https") == "")
					<img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$bf->image}}.jpg" data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$bf->image}}.jpg" alt="" class="lazy">
					@else 
					<img src="{{$bf->image}}" data-src="{{$bf->image}}" alt="" class="lazy">
					@endif
					<div class="short_info opacity-mask" data-opacity-mask="rgba(0, 0, 0, 0.5)">
						<h3>{{$bf->name}}</h3>
						<div><span class="btn_1">Shop Now</span></div>
					</div>
				</a>
			</li>
			@endforeach
		</ul>