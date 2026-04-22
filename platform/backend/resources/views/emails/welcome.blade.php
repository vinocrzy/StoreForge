@extends('emails.layout')

@section('content')
<h1>Welcome to {{ $store->name }}!</h1>

<p>Hi {{ $customer->first_name }},</p>

<p>Thank you for creating an account with us. We're excited to have you!</p>

<p>With your account, you can:</p>

<ul style="color: #51545e; font-size: 15px; padding-left: 20px;">
    <li>Track your orders</li>
    <li>Save your shipping addresses</li>
    <li>Get faster checkout</li>
    <li>Receive exclusive offers</li>
</ul>

<p>Start shopping today and discover what we have in store for you.</p>

<p>If you have any questions, feel free to contact us at <a href="mailto:{{ $store->email }}" style="color: #3490dc;">{{ $store->email }}</a>.</p>

<p>Welcome aboard!</p>
@endsection
