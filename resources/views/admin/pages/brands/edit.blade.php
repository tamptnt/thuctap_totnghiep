<div class="modal fade text-left" id="brands_edit{{$brand->id}}" tabindex="-1" role="dialog" aria-labelledby="brands_edit"
aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="brands_edit">{{__("Edit")}} {{__("Brands")}}</h5>
                <button type="button" class="close rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <form action="admin/brands/edit/{{$brand->id}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <label>{{__("Name")}}: </label>
                    <div class="form-group">
                        <input type="text" class="form-control" name="name" value="{{$brand->name}}" >
                    </div>

                    <label>{{__("Image")}}: </label>
                    <input type="file" name="Image" class="form-control image-brands">
                    <div class="form-group file-uploader">
                        @if(strstr($brand->image,"https") == "")
                        <img style="width: 400px" class="img_brands" src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{ $brand->image }}.jpg" alt="">
                        @else
                        <img style="width: 400px" class="img_brands" src="{{$brand->image}}" alt="">
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">
                        <i class="bx bx-x d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">{{__("Close")}}</span>
                    </button>
                    <button type="submit" class="btn btn-primary ml-1" data-bs-dismiss="modal">
                        <i class="bx bx-check d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">{{__("Accept")}}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>