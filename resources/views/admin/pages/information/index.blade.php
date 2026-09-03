@extends('admin/layout/index')
@section('info')
active
@endsection
@section('content')
<div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <form action="/admin/info" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary ml-1 float-end ">{{__("Edit")}}</button>
                            <br>
                            <br>
                            <div class="row">
                                <div class="col-md-10">
                                    <label>{{__("Image")}}: </label>
                                    <input type="file" name="Image" class="form-control image-info" multiple>
                                    <div class="form-group file-uploader">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <img style="width: 400px;" class="img_info mb-2" 
                                                @if(isset($info->logo))
                                                src="images/favicon/{{$info->logo}}" 
                                                @else
                                                src=""
                                                @endif
                                                alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("Name")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="name" value="{{$info->name}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("Phone")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="number" name="phone" value="{{$info->phone}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>Email: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="email" name="email" value="{{$info->email}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>Facebook: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="facebook" value="{{$info->facebook}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>Twitter: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="twitter" value="{{$info->twitter}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>Youtube: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="youtube" value="{{$info->youtube}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("Address")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="address" value="{{$info->address}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("Work time")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="worktime" value="{{$info->worktime}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("Copyright")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="copyright" value="{{$info->copyright}}"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("Tax")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="text" name="tax" value="{{$info->tax}}"/>
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
                $('.file-uploader .img_info').attr('src', e.target.result)
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(".image-info").change(function() {
        readURL(this);
    });
</script>
@endsection