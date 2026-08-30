@extends('layouts.admin')

@section('title', 'Admin Dashboard | ' . App\Models\Setting::get('store_name', 'Cracker Demo') . ' Sivakasi')

@section('content')
<div class="space-y-8 select-none text-slate-800">
    
    <!-- Title banner -->
    <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Administrative Console Dashboard</h2>
        <p class="text-[10px] text-slate-500 uppercase tracking-widest leading-none font-semibold">Real-Time Core Performance Metrics</p>
    </div>

    <!-- Analytics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Orders -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm flex items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="text-[9px] text-slate-450 font-bold uppercase tracking-wider block">Total Bookings</span>
                <strong class="text-2xl font-black text-slate-800 font-mono">{{ $stats['total_orders'] }}</strong>
            </div>
            <div class="w-11 h-11 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 text-base shadow-inner">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <!-- Pending Bookings -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm flex items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="text-[9px] text-slate-450 font-bold uppercase tracking-wider block">Pending Bookings</span>
                <strong class="text-2xl font-black text-amber-600 font-mono">{{ $stats['pending_orders'] }}</strong>
            </div>
            <div class="w-11 h-11 bg-amber-50 border border-amber-100 rounded-xl flex items-center justify-center text-amber-600 text-base shadow-inner">
                <i class="fa-solid fa-spinner-third animate-spin"></i>
            </div>
        </div>

        <!-- Total Revenue Verified -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm flex items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="text-[9px] text-slate-450 font-bold uppercase tracking-wider block">Verified Revenue</span>
                <strong class="text-2xl font-black text-emerald-600 font-mono">₹{{ number_format($stats['total_revenue'], 2) }}</strong>
            </div>
            <div class="w-11 h-11 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 text-base shadow-inner">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
        </div>

        <!-- Pending Unverified Revenue -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm flex items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="text-[9px] text-slate-450 font-bold uppercase tracking-wider block">Unverified Revenue</span>
                <strong class="text-2xl font-black text-crimson-650 font-mono">₹{{ number_format($stats['pending_revenue'], 2) }}</strong>
            </div>
            <div class="w-11 h-11 bg-crimson-50 border border-crimson-100 rounded-xl flex items-center justify-center text-crimson-600 text-base shadow-inner">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>

    </div>

    <!-- Main lists dashboard layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Recent Orders (ColSpan 2) -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            <div class="flex justify-between items-center border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest">Recent Bookings</h3>
                    <p class="text-[9px] text-slate-500 font-semibold">List of last 5 booked clients</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 px-3.5 py-1.5 rounded-full text-[10px] font-bold text-slate-600 transition-colors uppercase tracking-widest">
                    View All Orders <i class="fa-solid fa-chevron-right ml-0.5 text-[8px] text-crimson-500"></i>
                </a>
            </div>

            <!-- Orders Table -->
            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-4">Order Code</th>
                            <th class="py-3 px-4">Client Name</th>
                            <th class="py-3 px-4 text-right">Net Price (₹)</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center pr-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 text-slate-650 font-semibold">
                        @if($recentOrders->count() > 0)
                            @foreach($recentOrders as $order)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3.5 px-4 font-mono font-bold tracking-wider text-slate-800 select-all">{{ $order->order_number }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-extrabold text-slate-800">{{ $order->name }}</div>
                                    <div class="text-[10px] text-slate-450 font-mono select-all">{{ $order->phone }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-crimson-650">₹{{ number_format($order->net_amount, 2) }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider shadow-sm {{ $order->order_status_badge }}">
                                        {{ $order->order_status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center pr-4">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="inline-flex items-center justify-center bg-slate-50 hover:bg-slate-100 border border-slate-200 w-8 h-8 rounded-lg text-slate-600 hover:text-slate-900 transition-colors shadow-sm" title="Manage Order">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="py-6 px-4 text-center text-slate-400 italic font-medium">No orders registered yet.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Fast shortcuts operations -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-4">Fast Operations</h3>
            
            <div class="space-y-3.5 text-xs">
                <!-- Manage Inventory -->
                <a href="{{ route('admin.products.index') }}" class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-2xl transition-all shadow-sm group">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-boxes-stacked text-crimson-600 text-sm"></i>
                        <div>
                            <strong class="text-slate-800 group-hover:text-crimson-600 transition-colors">Manage Inventory</strong>
                            <p class="text-[9px] text-slate-450 font-bold">Edit products, MRP, discounts</p>
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 group-hover:text-crimson-600 transition-colors"></i>
                </a>

                <!-- Store Settings -->
                <a href="{{ route('admin.settings.index') }}" class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-2xl transition-all shadow-sm group">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-sliders text-crimson-600 text-sm"></i>
                        <div>
                            <strong class="text-slate-800 group-hover:text-crimson-600 transition-colors">Store Settings</strong>
                            <p class="text-[9px] text-slate-450 font-bold">Update GPay UPI, support numbers</p>
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 group-hover:text-crimson-600 transition-colors"></i>
                </a>
            </div>

            <!-- Database connection details -->
            @php
                $dbConnection = config('database.default');
                $dbDatabase = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
                $dbHost = config("database.connections.{$dbConnection}.host", '127.0.0.1');
            @endphp
            <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl space-y-2 font-semibold">
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block"><i class="fa-solid fa-database mr-1"></i> Database Status</span>
                <div class="text-[10px] text-slate-500 space-y-1.5">
                    <div class="flex justify-between">
                        <span>Engine:</span>
                        <strong class="text-slate-700 font-mono uppercase">{{ $dbConnection }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Database:</span>
                        <strong class="text-slate-500 font-mono text-[9px] truncate w-32 text-right" title="{{ $dbDatabase }}">{{ $dbDatabase }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Host:</span>
                        <strong class="text-slate-500 font-mono text-[9px] truncate w-32 text-right" title="{{ $dbHost }}">{{ $dbHost }}</strong>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
