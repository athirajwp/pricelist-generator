@extends('layouts.app')

@section('title', 'Booking Successful | ' . App\Models\Setting::get('store_name', 'Cracker Demo') . ' Sivakasi')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-4xl text-slate-800">
    
    <!-- Congratulations Header -->
    <div class="text-center space-y-4 mb-10 select-none animate-fade-in">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 text-3xl shadow-sm">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950">Order Booking Successful!</h2>
        <p class="text-xs text-slate-500 max-w-lg mx-auto leading-relaxed font-semibold">
            Your booking is registered. Please click the WhatsApp button below to confirm your order details and coordinate delivery options!
        </p>
    </div>

    <!-- Main Success Layout Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
        
        <!-- Left: Order Invoice Details (ColSpan 2) -->
        <div class="md:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            
            <!-- Invoice Title & Print actions -->
            <div class="flex justify-between items-center border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Order Details</h3>
                    <span class="text-base font-extrabold text-slate-800 tracking-wider font-mono select-all">{{ $order->order_number }}</span>
                </div>
                <button onclick="window.print()" class="bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 px-4 py-2 rounded-full text-[11px] font-bold flex items-center gap-1.5 shadow-sm transition-all active:scale-95">
                    <i class="fa-solid fa-print text-crimson-600"></i> Print Invoice
                </button>
            </div>

            <!-- Customer Shipping Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs text-slate-650 font-medium">
                <div class="space-y-2">
                    <div class="text-slate-400 uppercase tracking-wider text-[10px] font-bold">Billing / Delivery Address</div>
                    <div class="font-extrabold text-slate-800">{{ $order->name }}</div>
                    <div class="leading-relaxed">{{ $order->address }}</div>
                    @if($order->landmark)
                        <div class="text-slate-500"><strong class="text-slate-400 text-[10px] uppercase font-bold">Landmark:</strong> {{ $order->landmark }}</div>
                    @endif
                    <div>{{ $order->city }}, {{ $order->state }} - <strong>{{ $order->pincode }}</strong></div>
                </div>
                <div class="space-y-2.5">
                    <div class="text-slate-400 uppercase tracking-wider text-[10px] font-bold">Booking Details</div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Contact Mobile:</span>
                        <strong class="text-slate-800 font-mono select-all">{{ $order->phone }}</strong>
                    </div>
                    @if($order->whatsapp)
                    <div class="flex justify-between">
                        <span class="text-slate-500">WhatsApp:</span>
                        <strong class="text-slate-800 font-mono select-all">{{ $order->whatsapp }}</strong>
                    </div>
                    @endif
                    @if($order->transport_name)
                    <div class="flex justify-between">
                        <span class="text-slate-500">Transport Lorry:</span>
                        <strong class="text-crimson-605 font-bold">{{ $order->transport_name }}</strong>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-slate-500">Order Date:</span>
                        <span class="text-slate-700 font-bold">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>

            <!-- Invoice Order Items Table -->
            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="select-none">
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-450 font-bold text-[10px] uppercase tracking-wider">
                            <th class="py-3.5 px-3 sm:px-4">Item Details</th>
                            <th class="hidden sm:table-cell py-3.5 px-4 text-center">Unit</th>
                            <th class="py-3.5 px-3 sm:px-4 text-right">Price (₹)</th>
                            <th class="py-3.5 px-3 sm:px-4 text-center">Qty</th>
                            <th class="py-3.5 px-3 sm:px-4 text-right">Sub Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 text-slate-700 font-semibold">
                        @foreach($order->items as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3.5 px-3 sm:px-4 text-slate-800">
                                <div class="font-bold text-xs leading-normal">{{ $item->product_name }}</div>
                                <span class="sm:hidden text-[9px] font-bold text-slate-400 font-mono">{{ $item->pack_size }}</span>
                            </td>
                            <td class="hidden sm:table-cell py-3.5 px-4 text-slate-500 text-center font-mono">{{ $item->pack_size }}</td>
                            <td class="py-3.5 px-3 sm:px-4 text-right font-medium text-slate-650">₹{{ number_format($item->price, 2) }}</td>
                            <td class="py-3.5 px-3 sm:px-4 text-center text-slate-700 font-mono font-bold">{{ $item->quantity }}</td>
                            <td class="py-3.5 px-3 sm:px-4 text-right font-extrabold text-crimson-600">₹{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Price Totals Panel -->
            <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl space-y-2.5 text-xs font-semibold">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal printed MRP sum:</span>
                    <span class="line-through">₹{{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-crimson-600">
                    <span>Discount Savings:</span>
                    <span class="font-black">-₹{{ number_format($order->discount_amount, 2) }}</span>
                </div>
                
                @if(App\Models\Setting::get('enable_tax_delivery', 'no') === 'yes')
                    @php
                        $itemsNet = max(0, $order->subtotal - $order->discount_amount);
                        $taxPercent = (float) App\Models\Setting::get('tax_percent', 18);
                        $taxVal = $itemsNet * ($taxPercent / 100);
                        $deliveryVal = (float) App\Models\Setting::get('delivery_charge', 150);
                    @endphp
                    <div class="flex justify-between text-slate-500 border-t border-slate-200 pt-2.5">
                        <span>Items Net Value:</span>
                        <span>₹{{ number_format($itemsNet, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>GST / Tax ({{ $taxPercent }}%):</span>
                        <span>₹{{ number_format($taxVal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Delivery Charge:</span>
                        <span>₹{{ number_format($deliveryVal, 2) }}</span>
                    </div>
                @endif
                
                <div class="flex justify-between text-slate-800 border-t border-slate-200 pt-2.5 text-sm font-black">
                    <span>Net Amount Payable:</span>
                    <span class="text-crimson-650 text-base font-black">₹{{ number_format($order->net_amount, 2) }}</span>
                </div>
            </div>

        </div>

        <!-- Right: Payment QR and WhatsApp validation (ColSpan 1) -->
        <div class="space-y-6">
            
            <!-- WhatsApp Booking Confirmation Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4 text-center">
                <span class="inline-flex bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full shadow-sm">
                    <i class="fa-brands fa-whatsapp mr-1"></i> Fast Confirmation
                </span>
                
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-widest">Confirm Booking</h4>
                
                <p class="text-[10px] text-slate-500 leading-relaxed font-semibold">
                    Please share your order details with us on WhatsApp to verify your booking and discuss delivery and offline payment logistics!
                </p>
                
                <a href="{{ $whatsappUrl }}" target="_blank" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-3 rounded-full text-xs uppercase tracking-wider shadow-md shadow-emerald-100 flex items-center justify-center gap-2 transform active:scale-95 transition-all">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>Confirm on WhatsApp</span>
                </a>
            </div>

        </div>

    </div>

</div>
@endsection
