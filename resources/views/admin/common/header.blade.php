@if(Auth::check())
<nav class="navbar navbar-header navbar-expand navbar-light">
    <a class="sidebar-toggler" href="javascript:void(0)"><span class="navbar-toggler-icon"></span></a>
    <button class="btn navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav d-flex align-items-center navbar-light ms-auto">
            <li class="dropdown nav-icon">
                <a href="#" data-bs-toggle="dropdown" class="nav-link  dropdown-toggle nav-link-lg nav-link-user">
                    <div class="d-lg-inline-block">
                        <i data-feather="bell"></i>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-large">
                    <h6 class='py-2 px-4'>{{__("Notifications")}}</h6>
                    @foreach($new_orders as $new)
                    <ul class="list-group rounded-none">
                        <li class="list-group-item border-0 align-items-start">
                            <div class="avatar bg-success me-3">
                                <span class="avatar-content"><i data-feather="shopping-cart"></i></span>
                            </div>
                            <div>
                                <a href="/admin/orders" style="color:#212529">
                                    <h6 class='text-bold'>{{__("New Order")}}</h6>
                                    <p class='text-xs'>
                                        {{__("An order made by")}} {{$new->firstname}} {{__("with Order ID")}}: {{$new->id}}
                                    </p>
                                </a>
                            </div>
                        </li>
                    </ul>
                    @endforeach
                </div>
            </li>
            <li class=" nav-icon me-2">
                <a href="{{route('lang')}}" class="nav-link nav-link-lg nav-link-user">
                    <div class="d-lg-inline-block">
                    @if(Session("language") == "en")
                    <img src="images/language/{{__('en')}}.png" style="height: 30px">
                    @else
                    <img src="images/language/{{__('vi')}}.png" style="height: 30px">
                    @endif
                    </div>
                </a>
            </li>
            <li class="dropdown">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                    <div class="avatar me-1">
                    {{Auth::user()->firstname}}
                    </div>
                    <!-- <div class="d-none d-md-block d-lg-inline-block">{{Auth::user()->firstname}}</div> -->
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="/admin/profile"><i data-feather="user"></i>Account</a>
                    <a class="dropdown-item" href="#"><i data-feather="mail"></i> Messages</a>
                    <a class="dropdown-item" href="#"><i data-feather="settings"></i>{{__("Settings")}}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="/admin/logout"><i data-feather="log-out"></i>{{__("Logout")}}</a>
                </div>
            </li>
        </ul>
    </div>
</nav>
@endif