@extends('web.layout.index')
@section('css')
<link href="web_assets/css/account.css" rel="stylesheet">
@endsection
@section('content')
<style>
	a#signInToPhone {
		color: #999;
		font-weight: 500;
	}

	#phoneOTP {
		background-color: #fff;
		position: absolute;
		left: 0;
		top: 0;
		width: 100%;
		z-index: 99;
		min-height: 100%;
		padding: 15px;
		display: none;
	}

	#phoneOTP label {
		font-weight: 500;
	}
</style>
<main class="bg_gray">
	<div class="container margin_30">
		<div class="page_header">
			<div class="breadcrumbs">
				<ul>
					<li><a href="#">{{__('Home')}}</a></li>
					<li>{{__('Sign In & Sign Up')}}</li>
				</ul>
			</div>
			<h1>{{__("Sign In or Sign Up")}}</h1>
		</div>
		<!-- /page_header -->
		<div class="row justify-content-center">
			<!-- Sign In -->
			<div class="col-xl-6 col-lg-6 col-md-8">
				<div class="box_account">
					<h3 class="client">{{__("Sign In")}}</h3>
					<div class="form_container">
						<form action="/handle_login" method="post">
							@csrf
							<div class="form-group">
								<input type="text" class="form-control" @if(session()->has('username_client'))
								value="{{session()->get('username_client')}}"
								@endif
								name="username" id="email" placeholder="{{__('Username')}} {{__('or')}} Email*">
							</div>
							<div class="form-group">
								<input type="password" class="form-control" @if(session()->has('password_client'))
								value="{{session()->get('password_client')}}"
								@endif
								name="password" id="password" placeholder="{{__('Password')}}*">
							</div>
							<div class="clearfix add_bottom_15">
								<div class="checkboxes float-start">
									<label class="container_check">{{__("Remember me")}}
										<input type="checkbox" @if(session()->has('username_client'))
										checked
										@endif
										name="rememberme"
										>
										<span class="checkmark"></span>
									</label>
								</div>
								<div class="float-end"><a id="forgot" href="javascript:void(0);">{{__("Forgot Password?")}}</a></div>
							</div>
							<div class="text-center"><input type="submit" value="{{__('Sign In')}}" class="btn_1 full-width"></div>
						</form>
						@include('web.common.forgot_password')
					</div>
					<!-- /form_container -->
				</div>
			</div>

			<!-- Sign Up -->
			<div class="col-xl-6 col-lg-6 col-md-8">
				<div class="box_account">
					<h3 class="new_client">{{__("Sign Up")}}</h3> <small class="float-right pt-2 ">* {{__('Required Fields')}}</small>
					<div class="form_container">
						<form action="/register" method="POST">
							@csrf
							<div class="form-group">
								<input type="text" class="form-control" name="username" id="username" placeholder="{{__('Username')}}*">
							</div>
							<div class="form-group">
								<input type="password" class="form-control" name="password" id="password_in_2" value="" placeholder="{{__('Password')}}*">
							</div>
							<div class="form-group">
								<input type="password" class="form-control" name="repassword" id="password_in_2" value="" placeholder="{{__('Confirm Password')}}*">
							</div>
							<hr>
							<div class="private box">
								<div class="row no-gutters">
									<div class="col-6 pl-1">
										<div class="form-group">
											<input type="text" name="phone" id="phone" class="form-control" placeholder="{{__('Telephone')}}*">
										</div>
									</div>
									<div class="col-6 pl-1">
										<div class="form-group">
											<input type="email" class="form-control" name="email" id="email_2" placeholder="{{__('Email')}}*">
										</div>
									</div>
								</div>
								<div class="row no-gutters">
									<div class="col-6 pr-1">
										<div class="form-group">
											<input type="text" name="firstname" id="inputFirstName" class="form-control" placeholder="{{__('First Name')}}*">
										</div>
									</div>
									<div class="col-6 pl-1">
										<div class="form-group">
											<input type="text" name="lastname" id="inputLastName" class="form-control" placeholder="{{__('Last Name')}}*">
										</div>
									</div>
									<div class="col-12">
										<div class="form-group">
											<input type="text" name="address" class="form-control" placeholder="{{__('Address')}}">
										</div>
									</div>
								</div>
								<div class="row no-gutters">
									<div class="col-6 pr-1">
										<div class="form-group">
											<input type="text" name="district" class="form-control" placeholder="{{__('District')}}">
										</div>
									</div>
									<div class="col-6 pl-1">
										<div class="form-group mt-1">
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
							</div>
							<hr>
							<div class="text-center"><input type="submit"class="btn_1 full-width" value="{{__('Submit')}}"></div>
						</form>
					</div>
					<!-- /form_container -->
				</div>
				<!-- /box_account -->
			</div>
		</div>
		<!-- /row -->
	</div>
	<!-- /container -->
</main>
<!--/main-->
@endsection
@section('scripts')
<script>
	$(document).ready(function() {
		$('#city').select2();
	});
</script>

@endsection