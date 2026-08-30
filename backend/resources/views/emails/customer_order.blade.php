<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 30px 15px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #ffffff;
            padding: 30px 40px;
            text-align: center;
        }
        .header .checkmark {
            font-size: 42px;
            display: block;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .content {
            padding: 35px 40px;
        }
        .order-badge {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 10px;
        }
        .subtext {
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 5px 0;
            color: #334155;
            border-bottom: 1px dashed #f1f5f9;
        }
        .info-row strong {
            color: #0f172a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .items-table td.desc-col {
            font-weight: 600;
            color: #0f172a;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .totals-table tr.grand-total td {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            padding: 12px 12px 0;
        }
        .totals-table tr.grand-total td.amount {
            color: #15803d;
            text-align: right;
        }
        .info-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 15px 18px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 13px;
            line-height: 1.6;
            color: #78350f;
        }
        .info-box strong {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .track-btn {
            display: block;
            width: fit-content;
            margin: 25px auto 0;
            background-color: #15803d;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 25px 40px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #15803d;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <span class="checkmark">✅</span>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your order at {{ App\Models\Setting::get('store_name', 'Cracker Store') }}</p>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="order-badge">Order #{{ $order->order_number }}</div>

                <p class="greeting">Hello, {{ $order->name }}! 🎉</p>
                <p class="subtext">
                    We've successfully received your order. Our team will review it shortly and get in touch with you for confirmation and delivery coordination.
                </p>

                <!-- Order Summary -->
                <div class="section-title">Order Summary</div>
                <table style="width:100%; border-collapse:collapse; margin-bottom:20px; font-size:13px; color:#334155;">
                    <tr>
                        <td style="padding:4px 0; color:#64748b;">Order Number</td>
                        <td style="padding:4px 0; text-align:right; font-weight:700; color:#0f172a;">{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#64748b;">Order Date</td>
                        <td style="padding:4px 0; text-align:right;">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#64748b;">Payment Status</td>
                        <td style="padding:4px 0; text-align:right; font-weight:600; color:#d97706;">{{ strtoupper($order->payment_status) }}</td>
                    </tr>
                </table>

                <!-- Delivery Details -->
                <div class="section-title">Delivery Details</div>
                <table style="width:100%; border-collapse:collapse; margin-bottom:20px; font-size:13px; color:#334155;">
                    <tr>
                        <td style="padding:4px 0; color:#64748b;">Name</td>
                        <td style="padding:4px 0; text-align:right; font-weight:600; color:#0f172a;">{{ $order->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; color:#64748b;">Phone</td>
                        <td style="padding:4px 0; text-align:right;">{{ $order->phone }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 8px 4px 0; color:#64748b; vertical-align:top;">Address</td>
                        <td style="padding:4px 0; text-align:right; line-height:1.5;">
                            {{ $order->address }}@if($order->landmark), {{ $order->landmark }}@endif<br>
                            {{ $order->city }}, {{ $order->state }} - {{ $order->pincode }}
                        </td>
                    </tr>
                </table>

                <!-- Ordered Items -->
                <div class="section-title">Items Ordered</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align:center; width:80px;">Qty</th>
                            <th style="text-align:right; width:90px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td class="desc-col">
                                {{ $item->product_name }}
                                @if($item->pack_size)
                                    <br><span style="font-size:11px; color:#94a3b8; font-weight:400;">{{ $item->pack_size }}</span>
                                @endif
                            </td>
                            <td style="text-align:center; font-weight:600;">{{ $item->quantity }}</td>
                            <td style="text-align:right;">₹{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Grand Total -->
                <table class="totals-table">
                    <tr class="grand-total">
                        <td style="width:60%;"></td>
                        <td style="text-align:right; padding-top:12px;">Total Amount:</td>
                        <td class="amount">₹{{ number_format($order->net_amount, 2) }}</td>
                    </tr>
                </table>

                <!-- Payment Notice -->
                <div class="info-box">
                    <strong>⚠️ Payment Pending</strong>
                    Please complete your UPI/Bank transfer payment and share the screenshot on WhatsApp to confirm your booking. Our team will contact you shortly.
                </div>

                <!-- Track Button -->
                <a href="{{ route('track.index', ['query' => $order->order_number]) }}" class="track-btn">
                    📦 Track Your Order
                </a>
            </div>

            <!-- Footer -->
            <div class="footer">
                Questions? Contact us on WhatsApp or call us directly.<br>
                <strong>{{ App\Models\Setting::get('store_name', 'Cracker Store') }}</strong> — Sivakasi's Trusted Crackers<br>
                <a href="{{ config('app.url') }}">Visit our store</a>
            </div>
        </div>
    </div>
</body>
</html>
