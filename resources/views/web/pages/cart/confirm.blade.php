@extends('web.layout.index')
@section('css')
<link href="web_assets/css/checkout.css" rel="stylesheet">
@endsection
@section('content')
<main class="bg_gray">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-12">
				<div id="confirm">
					<div class="icon icon--order-success svg add_bottom_15">
						<svg xmlns="http://www.w3.org/2000/svg" width="72" height="72">
							<g fill="none" stroke="#8EC343" stroke-width="2">
								<circle cx="36" cy="36" r="35" style="stroke-dasharray:240px, 240px; stroke-dashoffset: 480px;"></circle>
								<path d="M17.417,37.778l9.93,9.909l25.444-25.393" style="stroke-dasharray:50px, 50px; stroke-dashoffset: 0px;"></path>
							</g>
						</svg>
					</div>
					<h2>{{__("Order completed!")}}</h2>
					<p>{{__("You will receive a confirmation email soon!")}}</p>
				</div>
				<input type="hidden" id="orders_id" value="{{$orders->id}}" data-id="{{$orders->id}}" />
			</div>
		</div>
		<!-- /row -->
	</div>
	<!-- /container -->
</main>
@endsection
@section('scripts')
<script>
	$(document).ready(function(){
	var orders = $('#orders_id').data('id');
	window.onload = () => {
		$.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
		$.ajax({
			url: '/send_mail_orders',
			type: 'POST',
			data: {
				orders: orders
			},
			dataType: 'json',
			statusCode:{
				200: () => {
				window.setTimeout(function() {
					window.location.href = "/myOrder";
				}, 3000);
				},
				500: () => {
					

				}
			}
			
		})
	}
	});
	
</script>
@endsection