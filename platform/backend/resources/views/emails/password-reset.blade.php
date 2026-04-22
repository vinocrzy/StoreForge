@extends('emails.layout')

@section('content')
<h1>Reset Your Password</h1>

<p>Hi {{ $customer->first_name }},</p>

<p>We received a request to reset your password. Click the button below to choose a new password:</p>

<p style="text-align: center; margin: 32px 0;">
    <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
</p>

<p>This link will expire in {{ $expireMinutes }} minutes.</p>

<p>If you didn't request a password reset, you can safely ignore this email. Your password will not be changed.</p>

<p style="font-size: 13px; color: #a0aec0; margin-top: 32px;">
    If the button doesn't work, copy and paste this URL into your browser:<br>
    <a href="{{ $resetUrl }}" style="color: #3490dc; word-break: break-all;">{{ $resetUrl }}</a>
</p>
@endsection
