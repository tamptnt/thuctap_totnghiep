<div class="modal fade text-left" id="discounts_create" tabindex="-1" role="dialog" aria-labelledby="discounts_create" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="discounts_create">{{__("Create")}} {{__("Discounts")}}</h5>
                <button type="button" class="close rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <form action="admin/discounts/create" method="post">
                @csrf
                <div class="modal-body">
                    <label>{{__("Name")}}: </label>
                    <div class="form-group">
                        <input type="text" placeholder="{{__('Type')}}...." class="form-control" name="name">
                    </div>
                    <label>{{__("Code")}}: </label>
                    <div class="form-group">
                        <input type="text" placeholder="{{__('Type')}}...." class="form-control" name="code">
                    </div>
                    <label>{{__("Percent")}}: </label>
                    <div class="form-group">
                        <input type="number" placeholder="{{__('Type')}}...." class="form-control" name="percent">
                    </div>
                    <label>{{__("Quantity")}}: </label>
                    <div class="form-group">
                        <input type="number" placeholder="{{__('Type')}}...." class="form-control" name="quantity">
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