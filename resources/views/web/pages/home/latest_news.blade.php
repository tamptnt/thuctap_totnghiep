<div class="container margin_60_35">
				<div class="main_title">
					<a href="/news/list">
						<h2>{{__("News")}}</h2>
						<span>{{__("News")}}</span>
					</a>
					<!-- <p>Cum doctus civibus efficiantur in imperdiet deterruisset</p> -->
				</div>
				<div class="row">
					@foreach($news as $new)
					<div class="col-lg-6">
						<a class="box_news" href="/news/{{$new->id}}">
							<figure>
								<img data-src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$new->image}}.jpg" width="400" height="266" class="lazy">
								<figcaption><strong>{{$new->created_at->format('d')}}</strong>{{$new->created_at->format('M')}}</figcaption>
							</figure>
							<ul>
								<li>by {{$new->users->firstname}}</li>
								<li>{{$new->created_at->format('d.m.Y')}}</li>
							</ul>
							<h4>{{$new->title}}</h4>
							<p style="max-width: 300px; height:60px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">
								{!! strip_tags($new->content) !!}
							</p>
						</a>
					</div>
					<!-- /box_news -->
					@endforeach
				</div>
				<!-- /row -->
</div>