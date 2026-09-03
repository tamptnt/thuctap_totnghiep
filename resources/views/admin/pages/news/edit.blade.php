@extends('admin/layout/index')
@section('info')
active
@endsection
@section('content')
<div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <form method="post" action="/admin/news/edit/{{$news->id}}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary ml-1 float-end ">{{__("Edit")}}</button>
                            <br>
                            <br>
                            <div class="row">
                                <div class="col-md-7">
                                    <label>{{__("Title")}}: </label>
                                    <div class="form-group">
                                        <input type="text" value="{{$news->title}}" class="form-control" name="title">
                                    </div>
                                </div>

                                <div class="col-md-7">
                                    <label>{{__("Image")}}: </label>
                                    <input type="file" name="Image" class="form-control image-news">
                                    <div class="form-group file-uploader">
                                        @if(strstr($news->image,"https") == "")
                                        <img style="width: 400px" class="img_news" src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{ $news->image }}.jpg" alt="">
                                        @else
                                        <img style="width: 400px" class="img_news" src="{{$news->image}}" alt="">
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label>{{__("Content")}}: </label>
                                    <div class="form-group">
                                        <textarea class="content form-control" name="content" >{{$news->content}}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script>
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.file-uploader .img_news').attr('src', e.target.result).removeClass('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(".image-news").change(function() {
        readURL(this);
    });
</script>

<script src="https://cdn.ckeditor.com/ckeditor5/35.2.1/classic/ckeditor.js"></script>
<script>
ClassicEditor
.create( document.querySelector( '.content' ) )
.then( content => {
        // console.log( content );
} )
.catch( error => {
        // console.error( error );
} );
</script>
@endsection