@extends('admin/layout/index')
@section('info')
active
@endsection
@section('css')
<link rel="stylesheet" href="admin_assets/vendors/simple-datatables/style.css">
@endsection
@section('content')
<div class="main-content container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>{{__("News")}}</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class='breadcrumb-header'>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin">{{__("Dashboard")}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__("News")}}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header">
            <button type="button" class="btn btn-outline-primary block" data-bs-toggle="modal" data-bs-target="#news_create">
            {{__("Create")}}
            </button>
            @include('admin/pages/news/create')
            </div>
            <div class="card-body">
                <table class='table table-striped' id="table1">
                    <thead>
                        <tr>
                            <th class="text-center">Id</th>
                            <th class="text-center">{{__("Title")}}</th>
                            <th class="text-center">{{__("Image")}}</th>
                            <th class="text-center">{{__("Content")}}</th>
                            <th class="text-center">{{__("Staff")}}</th>
                            <th class="text-center">{{__("Status")}}</th>
                            <th class="text-center">{{__("Operations")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($news as $key => $new)
                        <tr>
                            <td class="text-center">{{$key+1}}</td>
                            <td class="text-center">
                                <span style="max-width:170px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical"> 
                                    {{$new->title}}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($new->image == NULL)
                                    <img style="width: 300px">
                                @else
                                    @if(strstr($new->image,"https") == "")
                                        <img style="width: 300px" src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{ $new->image }}.jpg" >
                                    @elseif(strstr($new->image,"https") != "")
                                        <img style="width: 300px" src="{{ $new->image }}" >
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <span style="max-width:250px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical"> 
                                {!! $new->content !!}
                                </span>
                            </td>
                            <td class="text-center">
                                {{$new->users->firstname}}
                            </td>
                            <td id="status{{$new->id}}">
                                @if($new->status == 1)
                                <a href="javascript:void(0)" onclick="status({{$new->id}},0)"><span class="badge bg-success">{{__("Published")}}</span></a>
                                @else
                                <a href="javascript:void(0)" onclick="status({{$new->id}},1)"><span class="badge bg-danger">{{__("Pending")}}</span></a>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="admin/news/edit/{{$new->id}}">
                                    <i data-feather="edit"></i>
                                </a>
                             <a href="admin/news/delete/{{$new->id}}" onclick="return confirm(`{{__('Are you sure you want to delete this ?')}}`)">
                                    <i data-feather="trash-2" stroke="red"></i>
                             </a> 
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </section>
</div>
@endsection
@section('scripts')
<script src="admin_assets/vendors/simple-datatables/simple-datatables.js"></script>
<script src="admin_assets/js/vendors.js"></script>
<script>
    function status(status_id, active) {
        if (active === 1) {
            $("#status" + status_id).html(' <a href="javascript:void(0)" onclick="status(' + status_id + ',0)">\
                <span class="badge bg-success">{{__("Published")}}</span>\
            </a>')
        } else {
            $("#status" + status_id).html(' <a href="javascript:void(0)" onclick="status(' + status_id + ',1)">\
                <span class="badge bg-danger">{{__("Pending")}}</span>\
            </a>')
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: "/admin/news/status",
            type: 'GET',
            dataType: 'json',
            data: {
                'active': active,
                'status_id': status_id
            },
            success: function(data) {
                if (data['success']) {
                    // alert(data.success);
                } else if (data['error']) {
                    alert(data.error);
                }
            }
        });
    }
</script>
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
    .create(document.querySelector('.content'))
    .then(content => {
        // console.log( content );
    })
    .catch(error => {
        // console.error( error );
    });
</script>

@endsection