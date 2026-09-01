<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $editForm['store_name'] ?? 'MASS CRACKERS' }} - Price List {{ $editForm['store_year'] ?? date('Y') }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 0;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 11px;
        }
        @php
            $bgPath = public_path("images/cover_bg.jpg");
            if (!empty($settings['store_cover_bg'])) {
                if (str_starts_with($settings['store_cover_bg'], 'uploads/')) {
                    $bgPath = public_path($settings['store_cover_bg']);
                } else {
                    $bgPath = $settings['store_cover_bg'];
                }
            }
        @endphp
        .page-sheet {
            width: 210mm;
            height: 297mm;
            padding: 10mm 12mm;
            box-sizing: border-box;
            page-break-after: always;
            position: relative;
            background-image: url('{{ $bgPath }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        /* COVER PAGE STYLING */
        .cover-sheet {
            width: 210mm;
            height: 297mm;
            padding: 12mm 15mm;
            box-sizing: border-box;
            page-break-after: always;
            position: relative;
            background-image: url('{{ $bgPath }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #ffffff;
            text-align: center;
        }
        .cover-invocation {
            font-size: 11px;
            font-weight: bold;
            color: #fef08a;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        @php
            $fontChoice = $settings['store_name_font'] ?? 'cinzel';
            $fontFamilyStr = "'Cinzel Decorative', 'Cinzel', serif";
            if ($fontChoice === 'black') {
                $fontFamilyStr = "'Montserrat', 'Arial', sans-serif";
            } elseif ($fontChoice === 'playfair') {
                $fontFamilyStr = "'Playfair Display', 'Georgia', serif";
            } elseif ($fontChoice === 'outfit') {
                $fontFamilyStr = "'Outfit', 'Arial', sans-serif";
            }
        @endphp
        .cover-title {
            font-size: 42px;
            font-weight: 900;
            color: #ffffff;
            font-family: {{ $fontFamilyStr }};
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 5px 0;
            text-shadow: -2px -2px 0 #000000, 2px -2px 0 #000000, -2px 2px 0 #000000, 2px 2px 0 #000000, 0 6px 12px rgba(0,0,0,0.9);
        }
        .cover-tagline {
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .cover-year-pill {
            color: #0f172a;
            font-size: 20px;
            font-weight: 900;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-shadow: -2px -2px 0 #ffffff, 2px -2px 0 #ffffff, -2px 2px 0 #ffffff, 2px 2px 0 #ffffff, 0 3px 6px rgba(0,0,0,0.5);
            margin-bottom: 20px;
        }
        
        .cover-card {
            background: #ffffff;
            color: #0f172a;
            border-radius: 16px;
            border: 2px solid #fde047;
            padding: 16px 20px;
            text-align: left;
        }
        .cover-badge {
            background: #dc2626;
            color: #ffffff;
            border-radius: 16px;
            border: 2px solid #fde047;
            padding: 14px 20px;
            text-align: center;
        }
        
        /* TABLE STYLING */
        table.pricelist-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #334155;
            margin-top: 2px;
        }
        table.pricelist-table th {
            background-color: #fef3c7;
            color: #000000;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 6px {{ $settings['table_col_padding'] ?? '4' }}px;
            border: 1px solid #d97706;
            text-align: center;
        }
        table.pricelist-table td {
            font-size: 11px;
            padding: 3.5px {{ $settings['table_col_padding'] ?? '4' }}px;
            border: 1px solid #64748b;
        }
        tr.category-row td {
            background: linear-gradient(90deg, #f59e0b 0%, #facc15 50%, #f59e0b 100%);
            color: #0f172a;
            font-weight: 900;
            font-size: 11px;
            text-align: center;
            text-transform: uppercase;
            padding: 4px;
            letter-spacing: 0.5px;
            border: 1px solid #d97706;
        }
        tr.product-row {
            height: {{ $settings['table_row_height'] ?? '22' }}px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier', monospace; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .line-through { text-decoration: line-through; color: #000000; }
        .price-offer { color: #000000; font-weight: bold; }
        .req-box {
            width: 16px;
            height: 12px;
            border: 1px solid #64748b;
            border-radius: 2px;
            margin: 0 auto;
            background-color: #f8fafc;
        }
        .footer-note {
            position: absolute;
            bottom: 6mm;
            left: 12mm;
            right: 12mm;
            text-align: center;
            font-size: 8.5px;
            font-weight: bold;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <!-- PAGE 1: COVER PAGE -->
    <div class="page-sheet cover-sheet">
        <div class="cover-invocation" style="text-align: center; margin-top: 0px; margin-bottom: 5px;">
            @if(!empty($editForm['store_invocation_symbol']))
            <div style="font-size: 11px; font-weight: 900; color: #ffffff; margin-bottom: 1px;">{{ $editForm['store_invocation_symbol'] }}</div>
            @endif
            @if(!empty($editForm['store_invocation']))
            <div style="font-size: 9px; font-weight: 900; color: #ffffff; letter-spacing: 0.5px;">{{ $editForm['store_invocation'] }}</div>
            @endif
        </div>

        <div style="margin-top: 25px; margin-bottom: 10px;">
            <div class="cover-title">{{ $editForm['store_name'] ?? 'MASS CRACKERS' }}</div>
            <div class="cover-tagline">"{{ $editForm['store_tagline'] ?? 'Ready for the Sparkle' }}"</div>
            <div class="cover-year-pill">PRICE LIST - {{ $editForm['store_year'] ?? date('Y') }}</div>
        </div>

        @php
            $deityPreset = $settings['store_deity_preset'] ?? 'vinayagar';
            $deityImgPath = null;
            if ($deityPreset === 'custom' && !empty($settings['store_deity_image']) && file_exists(public_path($settings['store_deity_image']))) {
                $deityImgPath = public_path($settings['store_deity_image']);
            } elseif ($deityPreset !== 'none') {
                $presetMap = [
                    'vinayagar' => public_path('images/god_vinayagar.png'),
                    'murugan' => public_path('images/god_murugan.png'),
                    'perumal' => public_path('images/god_perumal.png'),
                    'lakshmi' => public_path('images/god_lakshmi.png'),
                    'default' => public_path('images/god_default.png'),
                ];
                $deityImgPath = $presetMap[$deityPreset] ?? public_path('images/god_vinayagar.png');
            }
        @endphp

        @if(!empty($deityImgPath) && file_exists($deityImgPath))
        <div style="text-align: center; margin-top: 5px; margin-bottom: 5px;">
            <img src="{{ $deityImgPath }}" style="max-height: 741px; width: auto;" alt="Deity"/>
        </div>
        @endif

        <div style="width: 100%; margin-left: 0; margin-top: auto; margin-bottom: 0;">
            <div style="background: #ffffff; border: 2px solid #f59e0b; border-radius: 12px; padding: 10px 14px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <!-- Left: Store Logo (Golden Double Circle Ring) -->
                        <td style="width: 25%; vertical-align: middle; text-align: left;">
                            @if(!empty($settings['store_logo']) && file_exists(public_path($settings['store_logo'])))
                            <img src="{{ public_path($settings['store_logo']) }}" style="max-height: 85px; width: auto; border-radius: 50%; border: 4px solid #f59e0b;" alt="Store Logo"/>
                            @else
                            <img src="{{ public_path('images/festive_firecrackers.png') }}" style="max-height: 85px; width: auto;" alt="Logo"/>
                            @endif
                        </td>
                        <!-- Center: Contact Info (Website, up to 4 Phone numbers, GPay) -->
                        <td style="width: 48%; vertical-align: middle; text-align: left; padding: 0 10px;">
                            <div style="font-size: 12px; font-weight: 900; line-height: 1.8; color: #0f172a; letter-spacing: 0.3px;">
                                @if(!empty($editForm['store_email']))
                                🌐 <strong>{{ $editForm['store_email'] }}</strong><br/>
                                @endif
                                📞 <strong>{{ implode(' , ', array_filter([$editForm['store_phone'] ?? '', $editForm['store_phone_2'] ?? '', $editForm['store_phone_3'] ?? '', $editForm['store_phone_4'] ?? ''])) }}</strong><br/>
                                @if(!empty($settings['store_gpay']) || !empty($editForm['store_phone_3']))
                                💳 <strong>{{ $settings['store_gpay'] ?? $editForm['store_phone_3'] }}</strong>
                                @endif
                            </div>
                        </td>
                        <!-- Right: Dynamic Mega Sale Offer Badge -->
                        <td style="width: 27%; vertical-align: middle; text-align: right;">
                            <div style="text-align: center; display: inline-block;">
                                <div style="font-size: 13px; font-weight: 900; color: #d97706; text-transform: uppercase; letter-spacing: 1px;">MEGA SALE</div>
                                <div style="font-size: 38px; font-weight: 900; color: #dc2626; line-height: 1; text-shadow: -1px -1px 0 #ffffff, 1px -1px 0 #ffffff, -1px 1px 0 #ffffff, 1px 1px 0 #ffffff;">{{ $discountPercent }}%</div>
                                <div style="background: #dc2626; color: #ffffff; font-size: 9px; font-weight: 900; padding: 2px 8px; border-radius: 3px; display: inline-block; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px;">DISCOUNT</div>
                            </div>
                        </td>
                    </tr>
                </table>

                @if(!empty($editForm['store_address']))
                <div style="text-align: center; font-size: 10.5px; font-weight: 900; color: #0f172a; margin-top: 8px; padding-top: 6px; border-top: 1px solid #e2e8f0;">
                    📍 {{ $editForm['store_address'] }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- PRODUCT PAGES -->
    @php $globalSnoCounter = 1; @endphp
    @foreach($productPageChunks as $chunkIdx => $chunkProducts)
        <div class="page-sheet">
            <table class="pricelist-table">
                <thead>
                    <tr>
                        <th style="width: 38px;">S.No</th>
                        <th>PRODUCT</th>
                        <th style="width: 85px;">Unit</th>
                        @if($showMrp)
                        <th style="width: 70px;">Rate (₹)</th>
                        @endif
                        <th style="width: 100px;">{{ $discountPercent }}% Offer Rate (₹)</th>
                        <th style="width: 35px;">Req</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $chunkCategories = [];
                        foreach($chunkProducts as $prod) {
                            $catId = $prod['category_id'];
                            if(!isset($chunkCategories[$catId])) {
                                $chunkCategories[$catId] = [
                                    'id' => $catId,
                                    'name' => $prod['category_name'],
                                    'products' => []
                                ];
                            }
                            $chunkCategories[$catId]['products'][] = $prod;
                        }
                    @endphp

                    @foreach($chunkCategories as $cat)
                        <tr class="category-row">
                            <td colspan="{{ $showMrp ? 6 : 5 }}">{{ $cat['name'] }}</td>
                        </tr>
                        @foreach($cat['products'] as $product)
                            @php
                                $displaySno = (!empty($product['product_code']) && trim((string)$product['product_code']) !== '')
                                    ? $product['product_code']
                                    : $globalSnoCounter;
                                $globalSnoCounter++;
                            @endphp
                            <tr class="product-row" style="color: #000000; font-weight: bold;">
                                <td class="text-center font-bold" style="color: #000000;">{{ $displaySno }}</td>
                                <td class="font-bold" style="color: #000000;">{{ $product['name'] }}</td>
                                <td class="text-center font-bold" style="color: #000000; font-size: 10px;">{{ $product['pack_size'] }}</td>
                                @if($showMrp)
                                <td class="text-right font-bold" style="color: #000000;">₹{{ number_format((float)$product['mrp'], 2) }}</td>
                                @endif
                                <td class="text-right font-bold" style="color: #000000;">₹{{ number_format((float)$product['selling_price'], 2) }}</td>
                                <td class="text-center"></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>

            @if($loop->last)
            @if(($settings['footer_position'] ?? 'below_table') === 'new_page')
            <div style="page-break-before: always; height: 1px;"></div>
            @endif
            <!-- UPI SCAN & PAY QR CODE + BANK ACCOUNT INFO CARDS -->
            <table style="width: 100%; border-collapse: separate; border-spacing: 12px; margin-top: 15px;">
                <tr>
                    <!-- Left: UPI QR Code Card -->
                    <td style="width: 50%; vertical-align: top; background: #ffffff; color: #0f172a; border-radius: 16px; border: 3.5px solid #f59e0b; padding: 15px; text-align: center;">
                        <div style="background: #f59e0b; color: #ffffff; font-size: 11px; font-weight: 900; padding: 3px 10px; border-radius: 20px; display: inline-block; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                            📱 SCAN & PAY VIA UPI
                        </div>
                        <div>
                            @php
                                $qrSrc = !empty($settings['store_upi_qr']) && file_exists(public_path($settings['store_upi_qr']))
                                    ? public_path($settings['store_upi_qr'])
                                    : 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=' . urlencode($settings['store_gpay'] ?? '9787772038') . '%40okicici';
                            @endphp
                            <img src="{{ $qrSrc }}" style="max-height: 140px; width: auto; border: 2px solid #fde047; border-radius: 10px; padding: 4px; background: #ffffff;" alt="UPI QR Code"/>
                        </div>
                        <div style="font-size: 11px; font-weight: 900; color: #0f172a; margin-top: 6px;">
                            GPay / PhonePe / Paytm / BHIM<br/>
                            <span style="font-family: monospace; font-size: 12px; color: #0f172a;">UPI / Mobile: {{ $settings['store_gpay'] ?? $editForm['store_phone_3'] ?? '9787772038' }}</span>
                        </div>
                    </td>

                    <!-- Right: Bank Account Info Card -->
                    <td style="width: 50%; vertical-align: top; background: #ffffff; color: #0f172a; border-radius: 16px; border: 3.5px solid #f59e0b; padding: 15px; text-align: left;">
                        <div style="background: #f59e0b; color: #ffffff; font-size: 11px; font-weight: 900; padding: 3px 10px; border-radius: 20px; display: inline-block; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                            🏦 BANK ACCOUNT INFO
                        </div>
                        <div style="font-size: 11px; font-weight: 900; line-height: 1.8; color: #0f172a;">
                            <strong>Account Name:</strong> {{ $editForm['bank_name'] ?? 'Muthusamy Ganesan' }}<br/>
                            <strong>Bank / Branch:</strong> {{ $editForm['bank_branch'] ?? 'IDBI Bank' }}<br/>
                            <strong>Account No:</strong> <span style="font-family: monospace;">{{ $editForm['bank_account_no'] ?? '1118104000136815' }}</span><br/>
                            <strong>IFSC Code:</strong> <span style="font-family: monospace;">{{ $editForm['bank_ifsc'] ?? 'IBKL0001118' }}</span>
                        </div>
                        <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 5px; text-align: center; font-size: 9.5px; font-weight: 900; margin-top: 10px; color: #78350f;">
                            ⚡ Quick Bank Transfer / IMPS Available
                        </div>
                    </td>
                </tr>
            </table>

            @if(!empty($editForm['important_note_1']) || !empty($editForm['important_note_2']))
            <div style="margin-top: 10px; background: #fffbeb; border: 1.5px solid #fde047; border-radius: 10px; padding: 10px 14px; text-align: center;">
                @if(!empty($editForm['important_note_1']))
                <div style="color: #0f172a; font-size: 10.5px; font-weight: 900; line-height: 1.4;">{{ $editForm['important_note_1'] }}</div>
                @endif
                @if(!empty($editForm['important_note_2']))
                <div style="color: #b91c1c; font-size: 10.5px; font-weight: 900; line-height: 1.4; margin-top: 4px;">{{ $editForm['important_note_2'] }}</div>
                @endif
            </div>
            @endif
            @endif

            <div class="footer-note">
                📄 Page {{ $chunkIdx + 2 }} of {{ count($productPageChunks) + 1 + (($settings['footer_position'] ?? 'below_table') === 'new_page' ? 1 : 0) }} &bull; {{ $editForm['store_name'] ?? 'MASS CRACKERS' }} &bull; 210mm × 297mm
            </div>
        </div>
    @endforeach

</body>
</html>
