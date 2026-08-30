@extends('layouts.app')

@section('title', App\Models\Setting::get('store_name', 'Cracker Demo') . ' | Sivakasi Online Fireworks Booking Shop')

@section('content')
<div x-data="storefrontData()" x-init="initStorefront()" class="relative text-slate-800">

    <!-- Mobile Sticky Category Swiper (Premium horizontal bar sticking under header) -->
    <div class="lg:hidden sticky top-[64px] z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 py-2.5 shadow-sm select-none overflow-x-auto scrollbar-none flex gap-1.5 items-center">
        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider pr-1 flex-shrink-0 flex items-center gap-1">
            <i class="fa-solid fa-filter text-crimson-600 text-xs"></i> Categories
        </span>
        <button @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'bg-slate-50 text-slate-650 border border-slate-200'" class="px-3.5 py-1.5 rounded-full text-[10px] uppercase tracking-wider whitespace-nowrap transition-all duration-200 flex items-center gap-1">
            All
        </button>
        @foreach($categories as $category)
        <button @click="activeCategory = '{{ $category->slug }}'" :class="activeCategory === '{{ $category->slug }}' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'bg-slate-50 text-slate-650 border border-slate-200'" class="px-3.5 py-1.5 rounded-full text-[10px] uppercase tracking-wider whitespace-nowrap transition-all duration-200">
            {{ $category->name }}
        </button>
        @endforeach
    </div>

    <!-- 1. Hero Image Slider Section -->
    @php
        $sliders = array_filter([
            App\Models\Setting::get('slider_image_1') ?: 'https://images.unsplash.com/photo-1531747118685-ca8fa6e08806?auto=format&fit=crop&w=1200&q=80',
            App\Models\Setting::get('slider_image_2') ?: 'https://images.unsplash.com/photo-1482862549707-f63cb32c5fd9?auto=format&fit=crop&w=1200&q=80',
            App\Models\Setting::get('slider_image_3') ?: 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80'
        ]);
    @endphp

    <section class="relative bg-slate-50 overflow-hidden z-10 select-none">
        <div class="container mx-auto px-4 pt-6">
            <div class="relative rounded-2xl md:rounded-3xl overflow-hidden shadow-lg border border-slate-200 bg-white group">
                
                <!-- Carousel Container -->
                <div x-data="{
                    activeSlide: 0,
                    totalSlides: {{ count($sliders) }},
                    autoplayInterval: null,
                    nextSlide() {
                        this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
                    },
                    prevSlide() {
                        this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides;
                    },
                    startAutoplay() {
                        if (this.totalSlides > 1) {
                            this.autoplayInterval = setInterval(() => { this.nextSlide() }, 5000);
                        }
                    },
                    stopAutoplay() {
                        if (this.autoplayInterval) {
                            clearInterval(this.autoplayInterval);
                        }
                    }
                }" x-init="startAutoplay()" @mouseenter="stopAutoplay()" @mouseleave="startAutoplay()" class="relative w-full aspect-[21/9] sm:aspect-[21/8] md:aspect-[3/1] min-h-[180px] sm:min-h-[260px] md:min-h-[360px] lg:min-h-[440px] overflow-hidden">
                    
                    @if(count($sliders) > 0)
                        <!-- Slide items -->
                        @foreach($sliders as $index => $slide)
                            <div x-show="activeSlide === {{ $index }}" 
                                 x-transition:enter="transition ease-out duration-700 transform" 
                                 x-transition:enter-start="opacity-0 translate-x-full" 
                                 x-transition:enter-end="opacity-100 translate-x-0" 
                                 x-transition:leave="transition ease-in duration-700 transform" 
                                 x-transition:leave-start="opacity-100 translate-x-0" 
                                 x-transition:leave-end="opacity-0 -translate-x-full" 
                                 class="absolute inset-0 w-full h-full" 
                                 style="display: none;">
                                <img src="{{ Str::startsWith($slide, ['http://', 'https://']) ? $slide : '/' . $slide }}" alt="Promotional Slide {{ $index + 1 }}" class="w-full h-full object-cover">
                            </div>
                        @endforeach

                        <!-- Left & Right Arrow Navigation -->
                        @if(count($sliders) > 1)
                            <button @click="prevSlide()" class="absolute left-3 md:left-5 top-1/2 -translate-y-1/2 w-8 h-8 md:w-11 md:h-11 bg-white/25 hover:bg-white/45 active:bg-white/60 text-white rounded-full flex items-center justify-center backdrop-blur-md border border-white/20 transition-all duration-300 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 hover:scale-105 z-20">
                                <i class="fa-solid fa-chevron-left text-xs md:text-sm"></i>
                            </button>
                            <button @click="nextSlide()" class="absolute right-3 md:right-5 top-1/2 -translate-y-1/2 w-8 h-8 md:w-11 md:h-11 bg-white/25 hover:bg-white/45 active:bg-white/60 text-white rounded-full flex items-center justify-center backdrop-blur-md border border-white/20 transition-all duration-300 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 hover:scale-105 z-20">
                                <i class="fa-solid fa-chevron-right text-xs md:text-sm"></i>
                            </button>

                            <!-- Carousel Dot Indicators -->
                            <div class="absolute bottom-3 md:bottom-5 left-1/2 -translate-x-1/2 flex gap-1.5 md:gap-2 z-20">
                                @foreach($sliders as $index => $slide)
                                    <button @click="activeSlide = {{ $index }}" :class="activeSlide === {{ $index }} ? 'bg-crimson-600 w-5 md:w-6 shadow-md shadow-crimson-600/40' : 'bg-white/60 hover:bg-white w-1.5 md:w-2'" class="h-1.5 md:h-2 rounded-full transition-all duration-300" title="Slide {{ $index + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <!-- Soft gradient mesh in background fallback -->
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-crimson-100/30 via-white to-slate-50 opacity-90 flex items-center justify-center">
                            <span class="text-xs uppercase tracking-widest text-slate-400 font-extrabold">Welcome to Sivakasi Fireworks</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </section>

    <!-- 1b. Welcome & Quick Order CTA Section -->
    <section class="container mx-auto px-4 py-8 select-none z-10 relative">
        <div class="bg-white border border-slate-200 p-6 md:p-8 rounded-2xl md:rounded-3xl shadow-sm text-center w-full space-y-6">
            <span class="inline-flex items-center gap-1.5 bg-crimson-50 border border-crimson-100 text-crimson-700 text-[10px] font-extrabold uppercase tracking-widest px-3.5 py-1 rounded-full shadow-sm animate-pulse">
                <i class="fa-solid fa-gift"></i> Sivakasi Wholesale Booking Open
            </span>
            
            <div class="space-y-3">
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-black tracking-tight text-slate-900 leading-tight">
                    Get Best Sivakasi Crackers with <br class="hidden sm:inline">
                    <span class="bg-gradient-to-r from-crimson-600 to-crimson-500 bg-clip-text text-transparent">Flat {{ $settings['discount_percent'] }}% Discount!</span>
                </h2>
                <p class="text-xs md:text-sm text-slate-650 max-w-2xl mx-auto leading-relaxed font-semibold">
                    Add Sivakasi-manufactured fireworks directly from our price list. Minimum order value is ₹{{ number_format($settings['min_order_value']) }}. Place your order, check out, and receive shipment details via WhatsApp!
                </p>
            </div>

            <div class="flex flex-wrap justify-center gap-3 md:gap-4 pt-2">
                <a href="#quick-order" class="bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white font-extrabold px-6 py-3 md:px-8 md:py-3.5 rounded-full text-xs uppercase tracking-wider shadow-md shadow-crimson-100/50 hover:scale-102 active:scale-98 transition-all duration-300 flex items-center gap-2">
                    <i class="fa-solid fa-basket-shopping text-sm"></i>
                    <span>Start Quick Order</span>
                </a>
                <a href="{{ route('price_list') }}" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 hover:border-slate-300 text-slate-700 font-extrabold px-6 py-3 md:px-8 md:py-3.5 rounded-full text-xs uppercase tracking-wider shadow-sm hover:scale-102 active:scale-98 transition-all duration-300 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-sm text-crimson-650"></i>
                    <span>Wholesale Price List</span>
                </a>
            </div>

            <div class="flex flex-wrap justify-center gap-3 pt-4 border-t border-slate-100">
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/60 px-3 py-1.5 rounded-xl text-[10px] font-bold text-slate-600">
                    <i class="fa-solid fa-truck text-crimson-600 text-[11px]"></i> Lorry Transport Delivery
                </div>
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/60 px-3 py-1.5 rounded-xl text-[10px] font-bold text-slate-600">
                    <i class="fa-solid fa-badge-check text-crimson-600 text-[11px]"></i> Sulphurless Sparklers
                </div>
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/60 px-3 py-1.5 rounded-xl text-[10px] font-bold text-slate-600">
                    <i class="fa-solid fa-circle-check text-crimson-600 text-[11px]"></i> Easy Offline Booking
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Store Content Grid -->
    <section id="quick-order" class="container mx-auto px-4 py-10 flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- Left: Category sidebar filters (Hidden on Mobile, Sticky on Desktop) -->
        <aside class="hidden lg:block lg:w-64 flex-shrink-0 lg:sticky lg:top-24 space-y-4 select-none">
            <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest border-b border-slate-150 pb-2.5 mb-3 flex justify-between items-center">
                    <span>Categories</span>
                    <i class="fa-solid fa-filter text-slate-400 text-xs"></i>
                </h3>
                
                <div class="flex flex-row lg:flex-col overflow-x-auto lg:overflow-visible gap-1 pb-2 lg:pb-0 scrollbar-none">
                    <button @click="selectCategory('all')" :class="activeCategory === 'all' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'text-slate-650 hover:bg-slate-100'" class="w-auto lg:w-full text-left px-3.5 py-2.5 rounded-xl text-xs flex items-center gap-2 whitespace-nowrap transition-all duration-200 cursor-pointer">
                        <i class="fa-solid fa-boxes-stacked text-[11px] opacity-80"></i> All Products
                    </button>
                    @foreach($categories as $category)
                    <button @click="selectCategory('{{ $category->slug }}')" :class="activeCategory === '{{ $category->slug }}' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'text-slate-650 hover:bg-slate-100'" class="w-auto lg:w-full text-left px-3.5 py-2.5 rounded-xl text-xs flex items-center gap-2 whitespace-nowrap transition-all duration-200 cursor-pointer">
                        <i class="fa-solid fa-fire-flame-curved text-[11px] opacity-80"></i> {{ $category->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Store Info Sidebar widget -->
            <div class="hidden lg:block bg-white border border-slate-200 p-4 rounded-2xl space-y-3.5 shadow-sm">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-crimson-600"></i> Store Info
                </h4>
                <div class="text-[11px] text-slate-500 space-y-2.5 leading-relaxed font-medium">
                    <div class="flex gap-2">
                        <i class="fa-solid fa-house-circle-check text-crimson-500/80 mt-0.5"></i>
                        <span><strong>Address:</strong> {{ $settings['store_address'] }}</span>
                    </div>
                    <div class="flex gap-2">
                        <i class="fa-solid fa-envelope text-crimson-500/80 mt-0.5"></i>
                        <span><strong>Support:</strong> {{ $settings['store_email'] }}</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Right: Product list spreadsheet -->
        <div class="flex-grow w-full space-y-6">
            
            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
                <div class="relative w-full sm:max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input x-model="searchQuery" type="text" placeholder="Search firecrackers by name..." class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl py-2 pl-10 pr-4 text-xs text-slate-700 placeholder-slate-400 focus:ring-1 focus:ring-slate-300 focus:outline-none transition-all">
                </div>
                
                <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold select-none">
                    <span>Showing <strong class="text-crimson-600" x-text="filteredProductsCount">0</strong> products</span>
                </div>
            </div>

            <!-- Dynamic Spreadsheet Grid -->
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto sm:overflow-visible">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px] select-none">
                                <th class="py-4 px-3 sm:px-4">Cracker Details</th>
                                <th class="hidden sm:table-cell py-4 px-4 w-28 text-center">Unit / Box</th>
                                <th class="py-4 px-3 sm:px-4 w-24 sm:w-36 text-right">Price (₹)</th>
                                <th class="py-4 px-3 sm:px-4 w-28 sm:w-40 text-center">Order Qty</th>
                                <th class="hidden md:table-cell py-4 px-4 w-28 text-right pr-6">Total (₹)</th>
                                <th class="py-4 px-3 sm:px-4 w-28 text-right pr-4">Sub Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150">
                            
                            <!-- Loop Categories and products -->
                            @foreach($categories as $category)
                            <tr id="category-row-{{ $category->slug }}" x-show="shouldShowCategory('{{ $category->slug }}')" @click="toggleCategory('{{ $category->slug }}')" class="bg-slate-50 font-bold text-slate-700 border-b border-slate-200/80 select-none cursor-pointer hover:bg-slate-100 transition-colors">
                                <td colspan="6" class="py-3 px-3 sm:px-4 flex items-center justify-between text-crimson-650 tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-fire text-[10px] text-crimson-500"></i>
                                        <span>{{ $category->name }}</span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200" :class="collapsedCategories.has('{{ $category->slug }}') ? '-rotate-90' : 'rotate-0'"></i>
                                </td>
                            </tr>

                            @foreach($category->products as $product)
                            <tr x-show="shouldShowProduct('{{ $category->slug }}', '{{ addslashes($product->name) }}')" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="hover:bg-slate-50/50 transition-colors">
                                
                                <!-- Description / Pic -->
                                <td class="py-3.5 px-3 sm:px-4 flex items-center gap-2 sm:gap-3">
                                    <div class="hidden sm:flex w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 items-center justify-center text-slate-400 overflow-hidden flex-shrink-0">
                                        @if($product->image)
                                            <img src="/{{ $product->image }}" alt="{{ $product->name }}" class="object-cover w-full h-full">
                                        @else
                                            <i class="fa-solid fa-sparkles text-sm text-crimson-450/40"></i>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <h4 class="font-extrabold text-slate-800 text-xs leading-normal flex items-center gap-1.5">
                                            @if($product->product_code)
                                                <span class="text-[9px] font-mono font-bold text-slate-700 bg-slate-100 border border-slate-200 px-1.5 py-0.2 rounded">{{ $product->product_code }}</span>
                                            @endif
                                            <span>{{ $product->name }}</span>
                                        </h4>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[8px] sm:text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $category->name }}</span>
                                            <!-- Responsive Inline Pack size badge on mobile -->
                                            <span class="sm:hidden text-[9px] font-bold text-slate-500 bg-slate-100 border border-slate-150 px-1.5 py-0.5 rounded-md font-mono">{{ $product->pack_size }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Pack size -->
                                <td class="hidden sm:table-cell py-3.5 px-4 text-center text-slate-650 font-bold font-mono">
                                    {{ $product->pack_size }}
                                </td>

                                <!-- MRP vs Discount Price -->
                                <td class="py-3.5 px-3 sm:px-4 text-right">
                                    <div class="text-slate-400 text-[10px] line-through">₹{{ number_format($product->mrp, 2) }}</div>
                                    <div class="text-crimson-650 font-extrabold">₹{{ number_format($product->selling_price, 2) }}</div>
                                </td>

                                <!-- Quantities selects -->
                                <td class="py-3.5 px-3 sm:px-4 text-center">
                                    <div class="inline-flex items-center bg-slate-100 border border-slate-200 rounded-lg p-0.5 sm:p-1 select-none">
                                        <button @click="decreaseQty({{ $product->id }})" class="w-6 h-6 sm:w-7 sm:h-7 text-slate-650 hover:text-slate-900 hover:bg-white rounded flex items-center justify-center font-bold text-xs transition-colors shadow-sm">
                                            <i class="fa-solid fa-minus text-[8px] sm:text-[9px]"></i>
                                        </button>
                                        <input @input="updateInputQty({{ $product->id }}, $event.target.value, {{ $product->mrp }}, {{ $product->selling_price }}, '{{ addslashes($product->name) }}', '{{ $product->pack_size }}')" type="number" :value="cart[{{ $product->id }}]?.qty || 0" min="0" class="w-8 sm:w-12 text-center bg-transparent border-0 text-xs font-black text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        <button @click="increaseQty({{ $product->id }}, {{ $product->mrp }}, {{ $product->selling_price }}, '{{ addslashes($product->name) }}', '{{ $product->pack_size }}')" class="w-6 h-6 sm:w-7 sm:h-7 text-slate-650 hover:text-slate-900 hover:bg-white rounded flex items-center justify-center font-bold text-xs transition-colors shadow-sm">
                                            <i class="fa-solid fa-plus text-[8px] sm:text-[9px]"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Row Tally (hidden on mobile) -->
                                <td class="hidden md:table-cell py-3.5 px-4 text-right font-extrabold text-slate-800 pr-6">
                                    ₹<span x-text="formatCurrency((cart[{{ $product->id }}]?.qty || 0) * {{ $product->selling_price }})">0.00</span>
                                </td>

                                <!-- Sub Total (always visible) -->
                                <td class="py-3.5 px-3 sm:px-4 text-right pr-4">
                                    <span class="font-extrabold text-crimson-600" x-text="(cart[{{ $product->id }}]?.qty || 0) > 0 ? '₹' + formatCurrency((cart[{{ $product->id }}]?.qty || 0) * {{ $product->selling_price }}) : '—'">—</span>
                                </td>

                            </tr>
                            @endforeach
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </section>

    <!-- 3. Dynamic Floating Sticky Footer Cart Tally -->
    <div x-show="totalQty > 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0" class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200/80 shadow-2xl py-2.5 backdrop-blur-md px-4 select-none">
        <div class="container mx-auto max-w-5xl flex flex-col lg:flex-row gap-3 items-center justify-between">
            
            <!-- Cart details totals summary -->
            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-5 gap-y-2 text-xs text-slate-500 w-full lg:w-auto font-medium">
                
                <!-- Total Items -->
                <div class="flex items-center gap-1.5 text-slate-800">
                    <span class="text-[9px] text-slate-400 uppercase tracking-wider font-extrabold">Total Items:</span>
                    <strong class="text-sm font-black text-crimson-600" x-text="totalQty">0</strong>
                    <span class="text-slate-300">/</span>
                    <strong class="text-slate-700 font-bold" x-text="totalUniqueProducts">0</strong> <span class="text-[9px] text-slate-400 uppercase font-extrabold">Products</span>
                </div>
                
                <span class="hidden sm:inline text-slate-350">|</span>
                
                <!-- MRP -->
                <div class="flex items-center gap-1.5">
                    <span class="text-[9px] text-slate-400 uppercase tracking-wider font-extrabold">Original MRP:</span>
                    <span class="line-through font-semibold text-slate-450">₹<span x-text="formatCurrency(totalMrp)">0.00</span></span>
                </div>
                
                <span class="hidden sm:inline text-slate-350">|</span>
                
                <!-- Savings -->
                <div class="text-crimson-750 bg-crimson-50 border border-crimson-100 px-2.5 py-0.5 rounded-lg shadow-sm flex items-center gap-1.5">
                    <span class="text-[9px] text-crimson-500 uppercase tracking-wider font-extrabold">Savings:</span>
                    <strong class="font-extrabold text-xs">₹<span x-text="formatCurrency(totalDiscount)">0.00</span></strong>
                </div>
                
                <span class="hidden sm:inline text-slate-350">|</span>
                
                <!-- Net Payable Amount -->
                <div class="flex items-center gap-1.5 text-slate-800">
                    <span class="text-[9px] text-slate-400 uppercase tracking-wider font-extrabold">Net Payable:</span>
                    <strong class="text-base font-black text-crimson-600">₹<span x-text="formatCurrency(totalNet)">0.00</span></strong>
                </div>
            </div>

            <!-- Checkout controls & meter progress -->
            <div class="flex flex-col sm:flex-row gap-2.5 w-full lg:w-auto items-center justify-center lg:justify-end">
                
                <!-- Minimum Order Value Progress bar -->
                <div class="w-full sm:w-44 text-center space-y-1" x-show="enableMinOrder && totalNet < {{ $settings['min_order_value'] }}">
                    <div class="flex justify-between text-[9px] text-slate-500 font-bold uppercase px-0.5">
                        <span>Min Order check</span>
                        <span class="text-crimson-650" x-text="minOrderProgressText()">Need ₹0 more</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5 border border-slate-300 overflow-hidden">
                        <div class="bg-gradient-to-r from-crimson-600 to-crimson-500 h-full rounded-full transition-all duration-300" :style="`width: ${minOrderProgressPercent()}%`"></div>
                    </div>
                </div>
                
                <!-- Clear All Button -->
                <button @click="clearCart()" class="w-full sm:w-auto px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition-all duration-300 flex items-center justify-center gap-1.5 bg-slate-100 border border-slate-200 text-slate-500 hover:bg-slate-200 hover:text-slate-700 hover:border-slate-300 shadow-sm">
                    <i class="fa-solid fa-trash text-crimson-600"></i>
                    <span>Clear All</span>
                </button>

                <!-- Standard Action Button -->
                <button @click="openCheckoutDrawer()" :disabled="enableMinOrder && totalNet < {{ $settings['min_order_value'] }}" :class="(!enableMinOrder || totalNet >= {{ $settings['min_order_value'] }}) ? 'bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white shadow-md shadow-crimson-100 hover:scale-105 animate-bounce-subtle' : 'bg-slate-200 border border-slate-350 text-slate-400 cursor-not-allowed'" class="w-full sm:w-auto px-5 py-2.5 rounded-full text-xs font-extrabold uppercase tracking-wider transition-all duration-300 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-basket-shopping-simple"></i>
                    <span>Checkout Now</span>
                </button>

            </div>
        </div>
    </div>

    <!-- 4. Slide-out Checkout Drawer -->
    <div x-show="checkoutOpen" x-transition:enter="transition ease-out duration-300" x-transition:leave="transition ease-in duration-300" class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <!-- Backdrop overlay -->
        <div @click="closeCheckoutDrawer()" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="checkoutOpen" x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="w-screen max-w-lg">
                
                <div class="h-full flex flex-col bg-white border-l border-slate-200 shadow-2xl overflow-y-auto">
                    
                    <!-- Header -->
                    <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between select-none">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-basket-shopping text-crimson-650"></i> Finalize Booking
                            </h3>
                            <p class="text-[10px] text-slate-500 font-semibold">Provide shipping details to book Sivakasi crackers</p>
                        </div>
                        <button @click="closeCheckoutDrawer()" class="text-slate-400 hover:text-slate-650 p-2 rounded-lg transition-colors">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <!-- Checkout Form content -->
                    <form @submit.prevent="submitOrder()" class="flex-grow flex flex-col p-6 space-y-5">
                        
                        <!-- Customer details fields -->
                        <div class="space-y-4">
                            
                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-user mr-1 text-crimson-500/80"></i>Full Name <span class="text-crimson-500">*</span></label>
                                <input x-model="form.name" type="text" required placeholder="Full Name" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-phone mr-1 text-crimson-500/80"></i>Mobile Number <span class="text-crimson-500">*</span></label>
                                    <input x-model="form.phone" @input="form.phone = form.phone.replace(/[^0-9]/g, '').slice(0, 10)" type="tel" maxlength="10" pattern="[0-9]{10}" required placeholder="10-Digit Mobile Number" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all font-mono">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-brands fa-whatsapp mr-1 text-crimson-500/80"></i>WhatsApp Number</label>
                                    <input x-model="form.whatsapp" @input="form.whatsapp = form.whatsapp.replace(/[^0-9]/g, '').slice(0, 10)" type="tel" maxlength="10" pattern="[0-9]{10}" placeholder="10-Digit WhatsApp (Optional)" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-envelope mr-1 text-crimson-500/80"></i>Email Address</label>
                                <input x-model="form.email" type="email" placeholder="Email Address (Optional)" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-location-dot mr-1 text-crimson-500/80"></i>Delivery Address <span class="text-crimson-500">*</span></label>
                                <textarea x-model="form.address" required rows="3" placeholder="Full Delivery Address" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all resize-none"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-map-pin mr-1 text-crimson-500/80"></i>Landmark</label>
                                    <input x-model="form.landmark" type="text" placeholder="Landmark (Optional)" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-city mr-1 text-crimson-500/80"></i>City / Town <span class="text-crimson-500">*</span></label>
                                    <input x-model="form.city" type="text" required placeholder="City or Town" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-globe mr-1 text-crimson-500/80"></i>State <span class="text-crimson-500">*</span></label>
                                    <select x-model="form.state" required class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 focus:outline-none transition-all">
                                        <option value="Tamilnadu">Tamilnadu</option>
                                        <option value="Kerala">Kerala</option>
                                        <option value="Karnataka">Karnataka</option>
                                        <option value="Andhra Pradesh">Andhra Pradesh</option>
                                        <option value="Telangana">Telangana</option>
                                        <option value="Puducherry">Puducherry</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-map-location mr-1 text-crimson-500/80"></i>Pin Code <span class="text-crimson-500">*</span></label>
                                    <input x-model="form.pincode" type="text" required placeholder="Pin Code" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all font-mono">
                                </div>
                                      <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-truck-ramp-box mr-1 text-crimson-500/80"></i>Preferred Lorry Transport Name</label>
                                <input x-model="form.transport_name" type="text" placeholder="Preferred Transport Name (Optional)" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all">
                            </div>

                            <!-- Promo Code Input (Visible if enablePromoCodes is true) -->
                            <div x-show="enablePromoCodes" class="space-y-1.5">
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1"><i class="fa-solid fa-ticket mr-1 text-crimson-500/80"></i>Promo / Coupon Code</label>
                                <div class="flex gap-2">
                                    <input x-model="form.promo_code" type="text" placeholder="Enter Promo Code" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none transition-all uppercase font-mono">
                                    <button type="button" @click="applyPromoCode()" class="bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap">
                                        Apply
                                    </button>
                                </div>
                                <span x-show="promoAppliedMessage" class="text-[10px] block mt-1 font-bold" :class="promoAppliedSuccess ? 'text-emerald-600' : 'text-crimson-600'" x-text="promoAppliedMessage"></span>
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"><i class="fa-solid fa-pencil mr-1 text-crimson-500/80"></i>Special Delivery Instructions</label>
                                <textarea x-model="form.notes" rows="2" placeholder="Instructions/Notes (Optional)" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none transition-all resize-none"></textarea>
                            </div>

                        </div>

                        <!-- Drawer Footer -->
                        <div class="pt-4 border-t border-slate-250 space-y-4">
                            <!-- Tax/Delivery & Promo Breakdown (only visible if enableTaxDelivery or a promo code is applied) -->
                            <div x-show="enableTaxDelivery || (enablePromoCodes && appliedPromoCode)" class="bg-slate-50 border border-slate-200 p-3.5 rounded-xl space-y-2 text-xs font-semibold">
                                <div class="flex justify-between text-slate-500">
                                    <span>Items Net Value:</span>
                                    <span>₹<span x-text="formatCurrency(totalNet)">0.00</span></span>
                                </div>
                                <div x-show="enablePromoCodes && appliedPromoCode" class="flex justify-between text-emerald-600 font-bold">
                                    <span>Promo Code Discount:</span>
                                    <span>-₹<span x-text="formatCurrency(promoDiscountAmount)">0.00</span></span>
                                </div>
                                <div x-show="enableTaxDelivery" class="flex justify-between text-slate-500">
                                    <span x-text="`GST / Tax (${taxPercent}%):`">GST:</span>
                                    <span>₹<span x-text="formatCurrency(taxAmount)">0.00</span></span>
                                </div>
                                <div x-show="enableTaxDelivery" class="flex justify-between text-slate-500">
                                    <span>Delivery Charge:</span>
                                    <span>₹<span x-text="formatCurrency(deliveryCharge)">0.00</span></span>
                                </div>
                                <div class="flex justify-between text-slate-800 border-t border-slate-200 pt-2 font-black">
                                    <span>Final Payable Total:</span>
                                    <span class="text-crimson-655 text-sm font-black">₹<span x-text="formatCurrency(finalPayableAmount)">0.00</span></span>
                                </div>
                            </div>
                            
                            <!-- Simple total if enableTaxDelivery and promo code is not applied -->
                            <div x-show="!enableTaxDelivery && !(enablePromoCodes && appliedPromoCode)" class="bg-slate-50 border border-slate-200 p-3 rounded-xl flex items-center justify-between text-xs font-semibold">
                                <span class="text-slate-500">Total Net Booking Amount:</span>
                                <span class="text-crimson-650 font-extrabold text-sm">₹<span x-text="formatCurrency(totalNet)">0.00</span></span>
                            </div>

                            <button type="submit" :disabled="submitting" class="w-full bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 disabled:from-slate-200 disabled:to-slate-200 text-white disabled:text-slate-400 font-extrabold py-3.5 rounded-full text-xs uppercase tracking-wider shadow transform active:scale-95 transition-all flex items-center justify-center gap-2">
                                <template x-if="submitting">
                                    <i class="fa-solid fa-spinner animate-spin mr-1"></i>
                                </template>
                                <template x-if="!submitting">
                                    <i class="fa-solid fa-file-invoice-dollar mr-1"></i>
                                </template>
                                <span x-text="submitting ? 'Placing Order...' : 'Submit & Confirm Booking'">Submit & Confirm Booking</span>
                            </button>
                        </div>                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function storefrontData() {
        return {
            activeCategory: 'all',
            searchQuery: '',
            collapsedCategories: new Set(),
            cart: {}, // Format: { productId: { id, qty, mrp, selling_price, name, pack_size } }
            totalQty: 0,
            totalMrp: 0.00,
            totalDiscount: 0.00,
            totalNet: 0.00,
            totalUniqueProducts: 0,
            
            // Feature flags config
            enableMinOrder: '{{ $settings['enable_min_order'] ?? 'yes' }}' === 'yes',
            enablePromoCodes: '{{ $settings['enable_promo_codes'] ?? 'yes' }}' === 'yes',
            enableTaxDelivery: '{{ $settings['enable_tax_delivery'] ?? 'no' }}' === 'yes',
            taxPercent: parseFloat('{{ $settings['tax_percent'] ?? 18 }}'),
            deliveryCharge: parseFloat('{{ $settings['delivery_charge'] ?? 150 }}'),
            
            promoCodes: [
                { code: '{{ $settings['promo_code_1'] ?? '' }}', value: '{{ $settings['promo_value_1'] ?? '' }}' },
                { code: '{{ $settings['promo_code_2'] ?? '' }}', value: '{{ $settings['promo_value_2'] ?? '' }}' },
                { code: '{{ $settings['promo_code_3'] ?? '' }}', value: '{{ $settings['promo_value_3'] ?? '' }}' },
                { code: '{{ $settings['promo_code_4'] ?? '' }}', value: '{{ $settings['promo_value_4'] ?? '' }}' },
                { code: '{{ $settings['promo_code_5'] ?? '' }}', value: '{{ $settings['promo_value_5'] ?? '' }}' },
            ],

            promoCodeInput: '',
            appliedPromoCode: '',
            promoDiscountAmount: 0.00,
            promoAppliedMessage: '',
            promoAppliedSuccess: false,
            
            taxAmount: 0.00,
            finalPayableAmount: 0.00,
            
            checkoutOpen: false,
            submitting: false,
            
            form: {
                name: '',
                phone: '',
                whatsapp: '',
                email: '',
                address: '',
                landmark: '',
                city: '',
                state: 'Tamilnadu',
                pincode: '',
                transport_name: '',
                notes: '',
                promo_code: ''
            },

            initStorefront() {
                // Initialize cart from localStorage if exists
                if (localStorage.getItem('athi_cart')) {
                    try {
                        this.cart = JSON.parse(localStorage.getItem('athi_cart'));
                        this.calculateCart();
                    } catch (e) {
                        this.cart = {};
                    }
                }
            },

            formatCurrency(value) {
                return parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            },

            increaseQty(id, mrp, selling_price, name, pack_size) {
                if (!this.cart[id]) {
                    this.cart[id] = {
                        id: id,
                        qty: 0,
                        mrp: parseFloat(mrp),
                        selling_price: parseFloat(selling_price),
                        name: name,
                        pack_size: pack_size
                    };
                }
                this.cart[id].qty++;
                this.saveCart();
            },

            decreaseQty(id) {
                if (this.cart[id]) {
                    this.cart[id].qty--;
                    if (this.cart[id].qty <= 0) {
                        delete this.cart[id];
                    }
                    this.saveCart();
                }
            },

            updateInputQty(id, val, mrp, selling_price, name, pack_size) {
                const qty = parseInt(val);
                if (isNaN(qty) || qty <= 0) {
                    if (this.cart[id]) {
                        delete this.cart[id];
                        this.saveCart();
                    }
                } else {
                    if (!this.cart[id]) {
                        this.cart[id] = {
                            id: id,
                            qty: 0,
                            mrp: parseFloat(mrp),
                            selling_price: parseFloat(selling_price),
                            name: name,
                            pack_size: pack_size
                        };
                    }
                    this.cart[id].qty = qty;
                    this.saveCart();
                }
            },

            saveCart() {
                localStorage.setItem('athi_cart', JSON.stringify(this.cart));
                this.calculateCart();
            },

            clearCart() {
                Swal.fire({
                    title: 'Clear Cart?',
                    text: 'Are you sure you want to remove all items from your cart?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e51d1d',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, clear it!',
                    cancelButtonText: 'No, keep it',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.cart = {};
                        this.saveCart();
                        
                        Swal.fire({
                            title: 'Cleared!',
                            text: 'Your cart has been cleared successfully.',
                            icon: 'success',
                            confirmButtonColor: '#e51d1d',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });
            },

            applyPromoCode() {
                const code = this.form.promo_code.trim().toUpperCase();
                if (!code) {
                    this.appliedPromoCode = '';
                    this.promoDiscountAmount = 0.00;
                    this.promoAppliedMessage = '';
                    this.promoAppliedSuccess = false;
                    this.calculateCart();
                    return;
                }

                // Check promo codes array
                const match = this.promoCodes.find(p => p.code && p.code.toUpperCase() === code);
                if (match && match.code) {
                    this.appliedPromoCode = match.code;
                    this.promoAppliedSuccess = true;
                    
                    // Calculate discount
                    const val = match.value.trim();
                    let discount = 0;
                    if (val.includes('%')) {
                        const pct = parseFloat(val.replace('%', ''));
                        if (pct > 0) {
                            discount = (this.totalNet * pct) / 100;
                        }
                    } else {
                        discount = parseFloat(val);
                    }
                    
                    this.promoDiscountAmount = Math.min(discount, this.totalNet);
                    this.promoAppliedMessage = `Code applied! You saved ₹${this.promoDiscountAmount.toFixed(2)}`;
                    this.calculateCart();
                } else {
                    this.appliedPromoCode = '';
                    this.promoDiscountAmount = 0.00;
                    this.promoAppliedMessage = 'Invalid promo code.';
                    this.promoAppliedSuccess = false;
                    this.calculateCart();
                }
            },

            calculateCart() {
                let qtySum = 0;
                let mrpSum = 0;
                let netSum = 0;
                let uniques = 0;

                for (const id in this.cart) {
                    const item = this.cart[id];
                    if (item.qty > 0) {
                        qtySum += item.qty;
                        mrpSum += item.mrp * item.qty;
                        netSum += item.selling_price * item.qty;
                        uniques++;
                    }
                }

                this.totalQty = qtySum;
                this.totalMrp = mrpSum;
                this.totalNet = netSum;
                this.totalDiscount = mrpSum - netSum;
                this.totalUniqueProducts = uniques;

                // Dynamic calculations for features
                if (this.enablePromoCodes && this.appliedPromoCode) {
                    const match = this.promoCodes.find(p => p.code && p.code.toUpperCase() === this.appliedPromoCode.toUpperCase());
                    if (match) {
                        const val = match.value.trim();
                        let discount = 0;
                        if (val.includes('%')) {
                            const pct = parseFloat(val.replace('%', ''));
                            discount = (this.totalNet * pct) / 100;
                        } else {
                            discount = parseFloat(val);
                        }
                        this.promoDiscountAmount = Math.min(discount, this.totalNet);
                    }
                } else {
                    this.promoDiscountAmount = 0.00;
                }

                const postPromoNet = Math.max(0, this.totalNet - this.promoDiscountAmount);

                if (this.enableTaxDelivery) {
                    this.taxAmount = postPromoNet * (this.taxPercent / 100);
                    this.deliveryCharge = this.totalQty > 0 ? this.deliveryCharge : 0.00;
                    this.finalPayableAmount = postPromoNet + this.taxAmount + this.deliveryCharge;
                } else {
                    this.taxAmount = 0.00;
                    this.deliveryCharge = 0.00;
                    this.finalPayableAmount = postPromoNet;
                }
            },

            minOrderProgressPercent() {
                const min = {{ $settings['min_order_value'] }};
                if (this.totalNet >= min) return 100;
                return (this.totalNet / min) * 100;
            },

            minOrderProgressText() {
                const min = {{ $settings['min_order_value'] }};
                if (this.totalNet >= min) return "Met!";
                const needed = min - this.totalNet;
                return `Add ₹${needed.toFixed(2)} more`;
            },

            selectCategory(slug) {
                this.activeCategory = slug;
                if (this.collapsedCategories.has(slug)) {
                    this.collapsedCategories.delete(slug);
                    this.collapsedCategories = new Set(this.collapsedCategories);
                }
                const targetId = slug === 'all' ? 'quick-order' : 'category-row-' + slug;
                const el = document.getElementById(targetId);
                if (el) {
                    const yOffset = -90;
                    const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                }
            },

            toggleCategory(slug) {
                if (this.collapsedCategories.has(slug)) {
                    this.collapsedCategories.delete(slug);
                } else {
                    this.collapsedCategories.add(slug);
                }
                // Force Alpine reactivity on Set mutation
                this.collapsedCategories = new Set(this.collapsedCategories);
            },

            shouldShowCategory(slug) {
                return true;
            },

            shouldShowProduct(categorySlug, name) {
                // Hide if category is collapsed by clicking the accordion header
                if (this.collapsedCategories.has(categorySlug)) {
                    return false;
                }

                if (this.searchQuery.trim() !== '') {
                    const query = this.searchQuery.toLowerCase();
                    return name.toLowerCase().includes(query);
                }

                return true;
            },

            get filteredProductsCount() {
                let count = 0;
                const rows = document.querySelectorAll('tbody tr[x-show^="shouldShowProduct"]');
                rows.forEach(row => {
                    if (row.style.display !== 'none') {
                        count++;
                    }
                });
                return count;
            },

            openCheckoutDrawer() {
                if (!this.enableMinOrder || this.totalNet >= {{ $settings['min_order_value'] }}) {
                    this.checkoutOpen = true;
                }
            },

            closeCheckoutDrawer() {
                this.checkoutOpen = false;
            },

            submitOrder() {
                const cleanPhone = (this.form.phone || '').replace(/[^0-9]/g, '');
                if (cleanPhone.length !== 10) {
                    Swal.fire({
                        title: 'Validation Error',
                        text: 'Mobile number must be exactly 10 digits.',
                        icon: 'warning',
                        confirmButtonColor: '#e51d1d'
                    });
                    return;
                }

                if (this.form.whatsapp && this.form.whatsapp.trim() !== '') {
                    const cleanWhatsapp = this.form.whatsapp.replace(/[^0-9]/g, '');
                    if (cleanWhatsapp.length !== 10) {
                        Swal.fire({
                            title: 'Validation Error',
                            text: 'WhatsApp number must be exactly 10 digits.',
                            icon: 'warning',
                            confirmButtonColor: '#e51d1d'
                        });
                        return;
                    }
                }

                this.submitting = true;
                
                const orderItems = [];
                for (const id in this.cart) {
                    orderItems.push({
                        id: id,
                        qty: this.cart[id].qty
                    });
                }

                const payload = {
                    ...this.form,
                    promo_code: this.appliedPromoCode,
                    items: orderItems
                };

                fetch('/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    this.submitting = false;
                    if (data.success) {
                        this.cart = {};
                        localStorage.removeItem('athi_cart');
                        this.calculateCart();
                        this.checkoutOpen = false;
                        window.location.href = data.redirect;
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.error || 'Failed to place order.',
                            icon: 'error',
                            confirmButtonColor: '#e51d1d'
                        });
                    }
                })
                .catch(error => {
                    this.submitting = false;
                    Swal.fire({
                        title: 'Server Error!',
                        text: 'Unable to connect to the server. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#e51d1d'
                    });
                });
            }
        };
    }
</script>
@endsection
