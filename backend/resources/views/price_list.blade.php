@extends('layouts.app')

@section('title', 'Wholesale Price List | ' . App\Models\Setting::get('store_name', 'Cracker Demo'))

@section('content')
<div class="container mx-auto max-w-4xl px-4 py-8 select-none">
    
    <!-- Header control bar (Hidden during print) -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-white border border-slate-200 rounded-2xl p-5 shadow-sm mb-8 gap-4 print:hidden">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-list-check text-crimson-600"></i> Wholesale Price List
            </h2>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1">Official Price Registry - Flat {{ $settings['discount_percent'] }}% Discount Applied</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <!-- Download PDF button -->
            <button onclick="downloadPDF()" class="w-full md:w-auto flex items-center justify-center gap-2 bg-crimson-50 border border-crimson-200 hover:bg-crimson-100 text-crimson-750 font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
                <i class="fa-solid fa-file-pdf text-crimson-650"></i> Download PDF
            </button>
            <!-- Print button -->
            <button onclick="window.print()" class="w-full md:w-auto flex items-center justify-center gap-2 bg-slate-100 border border-slate-200 hover:border-slate-350 text-slate-700 font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
                <i class="fa-solid fa-print text-slate-500"></i> Print List
            </button>
            <!-- Order Now button -->
            <a href="/" class="w-full md:w-auto flex items-center justify-center gap-2 bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white font-extrabold px-6 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow transition-all hover:scale-105 active:scale-95 text-center">
                <i class="fa-solid fa-basket-shopping-simple"></i> Order Online Now
            </a>
        </div>
    </div>

    <!-- Official Printable Invoice/Price Registry Document -->
    <div id="price-list-document" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-md relative overflow-hidden print:border-0 print:shadow-none print:p-0">
        
        <!-- Header Branding (Visible on Print and Screen) -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 pb-6 mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-crimson-700 uppercase leading-none">
                    {{ App\Models\Setting::get('store_name', 'Cracker Demo') }}
                </h1>
                <p class="text-[10px] text-slate-500 tracking-widest uppercase font-bold mt-1.5">Premium Sivakasi Fireworks Wholesale Price List</p>
            </div>
            
            <div class="text-xs text-slate-500 font-semibold space-y-1">
                <div><i class="fa-solid fa-phone text-crimson-600 mr-1.5 text-[10px]"></i><strong>Phone:</strong> {{ $settings['store_phone'] }}</div>
                <div><i class="fa-solid fa-envelope text-crimson-600 mr-1.5 text-[10px]"></i><strong>Email:</strong> {{ $settings['store_email'] }}</div>
                <div><i class="fa-solid fa-map-marker-alt text-crimson-600 mr-1.5 text-[10px]"></i><strong>Address:</strong> {{ $settings['store_address'] }}</div>
            </div>
        </div>

        <!-- Table Registry Container -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-inner print:border-0">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-650 font-extrabold uppercase tracking-wider text-[9px]">
                        <th class="py-3 px-3 sm:px-4 w-12 text-center">S.No</th>
                        <th class="py-3 px-4">Product Details</th>
                        <th class="py-3 px-4 w-32 text-center">Pack / Box size</th>
                        <th class="py-3 px-4 w-24 text-right">MRP (₹)</th>
                        <th class="py-3 px-4 w-24 text-right">Discount ({{ $settings['discount_percent'] }}% Off)</th>
                        <th class="py-3 px-4 w-28 text-right pr-6 font-bold">Net Price (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 font-semibold text-slate-600">
                    
                    @php $sno = 1; @endphp
                    @foreach($categories as $category)
                        <!-- Category Header Row -->
                        <tr class="bg-slate-100/60 font-black text-slate-700 text-[10px] uppercase tracking-wider border-y border-slate-200">
                            <td colspan="6" class="py-3.5 px-4">
                                <i class="fa-solid fa-circle-chevron-right text-crimson-600 mr-2 text-[9px]"></i>{{ $category->name }}
                            </td>
                        </tr>

                        <!-- Product Rows -->
                        @foreach($category->products as $product)
                        <tr class="hover:bg-slate-50/40 transition-colors">
                            <td class="py-3 px-3 sm:px-4 text-center font-mono font-bold text-slate-400">{{ $sno++ }}</td>
                            <td class="py-3 px-4 font-bold text-slate-800">{{ $product->name }}</td>
                            <td class="py-3 px-4 text-center font-mono text-slate-500 font-bold">{{ $product->pack_size }}</td>
                            <td class="py-3 px-4 text-right line-through text-slate-400 font-mono">₹{{ number_format($product->mrp, 2) }}</td>
                            <td class="py-3 px-4 text-right text-emerald-600 font-mono">₹{{ number_format($product->mrp * ($settings['discount_percent'] / 100), 2) }}</td>
                            <td class="py-3 px-4 text-right pr-6 font-extrabold text-crimson-600 font-mono">₹{{ number_format($product->selling_price, 2) }}</td>
                        </tr>
                        @endforeach
                    @endforeach

                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection

@section('scripts')
<!-- Client-Side Pixel-Perfect PDF Compiler -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        // Display premium SweetAlert compilator loader
        Swal.fire({
            title: 'Generating PDF...',
            text: 'Compiling wholesale registry sheets, please wait...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const element = document.getElementById('price-list-document');
        const storeName = "{{ App\Models\Setting::get('store_name', 'Cracker_Demo') }}";
        const cleanStoreName = storeName.replace(/[^a-z0-9]/gi, '_').toLowerCase();
        
        const opt = {
            margin:       [0.4, 0.3, 0.4, 0.3],
            filename:     cleanStoreName + '_wholesale_price_list.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
        };

        // Render & trigger direct local download
        html2pdf().set(opt).from(element).save().then(() => {
            Swal.fire({
                title: 'Compilation Complete!',
                text: 'Wholesale Price List downloaded successfully.',
                icon: 'success',
                confirmButtonColor: '#e51d1d',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(err => {
            console.error('PDF Compilation Error:', err);
            Swal.fire({
                title: 'Generation Failed!',
                text: 'Unable to compile PDF client-side. Please use the Print List option.',
                icon: 'error',
                confirmButtonColor: '#e51d1d'
            });
        });
    }
</script>
@endsection
