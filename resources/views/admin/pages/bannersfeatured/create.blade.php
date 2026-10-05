<div class="modal fade text-left" id="bannersfeatured_create" tabindex="-1" role="dialog" aria-labelledby="bannersfeatured_create"
    aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bannersfeatured_create">{{__("Create")}} {{__("Banners Collection")}}</h5>
                    <button type="button" class="close rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                        <i data-feather="x"></i>
                    </button>
                </div>
                <form action="admin/bannersfeatured/create" method="post" enctype="multipart/form-data">
                    @csrf
                <div class="modal-body">
                    <label>{{__("Name")}}: </label>
                    <div class="form-group">
                        <input type="text" placeholder="{{__('Type')}}...." class="form-control" name="name" >
                    </div>

                    <label>{{__("Image")}}: </label>
                    <div class="form-group file-uploader">
                        <input type="file" name="Image" class="form-control image-bannersfeatured">
                        <img style="width: 400px" src="" class="img_bannersfeatured d-none" alt="user1">
                    </div>

                    <label>{{__("Link")}}: </label>
                    <div class="form-group">
                        <input type="text" placeholder="{{__('Type')}}...." class="form-control" name="link" >
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