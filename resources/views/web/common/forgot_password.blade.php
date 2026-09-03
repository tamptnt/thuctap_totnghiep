<div id="forgot_pw">
    <form action="/forgotPassword" method="Post">
		@csrf
        <div class="form-group">
            <input type="email" class="form-control" name="email" id="email_forgot"
                placeholder="{{__('Type your email')}}">
        </div>
        <p>{{__('You will receive an email containing a link allowing you to reset your password to a new preferred one.')}}
        </p>
        <div class="text-center"><input type="submit" value="{{__('Reset Password')}}" class="btn_1"></div>
    </form>
</div>