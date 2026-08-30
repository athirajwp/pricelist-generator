<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
            color: #ffffff;
            padding: 30px 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
            font-weight: 500;
        }
        .content {
            padding: 35px 40px;
        }
        .order-badge {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
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
        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .grid td {
            padding: 0;
            vertical-align: top;
            width: 50%;
        }
        .info-card {
            font-size: 13px;
            line-height: 1.6;
            color: #334155;
            padding-right: 15px;
        }
        .info-card strong {
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
        .items-table td.qty-col {
            text-align: center;
            font-weight: 600;
        }
        .items-table td.price-col {
            text-align: right;
            font-mono: true;
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
        .totals-table td {
            padding: 6px 12px;
            font-size: 13px;
            color: #64748b;
        }
        .totals-table tr.grand-total td {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            padding-top: 12px;
        }
        .totals-table tr.grand-total td.amount {
            color: #b91c1c;
        }
        .instructions-box {
            background-color: #f8fafc;
            border-left: 4px solid #cbd5e1;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 13px;
            line-height: 1.5;
            color: #475569;
        }
        .instructions-box strong {
            color: #1e293b;
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
            color: #b91c1c;
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
                <h1>{{ strtoupper(App\Models\Setting::get('store_name', 'Cracker Store')) }}</h1>
                <p>New Wholesale Booking Notification</p>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="order-badge">
                    Order #{{ $order->order_number }}
                </div>

                <!-- Customer Details & Booking Details Grid -->
                <table class="grid">
                    <tr>
                        <td>
                            <div class="info-card">
                                <div class="section-title">Delivery Details</div>
                                <strong>{{ $order->name }}</strong><br>
                                {{ $order->address }}<br>
                                @if($order->landmark)
                                    Landmark: {{ $order->landmark }}<br>
                                @endif
                                {{ $order->city }}, {{ $order->state }} - {{ $order->pincode }}<br>
                                <strong>Phone:</strong> {{ $order->phone }}<br>
                                @if($order->whatsapp)
                                    <strong>WhatsApp:</strong> {{ $order->whatsapp }}<br>
                                @endif
                                @if($order->email)
                                    <strong>Email:</strong> <a href="mailto:{{ $order->email }}" style="color: #b91c1c; text-decoration: none;">{{ $order->email }}</a>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="info-card" style="padding-left: 15px; padding-right: 0;">
                                <div class="section-title">Order Info</div>
                                <strong>Date:</strong> {{ $order->created_at->format('d/m/Y h:i A') }}<br>
                                <strong>Payment Status:</strong> {{ strtoupper($order->payment_status) }}<br>
                                <strong>Preferred Lorry:</strong> {{ $order->transport_name ?: 'Not Specified' }}<br>
                                @if($order->lr_number)
                                    <strong>LR Number:</strong> {{ $order->lr_number }}<br>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Ordered Items -->
                <div class="section-title">Ordered Items</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align: center;">Pack Size</th>
                            <th style="text-align: center; width: 60px;">Qty</th>
                            <th style="text-align: right; width: 90px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td class="desc-col">{{ $item->product_name }}</td>
                            <td style="text-align: center; color: #64748b; font-size: 12px;">{{ $item->pack_size }}</td>
                            <td class="qty-col">{{ $item->quantity }}</td>
                            <td class="price-col">₹{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Summary Totals -->
                <table class="totals-table">
                    <tr class="grand-total">
                        <td style="width: 60%;"></td>
                        <td style="text-align: right; font-weight: bold; padding-top: 10px; font-size: 14px;">Net Paid:</td>
                        <td style="text-align: right; width: 100px; font-weight: bold; color: #b91c1c; padding-top: 10px; font-size: 14px;">₹{{ number_format($order->net_amount, 2) }}</td>
                    </tr>
                </table>

                @if($order->notes)
                    <div class="instructions-box">
                        <strong>Notes/Instructions:</strong><br>
                        {!! nl2br(e($order->notes)) !!}
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer">
                This email was auto-generated by your online booking storefront.<br>
                <strong>{{ App\Models\Setting::get('store_name', 'Cracker Store') }}</strong> | <a href="{{ config('app.url') }}/admin">Go to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
