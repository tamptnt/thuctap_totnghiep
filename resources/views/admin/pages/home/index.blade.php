@extends('admin/layout/index')
@section('css')
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

@endsection
@section('dashboard')
active
@endsection
@section('content')
<div class="main-content container-fluid">
<div class="page-title">
    <h3>{{__("Dashboard")}}</h3>
    <p class="text-subtitle text-muted">{{__("Display your statistics")}}</p>
</div>
<section class="section">
    <div class="row mb-2">
        <div class="col-12 col-md-3">
            <div class="card card-statistic">
                <div class="card-body p-0">
                    <div class="d-flex flex-column">
                        <div class='px-3 py-3 d-flex justify-content-between'>
                            <h3 class='card-title'>{{__("Sales Today")}}</h3>
                            <div class="card-right d-flex align-items-center text-nowrap">
                                <p>{{$orders_today->count()}} </p>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="canvas4" style="height:100px !important"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card card-statistic">
                <div class="card-body p-0">
                    <div class="d-flex flex-column">
                        <div class='px-3 py-3 d-flex justify-content-between'>
                            <h3 class='card-title'>{{__("Revenue Today")}}</h3>
                            <div class="card-right d-flex align-items-center text-nowrap">
                                <p>{{number_format($sum_today,0,',','.')}} <sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup> </p>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="canvas2" style="height:100px !important"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card card-statistic">
                <div class="card-body p-0">
                    <div class="d-flex flex-column">
                        <div class='px-3 py-3 d-flex justify-content-between'>
                            <h3 class='card-title'>{{__("Total Orders")}}</h3>
                            <div class="card-right d-flex align-items-center text-nowrap" >
                                <p>{{$orders->count()}} </p>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="canvas3" style="height:100px !important"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card card-statistic">
                <div class="card-body p-0">
                    <div class="d-flex flex-column">
                        <div class='px-3 py-3 d-flex justify-content-between '>
                            <h3 class='card-title'>{{__("Total Revenue")}}</h3>
                            <div class="card-right d-flex align-items-center text-nowrap" style="margin-left:2px">
                                <p>{{number_format($sum,0,',','.')}} <sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup> </p>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="canvas1" style="height:100px !important"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <!-- <h3 class='card-heading p-1 pl-3'>Sales</h3> -->
                </div>
                <div class="card-body">
                    <form autocomplete="off">
                        <div class="row">
                            <div class="col-md-2">
                                    <p>{{__("From:")}} <input type="text" id="datepickerFrom" class="form-control" style="width:70%"/></p>
                                <input id="btn-filter"  class="btn btn-primary mb-3" type="button" value="submit"/>
                            </div>
                            
                            <div class="col-md-2">
                                    <p>{{__("To:")}} <input type="text" id="datepickerTo" class="form-control" style="width:70%"/></p>
                            </div>

                            <div class="col-md-2">
                                    <p>
                                        {{__("Sort by:")}}
                                        <select id="statistical" style="width:70%;background-color: #ffffff !important;" class="form-control">
                                            <option value="null" selected>{{__("Select")}}</option>
                                            <option value="week">{{__("Sort by 7 days")}}</option>
                                            <option value="last_month">{{__("Sort by last month")}}</option>
                                            <option value="this_month">{{__("Sort by this month")}}</option>
                                            <option value="year">{{__("Sort by year")}}</option>
                                        </select>
                                    </p>
                            </div>
                            <div class="col-md-12 col-12">
                                <div id="myfirstchart" style="height: 400px; width: 100%"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @include('admin.pages.home.orders_today')
        </div>
    </div>
</section>
</div>
@endsection
@section('scripts')
<script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
<script>
$(function() {
     // Tạo một đối tượng Date cho ngày hiện tại
     var currentDate = new Date();

    // Trừ đi 1 ngày
    currentDate.setDate(currentDate.getDate() - 1);

    $( "#datepickerFrom" ).datepicker({
        maxDate: currentDate,
        dateFormat:"yy-mm-dd",
        duration:"slow",
        onSelect: function(selectedDate) {
            // Chuyển đổi ngày được chọn thành đối tượng Date
            var fromDate = new Date(selectedDate);
            
            // Tăng ngày lên 1
            fromDate.setDate(fromDate.getDate() + 1);

            // Đặt minDate của #datepickerTo thành ngày tăng lên 1 từ #datepickerFrom
            $("#datepickerTo").datepicker("option", "minDate", fromDate);
        }
    });
});

$(function() {
    $( "#datepickerTo" ).datepicker({
        maxDate: 'today',
        dateFormat:"yy-mm-dd",
        duration:"slow"
    });
  });
</script>
<script>
    $(document).ready(function() {
        var chart = new Morris.Line({
            element: 'myfirstchart',
            barColors: ['#09b1f3', '#fc8710', '#FF6541', '#A4ADD3', '#766B56'],
            parseTime: false,
            hideHover: 'auto',
            xkey: 'date',
            ykeys: ['total'],
            labels: ['total']
        });
        //filter by date
     $("#btn-filter").click(function(){
        var from = $('#datepickerFrom').val();
        var to = $('#datepickerTo').val();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url:"admin/filter-by-date",
            method:"GET",
            dataType:"json",
            data:{
                from:from,
                to:to
            },
            success:function(data){
                if (data['success']) {
                    chart.setData(data.chart_data);
                } else if (data['error']) {
                    alert(data.error);
                }
            }
        });

    });
      //sortBy
      $("#statistical").change(function(){
        var statistical = $(this).val();
        if(statistical === "null"){
            chart.setData([{
                date:null,
                total:null,
            }]);
            return;
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url:"admin/sort-by",
            method:"GET",
            dataType:"json",
            data:{
               'statistical': statistical
            },
            success:function(data){
                if (data['success']) {
                    chart.setData(data.chart_data);
                } else if (data['error']) {
                    alert(data.error);
                }
            }
        });
    });
});
   
</script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

@endsection
