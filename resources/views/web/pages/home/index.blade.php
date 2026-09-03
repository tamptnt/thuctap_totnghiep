@extends('web.layout.index')
@section('content')
<main>
			<!--/banners -->
			@include('web.pages.home.banners')
			<!--/banners -->

			<hr class="mb-0">
			
			<!-- Featured Products -->
			@include('web.pages.home.featured')
			<!-- /Featured Products -->

			<hr class="mb-0">

			<!-- Top selling -->
			@include('web.pages.home.top_selling')
			<!-- /Top selling -->

			<hr class="mb-0">

			<!-- New Products -->
			@include('web.pages.home.new_products')
			<!-- /New Products -->

			<hr class="mb-0">

			<!-- Brands -->
			@include('web.pages.home.brands')
			<!-- /Brands -->

			<hr class="mb-0">

			<!-- News -->
			@include('web.pages.home.latest_news')
			<!-- /News -->

		</main>

@endsection

