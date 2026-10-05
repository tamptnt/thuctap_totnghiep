<div class="modal fade text-left" id="news_create" tabindex="-1" role="dialog" aria-labelledby="news_create"
    aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="news_create">{{__("Create")}} {{__("News")}}</h5>
                    <button type="button" class="close rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                        <i data-feather="x"></i>
                    </button>
                </div>
                <form action="admin/news/create" method="post" enctype="multipart/form-data">
                    @csrf
                <div class="modal-body">
                    <label>{{__("Title")}}: </label>
                    <div class="form-group">
                        <input type="text" placeholder="{{__('Type')}}...." class="form-control" name="title" >
                    </div>

                    <label>{{__("Image")}}: </label>
                    <div class="form-group file-uploader">
                        <input type="file" name="Image" class="form-control image-news">
                        <img style="width: 400px" src="" class="img_news d-none" alt="user1">
                    </div>

                    <label>{{__("Content")}}: </label>
                    <div class="form-group">
                        <textarea class="form-control content" name="content" ></textarea>
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