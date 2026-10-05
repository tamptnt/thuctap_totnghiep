<div style="background-color: #ffffff">
    <div class="container margin_30">
        <div id="brands" class="owl-carousel owl-theme">
            @foreach($brands as $brand)
            @if($brand->image !=null)
                <div class="item">
                    <a href="/brands/{{$brand->id}}"><img style="width:100px; height:50px; " src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$brand->image}}.jpg" data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$brand->image}}.jpg" alt="" class="owl-lazy"></a>
                </div><!-- /item -->
            @endif
            @endforeach
        </div><!-- /carousel -->
    </div><!-- /container -->
</div>