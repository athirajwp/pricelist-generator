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
            if (!function_exists('formatMarkdownPdf')) {
                function formatMarkdownPdf($text) {
                    if (empty($text)) return '';
                    $html = e($text);
                    $html = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<strong><em>$1</em></strong>', $html);
                    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
                    $html = preg_replace('/__(.*?)__/s', '<strong>$1</strong>', $html);
                    $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html);
                    $html = preg_replace('/_(.*?)_/s', '<em>$1</em>', $html);
                    return $html;
                }
            }

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
            height: 277mm;
            padding: 10mm 12mm;
            box-sizing: border-box;
            page-break-after: always;
            page-break-inside: avoid;
            position: relative;
            background-image: url('{{ $bgPath }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        /* COVER PAGE STYLING */
        .cover-sheet {
            width: 210mm;
            height: 273mm;
            padding: 12mm 15mm;
            box-sizing: border-box;
            page-break-after: always;
            page-break-inside: avoid;
            position: relative;
            background-image: url('{{ $bgPath }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #ffffff;
            text-align: center;
        }
        @php
            $strokeC = $editForm['text_stroke_color'] ?? '#000000';
            $deityStrokeC = $editForm['deity_stroke_color'] ?? '#FFFFFF';
            $deityFilterCss = ($deityStrokeC === 'transparent' || !$deityStrokeC)
                ? 'drop-shadow(0 12px 24px rgba(0, 0, 0, 0.6))'
                : "drop-shadow(-2px -2px 0 {$deityStrokeC}) drop-shadow(2px -2px 0 {$deityStrokeC}) drop-shadow(-2px 2px 0 {$deityStrokeC}) drop-shadow(2px 2px 0 {$deityStrokeC}) drop-shadow(0 12px 24px rgba(0, 0, 0, 0.6))";
        @endphp
        .cover-invocation {
            font-size: 11px;
            font-weight: 900;
            color: #fef08a;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-transform: uppercase;
            text-shadow: -1.5px -1.5px 0 {{ $strokeC }}, 1.5px -1.5px 0 {{ $strokeC }}, -1.5px 1.5px 0 {{ $strokeC }}, 1.5px 1.5px 0 {{ $strokeC }}, 0 2px 4px rgba(0,0,0,0.5);
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
            text-shadow: -2px -2px 0 {{ $strokeC }}, 2px -2px 0 {{ $strokeC }}, -2px 2px 0 {{ $strokeC }}, 2px 2px 0 {{ $strokeC }}, 0 4px 8px rgba(0,0,0,0.5);
        }
        .cover-tagline {
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            text-shadow: -1.5px -1.5px 0 {{ $strokeC }}, 1.5px -1.5px 0 {{ $strokeC }}, -1.5px 1.5px 0 {{ $strokeC }}, 1.5px 1.5px 0 {{ $strokeC }}, 0 2px 4px rgba(0,0,0,0.5);
        }
        .cover-year-pill {
            color: #0f172a;
            font-size: 20px;
            font-weight: 900;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-shadow: -2px -2px 0 #ffffff, 2px -2px 0 #ffffff, -2px 2px 0 #ffffff, 2px 2px 0 #ffffff, 0 3px 6px rgba(0,0,0,0.4);
            margin-bottom: 20px;
        }
        .cover-deity img {
            max-height: 480px;
            max-width: 480px;
            object-fit: contain;
            filter: {{ $deityFilterCss }};
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
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 4px {{ $settings['table_col_padding'] ?? '4' }}px;
            border: 1px solid #d97706;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.15;
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
        @php
            $customFloatImg = !empty($editForm['custom_float_image']) ? $editForm['custom_float_image'] : (!empty($settings['custom_float_image']) ? $settings['custom_float_image'] : null);
            $customFloatShow = isset($editForm['show_custom_float_image']) ? $editForm['show_custom_float_image'] : (isset($settings['show_custom_float_image']) ? $settings['show_custom_float_image'] : true);
            $customFloatX = isset($editForm['custom_float_x']) ? $editForm['custom_float_x'] : (isset($settings['custom_float_x']) ? $settings['custom_float_x'] : 15);
            $customFloatY = isset($editForm['custom_float_y']) ? $editForm['custom_float_y'] : (isset($settings['custom_float_y']) ? $settings['custom_float_y'] : 15);
            $customFloatScale = isset($editForm['custom_float_scale']) ? $editForm['custom_float_scale'] : (isset($settings['custom_float_scale']) ? $settings['custom_float_scale'] : 100);
            
            $customFloatPath = null;
            if ($customFloatImg) {
                if (str_starts_with($customFloatImg, 'data:')) {
                    $customFloatPath = $customFloatImg;
                } elseif (file_exists(public_path($customFloatImg))) {
                    $customFloatPath = public_path($customFloatImg);
                }
            }
        @endphp

        @if($customFloatPath && $customFloatShow !== false && $customFloatShow !== 'false')
        <div style="position: absolute; left: {{ $customFloatX }}%; top: {{ $customFloatY }}%; z-index: 35; transform: scale({{ $customFloatScale / 100 }}); transform-origin: top left;">
            <img src="{{ $customFloatPath }}" style="max-width: 250px; max-height: 250px; object-fit: contain;" alt="Custom Float"/>
        </div>
        @endif

        <div class="cover-invocation" style="text-align: center; margin-top: 0px; margin-bottom: 5px;">
            @if(!empty($editForm['store_invocation_symbol']))
            <div style="font-size: 11px; font-weight: 900; color: {{ !empty($editForm['store_invocation_color']) ? $editForm['store_invocation_color'] : '#ffffff' }}; margin-bottom: 1px;">{{ $editForm['store_invocation_symbol'] }}</div>
            @endif
            @if(!empty($editForm['store_invocation']))
            <div style="font-size: 9px; font-weight: 900; color: {{ !empty($editForm['store_invocation_color']) ? $editForm['store_invocation_color'] : '#ffffff' }}; letter-spacing: 0.5px;">{{ $editForm['store_invocation'] }}</div>
            @endif
        </div>

        <div style="margin-top: 25px; margin-bottom: 10px;">
            <div class="cover-title" style="{{ !empty($editForm['store_title_color']) ? 'color: ' . $editForm['store_title_color'] . ';' : '' }}">{{ $editForm['store_name'] ?? 'MASS CRACKERS' }}</div>
            <div class="cover-tagline" style="{{ !empty($editForm['store_tagline_color']) ? 'color: ' . $editForm['store_tagline_color'] . ';' : '' }}">"{{ $editForm['store_tagline'] ?? 'Ready for the Sparkle' }}"</div>
            <div class="cover-year-pill" style="{{ !empty($editForm['store_badge_color']) ? 'color: ' . $editForm['store_badge_color'] . ';' : '' }}">PRICE LIST - {{ $editForm['store_year'] ?? date('Y') }}</div>
        </div>

        @php
            $deityPreset = $settings['store_deity_preset'] ?? 'vinayagar';
            $deityImgPath = null;
            if(!empty($editForm['store_deity_image']) && file_exists(public_path($editForm['store_deity_image']))) {
                $deityImgPath = public_path($editForm['store_deity_image']);
            } elseif(!empty($settings['store_deity_image']) && file_exists(public_path($settings['store_deity_image']))) {
                $deityImgPath = public_path($settings['store_deity_image']);
            }
        @endphp

        @if($deityImgPath)
        <div class="cover-deity">
            <img src="{{ $deityImgPath }}" alt="Deity Motif"/>
        </div>
        @else
        <div style="height: 250px;"></div>
        @endif

        <div style="width: 100%; margin-top: auto;">
            <div class="cover-banner">
                <div style="display: table; width: 100%;">
                    <div style="display: table-row;">
                        <div style="display: table-cell; width: 25%; vertical-align: middle;">
                            @php
                                $logoPath = !empty($settings['store_logo']) && file_exists(public_path($settings['store_logo']))
                                    ? public_path($settings['store_logo'])
                                    : null;
                            @endphp
                            @if($logoPath)
                            <img src="{{ $logoPath }}" class="cover-logo" alt="Logo"/>
                            @else
                            <div style="font-size: 16px; font-weight: 900; color: #dc2626;">{{ $editForm['store_name'] ?? 'MASS CRACKERS' }}</div>
                            @endif
                        </div>
                        <div style="display: table-cell; width: 50%; vertical-align: middle;">
                            @if(!empty($editForm['store_email']))
                            <div class="contact-item">🌐 {{ $editForm['store_email'] }}</div>
                            @endif
                            <div class="contact-item">📞 {{ implode(' , ', array_filter([$editForm['store_phone'] ?? '', $editForm['store_phone_2'] ?? '', $editForm['store_phone_3'] ?? '', $editForm['store_phone_4'] ?? ''])) }}</div>
                            @if(!empty($editForm['store_gpay']) || !empty($editForm['store_phone_3']))
                            <div class="contact-item">💳 GPay: {{ $editForm['store_gpay'] ?? $editForm['store_phone_3'] }}</div>
                            @endif
                        </div>
                        @if(($editForm['show_discount_badge'] ?? true) !== false)
                        <div style="display: table-cell; width: 25%; vertical-align: middle; text-align: right;">
                            <div class="cover-badge">
                                <div style="font-size: 12px; font-weight: 900; color: #fde047; text-transform: uppercase;">MEGA SALE</div>
                                <div style="font-size: 26px; font-weight: 900; color: #ffffff; line-height: 1;">{{ $discountPercent }}%</div>
                                <div style="font-size: 9px; font-weight: 900; color: #ffffff; letter-spacing: 1px;">DISCOUNT</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @if(!empty($editForm['store_address']))
            <div class="cover-address">📍 {{ $editForm['store_address'] }}</div>
            @endif
        </div>
    </div>

    <!-- PRODUCT PAGES -->
    @php 
        $globalSnoCounter = 1; 
        $showSno = isset($editForm['show_col_sno']) ? (bool)$editForm['show_col_sno'] : true;
        $showProduct = isset($editForm['show_col_product']) ? (bool)$editForm['show_col_product'] : true;
        $showTamilName = !empty($editForm['show_tamil_name']);
        $showUnit = isset($editForm['show_col_unit']) ? (bool)$editForm['show_col_unit'] : true;
        $showMrp = isset($showMrp) ? (bool)$showMrp : (isset($editForm['show_col_mrp']) ? (bool)$editForm['show_col_mrp'] : true);
        $showOffer = isset($editForm['show_col_offer']) ? (bool)$editForm['show_col_offer'] : true;
        $showReq = isset($editForm['show_col_req']) ? (bool)$editForm['show_col_req'] : true;
        $activeColCount = ($showSno ? 1 : 0) + ($showProduct ? 1 : 0) + ($showTamilName ? 1 : 0) + ($showUnit ? 1 : 0) + ($showMrp ? 1 : 0) + ($showOffer ? 1 : 0) + ($showReq ? 1 : 0);
    @endphp

    @foreach($productPageChunks as $chunkIdx => $chunkProducts)
        <div class="page-sheet">
            <table class="pricelist-table">
                <thead>
                    <tr>
                        @if($showSno)
                        <th style="width: {{ $showTamilName ? '32px' : '38px' }};">{{ $editForm['header_sno'] ?? 'S.NO' }}</th>
                        @endif
                        @if($showProduct)
                        <th style="{{ $showTamilName ? 'width: 170px;' : '' }}">{{ $editForm['header_product'] ?? ($showTamilName ? 'PRODUCT NAME (ENG)' : 'PRODUCT') }}</th>
                        @endif
                        @if($showTamilName)
                        <th style="width: 170px;">{{ $editForm['header_product_ta'] ?? 'பொருள் பெயர் (TAMIL)' }}</th>
                        @endif
                        @if($showUnit)
                        <th style="width: {{ $showTamilName ? '70px' : '85px' }};">{{ $editForm['header_unit'] ?? 'UNIT' }}</th>
                        @endif
                        @if($showMrp)
                        <th style="width: {{ $showTamilName ? '65px' : '70px' }};">{{ $editForm['header_mrp'] ?? 'RATE (₹)' }}</th>
                        @endif
                        @if($showOffer)
                        <th style="width: {{ $showTamilName ? '90px' : '100px' }};">{{ $editForm['header_offer'] ?? ($discountPercent . '% OFFER RATE (₹)') }}</th>
                        @endif
                        @if($showReq)
                        <th style="width: {{ $showTamilName ? '32px' : '35px' }};">{{ $editForm['header_req'] ?? 'REQ' }}</th>
                        @endif
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
                            <td colspan="{{ $activeColCount ?? 5 }}">{{ $cat['name'] }}</td>
                        </tr>
                        @foreach($cat['products'] as $product)
                            @php
                                $displaySno = (!empty($product['product_code']) && trim((string)$product['product_code']) !== '')
                                    ? $product['product_code']
                                    : $globalSnoCounter;
                                $globalSnoCounter++;
                            @endphp
                            <tr class="product-row" style="color: #000000; font-weight: bold;">
                                @if($showSno)
                                <td class="text-center font-bold" style="color: #000000;">{{ $displaySno }}</td>
                                @endif
                                @if($showProduct)
                                <td class="font-bold" style="color: #000000;">{{ $product['name'] }}</td>
                                @endif
                                @if($showTamilName)
                                <td class="font-bold" style="color: #000000;">{{ $product['name_ta'] ?? '' }}</td>
                                @endif
                                @if($showUnit)
                                <td class="text-center font-bold" style="color: #000000; font-size: 10px;">{{ $product['pack_size'] }}</td>
                                @endif
                                @if($showMrp)
                                @php $strikethroughMrp = ($editForm['strikethrough_mrp'] ?? true) !== false; @endphp
                                <td class="text-right font-bold {{ $strikethroughMrp ? 'line-through' : '' }}" style="color: #000000;">₹{{ number_format((float)$product['mrp'], 2) }}</td>
                                @endif
                                @if($showOffer)
                                <td class="text-right font-bold" style="color: #000000;">₹{{ number_format((float)$product['selling_price'], 2) }}</td>
                                @endif
                                @if($showReq)
                                <td class="text-center">{{ $product['req'] ?? '' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>

            @if($loop->last)
            @if(($settings['footer_position'] ?? 'below_table') === 'new_page')
            <div style="page-break-before: always; height: 1px;"></div>
            @endif
            @php
                $showUpiQr = isset($settings['show_upi_qr']) ? (bool)$settings['show_upi_qr'] : true;
                $showBankDetails = isset($settings['show_bank_details']) ? (bool)$settings['show_bank_details'] : true;
            @endphp
             @if($showUpiQr || $showBankDetails)
            @php
                $qrSrc1 = !empty($editForm['store_upi_qr']) && file_exists(public_path($editForm['store_upi_qr']))
                    ? public_path($editForm['store_upi_qr'])
                    : (!empty($settings['store_upi_qr']) && file_exists(public_path($settings['store_upi_qr']))
                        ? public_path($settings['store_upi_qr'])
                        : 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=' . urlencode($editForm['store_gpay'] ?? $settings['store_gpay'] ?? '9787772038') . '%40okicici');

                $hasQr2 = !empty($editForm['store_upi_qr_2']) || !empty($editForm['store_gpay_2']);
                $qrSrc2 = !empty($editForm['store_upi_qr_2']) && file_exists(public_path($editForm['store_upi_qr_2']))
                    ? public_path($editForm['store_upi_qr_2'])
                    : (!empty($editForm['store_gpay_2'])
                        ? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=' . urlencode($editForm['store_gpay_2']) . '%40okicici'
                        : '');
            @endphp

            <!-- UPI SCAN & PAY QR CODES + BANK ACCOUNT INFO CARDS -->
            <div style="margin-top: 15px;">
                @if($showUpiQr)
                <!-- QR Cards Row -->
                <table style="width: 100%; border-collapse: separate; border-spacing: 12px 0px; margin-bottom: {{ ($hasQr2 && $showBankDetails) ? '12px' : '0px' }};">
                    <tr>
                        <!-- QR 1 Card -->
                        <td style="width: {{ ($hasQr2 || $showBankDetails) ? '50%' : '100%' }}; vertical-align: top; background: #ffffff; color: #0f172a; border-radius: 16px; border: 3.5px solid #f59e0b; padding: 12px; text-align: center;">
                            <div style="background: #f59e0b; color: #ffffff; font-size: 11px; font-weight: 900; padding: 3px 10px; border-radius: 20px; display: inline-block; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                📱 SCAN & PAY VIA UPI
                            </div>
                            <div>
                                <img src="{{ $qrSrc1 }}" style="max-height: 110px; width: auto; border: 2px solid #fde047; border-radius: 10px; padding: 4px; background: #ffffff;" alt="UPI QR 1"/>
                            </div>
                            <div style="font-size: 10px; font-weight: 900; color: #0f172a; margin-top: 6px;">
                                @if(!empty($editForm['store_upi_name']))
                                <div style="font-[10px]; font-weight: 900; color: #0f172a; margin-bottom: 2px;">{{ $editForm['store_upi_name'] }}</div>
                                @endif
                                <span style="font-family: monospace; font-size: 11px; color: #0f172a;">UPI / Mobile: {{ $editForm['store_gpay'] ?? $settings['store_gpay'] ?? '9787772038' }}</span>
                            </div>
                        </td>

                        @if($hasQr2)
                        <!-- QR 2 Card (NEW div created next to 1st one) -->
                        <td style="width: 50%; vertical-align: top; background: #ffffff; color: #0f172a; border-radius: 16px; border: 3.5px solid #818cf8; padding: 12px; text-align: center;">
                            <div style="background: #4338ca; color: #ffffff; font-size: 11px; font-weight: 900; padding: 3px 10px; border-radius: 20px; display: inline-block; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                📱 SCAN & PAY VIA UPI
                            </div>
                            <div>
                                <img src="{{ $qrSrc2 }}" style="max-height: 110px; width: auto; border: 2px solid #818cf8; border-radius: 10px; padding: 4px; background: #ffffff;" alt="UPI QR 2"/>
                            </div>
                            <div style="font-size: 10px; font-weight: 900; color: #4338ca; margin-top: 6px;">
                                @if(!empty($editForm['store_upi_name_2']))
                                <div style="font-[10px]; font-weight: 900; color: #0f172a; margin-bottom: 2px;">{{ $editForm['store_upi_name_2'] }}</div>
                                @endif
                                @if(!empty($editForm['store_gpay_2']))
                                <span style="font-family: monospace; font-size: 11px; color: #0f172a;">UPI / Mobile: {{ $editForm['store_gpay_2'] }}</span>
                                @endif
                            </div>
                        </td>
                        @elseif($showBankDetails)
                        <!-- Right: Bank Account Info Card (When only 1 QR code) -->
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
                        @endif
                    </tr>
                </table>
                @endif

                @if($hasQr2 && $showBankDetails)
                <!-- Bank Account Info Card BELOW QR cards when 2nd QR image exists -->
                <div style="background: #ffffff; color: #0f172a; border-radius: 16px; border: 3.5px solid #f59e0b; padding: 12px 18px; text-align: left; margin-left: 12px; margin-right: 12px;">
                    <div style="background: #f59e0b; color: #ffffff; font-size: 11px; font-weight: 900; padding: 3px 10px; border-radius: 20px; display: inline-block; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                        🏦 BANK ACCOUNT INFO
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px; font-weight: 900;">
                        <tr>
                            <td style="width: 50%; padding: 3px 0;"><strong>Account Name:</strong> {{ $editForm['bank_name'] ?? 'Muthusamy Ganesan' }}</td>
                            <td style="width: 50%; padding: 3px 0;"><strong>Bank / Branch:</strong> {{ $editForm['bank_branch'] ?? 'IDBI Bank' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 50%; padding: 3px 0;"><strong>Account No:</strong> <span style="font-family: monospace;">{{ $editForm['bank_account_no'] ?? '1118104000136815' }}</span></td>
                            <td style="width: 50%; padding: 3px 0;"><strong>IFSC Code:</strong> <span style="font-family: monospace;">{{ $editForm['bank_ifsc'] ?? 'IBKL0001118' }}</span></td>
                        </tr>
                    </table>
                    <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 4px; text-align: center; font-size: 9.5px; font-weight: 900; margin-top: 6px; color: #78350f;">
                        ⚡ Quick Bank Transfer / IMPS Available
                    </div>
                </div>
                @elseif(!$showUpiQr && $showBankDetails)
                <!-- Full width Bank Account Info Card if No QR code -->
                <div style="background: #ffffff; color: #0f172a; border-radius: 16px; border: 3.5px solid #f59e0b; padding: 15px; text-align: left; margin-left: 12px; margin-right: 12px;">
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
                </div>
                @endif
            </div>
            @endif
            @endif

            @if(!empty($editForm['important_note_1']) || !empty($editForm['important_note_2']))
            <div style="margin-top: 10px; background: #fffbeb; border: 1.5px solid #fde047; border-radius: 10px; padding: 10px 14px; text-align: center;">
                @if(!empty($editForm['important_note_1']))
                <div style="color: #0f172a; font-size: 10.5px; font-weight: 900; line-height: 1.4; white-space: pre-wrap;">{!! formatMarkdownPdf($editForm['important_note_1']) !!}</div>
                @endif
                @if(!empty($editForm['important_note_2']))
                <div style="color: #b91c1c; font-size: 10.5px; font-weight: 900; line-height: 1.4; margin-top: 4px; white-space: pre-wrap;">{!! formatMarkdownPdf($editForm['important_note_2']) !!}</div>
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
