<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Voler Admin Dashboard</title>
    <base href="{{asset('')}}">
    <link rel="stylesheet" href="admin_assets/css/bootstrap.css">
    <link rel="stylesheet" href="admin_assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="admin_assets/css/app.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" integrity="sha512-aD9ophpFQ61nFZP6hXYu4Q/b/USW7rpLCQLX6Bi0WJHXNO7Js/fUENpBQf/+P4NtpzNX0jSgR5zVvPOJp+W2Kg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @yield('css')
    <link rel="shortcut icon" href="admin_assets/images/favicon.svg" type="image/x-icon">
</head>

<body>
    <div id="app">
    @include('sweetalert::alert')
       @include('admin/common/sidebar')
        <div id="main">
           @include('admin/common/header')

           
                @yield ('content')
          

            @include('admin/common/footer')
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.js" integrity="sha256-JlqSTELeR4TLqP0OG9dxM7yDPqX1ox/HfgiSLBj8+kM=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js" integrity="sha512-4MvcHwcbqXKUHB6Lx3Zb5CEAVoE9u84qN+ZSMM6s7z8IeJriExrV3ND5zRze9mxNlABJ6k864P/Vl8m0Sd3DtQ==" crossorigin="anonymous"></script>

    

    <script src="admin_assets/js/feather-icons/feather.min.js"></script>
    <script src="admin_assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="admin_assets/js/app.js"></script>
    
    
    <script src="admin_assets/js/main.js"></script>
    
    @yield('scripts')
</body>

</html>