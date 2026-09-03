@extends('web.layout.index')
@section('css')
<link href="web_assets/css/blog.css" rel="stylesheet">
@endsection
@section('content')
<style>
#news-img img {
    width: 100%;
}
</style>
<main class="bg_gray">
    <div class="container margin_30">
        <div class="page_header">
            <div class="breadcrumbs">
                <ul>
                    <li><a href="/">Home</a></li>
                    <li>{{__("News")}}</li>
                </ul>
            </div>
        </div>
        <!-- /page_header -->
        <div class="row">
            <div class="col-lg-9">
                <div class="singlepost">
                    <figure><img alt="" class="img-fluid"
                            src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$news->image}}.jpg">
                    </figure>
                    <h1>{{$news->title}}</h1>
                    <div class="postmeta">
                        <ul>
                            <li><a href="/news/list"><i class="ti-folder"></i> {{__("News")}}</a></li>
                            <li><i class="ti-calendar"></i> {{$news->updated_at->format('d/m/Y')}}</li>
                            <li><a href="#"><i class="ti-user"></i> {{$news->users->firstname}}</a></li>
                        </ul>
                    </div>
                    <!-- /post meta -->
                    <div class="post-content" id="news-img">
                        <p>
                            {!! $news->content !!}
                        </p>
                    </div>
                    <!-- /post -->
                </div>
                <!-- /single-post -->

            </div>
            <!-- /col -->
            <aside class="col-lg-3">
                <div class="widget search_blog">
                    <div class="form-group">
                        <input type="text" name="search" id="search" class="form-control" placeholder="Search..">
                        <button type="submit"><i class="ti-search"></i></button>
                    </div>
                </div>
                <!-- /widget -->
                <div class="widget">
                    <div class="widget-title">
                        <h4>{{__("Latest News")}}</h4>
                    </div>
                    <ul class="comments-list">
                        @foreach($news_all->take(4) as $new)
                        <li>
                            <div class="alignleft">
                                <a href="/news/{{$new->id}}"><img
                                        src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$new->image}}.jpg"
                                        alt=""></a>
                            </div>
                            <small>{{__("News")}} - {{$new->updated_at->format('d.m.Y')}}</small>
                            <h3><a href="/news/{{$new->id}}" title="">{{$new->title}}</a></h3>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <!-- /widget -->
                <div class="widget">
                    <div class="widget-title">
                        <h4>{{__("Categories")}}</h4>
                    </div>
                    <ul class="cats">
                        @foreach($categories as $category)
                        <li><a href="#">{{$category->name}} <span>({{$category->Subcategories->count()}})</span></a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
            <!-- /aside -->
        </div>
        <!-- /row -->
    </div>
    <!-- /container -->
</main>
@endsection