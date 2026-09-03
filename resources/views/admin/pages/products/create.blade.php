<div class="modal fade" id="products_create" tabindex="-1" role="dialog"
    aria-labelledby="products_createTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="products_createTitle">{{__("Create")}} {{__("Products")}}</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                <i data-feather="x"></i>
                </button>
            </div>
            <form action="admin/products/create" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <label>{{__("Categories")}}: </label>
                    <div class="form-group">
                        <select name="cat_id" class="form-control form-control-primary category" >
                            @foreach($categories as $cat)
                            <option value="{{$cat->id}}">{{$cat->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <label>{{__("SubCategories")}}: </label>
                    <div class="form-group">
                        <select name="sub_id" class="form-control form-control-primary subcategory" >
                            @foreach($subcategories as $sub)
                            <option value="{{$sub->id}}">{{$sub->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <label>{{__("Brands")}}: </label>
                    <div class="form-group">
                        <select name="brands_id" class="form-control form-control-primary" id="brands">
                            @foreach($brands as $brand)
                            <option value="{{$brand->id}}">{{$brand->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <label>{{__("Name")}}: </label>
                    <div class="form-group">
                        <input type="text" placeholder="{{__('Type')}}...." class="form-control" name="name">
                    </div> 

                    <label>{{__("Image")}}: </label>
                    <div class="form-group ">
                        <input type="file" name="ProductsImage[]" class="form-control " multiple>
                        <img style="width: 400px" src="" class="d-none" alt="user1">
                    </div>

                    <label>Video: </label>
                    <div class="form-group">
                        <input type="text" placeholder="https://www.youtube.com/watch?v=" class="form-control" name="youtube_path">
                    </div>

                    <label>{{__("Price")}}: </label>
                    <div class="form-group">
                        <input class="form-control" type="number" name="price"/>
                    </div>
                    <label>{{__("New Price")}}: </label>
                    <input type="checkbox" class="checkPrice" name="changeprice">
                    <div class="form-group">
                        <input class="new_price form-control" type="number" name="price_new" disabled/>
                    </div>

                    <label>{{__("Quantity")}}: </label>
                    <div class="form-group">
                        <input class="form-control" type="number" name="quantity"/>
                    </div>

                    <label>{{__("Content")}}: </label>
                    <div class="form-group">
                        <textarea class="form-control content" name="content" ></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">
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