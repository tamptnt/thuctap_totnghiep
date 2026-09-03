@extends('admin/layout/index')
@section('manage_products')
active
@endsection
@section('content')
<div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <form method="post" action="/admin/products/edit/{{$products->id}}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary ml-1 float-end ">{{__("Edit")}}</button>
                            <br>
                            <br>
                            <div class="row">
                                <div class="col-md-4">
                                    <label>{{__("Categories")}}: </label>
                                    <div class="form-group">
                                        <select name="cat_id" class="form-control form-control-primary category" >
                                            @foreach($categories as $cat)
                                            <option
                                                @if($products->cat_id == $cat->id)
                                                selected 
                                                @endif
                                            value="{{$cat->id}}">{{$cat->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("SubCategories")}}: </label>
                                    <div class="form-group">
                                        <select name="sub_id" class="form-control form-control-primary subcategory" >
                                            @foreach($subcategories as $sub)
                                            <option 
                                                @if($products->sub_id == $sub->id)
                                                selected
                                                @endif
                                            value="{{$sub->id}}">{{$sub->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label>{{__("Brands")}}: </label>
                                    <div class="form-group">
                                        <select name="brands_id" class="form-control form-control-primary" id="brands">
                                            @foreach($brands as $brand)
                                            <option 
                                                @if($products->brands_id == $brand->id)
                                                selected
                                                @endif
                                            value="{{$brand->id}}">{{$brand->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                             
                                <div class="col-md-12">
                                    <label>{{__("Name")}}: </label>
                                    <div class="form-group">
                                        <input type="text" value="{{$products->name}}" class="form-control" name="name">
                                    </div>
                                </div>
                               

                                <div class="col-md-6">
                                     <label>Video: </label>
                                     <div class="form-group">
                                        <input type="text" placeholder="https://www.youtube.com/watch?v=" value="{{$products->youtube_path}}" class="form-control" name="youtube_path">
                                        @if(isset($products->youtube_path)) 
                                        <iframe style="height: 400px;" width="700px" src="https://www.youtube.com/embed/{{$products->youtube_path}}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                        @endif
                                     </div>
                                </div>

                                <div class="col-md-7">
                                    <label>{{__("Image")}}: </label>
                                    <input type="file" name="ProductsImage[]" class="form-control" multiple>
                                    <div class="form-group file-uploader-library">
                                        <div class="row">
                                            @foreach($products->ProductsImage as $img)
                                            <div class="col-md-6">
                                                @if(strstr($img->image,"https") == "")
                                                <div>
                                                    <a href="javascript:void(0)" data-url="{{ url('admin/products/deleteimages', $img->id ) }}" class="btn text-danger delete-image">X</a> 
                                                    <img style="width: 370px; height: 370px;"class="img_products_library mb-2" src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{ $img->image }}.jpg" alt="">
                                                </div>
                                                @else
                                                <div>
                                                    <a href="javascript:void(0)" data-url="{{ url('admin/products/deleteimages', $img->id ) }}" class="btn text-danger delete-image">X</a> 
                                                    <img style="width: 370px; height: 370px;" class="img_products_library mb-2" src="{{$img->image}}" alt="">
                                                </div>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label>{{__("Price")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="number" name="price" value="{{$products->price}}"/>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label>{{__("New Price")}}: </label>
                                    <input type="checkbox" class="checkPrice" name="changeprice">
                                    <div class="form-group">
                                        <input class="new_price form-control" type="number" name="price_new" value="{{$products->price_new}}" disabled/>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label>{{__("Quantity")}}: </label>
                                    <div class="form-group">
                                        <input class="form-control" type="number" name="quantity" value="{{$products->quantity}}"/>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label>{{__("Content")}}: </label>
                                    <div class="form-group">
                                        <textarea class="content form-control" name="content" >{{$products->content}}</textarea>
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
<script>
$(document).ready(function(){
    $('.checkPrice').change(function(){
    if($(this).is(":checked"))
    {
        $('.new_price').removeAttr('disabled');
    }
    else 
    {
        $('.new_price').attr('disabled','');
    }
    });

    

    //change category to subcategory create
    $(".category").change(function(){
        var cat_id = $(this).val();
        $.get("admin/products/subcategory/"+cat_id,function(data){
            $(".subcategory").html(data);
        });
    });

});
</script>
<script>
     $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('.delete-image').on('click', function() {
            var userURL = $(this).data('url');
            var trObj = $(this);
            if (confirm("Are you sure you want to remove it?") == true) {
                $.ajax({
                    url: userURL,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(data) {
                        if (data['success']) {
                            alert(data.success);
                            trObj.parent().remove();
                        } else if (data['error']) {
                            alert(data.error);
                        }
                    }
                });
            }

        });
    });
</script>
@endsection