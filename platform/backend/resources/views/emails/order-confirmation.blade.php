@extends('emails.layout')

@section('content')
<h1>Order Confirmed!</h1>

<p>Hi {{ $customer->first_name }},</p>

<p>Thank you for your order! We've received your order <strong>#{{ $order->order_number }}</strong> and it's being processed.</p>

<table class="order-table">
    <thead>
        <tr>
            <th>Item</th>
            <th style="text-align:center;">Qty</th>
            <th style="text-align:right;">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product_snapshot['name'] ?? 'Product' }}</td>
            <td style="text-align:center;">{{ $item->quantity }}</td>
            <td style="text-align:right;">{{ $order->currency }} {{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals-table">
    <tr>
        <td>Subtotal</td>
        <td style="text-align:right;">{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</td>
    </tr>
    @if($order->discount_amount > 0)
    <tr>
        <td>Discount</td>
        <td style="text-align:right;">-{{ $order->currency }} {{ number_format($order->discount_amount, 2) }}</td>
    </tr>
    @endif
    @if($order->shipping_amount > 0)
    <tr>
        <td>Shipping</td>
        <td style="text-align:right;">{{ $order->currency }} {{ number_format($order->shipping_amount, 2) }}</td>
    </tr>
    @endif
    @if($order->tax_amount > 0)
    <tr>
        <td>Tax</td>
        <td style="text-align:right;">{{ $order->currency }} {{ number_format($order->tax_amount, 2) }}</td>
    </tr>
    @endif
    <tr class="total-row">
        <td>Total</td>
        <td style="text-align:right;">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
    </tr>
</table>

<p>We'll send you another email when your order ships.</p>

<p>Thanks for shopping with us!</p>
@endsection
