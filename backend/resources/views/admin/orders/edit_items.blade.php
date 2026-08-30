@extends('layouts.admin')

@section('title', 'Edit Order Items | Admin Console')

@section('content')

@php
    $enableTaxDelivery = App\Models\Setting::get('enable_tax_delivery', 'no') === 'yes';
    $taxPercent = (float) App\Models\Setting::get('tax_percent', 18);
    $deliveryCharge = (float) App\Models\Setting::get('delivery_charge', 150);
@endphp

<div x-data="editOrderData()" x-init="initCart()" class="space-y-8 text-slate-800">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 select-none">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-slate-400 hover:text-slate-650">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <span>Edit Order Items</span>
            </h2>
            <p class="text-[10px] text-slate-450 uppercase tracking-widest leading-none font-bold mt-0.5">
                Editing items for: <strong class="text-slate-700 font-mono select-all">{{ $order->order_number }}</strong>
                &mdash; <span class="text-slate-600">{{ $order->name }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <!-- Always-visible Save button -->
            <button type="button" @click="submitSave()"
                class="bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white px-5 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 shadow-sm transition-all active:scale-95 uppercase tracking-wider">
                <i class="fa-solid fa-floppy-disk"></i> Save Order
            </button>
            <a href="{{ route('admin.orders.show', $order->id) }}" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all active:scale-95">
                <i class="fa-solid fa-xmark text-crimson-600"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Storefront-Style Product Spreadsheet Area -->
    <div class="flex flex-col lg:flex-row gap-8 items-start">

        <!-- Left: Category Sidebar Filter (hidden on mobile, sticky on desktop) -->
        <aside class="hidden lg:block lg:w-56 flex-shrink-0 lg:sticky lg:top-24 space-y-3 select-none">
            <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest border-b border-slate-150 pb-2.5 mb-3 flex justify-between items-center">
                    <span>Categories</span>
                    <i class="fa-solid fa-filter text-slate-400 text-xs"></i>
                </h3>
                <div class="flex flex-col gap-1">
                    <button @click="activeCategory = 'all'"
                        :class="activeCategory === 'all' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'text-slate-650 hover:bg-slate-100'"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs flex items-center gap-2 transition-all duration-200">
                        <i class="fa-solid fa-boxes-stacked text-[11px] opacity-80"></i> All Products
                    </button>
                    @foreach($categories as $category)
                        @if($category->products->count() > 0)
                        <button @click="activeCategory = '{{ $category->slug }}'"
                            :class="activeCategory === '{{ $category->slug }}' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'text-slate-650 hover:bg-slate-100'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs flex items-center gap-2 transition-all duration-200">
                            <i class="fa-solid fa-fire-flame-curved text-[11px] opacity-80"></i>
                            {{ $category->name }}
                        </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </aside>

        <!-- Right: Product Spreadsheet Table -->
        <div class="flex-grow w-full space-y-5">

            <!-- Mobile horizontal category pills -->
            <div class="lg:hidden flex gap-1.5 overflow-x-auto pb-1 scrollbar-none select-none">
                <button @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'bg-slate-50 text-slate-650 border border-slate-200'"
                    class="px-3.5 py-1.5 rounded-full text-[10px] uppercase tracking-wider whitespace-nowrap transition-all duration-200">
                    All
                </button>
                @foreach($categories as $category)
                    @if($category->products->count() > 0)
                    <button @click="activeCategory = '{{ $category->slug }}'"
                        :class="activeCategory === '{{ $category->slug }}' ? 'bg-crimson-600 text-white font-extrabold shadow' : 'bg-slate-50 text-slate-650 border border-slate-200'"
                        class="px-3.5 py-1.5 rounded-full text-[10px] uppercase tracking-wider whitespace-nowrap transition-all duration-200">
                        {{ $category->name }}
                    </button>
                    @endif
                @endforeach
            </div>

            <!-- Search Bar -->
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between bg-white border border-slate-200 p-4 rounded-2xl shadow-sm">
                <div class="relative w-full sm:max-w-md group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 group-focus-within:text-crimson-500 transition-colors">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input x-model="searchQuery" type="text" placeholder="Search firecrackers by name..."
                        class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl py-2.5 pl-10 pr-4 text-xs text-slate-700 placeholder-slate-400 focus:ring-1 focus:ring-crimson-300 focus:outline-none transition-all">
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold select-none">
                    <span>Showing <strong class="text-crimson-600" x-text="visibleProductCount">0</strong> products</span>
                </div>
            </div>

            <!-- Product Table (mirrors storefront) -->
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px] select-none">
                                <th class="py-4 px-3 sm:px-4">Cracker Details</th>
                                <th class="hidden sm:table-cell py-4 px-4 w-28 text-center">Unit / Box</th>
                                <th class="py-4 px-3 sm:px-4 w-24 sm:w-36 text-right">Price (₹)</th>
                                <th class="py-4 px-3 sm:px-4 w-28 sm:w-40 text-center">Qty</th>
                                <th class="hidden md:table-cell py-4 px-4 w-28 text-right pr-6">Total (₹)</th>
                                <th class="py-4 px-3 sm:px-4 w-28 text-right pr-4">Sub Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150">

                            @foreach($categories as $category)
                                @if($category->products->count() > 0)
                                <!-- Category Row -->
                                <tr x-show="shouldShowCategory('{{ $category->slug }}')"
                                    @click="toggleCategory('{{ $category->slug }}')"
                                    class="bg-slate-50 font-bold text-slate-700 border-b border-slate-200/80 select-none cursor-pointer hover:bg-slate-100 transition-colors">
                                    <td colspan="6" class="py-3 px-3 sm:px-4">
                                        <div class="flex items-center justify-between text-crimson-650 tracking-wider">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-fire text-[10px] text-crimson-500"></i>
                                                <span>{{ $category->name }}</span>
                                            </div>
                                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"
                                               :class="collapsedCategories.has('{{ $category->slug }}') ? '-rotate-90' : 'rotate-0'"></i>
                                        </div>
                                    </td>
                                </tr>

                                @foreach($category->products as $product)
                                <!-- Product Row -->
                                <tr x-show="shouldShowProduct('{{ $category->slug }}', '{{ addslashes($product->name) }}')"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="hover:bg-slate-50/50 transition-colors">

                                    <!-- Name / Thumbnail -->
                                    <td class="py-3.5 px-3 sm:px-4">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <div class="hidden sm:flex w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 items-center justify-center text-slate-400 overflow-hidden flex-shrink-0">
                                                @if($product->image)
                                                    <img src="/{{ $product->image }}" alt="{{ $product->name }}" class="object-cover w-full h-full">
                                                @else
                                                    <i class="fa-solid fa-sparkles text-sm text-crimson-450/40"></i>
                                                @endif
                                            </div>
                                            <div class="space-y-1">
                                                <h4 class="font-extrabold text-slate-800 text-xs leading-normal">{{ $product->name }}</h4>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="text-[8px] sm:text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $category->name }}</span>
                                                    <span class="sm:hidden text-[9px] font-bold text-slate-500 bg-slate-100 border border-slate-150 px-1.5 py-0.5 rounded-md font-mono">{{ $product->pack_size }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Pack Size -->
                                    <td class="hidden sm:table-cell py-3.5 px-4 text-center text-slate-650 font-bold font-mono">
                                        {{ $product->pack_size }}
                                    </td>

                                    <!-- MRP vs Selling Price -->
                                    <td class="py-3.5 px-3 sm:px-4 text-right">
                                        <div class="text-slate-400 text-[10px] line-through">₹{{ number_format($product->mrp, 2) }}</div>
                                        <div class="text-crimson-650 font-extrabold">₹{{ number_format($product->selling_price, 2) }}</div>
                                    </td>

                                    <!-- Qty Controls -->
                                    <td class="py-3.5 px-3 sm:px-4 text-center">
                                        <div class="inline-flex items-center bg-slate-100 border border-slate-200 rounded-lg p-0.5 sm:p-1 select-none">
                                            <button type="button"
                                                @click="decreaseQty({{ $product->id }})"
                                                class="w-6 h-6 sm:w-7 sm:h-7 text-slate-650 hover:text-slate-900 hover:bg-white rounded flex items-center justify-center font-bold text-xs transition-colors shadow-sm">
                                                <i class="fa-solid fa-minus text-[8px] sm:text-[9px]"></i>
                                            </button>
                                            <input type="number"
                                                @input="updateQty({{ $product->id }}, $event.target.value, {{ $product->mrp }}, {{ $product->selling_price }})"
                                                :value="cart[{{ $product->id }}]?.qty || 0"
                                                min="0"
                                                class="w-8 sm:w-12 text-center bg-transparent border-0 text-xs font-black text-slate-800 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            <button type="button"
                                                @click="increaseQty({{ $product->id }}, {{ $product->mrp }}, {{ $product->selling_price }})"
                                                class="w-6 h-6 sm:w-7 sm:h-7 text-slate-650 hover:text-slate-900 hover:bg-white rounded flex items-center justify-center font-bold text-xs transition-colors shadow-sm">
                                                <i class="fa-solid fa-plus text-[8px] sm:text-[9px]"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Row total at MRP (hidden on mobile) -->
                                    <td class="hidden md:table-cell py-3.5 px-4 text-right font-extrabold text-slate-800 pr-6">
                                        ₹<span x-text="fmt((cart[{{ $product->id }}]?.qty || 0) * {{ $product->mrp }})">0.00</span>
                                    </td>

                                    <!-- Row total at selling price (always visible) -->
                                    <td class="py-3.5 px-3 sm:px-4 text-right pr-4">
                                        <span class="font-extrabold text-crimson-600"
                                            x-text="(cart[{{ $product->id }}]?.qty || 0) > 0 ? '₹' + fmt((cart[{{ $product->id }}]?.qty || 0) * {{ $product->selling_price }}) : '—'">—</span>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Sticky Footer Cart Tally + Submit (mirrors storefront cart bar) -->
<div x-show="totalQty > 0"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-slate-200/80 shadow-2xl py-3 px-4 select-none"
    style="display: none;">

    <div class="max-w-screen-xl mx-auto flex flex-col lg:flex-row gap-3 items-center justify-between">

        <!-- Totals summary -->
        <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-5 gap-y-2 text-xs text-slate-500 w-full lg:w-auto font-medium">

            <div class="flex items-center gap-1.5 text-slate-800">
                <span class="text-[9px] text-slate-400 uppercase tracking-wider font-extrabold">Total Items:</span>
                <strong class="text-sm font-black text-crimson-600" x-text="totalQty">0</strong>
                <span class="text-slate-300">/</span>
                <strong class="text-slate-700 font-bold" x-text="uniqueCount">0</strong>
                <span class="text-[9px] text-slate-400 uppercase font-extrabold">Products</span>
            </div>

            <span class="hidden sm:inline text-slate-350">|</span>

            <div class="flex items-center gap-1.5">
                <span class="text-[9px] text-slate-400 uppercase tracking-wider font-extrabold">MRP:</span>
                <span class="line-through font-semibold text-slate-450">₹<span x-text="fmt(totalMrp)">0.00</span></span>
            </div>

            <span class="hidden sm:inline text-slate-350">|</span>

            <div class="text-crimson-750 bg-crimson-50 border border-crimson-100 px-2.5 py-0.5 rounded-lg shadow-sm flex items-center gap-1.5">
                <span class="text-[9px] text-crimson-500 uppercase tracking-wider font-extrabold">Savings:</span>
                <strong class="font-extrabold text-xs">₹<span x-text="fmt(totalMrp - totalNet)">0.00</span></strong>
            </div>

            <span class="hidden sm:inline text-slate-350">|</span>

            <div class="flex items-center gap-1.5 text-slate-800">
                <span class="text-[9px] text-slate-400 uppercase tracking-wider font-extrabold">Net Payable:</span>
                <strong class="text-base font-black text-crimson-600">₹<span x-text="fmt(totalNet)">0.00</span></strong>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2.5 w-full lg:w-auto items-center justify-center lg:justify-end">

            <!-- Clear All -->
            <button type="button" @click="clearCart()"
                class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition-all duration-300 flex items-center gap-1.5 bg-slate-100 border border-slate-200 text-slate-500 hover:bg-slate-200 hover:text-slate-700 shadow-sm">
                <i class="fa-solid fa-trash text-crimson-600"></i>
                Clear All
            </button>

            <!-- Save Order -->
            <form :action="`/admin/orders/{{ $order->id }}/edit-items`" method="POST" id="saveOrderForm">
                @csrf
                @method('PUT')
                <template x-for="(item, id) in cart" :key="id">
                    <input type="hidden" :name="`items[${id}]`" :value="item.qty">
                </template>
                <button type="submit"
                    class="px-6 py-2.5 rounded-full text-xs font-extrabold uppercase tracking-wider transition-all duration-300 flex items-center gap-2 bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white shadow-md hover:scale-105 active:scale-95">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Save Order Items</span>
                </button>
            </form>

        </div>
    </div>
</div>

<!-- Bottom padding so items aren't hidden behind sticky footer -->
<div class="h-24" x-show="totalQty > 0" style="display: none;"></div>

@endsection

@section('scripts')
<script>
    function editOrderData() {
        return {
            searchQuery: '',
            activeCategory: 'all',
            collapsedCategories: new Set(),

            cart: {},

            // Pre-populate from existing order items
            initCart() {
                const existing = @json($orderItems);
                for (const [productId, qty] of Object.entries(existing)) {
                    if (qty > 0) {
                        const p = this.products[productId];
                        if (p) {
                            this.cart[productId] = {
                                qty: Number(qty),
                                mrp: p.mrp,
                                selling_price: p.selling_price,
                            };
                        }
                    }
                }
            },

            // Full product catalogue data from Blade
            products: {
                @foreach($categories as $category)
                    @foreach($category->products as $product)
                        {{ $product->id }}: {
                            mrp: {{ $product->mrp }},
                            selling_price: {{ $product->selling_price }},
                            name: '{{ addslashes($product->name) }}',
                            category: '{{ addslashes($category->name) }}',
                            categorySlug: '{{ $category->slug }}'
                        },
                    @endforeach
                @endforeach
            },

            increaseQty(id, mrp, selling_price) {
                if (!this.cart[id]) {
                    this.cart[id] = { qty: 0, mrp, selling_price };
                }
                this.cart[id].qty++;
            },

            decreaseQty(id) {
                if (!this.cart[id] || this.cart[id].qty <= 0) return;
                this.cart[id].qty--;
                if (this.cart[id].qty === 0) {
                    delete this.cart[id];
                }
            },

            updateQty(id, val, mrp, selling_price) {
                const qty = Math.max(0, parseInt(val) || 0);
                if (qty === 0) {
                    delete this.cart[id];
                } else {
                    this.cart[id] = { qty, mrp, selling_price };
                }
            },

            clearCart() {
                this.cart = {};
            },

            shouldShowCategory(slug) {
                if (this.activeCategory !== 'all' && this.activeCategory !== slug) return false;
                // If searching, show category only if it has a matching product
                if (!this.searchQuery.trim()) return true;
                return Object.values(this.products).some(p =>
                    p.categorySlug === slug &&
                    p.name.toLowerCase().includes(this.searchQuery.trim().toLowerCase())
                );
            },

            shouldShowProduct(categorySlug, name) {
                if (!this.shouldShowCategory(categorySlug)) return false;
                if (this.collapsedCategories.has(categorySlug)) return false;
                if (!this.searchQuery.trim()) return true;
                return name.toLowerCase().includes(this.searchQuery.trim().toLowerCase());
            },

            toggleCategory(slug) {
                if (this.collapsedCategories.has(slug)) {
                    this.collapsedCategories.delete(slug);
                } else {
                    this.collapsedCategories.add(slug);
                }
                this.collapsedCategories = new Set(this.collapsedCategories);
            },

            get visibleProductCount() {
                return Object.values(this.products).filter(p =>
                    this.shouldShowProduct(p.categorySlug, p.name)
                ).length;
            },

            get totalQty() {
                return Object.values(this.cart).reduce((s, i) => s + i.qty, 0);
            },

            get uniqueCount() {
                return Object.keys(this.cart).length;
            },

            get totalMrp() {
                return Object.entries(this.cart).reduce((s, [id, i]) => {
                    const p = this.products[id];
                    return s + (p ? p.mrp * i.qty : 0);
                }, 0);
            },

            get totalNet() {
                return Object.entries(this.cart).reduce((s, [id, i]) => {
                    const p = this.products[id];
                    return s + (p ? p.selling_price * i.qty : 0);
                }, 0);
            },

            fmt(n) {
                return Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },

            submitSave() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/orders/{{ $order->id }}/edit-items';

                // CSRF token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                // Method spoofing for PUT
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'PUT';
                form.appendChild(method);

                // Add all cart items as hidden inputs
                for (const [id, item] of Object.entries(this.cart)) {
                    if (item.qty > 0) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${id}]`;
                        input.value = item.qty;
                        form.appendChild(input);
                    }
                }

                document.body.appendChild(form);
                form.submit();
            }
        };
    }
</script>
@endsection
