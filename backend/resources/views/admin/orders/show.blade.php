@extends('layouts.admin')

@section('title', 'Manage Order Booking | ' . App\Models\Setting::get('store_name', 'Cracker Demo') . ' Sivakasi')

@section('content')
<div class="space-y-8 select-none text-slate-800">
    
    <!-- Title banner -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <a href="{{ route('admin.orders.index') }}" class="text-slate-400 hover:text-slate-650"><i class="fa-solid fa-arrow-left"></i></a>
                <span>Order Booking Management</span>
            </h2>
            <p class="text-[10px] text-slate-450 uppercase tracking-widest leading-none font-bold">Reference: <strong class="text-slate-700 font-mono select-all">{{ $order->order_number }}</strong></p>
        </div>
        
        @php
            $storeName = App\Models\Setting::get('store_name', 'Cracker Store');
            $waMessage = "Hello *" . $order->name . "*,\n\n"
                       . "Here is the invoice summary for your order at *" . $storeName . "*:\n\n"
                       . "*Order Number:* " . $order->order_number . "\n"
                       . "*Order Date:* " . $order->created_at->format('d M Y, h:i A') . "\n"
                       . "*Net Amount:* ₹" . number_format($order->net_amount, 2) . "\n"
                       . "*Order Status:* " . ucfirst($order->order_status) . "\n"
                       . "*Payment Status:* " . ucfirst($order->payment_status) . "\n\n"
                       . "*Order Items Summary:*\n";
            
            foreach($order->items as $item) {
                $waMessage .= "• " . $item->product_name . " (Qty: " . $item->quantity . ") - ₹" . number_format($item->total_price, 2) . "\n";
            }
            
            $waMessage .= "\nTrack your order here: " . route('track.index', ['query' => $order->order_number]) . "\n\n"
                        . "Thank you for booking with us!";
            
            $targetPhone = preg_replace('/[^0-9]/', '', $order->whatsapp ?: $order->phone);
            if (strlen($targetPhone) === 10) {
                $targetPhone = '91' . $targetPhone;
            }
            $waUrl = "https://api.whatsapp.com/send?phone=" . $targetPhone . "&text=" . urlencode($waMessage);
        @endphp
        <div class="flex gap-3">
            <a href="{{ $waUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all active:scale-95">
                <i class="fa-brands fa-whatsapp text-[13px]"></i> Send WhatsApp Invoice
            </a>
            <a href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all active:scale-95">
                <i class="fa-solid fa-file-invoice text-crimson-600"></i> View Retail Invoice
            </a>
        </div>
    </div>

    <!-- Main edit grid layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left: Order Details (ColSpan 2) -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-150 pb-3">Client Booking Information</h3>

            <!-- Customer Shipping details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs text-slate-650 font-medium">
                <div class="space-y-2">
                    <div class="text-slate-400 uppercase tracking-wider text-[10px] font-bold">Delivery Location</div>
                    <div class="font-bold text-slate-800">{{ $order->name }}</div>
                    <div class="leading-relaxed">{{ $order->address }}</div>
                    @if($order->landmark)
                        <div class="text-slate-550"><strong class="text-slate-400 text-[10px] uppercase font-bold">Landmark:</strong> {{ $order->landmark }}</div>
                    @endif
                    <div>{{ $order->city }}, {{ $order->state }} - <strong>{{ $order->pincode }}</strong></div>
                </div>
                <div class="space-y-2.5">
                    <div class="text-slate-400 uppercase tracking-wider text-[10px] font-bold">Contact Particulars</div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Contact Mobile:</span>
                        <a href="tel:{{ $order->phone }}" class="text-slate-700 hover:text-crimson-600 hover:underline font-mono select-all font-bold">
                            {{ $order->phone }} <i class="fa-solid fa-phone text-[10px] ml-1 opacity-70"></i>
                        </a>
                    </div>
                    @if($order->whatsapp)
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">WhatsApp:</span>
                        <a href="{{ $waUrl }}" target="_blank" class="text-emerald-600 hover:underline font-mono select-all font-bold" title="Send WhatsApp Invoice">
                            {{ $order->whatsapp }} <i class="fa-brands fa-whatsapp text-[11px] ml-1"></i>
                        </a>
                    </div>
                    @endif
                    @if($order->email)
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Contact Email:</span>
                        <a href="mailto:{{ $order->email }}" class="text-crimson-600 hover:underline font-mono select-all font-bold">
                            {{ $order->email }} <i class="fa-solid fa-envelope text-[10px] ml-1 opacity-70"></i>
                        </a>
                    </div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-2.5">
                        <span class="text-slate-500">Booked On:</span>
                        <span class="text-slate-700 font-bold">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>

            <!-- Ordered Line Items -->
            <div class="space-y-3">
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Ordered Line Items</h3>
                    <a href="{{ route('admin.orders.edit_items', $order->id) }}" class="text-crimson-600 hover:text-crimson-700 hover:underline font-bold text-[11px] flex items-center gap-1">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Order Items
                    </a>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-450 font-bold text-[10px] uppercase tracking-wider">
                                <th class="py-3.5 px-4">Firecracker Name</th>
                                <th class="py-3.5 px-4 text-center">Unit</th>
                                <th class="py-3.5 px-4 text-right">Price (₹)</th>
                                <th class="py-3.5 px-4 text-center">Qty Ordered</th>
                                <th class="py-3.5 px-4 text-right pr-4">Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 text-slate-700 font-semibold">
                            @foreach($order->items as $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-4 text-slate-800">{{ $item->product_name }}</td>
                                <td class="py-3 px-4 text-slate-400 text-center font-mono">{{ $item->pack_size }}</td>
                                <td class="py-3 px-4 text-right text-slate-600">₹{{ number_format($item->price, 2) }}</td>
                                <td class="py-3 px-4 text-center text-slate-800 font-mono font-bold">{{ $item->quantity }}</td>
                                <td class="py-3 px-4 text-right font-bold text-slate-800 pr-4">₹{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals -->
            <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl space-y-2.5 text-xs font-semibold">
                <div class="flex justify-between text-slate-500">
                    <span>Original subtotal printed MRP sum:</span>
                    <span class="line-through text-slate-400">₹{{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-crimson-600">
                    <span>Flat {{ App\Models\Setting::get('discount_percent', 60) }}% Discount Savings:</span>
                    <span class="font-black">-₹{{ number_format($order->discount_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-800 border-t border-slate-200 pt-2.5 text-sm font-black">
                    <span>Net Amount Payable:</span>
                    <span class="text-crimson-650 text-base font-black">₹{{ number_format($order->net_amount, 2) }}</span>
                </div>
            </div>

            <!-- Notes -->
            @if($order->notes)
            <div class="space-y-1.5 text-xs">
                <span class="text-slate-400 uppercase tracking-widest text-[9px] font-bold"><i class="fa-solid fa-pencil"></i> Client Order Instructions / Notes</span>
                <p class="bg-slate-50 border border-slate-200 p-3.5 rounded-xl text-slate-700 leading-normal font-semibold">{{ $order->notes }}</p>
            </div>
            @endif

        </div>

        <!-- Right: Status updates Form (ColSpan 1) -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-3">Update Order Status</h3>

            <form action="{{ route('admin.orders.update', $order->id) }}" method="POST" class="space-y-5 text-xs">
                @csrf
                @method('PUT')
                
                <!-- Order Status Select -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-slate-450 uppercase tracking-wider mb-1 px-0.5">Booking Delivery Status</label>
                    <select name="order_status" class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2.5 text-xs text-slate-700 focus:outline-none transition-all">
                        <option value="pending" {{ $order->order_status === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ $order->order_status === 'approved' ? 'selected' : '' }}>Approved / Paid</option>
                        <option value="processing" {{ $order->order_status === 'processing' ? 'selected' : '' }}>Processing / Packing</option>
                        <option value="shipped" {{ $order->order_status === 'shipped' ? 'selected' : '' }}>Dispatched / Shipped</option>
                        <option value="delivered" {{ $order->order_status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ $order->order_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Payment Status Select -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-slate-450 uppercase tracking-wider mb-1 px-0.5">Payment Verification Status</label>
                    <select name="payment_status" class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2.5 text-xs text-slate-700 focus:outline-none transition-all">
                        <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>Pending Payment Verification</option>
                        <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Verified / Paid (Full)</option>
                        <option value="verified" {{ $order->payment_status === 'verified' ? 'selected' : '' }}>Verified (Partial)</option>
                    </select>
                </div>

                <!-- Lorry Transport details -->
                <div class="space-y-4 border-t border-slate-200 pt-4">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block"><i class="fa-solid fa-truck"></i> Transport Lorry Tracking</span>
                    
                    <div class="space-y-1.5">
                        <label class="block text-[9px] font-bold text-slate-450 uppercase tracking-wider mb-1 px-0.5">Lorry Transport Agent Name</label>
                        <input type="text" name="transport_name" value="{{ $order->transport_name }}" placeholder="e.g. KPN Transport, ARC Lorry Service" class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[9px] font-bold text-slate-450 uppercase tracking-wider mb-1 px-0.5">Lorry Receipt (LR) tracking number</label>
                        <input type="text" name="lr_number" value="{{ $order->lr_number }}" placeholder="e.g. LR-987654-XYZ" class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all font-mono">
                    </div>
                </div>

                <!-- Action Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white font-extrabold py-3 rounded-xl text-xs uppercase tracking-wider shadow-sm transform active:scale-95 transition-all flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-circle-check text-[11px]"></i>
                    <span>Apply Modifications</span>
                </button>

            </form>

        </div>

    </div>

</div>
@endsection
