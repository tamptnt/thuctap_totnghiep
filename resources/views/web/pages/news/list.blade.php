@extends('web.layout.index')
@section('css')
<link href="web_assets/css/blog.css" rel="stylesheet">
@endsection
@section('content')
<main class="bg_gray">
    <div class="container margin_30">
        <div class="page_header">
            <div class="breadcrumbs">
                <ul>
                    <li><a href="/">Home</a></li>
                    <li>{{__("News")}}</li>
                </ul>
            </div>
            <h1>{{__("News")}}</h1>
        </div>
        <!-- /page_header -->
        <div class="row">
            <div class="col-lg-12">
                <div class="widget search_blog d-block d-sm-block d-md-block d-lg-none">
                    <div class="form-group">
                        <input type="text" name="search" id="search" class="form-control" placeholder="Search..">
                        <button type="submit"><i class="ti-search"></i></button>
                    </div>
                </div>
                <!-- /widget -->
                <div class="row">
                    @foreach($news as $new)
                    <div class="col-md-6" >
                        <article class="blog">
                            <figure>
                                <a href="/news/{{$new->id}}">
                                    <img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$new->image}}.jpg" alt="" style="width:600px; height:400px">
                                    <div class="preview"><span>{{__("Read more")}}</span></div>
                                </a>
                            </figure>
                            <div class="post_info" style="width:431.25px; height:261.38px">
                                <small>{{__("News")}} - {{$new->updated_at->format('d.m.Y')}}</small>
                                <h2 style="width: 550px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"><a href="/news/{{$new->id}}">{{$new->title}}</a></h2>
                                <p style="width: 550px; height:60px; overflow: hidden;text-overflow: ellipsis;white-space: normal;">
								{!! strip_tags($new->content) !!}
							    </p>
                                <ul>
                                    <li>
                                        <div class="thumb">
                                            @if(isset($new->users->image))
                                            <img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$new->users->image}}.jpg" alt="">
                                            @else
                                            <img src='images/avatar/avatar.png'>
                                            @endif
                                        </div> 
                                        {{$new->users->firstname}}
                                    </li>
                                    <li></li>
                                </ul>
                            </div>
                        </article>
                        <!-- /article -->
                    </div>
                    @endforeach
                    <!-- /col -->
                </div>
                <!-- /row -->

                <div class="pagination__wrapper no_border add_bottom_30">
                    <ul class="pagination">
                    {{$news->links()}}
                    </ul>
                </div>
                <!-- /pagination -->

            </div>
            <!-- /col -->
   
            <!-- /aside -->
        </div>
        <!-- /row -->
    </div>
    <!-- /container -->
</main>
@endsection