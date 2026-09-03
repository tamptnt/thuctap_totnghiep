<div style="width: 600px; margin:0 auto; color: #333">
    <h2 style="text-align: center">{{__("Hi ")}} {{ $name }}</h2>
    <h3 style="text-align: center">{{__("Thank you for ordering from our store")}}</h3>
    <h3 style="text-align: center">
        {{__("This is your shopping cart information, please check and feedback to us if there are any errors.")}}</h3>
</div>
<div style="width: 100%;text-align: -webkit-center;">
    <table  style="width: 100%; color: #333;" border="1">
        <tr>

            <th style="text-align:center">{{__("Products")}}</th>
            <th style="text-align:center">{{__("Image")}}</th>
            <th style="text-align:center">{{__("Quantity")}}</th>
            <th style="text-align:center">{{__("Unit Price")}}</th>

        </tr>
        @foreach($cart->content() as $carts)
        <tr>

            <td style="text-align:center; max-width: 300px;word-wrap: break-word;">{{$carts->name}}</td>
            <td style="text-align:center">
                <img style="width: 150px"
                    src="https://res.cloudinary.com/{{env('CLOUD_NAME')}}/image/upload/{{$carts->options->image}}.jpg"
                    class="lazy" alt="Image">
            </td>
            <td style="text-align:center">{{$carts->qty}}</td>
            <td style="text-align:center">
                {{number_format($carts->price,0,",",".")}}<sup
                    style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup>
            </td>

        </tr>
        @endforeach
        <tr>
            <th style="border: 0px !important"></th>
            <th style="border: 0px !important"></th>
            <th style="text-align: center">{{__("Total")}} + {{__("Tax")}} ({{(($orders->total-($orders->subtotal-$orders->discount))/($orders->subtotal-$orders->discount))*100}}%) + {{__("Discount")}} ({{(($orders->subtotal-($orders->total-$orders->tax))/$orders->subtotal)*100}}%) </th>
            <td style="text-align: center">{{$cart->total(0,',','.')}}<sup style="text-decoration: underline; padding: 3px; text-transform: lowercase !important;">đ</sup></td>
        </tr>
    </table>

</div>