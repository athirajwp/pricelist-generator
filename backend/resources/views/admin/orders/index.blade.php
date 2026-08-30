@extends('layouts.admin')

@section('title', 'Manage Orders | Admin Console')

@section('content')
<div class="space-y-8 select-none text-slate-800">
    
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Client Bookings Registry</h2>
            <p class="text-[10px] text-slate-500 uppercase tracking-widest leading-none font-semibold">Review and dispatch client cracker bookings</p>
        </div>
    </div>

    <!-- Filter Navigation Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-4">
        @php
            $statuses = ['all' => 'All Bookings', 'pending' => 'Pending', 'approved' => 'Approved', 'processing' => 'Packing', 'shipped' => 'Dispatched', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
        @endphp
        @foreach($statuses as $slug => $label)
            <a href="{{ route('admin.orders.index', $slug === 'all' ? [] : ['status' => $slug]) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all uppercase tracking-wider 
               {{ ($status === $slug || (!$status && $slug === 'all')) ? 'bg-crimson-600 text-white shadow-md shadow-crimson-100/50' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Bookings Table Card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        
        <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-inner bg-slate-50/20">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-4 px-4">Order Code</th>
                        <th class="py-4 px-4">Customer Details</th>
                        <th class="py-4 px-4">Region / Delivery</th>
                        <th class="py-4 px-4 text-right">Net Price (₹)</th>
                        <th class="py-4 px-4 text-center">Payment</th>
                        <th class="py-4 px-4 text-center">Logistics</th>
                        <th class="py-4 px-4 text-center pr-4">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 text-slate-650 font-semibold">
                    @if($orders->count() > 0)
                        @foreach($orders as $order)
                        <tr class="hover:bg-slate-50/50">
                            <!-- Order Number -->
                            <td class="py-3.5 px-4 font-mono font-bold tracking-wider text-slate-800 select-all">{{ $order->order_number }}</td>
                            
                            <!-- Customer Info -->
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-slate-800">{{ $order->name }}</div>
                                <div class="text-[10px] text-slate-450 font-mono select-all">{{ $order->phone }}</div>
                            </td>
                            
                            <!-- Delivery Location -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-700">{{ $order->city }}</div>
                                <div class="text-[10px] text-slate-450">{{ $order->state }} - {{ $order->pincode }}</div>
                            </td>
                            
                            <!-- Prices -->
                            <td class="py-3.5 px-4 text-right font-extrabold text-crimson-650">
                                ₹{{ number_format($order->net_amount, 2) }}
                            </td>
                            
                            <!-- Payment Status -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider shadow-sm {{ $order->payment_status_badge }}">
                                    {{ $order->payment_status }}
                                </span>
                            </td>

                            <!-- Order Status -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider shadow-sm {{ $order->order_status_badge }}">
                                    {{ $order->order_status }}
                                </span>
                                @if($order->transport_name)
                                    <div class="text-[9px] text-slate-450 font-bold mt-1 uppercase tracking-wider truncate max-w-[100px] mx-auto">{{ $order->transport_name }}</div>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 text-center pr-4">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="inline-flex items-center justify-center bg-slate-50 hover:bg-slate-100 border border-slate-200 w-8 h-8 rounded-lg text-slate-650 hover:text-slate-900 transition-all shadow-sm" title="Open Console Manager">
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="py-12 px-4 text-center text-slate-400 italic font-semibold">
                                <i class="fa-solid fa-inbox text-3xl mb-3 block text-slate-300"></i>
                                <span>No booked orders registered under this status.</span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
