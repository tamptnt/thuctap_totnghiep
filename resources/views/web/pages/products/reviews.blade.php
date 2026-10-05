<form action="/reviews" method="POST">
    @csrf
    <div class="collapse" id="Reviews">
        <div class="card card-body">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="write_review">
                            <div class="rating_submit">
                                <div class="form-group">
                                    <label class="d-block">{{__("Overall rating")}}</label>
                                    <span class="rating mb-0">
                                        <input type="radio" class="rating-input" id="5_star" name="rate" value="5"><label for="5_star" class="rating-star"></label>
                                        <input type="radio" class="rating-input" id="4_star" name="rate" value="4"><label for="4_star" class="rating-star"></label>
                                        <input type="radio" class="rating-input" id="3_star" name="rate" value="3"><label for="3_star" class="rating-star"></label>
                                        <input type="radio" class="rating-input" id="2_star" name="rate" value="2"><label for="2_star" class="rating-star"></label>
                                        <input type="radio" class="rating-input" id="1_star" name="rate" value="1"><label for="1_star" class="rating-star"></label>
                                        <input type="hidden" name="products_id" value="{{$products->id}}"/>
                                    </span>
                                </div>
                            </div>
                            <!-- /rating_submit -->
                            <div class="form-group">
                                <label>{{__("Your review")}}</label>
                                <textarea name="content" class="form-control" style="height: 180px;" placeholder="{{__('Write your review to help others learn about this online business.')}}"></textarea>
                            </div>
                            <button type="submit" class="btn_1">{{__("Submit review")}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
