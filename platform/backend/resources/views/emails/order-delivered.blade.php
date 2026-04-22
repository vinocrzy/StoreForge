@extends('emails.layout')

@section('content')
<h1>Your Order Has Been Delivered!</h1>

<p>Hi {{ $customer->first_name }},</p>

<p>Your order <strong>#{{ $order->order_number }}</strong> has been delivered.</p>

<table class="totals-table">
    <tr>
        <td>Order Number</td>
        <td style="text-align:right;"><strong>#{{ $order->order_number }}</strong></td>
    </tr>
    <tr>
        <td>Delivered Date</td>
        <td style="text-align:right;">{{ $order->delivered_at ? $order->delivered_at->format('M d, Y') : now()->format('M d, Y') }}</td>
    </tr>
</table>

<p>We hope you enjoy your purchase! If you have any questions or concerns, don't hesitate to reach out.</p>

<p>Thank you for shopping with {{ $store->name }}!</p>
@endsection
