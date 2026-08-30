@extends('layouts.app')

@section('title', 'Track Order Booking | ' . App\Models\Setting::get('store_name', 'Cracker Demo') . ' Sivakasi')

@section('content')
<div class="container mx-auto px-4 py-16 max-w-3xl text-slate-800">
    
    <!-- Header -->
    <div class="text-center space-y-4 mb-10 select-none animate-fade-in">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-crimson-50 border border-crimson-100 text-crimson-600 text-2xl shadow-sm">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">Track Your Order Status</h2>
        <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed font-semibold">
            Enter your Order Booking Number (e.g. ATC-...) or the Mobile Number used during checkout to check real-time payment validation and lorry transport updates.
        </p>
    </div>

    <!-- Search Box Card -->
    <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm space-y-6">
        <form action="/track" method="POST" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="relative flex-grow">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-barcode text-xs"></i>
                </span>
                <input type="text" name="search_query" required value="{{ $query ?? '' }}" placeholder="Enter Order Number or Phone Number..." class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl py-3 pl-10 pr-4 text-xs text-slate-700 placeholder-slate-400 focus:ring-1 focus:ring-slate-300 focus:outline-none transition-all">
            </div>
            
            <button type="submit" class="bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-750 hover:to-crimson-650 text-white font-extrabold px-6 py-3 rounded-xl text-xs uppercase tracking-wider shadow-sm transition-all active:scale-95 flex items-center justify-center gap-1.5 flex-shrink-0">
                <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                <span>Track Booking</span>
            </button>
        </form>
    </div>

    <!-- Search Results -->
    @if(isset($searched))
    <div class="mt-10 space-y-8 animate-fade-in">
        
        @if($orders->count() > 0)
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pl-1 select-none">Found {{ $orders->count() }} booking(s)</h3>
            
            @foreach($orders as $order)
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                
                <!-- Card Header -->
                <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 select-none">
                    <div>
                        <span class="text-[9px] text-slate-400 uppercase tracking-wider block font-bold">Booking Reference</span>
                        <strong class="text-sm font-extrabold text-slate-800 tracking-wider font-mono select-all">{{ $order->order_number }}</strong>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        <!-- Order Status Badge -->
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm {{ $order->order_status_badge }}">
                            <i class="fa-solid fa-circle mr-1 text-[8px] opacity-80"></i>Order: {{ $order->order_status }}
                        </span>
                        
                        <!-- Payment Status Badge -->
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm {{ $order->payment_status_badge }}">
                            <i class="fa-solid fa-wallet mr-1 text-[8px] opacity-80"></i>Payment: {{ $order->payment_status }}
                        </span>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6 space-y-6">
                    
                    <!-- Progress Tracker Visual Component -->
                    <div class="grid grid-cols-3 gap-2 text-center text-[10px] font-bold uppercase tracking-wider relative select-none">
                        <!-- Line connectors -->
                        <div class="absolute top-3.5 left-[16.6%] right-[16.6%] h-1 bg-slate-100 -z-10 rounded-full border border-slate-200 overflow-hidden">
                            <div class="bg-crimson-600 h-full transition-all duration-500" 
                                 style="width: {{ $order->order_status === 'pending' ? '0' : ($order->order_status === 'shipped' || $order->order_status === 'delivered' ? '100%' : '50%') }}%">
                            </div>
                        </div>

                        <!-- Step 1: Placed -->
                        <div class="space-y-2">
                            <div class="w-8 h-8 rounded-full border flex items-center justify-center mx-auto transition-all shadow-sm 
                                 {{ $order->order_status !== 'cancelled' ? 'bg-crimson-50 border-crimson-500 text-crimson-600 font-extrabold' : 'bg-slate-100 border-slate-200 text-slate-400' }}">
                                <i class="fa-solid fa-receipt text-xs"></i>
                            </div>
                            <span class="text-slate-600">Booked</span>
                        </div>

                        <!-- Step 2: Processing -->
                        <div class="space-y-2">
                            <div class="w-8 h-8 rounded-full border flex items-center justify-center mx-auto transition-all shadow-sm
                                 {{ $order->order_status !== 'pending' && $order->order_status !== 'cancelled' ? 'bg-crimson-50 border-crimson-500 text-crimson-600 font-extrabold' : 'bg-slate-100 border-slate-200 text-slate-400' }}">
                                <i class="fa-solid fa-spinner-third text-xs {{ $order->order_status === 'processing' ? 'animate-spin' : '' }}"></i>
                            </div>
                            <span class="text-slate-600">Processing</span>
                        </div>

                        <!-- Step 3: Shipped -->
                        <div class="space-y-2">
                            <div class="w-8 h-8 rounded-full border flex items-center justify-center mx-auto transition-all shadow-sm
                                 {{ $order->order_status === 'shipped' || $order->order_status === 'delivered' ? 'bg-crimson-50 border-crimson-500 text-crimson-600 font-extrabold' : 'bg-slate-100 border-slate-200 text-slate-400' }}">
                                <i class="fa-solid fa-truck-fast text-xs"></i>
                            </div>
                            <span class="text-slate-600">Dispatched</span>
                        </div>
                    </div>

                    <!-- Logistics & Lorry Transport updates -->
                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl space-y-3.5 text-xs font-semibold">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-truck text-crimson-600"></i> Transport & Tracking Details
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-slate-150 pt-3 text-slate-500">
                            <div>
                                <span class="text-[9px] text-slate-400 uppercase tracking-widest block font-bold mb-0.5">Lorry Transport Company</span>
                                <strong class="text-slate-800 text-xs">{{ $order->transport_name ?? 'Not Registered Yet' }}</strong>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 uppercase tracking-widest block font-bold mb-0.5">Lorry Receipt (LR) / Waybill No</span>
                                @if($order->lr_number)
                                    <strong class="text-crimson-650 text-xs font-mono select-all">{{ $order->lr_number }}</strong>
                                @else
                                    <span class="text-slate-400 text-xs italic font-medium">Awaiting dispatch</span>
                                @endif
                            </div>
                        </div>

                        <div class="text-[9px] text-slate-400 leading-normal border-t border-slate-150 pt-3 font-medium">
                            <i class="fa-solid fa-circle-info mr-1 text-slate-500"></i>
                            Once dispatched, transport receipts are filed. Use the LR receipt code directly with your local lorry booking office (e.g. KPN, VRL, ARC, etc.) to collect your firecrackers package.
                        </div>
                    </div>

                    <!-- Item Summary preview -->
                    <div class="space-y-3 text-xs">
                        <div class="text-slate-400 font-bold uppercase tracking-wider text-[9px]">Booking Summary</div>
                        <div class="bg-slate-50 border border-slate-150 rounded-xl p-3 flex justify-between items-center text-slate-600 font-semibold">
                            <div>
                                Booking for: <strong class="text-slate-800">{{ $order->name }}</strong>
                            </div>
                            <div class="font-extrabold text-crimson-650">
                                Total net payable: ₹{{ number_format($order->net_amount, 2) }}
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            @endforeach

        @else
            <!-- No bookings found -->
            <div class="bg-white border border-slate-200 p-10 rounded-2xl shadow-sm text-center space-y-3 select-none">
                <div class="text-crimson-600 text-3xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">No Booking Records Found!</h4>
                <p class="text-xs text-slate-400 max-w-sm mx-auto leading-normal font-semibold">
                    We couldn't locate any order bookings matching the query: <strong class="text-slate-700 font-mono">"{{ $query }}"</strong>. Double-check your code or try searching by phone number.
                </p>
            </div>
        @endif

    </div>
    @endif

</div>
@endsection
