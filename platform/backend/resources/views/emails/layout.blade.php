<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? '' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            color: #51545e;
            line-height: 1.6;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f7;
            padding: 40px 0;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .email-header {
            background-color: #2d3748;
            padding: 24px 32px;
            text-align: center;
        }
        .email-header img {
            max-height: 48px;
            max-width: 200px;
        }
        .email-header .store-name {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }
        .email-body {
            padding: 32px;
        }
        .email-body h1 {
            color: #2d3748;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 16px;
        }
        .email-body p {
            color: #51545e;
            font-size: 15px;
            margin: 0 0 16px;
        }
        .email-body .btn {
            display: inline-block;
            background-color: #3490dc;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            margin: 8px 0;
        }
        .email-body table.order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .email-body table.order-table th {
            background-color: #f7fafc;
            text-align: left;
            padding: 10px 12px;
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }
        .email-body table.order-table td {
            padding: 10px 12px;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .email-body .totals-table {
            width: 100%;
            margin: 16px 0;
        }
        .email-body .totals-table td {
            padding: 6px 12px;
            font-size: 14px;
        }
        .email-body .totals-table .total-row td {
            font-weight: 700;
            font-size: 16px;
            border-top: 2px solid #2d3748;
            padding-top: 10px;
        }
        .email-footer {
            padding: 24px 32px;
            text-align: center;
            background-color: #f7fafc;
            border-top: 1px solid #e2e8f0;
        }
        .email-footer p {
            color: #a0aec0;
            font-size: 13px;
            margin: 0 0 4px;
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-content">
        {{-- Header --}}
        <div class="email-header">
            @if(!empty($store->logo_url))
                <img src="{{ $store->logo_url }}" alt="{{ $store->name }}">
            @else
                <p class="store-name">{{ $store->name }}</p>
            @endif
        </div>

        {{-- Body --}}
        <div class="email-body">
            @yield('content')
        </div>

        {{-- Footer --}}
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ $store->name }}. All rights reserved.</p>
            @if(!empty($store->address))
                <p>
                    @php
                        $addr = is_array($store->address) ? $store->address : [];
                    @endphp
                    {{ implode(', ', array_filter([
                        $addr['street'] ?? null,
                        $addr['city'] ?? null,
                        $addr['state'] ?? null,
                        $addr['zip'] ?? null,
                        $addr['country'] ?? null,
                    ])) }}
                </p>
            @endif
            <p>{{ $store->email }}</p>
        </div>
    </div>
</div>
</body>
</html>
