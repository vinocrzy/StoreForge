@extends('emails.layout')

@section('content')
<h1>Your Order Has Shipped!</h1>

<p>Hi {{ $customer->first_name }},</p>

<p>Great news! Your order <strong>#{{ $order->order_number }}</strong> has been shipped.</p>

<table class="totals-table">
    <tr>
        <td>Order Number</td>
        <td style="text-align:right;"><strong>#{{ $order->order_number }}</strong></td>
    </tr>
    <tr>
        <td>Shipped Date</td>
        <td style="text-align:right;">{{ $order->shipped_at ? $order->shipped_at->format('M d, Y') : now()->format('M d, Y') }}</td>
    </tr>
</table>

<h2 style="font-size:16px; margin: 24px 0 12px;">Items in this shipment:</h2>

<table class="order-table">
    <thead>
        <tr>
            <th>Item</th>
            <th style="text-align:center;">Qty</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product_snapshot['name'] ?? 'Product' }}</td>
            <td style="text-align:center;">{{ $item->quantity }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p>We'll let you know when your order has been delivered.</p>

<p>Thank you for shopping with us!</p>
@endsection
