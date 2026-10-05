@extends('admin/layout/index')
@section('manage_clients')
active
@endsection
@section('css')
<link rel="stylesheet" href="admin_assets/vendors/simple-datatables/style.css">
@endsection
@section('content')
<div class="main-content container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>{{__("Clients")}}</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class='breadcrumb-header'>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin">{{__("Dashboard")}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__("Clients")}}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">

            <div class="card-body">
                <table class='table table-striped' id="table1">
                    <thead>
                        <tr>
                            <th class="text-center">{{__("Image")}}</th>
                            <th class="text-center">{{__("Last Name")}}</th>
                            <th class="text-center">{{__("First Name")}}</th>
                            <th class="text-center">{{__("Username")}}</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">{{__("Phone")}}</th>
                            <th class="text-center">{{__("Status")}}</th>
                            <th class="text-center">{{__("Created Date")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user as $key => $users)
                        <tr>
                            <td>
                                @if($users->image !=null)
                                    @if(strstr($users->image,"https") == "")
                                        <img style="width: 80px" src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$users->image}}.jpg" >
                                    @else
                                        <img style="width: 80px" src="{{$users->image}}" >
                                    @endif
                                @else
                                        <img src='images/avatar/avatar.png'  style="width: 80px">
                                @endif
                            </td>
                            <td class="text-center">{{$users->lastname}}</td>
                            <td class="text-center">{{$users->firstname}}</td>
                            <td class="text-center">{{$users->username}}</td>
                            <td class="text-center">{{$users->email}}</td>
                            <td class="text-center">{{$users->phone}}</td>
                            <td class="text-center" id="status{{$users->id}}">
                                @if($users->status == 1)
                                <a href="javascript:void(0)" onclick="status({{$users->id}},0)"><span class="badge bg-success">{{__("Published")}}</span></a>
                                @else
                                <a href="javascript:void(0)" onclick="status({{$users->id}},1)"><span class="badge bg-danger">{{__("Pending")}}</span></a>
                                @endif
                            </td>
                            <td class="text-center">
                                {{$users->created_at}}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </section>
</div>
@endsection
@section('scripts')
<script src="admin_assets/vendors/simple-datatables/simple-datatables.js"></script>
<script src="admin_assets/js/vendors.js"></script>
<script>
    function status(status_id, active) {
        if (active === 1) {
            $("#status" + status_id).html(' <a href="javascript:void(0)" onclick="status(' + status_id + ',0)">\
                <span class="badge bg-success">{{__("Published")}}</span>\
            </a>')
        } else {
            $("#status" + status_id).html(' <a href="javascript:void(0)" onclick="status(' + status_id + ',1)">\
                <span class="badge bg-danger">{{__("Pending")}}</span>\
            </a>')
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: "/admin/clients/status",
            type: 'GET',
            dataType: 'json',
            data: {
                'active': active,
                'status_id': status_id
            },
            success: function(data) {
                if (data['success']) {
                    // alert(data.success);
                } else if (data['error']) {
                    alert(data.error);
                }
            }
        });
    }
</script>
@endsection