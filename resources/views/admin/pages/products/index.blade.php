@extends('admin/layout/index')
@section('manage_products')
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
                <h3>{{__("Products")}}</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class='breadcrumb-header'>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin">{{__("Dashboard")}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__("Products")}}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header">
                <button type="button" class="btn btn-outline-primary block" data-bs-toggle="modal"
                    data-bs-target="#products_create">
                    {{__("Create")}}
                </button>
                @include('admin/pages/products/create')
            </div>
            <div class="card-body">
                <table class='table table-striped' id="table1">
                    <thead>
                        <tr>
                            <th class="text-center">Id</th>
                            <th class="text-center text-nowrap">{{__("Image")}}</th>
                            <th class="text-center text-nowrap">{{__("Name")}}</th>
                            <th class="text-center text-nowrap">{{__("Categories")}}</th>
                            <th class="text-center text-nowrap">{{__("SubCategories")}}</th>
                            <th class="text-center text-nowrap">{{__("Brands")}}</th>
                            <th class="text-center text-nowrap">{{__("Staff")}}</th>
                            <th class="text-center text-nowrap">{{__("Featured")}}</th>
                            <th class="text-center text-nowrap">{{__("Status")}}</th>
                            <th class="text-center text-nowrap">{{__("Operations")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $key => $pro)
                        <tr>
                            <td class="text-center">{{$key+1}}</td>
                            <td class="text-center">
                                @foreach($pro->ProductsImage as $img)
                                @if($loop ->first)
                                @if($img->image == NULL)
                                <img style="width: 150px">
                                @else
                                @if(strstr($img->image,"https") == "")
                                <img style="width: 150px"
                                    src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$img->image}}.jpg">
                                @elseif(strstr($img->image,"https") != "")
                                <img style="width: 150px" src="{{$img->image}}">
                                @endif
                                @endif
                                @endif
                                @endforeach
                            </td>
                            <td class="text-truncate" style="max-width: 130px;">{{$pro->name}}</td>
                            <td class="text-center">{{$pro->categories->name}}</td>
                            <td class="text-center">{{$pro->subcategories->name}}</td>
                            <td class="text-center">{{$pro->brands->name}}</td>
                            <td class="text-center">{{$pro->users->firstname ?? ''}}</td>
                            <td class="text-center" id="featured{{$pro->id}}">
                                @if($pro->featured_product == 1)
                                <a href="javascript:void(0)" onclick="featured({{$pro->id}},0)"><span
                                        class="badge bg-success">{{__("Published")}}</span></a>
                                @else
                                <a href="javascript:void(0)" onclick="featured({{$pro->id}},1)"><span
                                        class="badge bg-danger">{{__("Pending")}}</span></a>
                                @endif
                            </td>
                            <td class="text-center" id="status{{$pro->id}}">
                                @if($pro->status == 1)
                                <a href="javascript:void(0)" onclick="status({{$pro->id}},0)"><span
                                        class="badge bg-success">{{__("Published")}}</span></a>
                                @else
                                <a href="javascript:void(0)" onclick="status({{$pro->id}},1)"><span
                                        class="badge bg-danger">{{__("Pending")}}</span></a>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="admin/products/edit/{{$pro->id}}">
                                    <i data-feather="edit"></i>
                                </a>
                                <a href="admin/products/delete/{{$pro->id}}"
                                    onclick="return confirm(`{{__('Are you sure you want to delete this ?')}}`)">
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
$(document).ready(function() {
    $('.checkPrice').change(function() {
        if ($(this).is(":checked")) {
            $('.new_price').removeAttr('disabled');
        } else {
            $('.new_price').attr('disabled', '');
        }
    });



    //change category to subcategory create ©Ph
    $(".category").change(function() {
        var cat_id = $(this).val();
        $.get("admin/products/subcategory/" + cat_id, function(data) {
            $(".subcategory").html(data);
        });
    });

});
</script>
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
        url: "/admin/products/status",
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
function featured(featured_id, active) {
    if (active === 1) {
        $("#featured" + featured_id).html(' <a href="javascript:void(0)" onclick="featured(' + featured_id + ',0)">\
                <span class="badge bg-success">{{__("Published")}}</span>\
            </a>')
    } else {
        $("#featured" + featured_id).html(' <a href="javascript:void(0)" onclick="featured(' + featured_id + ',1)">\
                <span class="badge bg-danger">{{__("Pending")}}</span>\
            </a>')
    }
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.ajax({
        url: "/admin/products/featured",
        type: 'GET',
        dataType: 'json',
        data: {
            'active': active,
            'featured_id': featured_id
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
<script src="https://cdn.ckeditor.com/ckeditor5/35.2.1/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create(document.querySelector('.content'))
    .then(content => {//©Ph
        // console.log( content );
    })
    .catch(error => {
        // console.error( error );
    });
</script>
@endsection