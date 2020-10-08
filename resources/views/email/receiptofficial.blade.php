@component('email.header')
@endcomponent
<span class="holder">
    <span class="thank-you-header">
        <h1 style="line-height: 125px;">Thank you for your order, {{$user->username}}!</h1>
        <label><b>Order # {{$dataReceipt['order_number']}}</b></label>
        <label><b>Merchant Name: {{dataReceipt['merchant_name']}}</b></label>
        <label><b>Date: {{dataReceipt['date']}}</b></label>
    </span>
    <span class="thank-you-item">
        <label><b>Products</b></label>
        <label><b>Quantity</b></label>
        <label><b>Price</b></label>
        <label><b>Total</b></label>
    </span>
<!--    @for($i = 0; $i < count($dataReceipt['templates']); $i++)
        <span class="thank-you-item">
            <label>Template: {{$dataReceipt['templates'][$i]['template']['title']}}</label>
            <label>1</label>
            <label>{{$dataReceipt['templates'][$i]['price']}}</label>
            <label>{{floatval($dataReceipt['templates'][$i]['price']) * 1}}</label>
        </span>
    @endfor
-->
    @for($i = 0; $i < count($dataReceipt['products']); $i++)
        <span class="thank-you-item">
            <label>{{$dataReceipt['products'][$i]['product']['title']}}</label>
            <label>{{$dataReceipt['products'][$i]['qty']}}</label>
            <label>{{$dataReceipt['products'][$i]['price']}}</label>
            <label>{{floatval($dataReceipt['products'][$i]['price']) * intval($dataReceipt['products'][$i]['qty'])}}</label>
        </span>
    @endfor
    @if($dataReceipt['employees'] != null)
        <span class="thank-you-item">
            <label>{{count($dataReceipt['employees'])}}</label>
            <label>1</label>
            <label>100</label>
            <label>{{count($dataReceipt['employees']) * 100}}</label>
        </span>
    @endif
    <span class="thank-you-item">
        <label>&nbsp;</label>
        <label>&nbsp;</label>
        <label><b>Summary</b></label>
    </span>
    <span class="thank-you-item">
        <label>&nbsp;</label>
        <label>&nbsp;</label>
        <label>Subtotal</label>
        <label>PHP {{$dataReceipt['sub_total']}}</label>
    </span>
    <span class="thank-you-item">
        <label>&nbsp;</label>
        <label>&nbsp;</label>
        <label>Shipping Fee</label>
        <label>PHP {{$dataReceipt['shipping_fee']}}</label>
    </span>
    <span class="thank-you-item">
        <label>&nbsp;</label>
        <label>&nbsp;</label>
        <label>Tax</label>
        <label>PHP {{$dataReceipt['tax']}}</label>
    </span>
    @if($dataReceipt['coupon'] != null)
        <span class="thank-you-item">
            <label>&nbsp;</label>
            <label>&nbsp;</label>
            <label class="text-primary">
            <b>Promo Code</b>: 
                @if($dataReceipt['coupon'] != null)
                    <b>{{$dataReceipt['coupon']['code']}}</b>
                @endif
                @if($dataReceipt['coupon'] != null && $dataReceipt['coupon']['type'] == 'percentage')
                    <b> (-{{$dataReceipt['coupon']['value']}}%)</b>
                @elseif($dataReceipt['coupon'] != null && $dataReceipt['coupon']['type'] == 'fixed_amount')
                    <b> (-{{$dataReceipt['coupon']['value']}})</b>
                @endif
            </label>
            <label>
                PHP {{$dataReceipt['discount']}}
            </label>
        </span>
    @endif
    <span class="thank-you-item">
        <label>&nbsp;</label>
        <label>&nbsp;</label>
        <label><b>Total</b></label>
        <label><b>PHP {{$dataReceipt['total']}}</b></label>
    </span>
    <span class="thank-you-item">
        <label>&nbsp;</label>
        <label>&nbsp;</label>
        <label>Payment Method</label>
        @if($dataReceipt['method']['stripe'] != null)
            <label>
                <i class="fa fa-credit-card"></i>
                ********{{$dataReceipt['method']['stripe']['last4']}}
            </label>
        @elseif($dataReceipt['method']['paypal'] != null)
            <label>
                <i class="fa fa-paypal"></i> {{$dataReceipt['method']['paypal']['email']}}
            </label>
        @else
            <label>
            <label><b>{{$dataReceipt['method']}}</b></label>
            </label>
        @endif
    </span>
</span>
@component('email.footer')
@endcomponent


