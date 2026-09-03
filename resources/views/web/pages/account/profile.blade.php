@extends('web.layout.index')
@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endsection
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

    .inputs input {
        width: 40px;
        height: 40px
    }

    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        margin: 0
    }

    .validate {
        border-radius: 20px;
        height: 40px;
        background-color: red;
        border: 1px solid red;
        width: 140px
    }
</style>
<div class="container">
    <div class="main-body ">
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <form action="/imageProfile" method="POST" enctype="multipart/form-data">
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
                                <input type="file" name="Image" class="form-control " />
                                <div class="mt-3">
                                    @if(Session("language") == "en")
                                    <h4>{{$user->firstname}} {{$user->lastname}} </h4>
                                    @else
                                    <h4>{{$user->lastname}} {{$user->firstname}}</h4>
                                    @endif
                                    <p class="text-secondary mb-1">{{__("Username")}}: {{$user->username}}</p>
                                    <!-- <p class="text-secondary mb-1">Email: {{$user->email}}</p>
                                    <p class="text-muted font-size-sm">Phone: {{$user->phone}}</p> -->
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
                    <form action="/profile" method="POST">
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
                                    @if($user->email_verified == 0 && isset($user->email))
                                    <span class="ml-1" style="color:red">{{__("Please activate your email")}}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 mt-2">
                                    <h6>{{__("Phone")}}</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="text" name="phone" id="phone" class="form-control phone" value="{{$user->phone}}">
                                    <div class="justify-center mb-2" id="recaptcha-container"></div>
                                    <a type="button" class="btn btn-danger" style="color:white" data-bs-toggle="modal" data-bs-target="#otpModal" onclick="sendOTP();">{{__("Verify")}}</a>
                                    @include('web.pages.account.otp')
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

<script>
    $(document).ready(function() {
        $('.checkPassword').change(function() {
            if ($(this).is(":checked")) {
                $('.password').removeAttr('disabled');
            } else {
                $('.password').attr('disabled', '');
            }
        });
    });
    $(document).ready(function() {
        $("#city option[value='{{ $user->city }}']").prop("selected", true);
        $('#city').select2();
    });
</script>
<script>
    window.onload = function() {
        render();
    };

    function render() {
        window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
            size: "invisible"
        });
        recaptchaVerifier.render();
    }

    function sendOTP() {
        var phone = $('#phone').val();
        firebase.auth().signInWithPhoneNumber(phone, window.recaptchaVerifier).then(function(confirmationResult) {
            window.confirmationResult = confirmationResult;
            coderesult = confirmationResult;
            $("#success").text("A code has been sent");
            $("#success").show();
        }).catch(function(error) {
            $("#error").text(error.message);
            $("#error").show();
        });
    };

    function verify() {
        var firstDigit = $("#first").val();
        var secondDigit = $("#second").val();
        var thirdDigit = $("#third").val();
        var fourthDigit = $("#fourth").val();
        var fifthDigit = $("#fifth").val();
        var sixthDigit = $("#sixth").val();
        var code = firstDigit + secondDigit + thirdDigit + fourthDigit + fifthDigit + sixthDigit;
        var phone = $('#phone').val();
        coderesult.confirm(code).then(function(result) {
            var user = result.user;
            alert('Verify otp code successfully');
            window.location.reload();
        }).catch(function(error) {
            alert('Wrong OTP, please try to resend');
        });

    }

</script>
<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        function OTPInput() {
            const inputs = document.querySelectorAll('#otp > *[id]');
            for (let i = 0; i < inputs.length; i++) {
                inputs[i].addEventListener('keydown', function(event) {
                    if (event.key === "Backspace") {
                        inputs[i].value = '';
                        if (i !== 0) inputs[i - 1].focus();
                    } else {
                        if (i === inputs.length - 1 && inputs[i].value !== '') {
                            return true;
                        } else if (event.keyCode > 47 && event.keyCode < 58) {
                            inputs[i].value = event.key;
                            if (i !== inputs.length - 1) inputs[i + 1].focus();
                            event.preventDefault();
                        } else if (event.keyCode > 64 && event.keyCode < 91) {
                            inputs[i].value = String.fromCharCode(event.keyCode);
                            if (i !== inputs.length - 1) inputs[i + 1].focus();
                            event.preventDefault();
                        }
                    }
                });
            }
        }
        OTPInput();


    });
</script>
@endsection