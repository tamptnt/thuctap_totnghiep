@extends('web.layout.index')
@section('css')
<link href="web_assets/css/account.css" rel="stylesheet">
@endsection
@section('content')
<main class="bg_gray">
    <div class="container margin_30">
        <div class="page_header">
            <div class="breadcrumbs">
                <ul>
                    <li><a href="#">{{__('Home')}}</a></li>
                    <li>{{__('Reset your password')}}</li>
                </ul>
            </div>
            <h1>{{__("Reset your password")}}</h1>
        </div>
        <!-- /page_header -->
        <div class="row justify-content-center">
            <!-- Sign In -->
            <div class="col-xl-8 col-lg-8 col-md-8">
                <div class="box_account">
                    <div class="form_container">
                        <form action="/reset-password" method="post">
                            @csrf
                            <div class="form-group">
                                <input type="password" name="password" placeholder="{{__('New password')}}" style="width: 100%;" class="form-control">
                            </div>
                            <div class="form-group">
                                <input type="password" name="repassword" placeholder="{{__('Password again')}}" style="width: 100%;" class="form-control">
                            </div>
                            <div class="clearfix add_bottom_15">
                            <div class="text-center"><input type="submit" value="{{__('Reset Password')}}" class="btn_1"></div>
                            <input type="hidden" name="email" value="{{$_GET['email']}}"/>
                            <input type="hidden" name="token" value="{{$_GET['token']}}"/>
                        </form>
                    </div>
                    <!-- /form_container -->
                </div>
            </div>
        </div>
        <!-- /row -->
    </div>
    <!-- /container -->
</main>
<!--/main-->
@endsection