@extends('admin.layout.index')

@section('content')
<style type="text/css">
    body {
        background: #f7f7ff;
    }

    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 0 solid transparent;
        border-radius: .25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 6px 0 rgb(218 218 253 / 65%), 0 2px 6px 0 rgb(206 206 238 / 54%);
    }

    .me-2 {
        margin-right: .5rem !important;
    }
</style>
<div class="container">
    <div class="main-body ">
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <form action="admin/imageProfile" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex flex-column align-items-center text-center">
                                @if($user->image == NULL)
                                <img src="images/avatar/avatar.png" class="rounded-circle p-1 bg-primary " width="110">
                                @else
                                    @if(strstr($user->image,"https") == "")
                                    <img src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$user->image}}.jpg" class="rounded-circle p-1 bg-primary mb-2" height="100" width="100">
                                    @else
                                    <img src="images/avatar/avatar.png" class="rounded-circle p-1 bg-primary" width="110">
                                    @endif
                                @endif
                                <input type="file" name="Image" class="form-control "/>
                                <div class="mt-3">
                                    @if(Session("language") == "en")
                                        <h4>{{$user->firstname}} {{$user->lastname}} </h4>
                                    @else
                                        <h4>{{$user->lastname}} {{$user->firstname}}</h4>
                                    @endif
                                    <p class="text-secondary mb-1">{{__("Username")}}: {{$user->username}}</p>
                                    <button type="submit" class="btn btn-outline-primary">{{__("Update Image")}}</button>
                                </div>
                            </div>
                        </form>
                        <hr class="my-4">
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <form action="admin/profile" method="POST">
                        @csrf
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-3 mt-2">
                                <h6>{{__("First Name")}}</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="text" name="firstname" class="form-control" value="{{$user->firstname}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 mt-2">
                                <h6>{{__("Last Name")}}</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="text" name="lastname" class="form-control" value="{{$user->lastname}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 mt-2">
                                <h6>Email</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="text" name="email" class="form-control" value="{{$user->email}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 mt-2">
                                <h6>{{__("Phone")}}</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="text" name="phone" class="form-control" value="{{$user->phone}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 mt-2">
                                <h6>{{__("Address")}}</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="text" name="address" class="form-control" value="{{$user->address}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 mt-2">
                                <h6 class="mb-0">{{__("District")}}</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="text" name="district" class="form-control" value="{{$user->district}}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 mt-2">
                                <h6 class="mt-1">{{__("City")}}</h6>
                            </div>
                            <div class="col-sm-9 mt-2">
                                <div class="custom-select-form">
                                    <select class="wide add_bottom_15 form-control" name="city" id="city">
                                        <option value="">{{__("City")}}</option>
                                        <option value="Hà Nội">Hà Nội</option>
                                        <option value="Hồ Chí Minh">Hồ Chí Minh</option>
                                        <option value="Đà Nẵng">Đà Nẵng</option>
                                        <option value="Cần Thơ">Cần Thơ</option>
                                        <option value="Hải Phòng">Hải Phòng</option>
                                        <option value="Nghệ An">Nghệ An</option>
                                        <option value="Bình Dương">Bình Dương</option>
                                        <option value="Quảng Ninh">Quảng Ninh</option>
                                        <option value="Thanh Hóa">Thanh Hóa</option>
                                        <option value="Đồng Nai">Đồng Nai</option>
                                        <option value="An Giang">An Giang</option>
                                        <option value="Lâm Đồng">Lâm Đồng</option>
                                        <option value="Thừa Thiên Huế">Thừa Thiên Huế</option>
                                        <option value="Đắk Lắk">Đắk Lắk</option>
                                        <option value="Bình Phước">Bình Phước</option>
                                        <option value="Bình Định">Bình Định</option>
                                        <option value="Tây Ninh">Tây Ninh</option>
                                        <option value="Bà Rịa - Vũng Tàu">Bà Rịa - Vũng Tàu</option>
                                        <option value="Phú Yên">Phú Yên</option>
                                        <option value="Kiên Giang">Kiên Giang</option>
                                        <option value="Kon Tum">Kon Tum</option>
                                        <option value="Ninh Thuận">Ninh Thuận</option>
                                        <option value="Đồng Tháp">Đồng Tháp</option>
                                        <option value="Long An">Long An</option>
                                        <option value="Bạc Liêu">Bạc Liêu</option>
                                        <option value="Cà Mau">Cà Mau</option>
                                        <option value="Đắk Nông">Đắk Nông</option>
                                        <option value="Gia Lai">Gia Lai</option>
                                        <option value="Quảng Nam">Quảng Nam</option>
                                        <option value="Thái Bình">Thái Bình</option>
                                        <option value="Hải Dương">Hải Dương</option>
                                        <option value="Phú Thọ">Phú Thọ</option>
                                        <option value="Bắc Ninh">Bắc Ninh</option>
                                        <option value="Vĩnh Phúc">Vĩnh Phúc</option>
                                        <option value="Hà Nam">Hà Nam</option>
                                        <option value="Nam Định">Nam Định</option>
                                        <option value="Hưng Yên">Hưng Yên</option>
                                        <option value="Thái Nguyên">Thái Nguyên</option>
                                        <option value="Lạng Sơn">Lạng Sơn</option>
                                        <option value="Tuyên Quang">Tuyên Quang</option>
                                        <option value="Yên Bái">Yên Bái</option>
                                        <option value="Lai Châu">Lai Châu</option>
                                        <option value="Điện Biên">Điện Biên</option>
                                        <option value="Sơn La">Sơn La</option>
                                        <option value="Hà Giang">Hà Giang</option>
                                        <option value="Cao Bằng">Cao Bằng</option>
                                        <option value="Bắc Kạn">Bắc Kạn</option>
                                        <option value="Tiền Giang">Tiền Giang</option>
                                        <option value="Bến Tre">Bến Tre</option>
                                        <option value="Trà Vinh">Trà Vinh</option>
                                        <option value="Vĩnh Long">Vĩnh Long</option>
                                        <option value="Cần Thơ">Cần Thơ</option>
                                        <option value="Sóc Trăng">Sóc Trăng</option>
                                        <option value="An Giang">An Giang</option>
                                        <option value="Kiên Giang">Kiên Giang</option>
                                        <option value="Cà Mau">Cà Mau</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <input type="checkbox" class="checkPassword" name="changepassword">
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">{{__("Password")}}</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="password" name="password" class="password form-control" disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">{{__("RePassword")}}</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="password" name="repassword" class="password form-control" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3"></div>
                            <div class="col-sm-9 text-secondary">
                                <input type="submit" class="btn btn-primary px-4" value="{{__('Save Changes')}}">
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
        
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<!-- <script>
    $(document).ready(function(){
       $("#city option[value='{{$user->city}}']").prop("selected",true);
    });
</script> -->
<script>
    $(document).ready(function(){
        $('.checkPassword').change(function(){
        if($(this).is(":checked"))
        {
            $('.password').removeAttr('disabled');
        }
        else 
        {
            $('.password').attr('disabled','');
        }
        });
    });
    $(document).ready(function () {
        $("#city option[value='{{ Auth::user()->city }}']").prop("selected",true);
        $('#city').select2();  
    });
</script>

@endsection