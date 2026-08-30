@extends('layouts.app')

@section('title', 'Terms & Conditions | ' . App\Models\Setting::get('store_name', 'Cracker Demo') . ' Sivakasi')

@section('content')
<div class="relative text-slate-800 select-none">

    <!-- 1. Premium Glassmorphic Hero Banner -->
    <section class="relative bg-white border-b border-slate-200 overflow-hidden py-16">
        <!-- Soft gradient mesh in background -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-crimson-100/20 via-white to-slate-50 opacity-90 pointer-events-none"></div>
        
        <div class="container mx-auto px-4 text-center relative z-10 space-y-4">
            <span class="inline-flex items-center gap-1.5 bg-gold-50 border border-gold-200 text-gold-700 text-xs font-extrabold uppercase tracking-widest px-3.5 py-1.5 rounded-full shadow-sm">
                <i class="fa-solid fa-scale-balanced text-crimson-600"></i> Official booking policy
            </span>
            <h2 class="text-3xl md:text-4xl font-black tracking-tight text-slate-900 leading-tight">
                Terms & Conditions
            </h2>
            <p class="text-xs text-slate-550 max-w-xl mx-auto leading-relaxed font-semibold">
                Please review our official terms, payment policies, delivery frameworks, and transport conditions before placing your fireworks booking.
            </p>
        </div>
    </section>

    <!-- 2. Terms and Conditions Main Content Area -->
    <section class="container mx-auto px-4 py-12 max-w-4xl">
        <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-10 shadow-sm relative overflow-hidden">
            <div class="absolute -right-24 -top-24 w-64 h-64 bg-slate-50/50 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="prose prose-slate max-w-none text-xs leading-relaxed space-y-6">
                @php
                    $termsContent = App\Models\Setting::get('terms_conditions');
                @endphp

                @if(!empty(trim(strip_tags($termsContent))))
                    <div class="text-slate-650 space-y-4 font-medium">
                        {!! $termsContent !!}
                    </div>
                @else
                    <!-- High-fidelity premium default terms fallback -->
                    <div class="space-y-8 text-slate-550 font-medium">
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                                <i class="fa-solid fa-circle-check text-crimson-600"></i> 1. Booking Eligibility & Order Guidelines
                            </h3>
                            <p>
                                By placing an order on our online booking storefront, you confirm that you are at least 18 years of age and authorized to purchase fireworks products in your local jurisdiction.
                            </p>
                            <p>
                                All items added to your cart represent Sivakasi wholesale stock and are subject to availability. The minimum purchase value to qualify for transport delivery is strictly <strong>₹{{ number_format(App\Models\Setting::get('min_order_value', 3800)) }}</strong> (net payable value after flat discounts are calculated).
                            </p>
                        </div>

                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                                <i class="fa-solid fa-circle-check text-crimson-600"></i> 2. Pricing, Discounts & Wholesale Schemes
                            </h3>
                            <p>
                                All products listed indicate their official Maximum Retail Price (MRP) alongside our discounted Sivakasi wholesale rate (a standard <strong>{{ App\Models\Setting::get('discount_percent', 60) }}% off</strong>). Prices are subject to change in line with chemical feedstock costs, but prices locked in at order submission remain fully guaranteed.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                                <i class="fa-solid fa-circle-check text-crimson-600"></i> 3. Payment & Booking Terms
                            </h3>
                            <p>
                                We operate on a booking estimation model. No online payments are accepted on this website due to regulatory guidelines.
                            </p>
                            <p>
                                Upon finalizing your checkout, a booking invoice summary is generated. Please click the WhatsApp button to share your booking reference with us. Our representative will contact you to verify details, discuss payment terms, and complete the order processing offline.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                                <i class="fa-solid fa-circle-check text-crimson-600"></i> 4. Shipping, Lorry Transports & Delivery
                            </h3>
                            <p>
                                In accordance with the Explosives Act of India, firecrackers cannot be delivered via courier services (such as DTDC or BlueDart) or postal mail. 
                            </p>
                            <p>
                                All bookings are securely packed in heavy-duty wooden boxes and shipped via licensed third-party <strong>Lorry Transport Services</strong> to your nearest transport hub/godown in Tamilnadu, Kerala, Karnataka, Andhra Pradesh, or Telangana.
                            </p>
                            <p>
                                Customers will receive their Lorry Receipt (LR) tracking slip containing booking logs. You are required to collect the goods directly from the transport godown and pay any minor local freight charges upon collection.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                                <i class="fa-solid fa-circle-check text-crimson-600"></i> 5. Safety, Liability & Bursting Guidelines
                            </h3>
                            <p>
                                Firecrackers are inherently chemical materials. Please exercise extreme caution when storing and ignited. Always supervise children, wear cotton garments, keep a bucket of water or sand adjacent to the firing area, and follow all state safety guidelines. 
                            </p>
                            <p>
                                {{ App\Models\Setting::get('store_name', 'Cracker Demo') }} Sivakasi is solely a merchant supplier and assumes no civil or criminal liability for physical damage, injury, or loss of life resulting from improper or negligent handling of purchased items.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer confirmation watermark -->
            <div class="border-t border-slate-100 mt-10 pt-6 flex justify-between items-center text-[10px] text-slate-400 select-none">
                <span>Last updated: {{ date('F Y') }}</span>
                <span>{{ App\Models\Setting::get('store_name', 'Cracker Demo') }} Sivakasi</span>
            </div>
        </div>
    </section>

</div>
@endsection
