<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiry Invoice - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            color: #000000;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }

        .rupee {
            font-family: 'DejaVu Sans', sans-serif;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000000;
            padding: 20px;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px double #000000;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .header-brand {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .header-details {
            text-align: right;
            font-size: 11px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-col {
            width: 50%;
            vertical-align: top;
        }

        .info-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            border-bottom: 1px solid #000000;
            margin-bottom: 5px;
            padding-bottom: 2px;
            width: 90%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items-table th {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            text-align: left;
            padding: 8px 4px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 6px 4px;
            border-bottom: 1px dashed #cccccc;
        }

        .items-table tr.total-row td {
            border-top: 1px solid #000000;
            font-weight: bold;
            font-size: 11px;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .sign-row {
            margin-top: 35px;
            margin-bottom: 15px;
            width: 100%;
        }

        .sign-col {
            width: 50%;
            vertical-align: bottom;
            font-size: 11px;
        }

        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                border: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Printable container -->
    <div class="invoice-container">
        
        <!-- Header -->
        <table class="header-table" style="width: 100%; table-layout: fixed;">
            <tr>
                <td style="width: 38%; vertical-align: middle;">
                    <div class="header-brand">{{ strtoupper(App\Models\Setting::get('store_name', 'Cracker Demo')) }}</div>
                    <div style="font-size: 10px; margin-top: 3px;">
                        {{ App\Models\Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi') }}<br>
                        Phone: {{ App\Models\Setting::get('store_phone', '+91 9998887776') }} | Email: {{ App\Models\Setting::get('store_email', 'store@example.com') }}
                    </div>
                </td>
                <td style="width: 32%; text-align: center; vertical-align: middle;">
                    @php
                        $storeLogo = App\Models\Setting::get('store_logo', '');
                        $logoSrc = '';
                        if ($storeLogo) {
                            if (\Illuminate\Support\Str::startsWith($storeLogo, ['http', 'data:'])) {
                                $logoSrc = $storeLogo;
                            } elseif (isset($is_pdf_render) && $is_pdf_render) {
                                if (file_exists(public_path($storeLogo))) {
                                    try {
                                        $imageData = base64_encode(file_get_contents(public_path($storeLogo)));
                                        $mime = mime_content_type(public_path($storeLogo)) ?: 'image/png';
                                        $logoSrc = "data:{$mime};base64,{$imageData}";
                                    } catch (\Throwable $e) {
                                        $logoSrc = asset($storeLogo);
                                    }
                                } else {
                                    $logoSrc = asset($storeLogo);
                                }
                            } else {
                                $logoSrc = asset($storeLogo);
                            }
                        }
                    @endphp
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" style="max-height: 80px; max-width: 220px; object-fit: contain; margin: 0 auto; display: block;" alt="Logo">
                    @endif
                </td>
                <td class="header-details" style="width: 30%; vertical-align: middle; text-align: right;">
                    <div style="font-size: 13px; font-weight: bold;">ENQUIRY ESTIMATE</div>
                    <div style="margin-top: 5px;">
                        <strong>Enquiry No:</strong> {{ $order->order_number }}<br>
                        <strong>Enquiry Date:</strong> {{ $order->created_at->format('d/m/Y h:i A') }}<br>
                        <strong>Status:</strong> {{ strtoupper($order->order_status) }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Customer Billing information -->
        <table class="info-table">
            <tr>
                <td class="info-col">
                    <div class="info-title">Deliver To</div>
                    <strong>{{ $order->name }}</strong><br>
                    {{ $order->address }}<br>
                    @if($order->landmark)
                        Landmark: {{ $order->landmark }}<br>
                    @endif
                    {{ $order->city }}, {{ $order->state }} - {{ $order->pincode }}<br>
                    Phone: {{ $order->phone }}
                </td>
                <td class="info-col" style="padding-left: 20px;">
                    <div class="info-title">Enquiry Information</div>
                    <strong>Contact Mobile:</strong> {{ $order->phone }}<br>
                    <strong>WhatsApp:</strong> {{ $order->whatsapp ?: $order->phone }}<br>
                    <strong>Enquiry Date:</strong> {{ $order->created_at->format('d M Y, h:i a') }}<br>
                    @if($order->transport_name)
                        <strong>Transport Lorry:</strong> {{ $order->transport_name }}<br>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Invoice Ordered items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Item Details</th>
                    <th class="text-center">Pack</th>
                    <th class="text-right">Price (INR)</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Sub Total (INR)</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $sno = 1; 
                    $mrpSum = $order->subtotal > 0 ? $order->subtotal : ($order->net_amount + $order->discount_amount);
                @endphp
                @foreach($order->items as $item)
                <tr>
                    <td style="width: 5%;">{{ $sno++ }}</td>
                    <td style="width: 40%;"><strong>{{ $item->product_name }}</strong></td>
                    <td class="text-center" style="width: 15%;">{{ $item->pack_size }}</td>
                    <td class="text-right" style="width: 13%;">{{ number_format($item->price, 2) }}</td>
                    <td class="text-center" style="width: 8%;">{{ $item->quantity }}</td>
                    <td class="text-right" style="width: 19%;">{{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach

                <!-- Summary Breakdown rows -->
                @if($mrpSum > 0 && $mrpSum != $order->net_amount)
                <tr class="total-row">
                    <td colspan="4" style="border: none;"></td>
                    <td class="text-right" style="padding-top: 10px; color: #555555;">Subtotal printed MRP sum:</td>
                    <td class="text-right" style="padding-top: 10px; color: #555555; text-decoration: line-through;"><span class="rupee">&#8377;</span>{{ number_format($mrpSum, 2) }}</td>
                </tr>
                @endif
                @if($order->discount_amount > 0)
                <tr class="total-row">
                    <td colspan="4" style="border: none;"></td>
                    <td class="text-right" style="color: #2e7d32;">Discount Savings:</td>
                    <td class="text-right" style="color: #2e7d32;">-<span class="rupee">&#8377;</span>{{ number_format($order->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row" style="font-size: 13px;">
                    <td colspan="4" style="border: none;"></td>
                    <td class="text-right" style="padding-top: 8px; border-top: 2px solid #000000; border-bottom: 2px solid #000000;">Net Amount:</td>
                    <td class="text-right" style="padding-top: 8px; border-top: 2px solid #000000; border-bottom: 2px solid #000000;"><span class="rupee">&#8377;</span>{{ number_format($order->net_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Statutory Legal Notice Banner -->
        <div style="font-size: 10px; margin-top: 15px; margin-bottom: 15px; background: #fffbe6; padding: 10px 12px; border: 1px solid #ffe58f; color: #873800; border-radius: 4px; line-height: 1.5;">
            <strong>Statutory Legal Notice:</strong> This website is for product information and enquiry purposes only and does not accept online firecracker orders or online payments. All purchases are fulfilled through authorised and licensed channels in accordance with applicable laws and local regulations.
        </div>

        <!-- Signature Lines -->
        <table class="sign-row">
            <tr>
                <td class="sign-col">
                    Customer Signature
                </td>
                <td class="sign-col text-right">
                    For <strong>Customer</strong>
                </td>
            </tr>
        </table>

    </div>

    <!-- Print triggers -->
    @if(!isset($is_email_or_pdf) || !$is_email_or_pdf)
    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; font-weight: bold; background: #000000; color: #ffffff; cursor: pointer; border: none;">PRINT ENQUIRY ESTIMATE</button>
    </div>
    @endif

</body>
</html>
