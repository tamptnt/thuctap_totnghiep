<div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">
                        <a style="color:#475f7b" href="/admin/orders">
                            {{__("Orders Today")}}
                        </a>
                    </h4>
                    <div class="d-flex ">
                        <i data-feather="download"></i>
                    </div>
                </div>
                <div class="card-body px-0 pb-0">
                    <div class="table-responsive">
                        <table class='table mb-0' id="table1">
                            <thead>
                                <tr>
                                    <th>{{__("Name")}}</th>
                                    <th>Email</th>
                                    <th>{{__("Phone")}}</th>
                                    <th>{{__("City")}}</th>
                                    <th>{{__("Status")}}</th>
                                    <th>{{__("Order date")}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders_today as $order)
                                <tr>
                                    @if(Session("language") == "en")
                                    <td>{{$order->firstname}} {{$order->lastname}}</td>
                                    @else
                                    <td>{{$order->lastname}} {{$order->firstname}}</td>
                                    @endif
                                    <td>{{$order->email}}</td>
                                    <td>{{$order->phone}}</td>
                                    <td>
                                        {{$order->city}}
                                    </td>
                                    <td>
                                        @if($order->status == 1)
                                    <span class="badge bg-warning">{{__("In Progress")}}</span>
                                        @elseif($order->status == 2)
                                    <span class="badge bg-info">{{__("Delivery in Progress")}}</span>
                                        @elseif($order->status == 3)
                                    <span class="badge bg-success">{{__("Delivered")}}</span>
                                        @else
                                    <span class="badge bg-danger">{{__("Cancelled")}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{$order->created_at->format('d/m/Y | H:i')}}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>