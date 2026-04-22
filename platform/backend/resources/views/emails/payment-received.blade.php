@extends('emails.layout')

@section('content')
<h1>Payment Received</h1>

<p>Hi {{ $customer->first_name }},</p>

<p>We've received your payment for order <strong>#{{ $order->order_number }}</strong>.</p>

<table class="totals-table">
    <tr>
        <td>Order Number</td>
        <td style="text-align:right;"><strong>#{{ $order->order_number }}</strong></td>
    </tr>
    <tr>
        <td>Amount Paid</td>
        <td style="text-align:right;"><strong>{{ $order->currency }} {{ number_format($order->total, 2) }}</strong></td>
    </tr>
    <tr>
        <td>Payment Method</td>
        <td style="text-align:right;">{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</td>
    </tr>
    <tr>
        <td>Payment Date</td>
        <td style="text-align:right;">{{ $order->paid_at ? $order->paid_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A') }}</td>
    </tr>
</table>

<p>Your order is now being prepared. We'll notify you when it ships.</p>

<p>Thank you for your purchase!</p>
@endsection
