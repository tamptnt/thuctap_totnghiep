<div class="modal fade text-left" id="subcategories_edit{{$sub->id}}" tabindex="-1" role="dialog" aria-labelledby="subcategories_edit"
                        aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="subcategories_edit">{{__("Edit")}} {{__("SubCategories")}}</h5>
                                        <button type="button" class="close rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                                            <i data-feather="x"></i>
                                        </button>
                                    </div>
                                    <form action="admin/subcategories/edit/{{$sub->id}}" method="post">
                                        @csrf
                                        <div class="modal-body">
                                            <label>{{__("Categories")}}: </label>
                                                <div class="form-group">
                                                    <select name="cat_id" class="form-control form-control-primary">
                                                        @foreach($categories as $cat)
                                                        <option 
                                                            @if($sub->categories->id == $cat->id)
                                                            selected
                                                            @endif
                                                        value="{{$cat->id}}">{{$cat->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            <label>{{__("Name")}}: </label>
                                                <div class="form-group">
                                                    <input type="text" class="form-control" name="name" value="{{$sub->name}}">
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