@extends('layouts.app')

@section('title', 'About Us | ' . App\Models\Setting::get('store_name', 'Cracker Demo') . ' Sivakasi')

@section('content')
<div class="relative text-slate-800 select-none">

    <!-- 1. Premium Glassmorphic Hero Banner -->
    <section class="relative bg-white border-b border-slate-200 overflow-hidden py-20">
        <!-- Soft gradient mesh in background -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-crimson-100/20 via-white to-slate-50 opacity-90 pointer-events-none"></div>
        
        <div class="container mx-auto px-4 text-center relative z-10 space-y-4">
            <span class="inline-flex items-center gap-1.5 bg-gold-50 border border-gold-200 text-gold-700 text-xs font-extrabold uppercase tracking-widest px-3.5 py-1.5 rounded-full shadow-sm">
                <i class="fa-solid fa-fire text-crimson-600"></i> Pure sivakasi manufactured
            </span>
            <h2 class="text-4xl md:text-5xl font-black tracking-tight text-slate-900 leading-tight">
                About {{ App\Models\Setting::get('store_name', 'Cracker Demo') }}
            </h2>
            <p class="text-sm text-slate-550 max-w-xl mx-auto leading-relaxed font-semibold">
                Reputed and reliable Sivakasi fireworks dealers offering premium quality, highly colorful, and 100% safe crackers for all your celebrations.
            </p>
            @php
                $licName = App\Models\Setting::get('license_name');
                $licNo = App\Models\Setting::get('license_no');
            @endphp
            @if(!empty($licName) || !empty($licNo))
                <div class="inline-flex flex-wrap justify-center items-center gap-x-6 gap-y-2 bg-slate-50 border border-slate-200 px-6 py-3 rounded-2xl shadow-sm text-xs font-bold text-slate-600 max-w-xl mx-auto mt-4">
                    @if(!empty($licName))
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-scale-balanced text-emerald-600 text-sm"></i>
                            <span>License Name: <strong class="text-slate-800 font-extrabold">{{ $licName }}</strong></span>
                        </div>
                    @endif
                    @if(!empty($licNo))
                        <div class="h-4 w-px bg-slate-350 hidden md:block"></div>
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-file-signature text-crimson-600 text-sm"></i>
                            <span>License No: <strong class="text-slate-850 font-mono font-black">{{ $licNo }}</strong></span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <!-- 2. Premium Core Company Showcase Section -->
    <section class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            
            <!-- Left Column: Generated collage of products / uploaded aboutus images -->
            <div class="relative group">
                <div class="absolute -inset-2 bg-gradient-to-tr from-gold-500 to-crimson-600 rounded-3xl opacity-10 blur-xl group-hover:opacity-15 transition-opacity duration-500"></div>
                <div class="relative bg-white border border-slate-200 p-3.5 rounded-3xl shadow-lg overflow-hidden transform group-hover:scale-[1.01] transition-transform duration-300">
                    @php
                        $aboutImg1 = App\Models\Setting::get('aboutus_image_1');
                    @endphp
                    <img src="{{ $aboutImg1 ? '/' . $aboutImg1 : '/images/about_showcase.png' }}" alt="Sivakasi Fireworks Showcase" class="w-full h-auto object-cover rounded-2xl">
                </div>
            </div>

            <!-- Right Column: Content -->
            <div class="space-y-6">
                @php
                    $aboutUsBadge = App\Models\Setting::get('about_us_badge', 'A Decade of Quality');
                    $aboutUsTitle = App\Models\Setting::get('about_us_title', 'We Provide Premium Quality Fireworks');
                    $aboutUsContent = App\Models\Setting::get('about_us');
                @endphp

                <div class="space-y-4">
                    <div class="space-y-2">
                        @if(!empty(trim($aboutUsBadge)))
                            <span class="text-xs font-bold text-crimson-600 uppercase tracking-widest">
                                <i class="fa-solid fa-shield-heart mr-1"></i> {{ $aboutUsBadge }}
                            </span>
                        @endif
                        @if(!empty(trim($aboutUsTitle)))
                            <h3 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-tight">
                                {{ $aboutUsTitle }}
                            </h3>
                        @endif
                    </div>
                    
                    @if(!empty(trim(strip_tags($aboutUsContent))))
                        <div class="text-xs text-slate-600 leading-relaxed font-medium space-y-4 prose prose-slate max-w-none">
                            {!! $aboutUsContent !!}
                        </div>
                    @else
                        <p class="text-xs text-slate-650 leading-relaxed font-semibold">
                            We are a highly reputed and reliable name involved in the field of Fireworks trading business for the past 10 years. 
                        </p>
                        <p class="text-xs text-slate-500 leading-relaxed font-medium">
                            We offer a wide range of fireworks products such as Sparklers, Ground Chakkars, Twinkling Stars, Chorsa, Rockets, Flower Pots, Pencils, Atom Bombs, Colour Matches, and other Fancy Aerial Items. We also offer standard and customized fireworks gift boxes at highly competitive Sivakasi wholesale prices.
                        </p>
                        <p class="text-xs text-slate-500 leading-relaxed font-medium">
                            Through websites, instant WhatsApp checkouts, and modern logistic systems, we are able to process, pack, and ship your orders to Kerala, Karnataka, Andhra, Telangana, and Tamilnadu faster, safer, and on-time to your complete satisfaction.
                        </p>
                    @endif
                </div>
                
                <div class="pt-4 border-t border-slate-100 flex flex-wrap gap-6 text-xs text-slate-650 font-bold">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-square-check text-emerald-600 text-lg"></i>
                        <span>Sivakasi Direct Stock</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-square-check text-emerald-600 text-lg"></i>
                        <span>Flat {{ App\Models\Setting::get('discount_percent', 60) }}% Wholesale Savings</span>
                    </div>
                </div>
            </div>

    </section>

    @php
        $galleryImages = [];
        for ($i = 1; $i <= 18; $i++) {
            $path = App\Models\Setting::get("gallery_image_{$i}");
            if (!empty($path) && file_exists(public_path($path))) {
                $galleryImages[] = '/' . $path;
            }
        }
    @endphp

    @if(count($galleryImages) > 0)
    <!-- Gallery Section -->
    <section class="container mx-auto px-4 py-16 border-t border-slate-200" x-data="{ lightboxOpen: false, lightboxImg: '' }">
        <div class="text-center space-y-4 mb-10 select-none">
            <span class="inline-flex items-center gap-1.5 bg-crimson-50 border border-crimson-100 text-crimson-700 text-xs font-extrabold uppercase tracking-widest px-3.5 py-1.5 rounded-full shadow-sm">
                <i class="fa-solid fa-images text-crimson-600"></i> Gallery
            </span>
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 leading-tight">Capturing Our Celebrations & Infrastructure</h2>
            <p class="text-xs text-slate-500 max-w-lg mx-auto leading-relaxed font-semibold">
                Take a visual tour of our wholesale crackers godowns, safety packaging standards, and vibrant celebrations.
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            @foreach($galleryImages as $imgUrl)
            <div @click="lightboxOpen = true; lightboxImg = '{{ $imgUrl }}'" class="cursor-pointer overflow-hidden rounded-2xl border border-slate-200 shadow-sm relative group aspect-video">
                <img src="{{ $imgUrl }}" class="object-cover w-full h-full transform group-hover:scale-105 transition-all duration-500">
                <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <i class="fa-solid fa-magnifying-glass-plus text-white text-2xl"></i>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Lightbox Modal -->
        <div x-show="lightboxOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4"
             style="display: none;"
             @click="lightboxOpen = false">
            <div class="relative max-w-4xl max-h-[90vh] bg-white p-2 rounded-3xl overflow-hidden shadow-2xl" @click.stop>
                <button @click="lightboxOpen = false" class="absolute top-4 right-4 bg-slate-900/60 hover:bg-slate-900/80 text-white rounded-full p-2.5 transition-colors focus:outline-none z-10">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
                <img :src="lightboxImg" class="max-w-full max-h-[80vh] rounded-2xl object-contain">
            </div>
        </div>
    </section>
    @endif

    <!-- 3. Dynamic Interactive CTA Section -->
    <section class="bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-950 text-white py-16 relative overflow-hidden select-none shadow-inner border-y border-slate-900">
        <div class="absolute w-[500px] h-[500px] bg-[radial-gradient(circle,_rgba(229,191,19,0.05)_0%,_rgba(0,0,0,0)_70%)] -top-44 -left-44 pointer-events-none"></div>
        <div class="absolute w-[500px] h-[500px] bg-[radial-gradient(circle,_rgba(248,59,59,0.05)_0%,_rgba(0,0,0,0)_70%)] -bottom-44 -right-44 pointer-events-none"></div>
        
        <div class="container mx-auto px-4 text-center max-w-2xl relative z-10 space-y-6">
            <h3 class="text-2xl md:text-3xl font-black tracking-tight text-white leading-tight">
                Have any questions related to our Sivakasi products? Feel free to enquire!
            </h3>
            <p class="text-xs text-slate-400 font-semibold leading-relaxed">
                Our support team is active and ready to assist you with order bookings, bulk discounts, and transport logistics details.
            </p>
            <div class="pt-4 flex flex-wrap justify-center gap-4">
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', App\Models\Setting::get('store_whatsapp', '919998887776')) }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-3 rounded-full text-xs font-extrabold flex items-center gap-2 shadow shadow-emerald-950/20 transform active:scale-95 transition-all">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>Contact WhatsApp Support</span>
                </a>
                <a href="/" class="bg-gradient-to-r from-gold-500 to-amber-500 hover:from-gold-600 hover:to-amber-600 text-slate-950 px-6 py-3 rounded-full text-xs font-black uppercase tracking-wider shadow transform active:scale-95 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span>Browse Price List</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 4. Three-Column Pillars Section (Why Choose Us & Vision/Mission & Safety) -->
    <section class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Column 1: Why Choose Us -->
            <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm space-y-6">
                <div class="space-y-1.5 border-b border-slate-100 pb-4">
                    <span class="text-[9px] text-crimson-600 font-bold uppercase tracking-widest block"><i class="fa-solid fa-star"></i> Core values</span>
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Why Choose Us</h3>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed font-semibold">
                    Our company is renowned for providing high-grade fireworks products to our esteemed customers at the lowest Sivakasi market prices.
                </p>
                <ul class="space-y-2.5 text-xs text-slate-650 font-bold">
                    <li class="flex items-center gap-2.5">
                        <i class="fa-solid fa-fire text-gold-500 text-xs flex-shrink-0"></i>
                        <span>10+ Years of Trading Experience</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <i class="fa-solid fa-fire text-gold-500 text-xs flex-shrink-0"></i>
                        <span>Direct Dealers of Reputed Brands</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <i class="fa-solid fa-fire text-gold-500 text-xs flex-shrink-0"></i>
                        <span>Quality & Standard Safety Crackers</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <i class="fa-solid fa-fire text-gold-500 text-xs flex-shrink-0"></i>
                        <span>Transparent & Ethical Operations</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <i class="fa-solid fa-fire text-gold-500 text-xs flex-shrink-0"></i>
                        <span>Quick Responses & Swift Shipping</span>
                    </li>
                </ul>
            </div>

            <!-- Column 2: Center visual graphic -->
            <div class="bg-gradient-to-tr from-gold-50/50 to-crimson-50/50 border border-slate-200/60 p-6 rounded-2xl shadow-sm flex flex-col justify-center items-center text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-white border border-slate-200 flex items-center justify-center text-crimson-600 text-3xl shadow-sm">
                    <i class="fa-solid fa-fire-burner"></i>
                </div>
                <h4 class="font-extrabold text-sm text-slate-850 uppercase tracking-widest">{{ App\Models\Setting::get('store_name', 'Cracker Demo') }}</h4>
                <p class="text-[11px] text-slate-500 leading-normal max-w-xs font-semibold">
                    Spreading lights, sparkles, and infinite joy to all Indian households observing strict security and environment protocols.
                </p>
            </div>

            <!-- Column 3: Vision & Mission -->
            <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm space-y-6">
                <div class="space-y-1.5 border-b border-slate-100 pb-4">
                    <span class="text-[9px] text-gold-600 font-bold uppercase tracking-widest block"><i class="fa-solid fa-eye"></i> Goals & targets</span>
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Vision & Mission</h3>
                </div>
                
                <div class="space-y-4 text-xs font-semibold">
                    <div class="space-y-1">
                        <strong class="text-slate-800 block font-bold uppercase tracking-wider text-[10px]"><i class="fa-solid fa-circle-check text-gold-500 text-[9px] mr-1"></i> Our Vision</strong>
                        <p class="text-slate-500 leading-relaxed">
                            To be the finest wholesale and retail fireworks booking shop for Sivakasi fancy crackers and customized diwali giftboxes.
                        </p>
                    </div>
                    <div class="space-y-1 pt-3 border-t border-slate-100">
                        <strong class="text-slate-800 block font-bold uppercase tracking-wider text-[10px]"><i class="fa-solid fa-circle-check text-gold-500 text-[9px] mr-1"></i> Our Mission</strong>
                        <p class="text-slate-500 leading-relaxed">
                            To provide pure quality, highly innovative, and completely safe crackers to our valuable customers at highly affordable rates.
                        </p>
                    </div>
                </div>
            </div>

    </section>

    <!-- 4.5. Premium Booking Terms & Conditions Section -->
    <section class="container mx-auto px-4 py-12 border-t border-slate-200">
        <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-10 shadow-sm relative overflow-hidden">
            <div class="absolute -right-24 -top-24 w-64 h-64 bg-slate-50/50 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="space-y-6">
                <div class="space-y-2 border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold text-crimson-600 uppercase tracking-widest"><i class="fa-solid fa-scale-balanced mr-1"></i> Terms & Policies</span>
                    <h3 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-tight">Terms & Conditions</h3>
                    <p class="text-xs text-slate-550 max-w-xl leading-relaxed font-semibold">
                        Please review our official terms, payment policies, delivery frameworks, and transport conditions before placing your fireworks booking.
                    </p>
                </div>

                <div class="prose prose-slate max-w-none text-xs leading-relaxed space-y-4 text-slate-600 font-medium">
                    @php
                        $termsContent = App\Models\Setting::get('terms_conditions');
                    @endphp

                    @if(!empty(trim(strip_tags($termsContent))))
                        {!! $termsContent !!}
                    @else
                        <!-- High-fidelity premium default terms fallback -->
                        <div class="space-y-6 text-slate-550">
                            <p><strong>1. Booking Eligibility & Order Guidelines:</strong> By placing an order on our online booking storefront, you confirm that you are at least 18 years of age and authorized to purchase fireworks products in your local jurisdiction.</p>
                            <p>All items added to your cart represent Sivakasi wholesale stock and are subject to availability. The minimum purchase value to qualify for transport delivery is strictly <strong>₹{{ number_format(App\Models\Setting::get('min_order_value', 3800)) }}</strong> (net payable value after flat discounts are calculated).</p>
                            <p><strong>2. Pricing, Discounts & Wholesale Schemes:</strong> All products listed indicate their Maximum Retail Price (MRP) alongside our discounted Sivakasi wholesale rate (a standard <strong>{{ App\Models\Setting::get('discount_percent', 60) }}% off</strong>).</p>
                            <p><strong>3. Payment & Booking Terms:</strong> We operate on a booking estimation model. No online payments are accepted on this website due to regulatory guidelines. Upon checkout, a booking invoice summary is generated. Please click the WhatsApp button to share your booking reference with us so we can finalize processing offline.</p>
                            <p><strong>4. Shipping, Lorry Transports & Delivery:</strong> All bookings are securely packed and shipped via licensed <strong>Lorry Transport Services</strong> to your nearest transport hub/godown.</p>
                            <p><strong>5. Safety & Liability:</strong> Firecrackers are inherently chemical materials. Please exercise extreme caution. Always supervise children, wear cotton garments, keep a bucket of water adjacent, and follow all safety guidelines.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Interactive Contact Information & Map Section -->
    <section class="container mx-auto px-4 py-16 border-t border-slate-200">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
            
            <!-- Left Column: Elegant Contact Information Cards -->
            <div class="lg:col-span-5 flex flex-col justify-between space-y-6">
                <div class="space-y-2">
                    <span class="text-xs font-bold text-crimson-600 uppercase tracking-widest"><i class="fa-solid fa-map-location-dot mr-1"></i> Get In Touch</span>
                    <h3 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-tight">Contact Information</h3>
                    <p class="text-xs text-slate-500 font-semibold leading-relaxed">
                        Need assistance with your booking? Visually trace our physical retail shop or contact us directly via phone, email, or WhatsApp.
                    </p>
                </div>
                
                <div class="space-y-4">
                    <!-- Address Card -->
                    <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-crimson-50 text-crimson-600 flex items-center justify-center flex-shrink-0 border border-crimson-100">
                            <i class="fa-solid fa-location-dot text-base"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-[10px] text-slate-400 font-extrabold uppercase tracking-widest">Our Store Address</h4>
                            <p class="text-xs text-slate-700 leading-normal font-bold">
                                {{ App\Models\Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi') }}
                            </p>
                        </div>
                    </div>

                    <!-- Call Card -->
                    <a href="tel:{{ App\Models\Setting::get('store_phone') }}" class="bg-white border border-slate-200 hover:border-slate-350 p-4 rounded-2xl shadow-sm flex items-start gap-4 hover:scale-[1.01] transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-gold-50 text-gold-600 flex items-center justify-center flex-shrink-0 border border-gold-100">
                            <i class="fa-solid fa-phone text-base"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-[10px] text-slate-400 font-extrabold uppercase tracking-widest">Phone & Call Support</h4>
                            <p class="text-xs text-slate-700 leading-normal font-bold">
                                {{ App\Models\Setting::get('store_phone', '+91 9998887776') }}
                            </p>
                        </div>
                    </a>

                    <!-- Email Card -->
                    <a href="mailto:{{ App\Models\Setting::get('store_email') }}" class="bg-white border border-slate-200 hover:border-slate-300 p-4 rounded-2xl shadow-sm flex items-start gap-4 hover:scale-[1.01] transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 border border-blue-100">
                            <i class="fa-solid fa-envelope text-base"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-[10px] text-slate-400 font-extrabold uppercase tracking-widest">Email Support Address</h4>
                            <p class="text-xs text-slate-700 leading-normal font-bold">
                                {{ App\Models\Setting::get('store_email', 'crackerdemo@gmail.com') }}
                            </p>
                        </div>
                    </a>

                    <!-- WhatsApp Card -->
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', App\Models\Setting::get('store_whatsapp', '919998887776')) }}" target="_blank" class="bg-emerald-50/50 border border-emerald-100 hover:border-emerald-200 p-4 rounded-2xl shadow-sm flex items-start gap-4 hover:scale-[1.01] transition-transform">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-[10px] text-emerald-650 font-extrabold uppercase tracking-widest">WhatsApp Direct Booking</h4>
                            <p class="text-xs text-emerald-800 leading-normal font-extrabold">
                                Click to Chat with Support
                            </p>
                        </div>
                    </a>

                    <!-- License Card (Government Explosive License) -->
                    @php
                        $licNameCard = App\Models\Setting::get('license_name');
                        $licNoCard = App\Models\Setting::get('license_no');
                    @endphp
                    @if(!empty($licNameCard) || !empty($licNoCard))
                        <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0 border border-slate-150">
                                <i class="fa-solid fa-scale-balanced text-base"></i>
                            </div>
                            <div class="space-y-1">
                                <h4 class="text-[10px] text-slate-400 font-extrabold uppercase tracking-widest">Government Explosive License</h4>
                                <p class="text-xs text-slate-700 leading-normal font-bold">
                                    @if(!empty($licNameCard))
                                        {{ $licNameCard }}
                                    @endif
                                    @if(!empty($licNoCard))
                                        <span class="block text-[10px] text-slate-500 font-mono font-semibold mt-0.5">License No: {{ $licNoCard }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Interactive Map Widget -->
            <div class="lg:col-span-7">
                <div class="map-container w-full h-full min-h-[350px] rounded-3xl overflow-hidden border border-slate-200 shadow-md">
                    @if($mapIframe = App\Models\Setting::get('store_map_iframe'))
                        {!! $mapIframe !!}
                    @else
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31484.78768782782!2d77.78440079999999!3d9.4475475!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3b06cee41fe51a8d%3A0xe964a2754897f1f!2sSivakasi%2C%20Tamil%20Nadu!5e0!3m2!1sen!2sin!4v1717830000000!5m2!1sen!2sin" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    @endif
                </div>
            </div>
            
        </div>
    </section>

</div>
@endsection
