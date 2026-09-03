<div class="modal fade text-left" id="categories_edit{{$cat->id}}" tabindex="-1" role="dialog" aria-labelledby="categories_edit"
                        aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="categories_edit">{{__("Edit")}} {{__("Categories")}}</h5>
                                        <button type="button" class="close rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                                            <i data-feather="x"></i>
                                        </button>
                                    </div>
                                    <form action="admin/categories/edit/{{$cat->id}}" method="post">
                                        @csrf
                                        <div class="modal-body">
                                            <label>{{__("Name")}}: </label>
                                            <div class="form-group">
                                            <input type="text" class="form-control" name="name" value="{{$cat->name}}">
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