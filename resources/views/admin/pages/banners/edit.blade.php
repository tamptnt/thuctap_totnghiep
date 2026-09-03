<div class="modal fade text-left" id="banners_edit{{$banner->id}}" tabindex="-1" role="dialog" aria-labelledby="banners_edit"
aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="banners_edit">{{__("Edit")}} {{__("Banners")}}</h5>
                <button type="button" class="close rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <form action="admin/banners/edit/{{$banner->id}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <label>{{__("Image")}}: </label>
                    <input type="file" name="Image" class="form-control image-banners">
                    <div class="form-group file-uploader">
                        @if(strstr($banner->image,"https") == "")
                        <img style="width: 400px" class="img_banners" src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{ $banner->image }}.jpg" alt="">
                        @else
                        <img style="width: 400px" class="img_banners" src="{{$banner->image}}" alt="">
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