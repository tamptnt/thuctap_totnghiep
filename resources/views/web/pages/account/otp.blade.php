<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="otpModalLabel">{{__("Verify OTP")}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container height-100 d-flex justify-content-center align-items-center">
                    <div class="position-relative">
                        <div class="p-2 text-center">
                            <h6 id="success" style="color:green"></h6>
                            <h6 id="error" style="color:red"></h6>
                            <div id="otp" class="inputs d-flex flex-row justify-content-center mt-2">
                                <input class="m-2 text-center form-control rounded" type="text" id="first" maxlength="1" />
                                <input class="m-2 text-center form-control rounded" type="text" id="second" maxlength="1" />
                                <input class="m-2 text-center form-control rounded" type="text" id="third" maxlength="1" />
                                <input class="m-2 text-center form-control rounded" type="text" id="fourth" maxlength="1" />
                                <input class="m-2 text-center form-control rounded" type="text" id="fifth" maxlength="1" />
                                <input class="m-2 text-center form-control rounded" type="text" id="sixth" maxlength="1" />
                            </div>
                            <div class="mt-4"> <a href="javascript:void(0)" class="btn btn-danger px-4 validate" onclick="verify();">{{__("Validate")}}</a> </div>
                        </div>
                        <div class="card-2">
                            <div class="content d-flex justify-content-center align-items-center"> <span>{{__("Didn't get the code ?")}}</span> 
                            <a type="button" href="javascript:void(0);" class="text-decoration-none ms-3" onclick="sendOTP();">{{__("Resend")}}</a> 
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>