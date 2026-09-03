<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - Voler Admin Dashboard</title>
    <base href="{{asset('')}}">
    <link rel="stylesheet" href="admin_assets/css/bootstrap.css">
    
    <link rel="shortcut icon" href="admin_assets/images/favicon.svg" type="image/x-icon">
    <link rel="stylesheet" href="admin_assets/css/app.css">
</head>

<body>
    <div id="auth">
        <div class="container">
            <div class="row">
                <div class="col-md-5 col-sm-12 mx-auto">
                    <div class="card pt-4">
                        <div class="card-body">
                            <div class="text-center mb-5">
                                <img src="admin_assets/images/favicon.svg" height="48" class='mb-4'>
                                <h3>{{__("Sign In")}}</h3>
                                <p>{{__("Please sign in to continue to Voler")}}</p>
                            </div>
                            <form action="admin/login" method="post">
                                @csrf
                                <div class="form-group position-relative has-icon-left">
                                    <label for="username">{{__("Username")}}</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="username" name="username">
                                        <div class="form-control-icon">
                                            <i data-feather="user"></i>
                                        </div>
                                        <!-- @if(Session::has('errors'))
                                           {{Session::get('errors')->first()}}
                                        @endif -->
                                    </div>
                                </div>
                                <div class="form-group position-relative has-icon-left">
                                    <div class="clearfix">
                                        <label for="password">{{__("Password")}}</label>
                                      
                                    </div>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="password" name="password">
                                        <div class="form-control-icon">
                                            <i data-feather="lock"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="clearfix">
                                    <button type="submit" class="btn btn-primary float-end">{{__("Submit")}}</button>
                                </div>
                            </form>
                            <div class="divider">
                                <div class="divider-text">{{__("OR")}}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <button class="btn btn-block mb-2 btn-primary"><i data-feather="facebook"></i> Facebook</button>
                                </div>
                                <div class="col-sm-6">
                                    <button class="btn btn-block mb-2 btn-secondary"><i data-feather="github"></i> Github</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('sweetalert::alert')
    <script src="admin_assets/js/feather-icons/feather.min.js"></script>
    <script src="admin_assets/js/app.js"></script>
    <script src="admin_assets/js/main.js"></script>
</body>

</html>
