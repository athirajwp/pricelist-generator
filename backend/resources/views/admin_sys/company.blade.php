@extends('layouts.admin_sys')

@php
    $theme = App\Models\Setting::get('admin_theme', 'gold');
    
    // Set theme classes for local rendering scope
    $themeClasses = [
        'gold' => [
            'accent' => 'gold-500',
            'accent_hover' => 'gold-650',
            'thead' => 'bg-gold-500 text-slate-950 shadow-sm shadow-gold-500/10'
        ],
        'blue' => [
            'accent' => 'blue-600',
            'accent_hover' => 'blue-750',
            'thead' => 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
        ],
        'crimson' => [
            'accent' => 'crimson-600',
            'accent_hover' => 'crimson-750',
            'thead' => 'bg-crimson-600 text-white shadow-md shadow-crimson-500/20'
        ],
        'emerald' => [
            'accent' => 'emerald-600',
            'accent_hover' => 'emerald-750',
            'thead' => 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20'
        ]
    ];
    
    $currentTheme = $themeClasses[$theme] ?? $themeClasses['gold'];
@endphp

@section('title', 'Website Overview | Super Admin Console')

@section('content')
<div x-data="companyManagement()" class="space-y-6 select-none">
    
    <!-- 1. Header with Website Overview Title & Add Button -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 select-none">
        <div>
            <h2 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight leading-none">Website Overview</h2>
        </div>
        <button @click="openAddModal()" class="bg-{{ $currentTheme['accent'] }} hover:bg-{{ $currentTheme['accent_hover'] }} text-{{ $theme === 'gold' ? 'slate-950' : 'white' }} font-extrabold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition-all shadow shadow-{{ $currentTheme['accent'] }}/20 flex items-center gap-1.5 active:scale-95">
            <i class="fa-solid fa-plus text-sm"></i>
            <span>Add company</span>
        </button>
    </div>

    <!-- 2. Interactive Filter & Table Card -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6">
        
        <!-- Controls row exactly matching reference visual details -->
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <span>Show</span>
                <select x-model="entriesLimit" class="bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-{{ $currentTheme['accent'] }} transition-colors text-slate-850 font-bold">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
                <span>entries</span>
            </div>
            
            <!-- Real-time live Search bar -->
            <div class="relative w-full md:max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input x-model="searchQuery" type="text" placeholder="Search by Name, Status etc..." class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl py-2.5 pl-10 pr-4 text-xs text-slate-750 placeholder-slate-400 focus:outline-none transition-all font-semibold shadow-inner">
            </div>
        </div>

        <!-- 3. Dynamic Company Domains Grid Table -->
        <div class="border border-slate-150 rounded-2xl overflow-hidden shadow-inner">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="{{ $currentTheme['thead'] }} font-black uppercase tracking-wider text-[10px] select-none">
                            <th class="py-3.5 px-4 w-16 text-center border-r border-white/10">S.No</th>
                            <th class="py-3.5 px-4 w-64 border-r border-white/10">Company Details</th>
                            <th class="py-3.5 px-4 w-48 border-r border-white/10">Website</th>
                            <th class="py-3.5 px-4 w-72 border-r border-white/10">Address</th>
                            <th class="py-3.5 px-4 w-32 border-r border-white/10">GST Number</th>
                            <th class="py-3.5 px-4 w-32 border-r border-white/10">Pan Number</th>
                            <th class="py-3.5 px-4 w-32 border-r border-white/10">MSME Number</th>
                            <th class="py-3.5 px-4 w-28 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-semibold">
                        
                        <template x-for="(company, index) in filteredCompanies" :key="company.id">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'" class="hover:bg-blue-50/20 transition-colors">
                                <!-- Serial No -->
                                <td class="py-3.5 px-4 text-center text-slate-500 font-mono border-r border-slate-150" x-text="index + 1"></td>
                                
                                <!-- Details -->
                                <td class="py-3.5 px-4 border-r border-slate-150 space-y-1">
                                    <div class="text-slate-850 font-black" x-text="'Name : ' + company.name"></div>
                                    <div class="text-[10px] text-slate-500">
                                        Email : 
                                        <template x-if="company.email_1">
                                            <a :href="'mailto:' + company.email_1" class="text-blue-600 hover:underline" x-text="company.email_1"></a>
                                        </template>
                                        <template x-if="!company.email_1">
                                            <span>—</span>
                                        </template>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono" x-text="'Mobile Number : ' + (company.contact_1 || '—')"></div>
                                </td>
                                
                                <!-- Website Domain -->
                                <td class="py-3.5 px-4 border-r border-slate-150 font-mono text-{{ $currentTheme['accent'] }} hover:underline">
                                    <a :href="'http://' + company.website" target="_blank" x-text="company.website || '—'"></a>
                                </td>
                                
                                <!-- Address -->
                                <td class="py-3.5 px-4 border-r border-slate-150 text-slate-550 leading-normal text-[11px]" x-text="company.address_1 || '—'"></td>
                                
                                <!-- GST -->
                                <td class="py-3.5 px-4 border-r border-slate-150 font-mono text-slate-600" x-text="company.gst_number || '—'"></td>
                                
                                <!-- PAN -->
                                <td class="py-3.5 px-4 border-r border-slate-150 font-mono text-slate-600" x-text="company.pan_number || '—'"></td>
                                
                                <!-- MSME -->
                                <td class="py-3.5 px-4 border-r border-slate-150 font-mono text-slate-600" x-text="company.msme_number || '—'"></td>
                                
                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center space-y-2 select-none">
                                    <!-- Status Pill -->
                                    <button @click="toggleStatus(company)" :class="company.status === 'active' ? 'bg-emerald-600 shadow-sm shadow-emerald-500/20 hover:bg-emerald-700' : 'bg-slate-400 hover:bg-slate-500'" class="inline-block text-[8px] uppercase tracking-wider text-white font-extrabold px-2.5 py-1 rounded-md transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer focus:outline-none" x-text="company.status" title="Click to toggle status"></button>
                                    
                                    <div class="flex items-center justify-center gap-1.5 pt-1">
                                        <a :href="'/admin/login?company=' + company.code" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white p-1.5 rounded-lg shadow-sm hover:scale-105 active:scale-95 transition-all flex items-center justify-center" title="Login to Admin Panel">
                                            <i class="fa-solid fa-user-shield text-[10px]"></i>
                                        </a>
                                        
                                        <button @click="openEditModal(company)" class="bg-amber-500 hover:bg-amber-600 text-white p-1.5 rounded-lg shadow-sm hover:scale-105 active:scale-95 transition-all" title="Edit Company Details">
                                            <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                        </button>
                                        
                                        <button @click="confirmDelete(company.id)" class="bg-rose-600 hover:bg-rose-700 text-white p-1.5 rounded-lg shadow-sm hover:scale-105 active:scale-95 transition-all" title="Delete Domain Company">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty state fallback -->
                        <template x-if="filteredCompanies.length === 0">
                            <tr class="bg-white">
                                <td colspan="8" class="py-8 px-4 text-center text-slate-450 font-semibold uppercase tracking-wider">
                                    <i class="fa-solid fa-magnifying-glass text-lg text-slate-350 block mb-2"></i>
                                    <span>No domain company records matching criteria</span>
                                </td>
                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ================= UNIFIED ADD / EDIT DIALOG MODAL ================= -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop overlay -->
            <div @click="modalOpen = false" class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <!-- Modal Content box - max width extra large for extensive fields -->
            <div x-show="modalOpen" x-transition class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white border border-slate-200 rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full relative z-10">
                
                <!-- Modal Header -->
                <div class="{{ $currentTheme['thead'] }} px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-wider" x-text="isEditMode ? 'Edit Company Domain' : 'Add Company'"></h3>
                    <button @click="modalOpen = false" class="hover:opacity-80 p-1.5 rounded-lg transition-opacity">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Form -->
                <form id="company-form" action="/admin_sys/company" :action="isEditMode ? '/admin_sys/company/' + form.id + '/update' : '/admin_sys/company'" method="POST" enctype="multipart/form-data" class="p-6 space-y-7 text-xs font-semibold max-h-[80vh] overflow-y-auto">
                    @csrf
                    
                    <!-- 1. MAIN PROPERTIES SECTION -->
                    <div class="grid grid-cols-1 lg:grid-cols-10 gap-4 mt-2">
                        <div class="relative lg:col-span-2">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Company Code<span class="text-crimson-600">*</span></label>
                            <input type="text" name="code" x-model="form.code" required class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none uppercase font-bold">
                        </div>
                        <div class="relative lg:col-span-4">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Company Name<span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" x-model="form.name" required class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none">
                        </div>
                        <div class="relative lg:col-span-4">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Website (165.22.208.87)<span class="text-crimson-600">*</span></label>
                            <input type="text" name="website" x-model="form.website" required class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">GST Number</label>
                            <input type="text" name="gst_number" x-model="form.gst_number" class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none font-mono uppercase">
                        </div>
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">PAN Number</label>
                            <input type="text" name="pan_number" x-model="form.pan_number" class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none font-mono uppercase">
                        </div>
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">MSME Number</label>
                            <input type="text" name="msme_number" x-model="form.msme_number" class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none font-mono">
                        </div>
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Theme<span class="text-crimson-600">*</span></label>
                            <select name="theme" x-model="form.theme" required class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none font-bold">
                                <option value="Theme_1">Crimson Red & Gold (Theme 1)</option>
                                <option value="Theme_2">Indigo Blue & Amber (Theme 2)</option>
                                <option value="Theme_3">Emerald Green & Orange (Theme 3)</option>
                                <option value="Theme_4">Purple & Yellow (Theme 4)</option>
                                <option value="Theme_5">Rose Pink & Teal (Theme 5)</option>
                                <option value="Theme_6">Cyan & Red-Orange (Theme 6)</option>
                                <option value="Theme_7">Forest Green & Gold (Theme 7)</option>
                                <option value="Theme_8">Teal & Rose (Theme 8)</option>
                                <option value="Theme_9">Charcoal & Amber (Theme 9)</option>
                                <option value="Theme_10">Slate Blue & Coral (Theme 10)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Type<span class="text-crimson-600">*</span></label>
                            <select name="type" x-model="form.type" required class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none">
                                <option value="Premium">Premium</option>
                                <option value="Standard">Standard</option>
                            </select>
                        </div>
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Status<span class="text-crimson-600">*</span></label>
                            <select name="status" x-model="form.status" required class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Website Tagline</label>
                            <input type="text" name="tagline" x-model="form.tagline" class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-800 focus:outline-none">
                        </div>
                        <div class="relative">
                            <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Logo Icon (Fallback)</label>
                            <select name="logo_icon" x-model="form.logo_icon" class="w-full bg-white border border-slate-350 rounded-lg px-3.5 py-3 text-xs text-slate-850 focus:outline-none font-bold">
                                <option value="fa-solid fa-fire-burner">Fire Burner (Default)</option>
                                <option value="fa-solid fa-fire">Fire</option>
                                <option value="fa-solid fa-rocket">Rocket</option>
                                <option value="fa-solid fa-burst">Burst</option>
                                <option value="fa-solid fa-bahai">Starburst</option>
                                <option value="fa-solid fa-bomb">Bomb</option>
                                <option value="fa-solid fa-gift">Gift</option>
                                <option value="fa-solid fa-wand-magic-sparkles">Sparkles</option>
                            </select>
                        </div>
                    </div>

                    <!-- 2. CONTACTS SECTION (Border wrapping) -->
                    <div class="border border-slate-200 rounded-3xl p-5 space-y-6 relative">
                        <span class="absolute -top-2.5 left-6 bg-white px-2.5 text-[10px] text-slate-550 font-black uppercase tracking-widest">Contacts</span>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-1">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Contact Number 1<span class="text-crimson-600">*</span></label>
                                <input type="text" name="contact_1" x-model="form.contact_1" required class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Contact Number 2</label>
                                <input type="text" name="contact_2" x-model="form.contact_2" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Contact Number 3</label>
                                <input type="text" name="contact_3" x-model="form.contact_3" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Contact Number 4</label>
                                <input type="text" name="contact_4" x-model="form.contact_4" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Contact Number 5</label>
                                <input type="text" name="contact_5" x-model="form.contact_5" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Email 1</label>
                                <input type="email" name="email_1" x-model="form.email_1" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Email 2</label>
                                <input type="email" name="email_2" x-model="form.email_2" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative lg:col-span-2">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Address 1</label>
                                <input type="text" name="address_1" x-model="form.address_1" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Address 2</label>
                                <input type="text" name="address_2" x-model="form.address_2" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">State</label>
                                <select name="state" x-model="form.state" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-bold">
                                    <option value="Tamilnadu">Tamilnadu</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Karnataka">Karnataka</option>
                                </select>
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">City</label>
                                <select name="city" x-model="form.city" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-bold">
                                    <option value="Virudhunagar">Virudhunagar</option>
                                    <option value="Sivakasi">Sivakasi</option>
                                    <option value="Madurai">Madurai</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Pincode</label>
                                <input type="text" name="pincode" x-model="form.pincode" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative lg:col-span-2">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Location Map</label>
                                <input type="text" name="map_link" x-model="form.map_link" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- 3. BANK DETAILS SECTION (Border wrapping) -->
                    <div class="border border-slate-200 rounded-3xl p-5 space-y-6 relative">
                        <span class="absolute -top-2.5 left-6 bg-white px-2.5 text-[10px] text-slate-550 font-black uppercase tracking-widest">Bank Details</span>
                        
                        @for($b = 1; $b <= 3; $b++)
                        <div class="border border-slate-150 rounded-2xl p-4 bg-slate-50/50 space-y-4">
                            <span class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400 block border-b border-slate-200 pb-1.5 flex justify-between items-center">
                                <span>Bank Details {{ $b }}</span>
                                <i class="fa-solid fa-building-columns text-[10px]"></i>
                            </span>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-center">
                                <!-- QR Display & upload -->
                                <div class="col-span-1 flex items-center gap-3">
                                    <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center text-slate-300 flex-shrink-0 shadow-inner">
                                        <template x-if="form.bank_qr_{{ $b }}">
                                            <img :src="'/' + form.bank_qr_{{ $b }}" class="object-cover w-full h-full">
                                        </template>
                                        <template x-if="!form.bank_qr_{{ $b }}">
                                            <i class="fa-solid fa-qrcode text-lg"></i>
                                        </template>
                                    </div>
                                    <div class="relative w-full">
                                        <label class="absolute -top-2 left-3 bg-white px-1 text-[8px] text-slate-400 font-bold uppercase tracking-wider">Add QR Code {{ $b }}</label>
                                        <input type="file" name="bank_qr_{{ $b }}" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-[9px] text-slate-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <div class="relative">
                                    <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Bank Name {{ $b }}</label>
                                    <input type="text" name="bank_name_{{ $b }}" x-model="form.bank_name_{{ $b }}" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none">
                                </div>
                                <div class="relative">
                                    <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">IFSC Code {{ $b }}</label>
                                    <input type="text" name="bank_ifsc_{{ $b }}" x-model="form.bank_ifsc_{{ $b }}" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none font-mono uppercase">
                                </div>
                                <div class="relative">
                                    <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Account No. {{ $b }}</label>
                                    <input type="text" name="bank_acc_{{ $b }}" x-model="form.bank_acc_{{ $b }}" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none font-mono">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <div class="relative">
                                    <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Branch {{ $b }}</label>
                                    <input type="text" name="bank_branch_{{ $b }}" x-model="form.bank_branch_{{ $b }}" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none">
                                </div>
                                <div class="relative">
                                    <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Account Type {{ $b }}</label>
                                    <input type="text" name="bank_type_{{ $b }}" x-model="form.bank_type_{{ $b }}" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none">
                                </div>
                                <div class="relative">
                                    <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Holder Name {{ $b }}</label>
                                    <input type="text" name="bank_holder_{{ $b }}" x-model="form.bank_holder_{{ $b }}" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none">
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>

                    <!-- 4. PROMO CODES SECTION (Border wrapping) -->
                    <div class="border border-slate-200 rounded-3xl p-5 space-y-4 relative">
                        <span class="absolute -top-2.5 left-6 bg-white px-2.5 text-[10px] text-slate-550 font-black uppercase tracking-widest">Promo Code</span>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 pt-1">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Code Name 1</label>
                                <input type="text" name="promo_code_1" x-model="form.promo_code_1" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none uppercase">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Value 1</label>
                                <input type="text" name="promo_value_1" x-model="form.promo_value_1" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Code Name 2</label>
                                <input type="text" name="promo_code_2" x-model="form.promo_code_2" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none uppercase">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Value 2</label>
                                <input type="text" name="promo_value_2" x-model="form.promo_value_2" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Code Name 3</label>
                                <input type="text" name="promo_code_3" x-model="form.promo_code_3" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none uppercase">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Value 3</label>
                                <input type="text" name="promo_value_3" x-model="form.promo_value_3" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Code Name 4</label>
                                <input type="text" name="promo_code_4" x-model="form.promo_code_4" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none uppercase">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Value 4</label>
                                <input type="text" name="promo_value_4" x-model="form.promo_value_4" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Code Name 5</label>
                                <input type="text" name="promo_code_5" x-model="form.promo_code_5" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none uppercase">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Promo Value 5</label>
                                <input type="text" name="promo_value_5" x-model="form.promo_value_5" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- 5. SMTP DETAILS SECTION (Border wrapping) -->
                    <div class="border border-slate-200 rounded-3xl p-5 space-y-4 relative">
                        <span class="absolute -top-2.5 left-6 bg-white px-2.5 text-[10px] text-slate-550 font-black uppercase tracking-widest">SMTP Details</span>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-1">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">SMTP Host</label>
                                <input type="text" name="smtp_host" x-model="form.smtp_host" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">SMTP Port</label>
                                <input type="text" name="smtp_port" x-model="form.smtp_port" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">SMTP Username</label>
                                <input type="text" name="smtp_user" x-model="form.smtp_user" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">SMTP Password</label>
                                <input type="text" name="smtp_pass" x-model="form.smtp_pass" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">SSL Enabled</label>
                                <select name="smtp_ssl" x-model="form.smtp_ssl" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-bold">
                                    <option value="True">True</option>
                                    <option value="False">False</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 6. SMS CONFIGURATION SECTION (Border wrapping) -->
                    <div class="border border-slate-200 rounded-3xl p-5 space-y-4 relative">
                        <span class="absolute -top-2.5 left-6 bg-white px-2.5 text-[10px] text-slate-550 font-black uppercase tracking-widest">SMS Configuration Details</span>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-1">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Header</label>
                                <input type="text" name="sms_header" x-model="form.sms_header" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">API Key</label>
                                <input type="text" name="sms_apikey" x-model="form.sms_apikey" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">SMS Balance</label>
                                <input type="text" name="sms_balance" x-model="form.sms_balance" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                        </div>
                    </div>

                    <!-- 7. OTHER INFORMATION SECTION (Border wrapping) -->
                    <div class="border border-slate-200 rounded-3xl p-5 space-y-4 relative">
                        <span class="absolute -top-2.5 left-6 bg-white px-2.5 text-[10px] text-slate-550 font-black uppercase tracking-widest">Other Information</span>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-1">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Min. Amt to Purchase</label>
                                <input type="text" name="min_purchase" x-model="form.min_purchase" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-mono">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Tax Calculation</label>
                                <select name="tax_calc" x-model="form.tax_calc" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-bold">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Delivery Amt Calculation</label>
                                <select name="delivery_calc" x-model="form.delivery_calc" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none font-bold">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 8. WEBSITE OVERVIEW SOCIALS & ASSETS (Border wrapping) -->
                    <div class="border border-slate-200 rounded-3xl p-5 space-y-6 relative">
                        <span class="absolute -top-2.5 left-6 bg-white px-2.5 text-[10px] text-slate-550 font-black uppercase tracking-widest">Website Overview</span>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-1">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Facebook Link</label>
                                <input type="text" name="fb_link" x-model="form.fb_link" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Twitter Link</label>
                                <input type="text" name="tw_link" x-model="form.tw_link" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Youtube Link</label>
                                <input type="text" name="yt_link" x-model="form.yt_link" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Whatsapp Link</label>
                                <input type="text" name="wa_link" x-model="form.wa_link" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Instagram Link</label>
                                <input type="text" name="ig_link" x-model="form.ig_link" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Pinterest Link</label>
                                <input type="text" name="pin_link" x-model="form.pin_link" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                            <div class="relative lg:col-span-2">
                                <label class="absolute -top-2 left-3 bg-white px-1.5 text-[9px] text-slate-500 font-bold uppercase tracking-wider">Copyright Text</label>
                                <input type="text" name="copyright_text" x-model="form.copyright_text" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>

                        <!-- Brand asset files uploads exactly matching visual preview slots -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
                            <!-- Company Logo slot -->
                            <div class="border border-slate-200 bg-slate-50/50 p-4 rounded-2xl flex flex-col items-center gap-3">
                                <span class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400 block border-b border-slate-200 pb-1.5 w-full text-center">Company Logo</span>
                                <div class="w-32 h-12 bg-white rounded-lg border border-slate-200 shadow-inner flex items-center justify-center overflow-hidden">
                                    <template x-if="form.logo_path">
                                        <img :src="'/' + form.logo_path" class="object-contain max-h-full max-w-full">
                                    </template>
                                    <template x-if="!form.logo_path">
                                        <div class="flex items-center gap-1.5 text-slate-400">
                                            <i :class="form.logo_icon || 'fa-solid fa-fire-burner'" class="text-sm"></i>
                                            <span class="text-[9px] font-bold uppercase tracking-wider">Icon Logo</span>
                                        </div>
                                    </template>
                                </div>
                                <div class="relative w-full">
                                    <label class="absolute -top-2 left-3 bg-white px-1 text-[8px] text-slate-400 font-bold uppercase">Choose Logo File</label>
                                    <input type="file" name="logo_path" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-[9px] text-slate-500 focus:outline-none">
                                </div>
                            </div>

                            <!-- Favicon slot -->
                            <div class="border border-slate-200 bg-slate-50/50 p-4 rounded-2xl flex flex-col items-center gap-3">
                                <span class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400 block border-b border-slate-200 pb-1.5 w-full text-center">Favicon</span>
                                <div class="w-12 h-12 bg-white rounded-lg border border-slate-200 shadow-inner flex items-center justify-center overflow-hidden">
                                    <template x-if="form.favicon_path">
                                        <img :src="'/' + form.favicon_path" class="object-contain max-h-full max-w-full">
                                    </template>
                                    <template x-if="!form.favicon_path">
                                        <i class="fa-solid fa-gem text-slate-300 text-base"></i>
                                    </template>
                                </div>
                                <div class="relative w-full">
                                    <label class="absolute -top-2 left-3 bg-white px-1 text-[8px] text-slate-400 font-bold uppercase">Choose Favicon File</label>
                                    <input type="file" name="favicon_path" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-[9px] text-slate-500 focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions Bar at bottom exactly matching colors -->
                    <div class="flex justify-end gap-3 pt-5 border-t border-slate-150 select-none">
                        <button type="button" @click="modalOpen = false" class="bg-slate-500 hover:bg-slate-600 text-white font-extrabold px-6 py-2.5 rounded-xl transition-all shadow-sm select-none">Close</button>
                        <button type="button" @click="clearForm()" class="bg-amber-500 hover:bg-amber-600 text-white font-extrabold px-6 py-2.5 rounded-xl transition-all shadow-sm select-none">Clear</button>
                        <button type="submit" class="bg-{{ $currentTheme['accent'] }} hover:bg-{{ $currentTheme['accent_hover'] }} text-{{ $theme === 'gold' ? 'slate-950' : 'white' }} font-extrabold px-6 py-2.5 rounded-xl transition-all shadow shadow-{{ $currentTheme['accent'] }}/10 select-none">
                            <span x-text="isEditMode ? 'Save changes' : 'Add Company'"></span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- ================= DELETE ACTION FORM LOGIC ================= -->
    <form :action="'/admin_sys/company/' + deleteId" method="POST" id="delete-form" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

</div>
@endsection

@section('scripts')
<script>
    function companyManagement() {
        return {
            companies: @json($companies),
            searchQuery: '',
            entriesLimit: 500,
            
            modalOpen: false,
            isEditMode: false,
            
            form: {
                id: '',
                code: '',
                name: '',
                website: '',
                gst_number: '',
                pan_number: '',
                msme_number: '',
                theme: 'Theme_1',
                type: 'Premium',
                status: 'active',
                
                // Contacts
                contact_1: '', contact_2: '', contact_3: '', contact_4: '', contact_5: '',
                email_1: '', email_2: '',
                address_1: '', address_2: '',
                state: 'Tamilnadu', city: 'Virudhunagar', pincode: '', map_link: '',

                // Banks
                bank_qr_1: '', bank_name_1: '', bank_ifsc_1: '', bank_acc_1: '', bank_branch_1: '', bank_type_1: '', bank_holder_1: '',
                bank_qr_2: '', bank_name_2: '', bank_ifsc_2: '', bank_acc_2: '', bank_branch_2: '', bank_type_2: '', bank_holder_2: '',
                bank_qr_3: '', bank_name_3: '', bank_ifsc_3: '', bank_acc_3: '', bank_branch_3: '', bank_type_3: '', bank_holder_3: '',

                // Promos
                promo_code_1: '', promo_value_1: '',
                promo_code_2: '', promo_value_2: '',
                promo_code_3: '', promo_value_3: '',
                promo_code_4: '', promo_value_4: '',
                promo_code_5: '', promo_value_5: '',

                // SMTP
                smtp_host: '', smtp_port: '', smtp_user: '', smtp_pass: '', smtp_ssl: 'True',

                // SMS
                sms_header: '', sms_apikey: '', sms_balance: '',

                // Other
                min_purchase: '3800', tax_calc: 'No', delivery_calc: 'No',

                // Socials Overview
                fb_link: '', tw_link: '', yt_link: '', wa_link: '', ig_link: '', pin_link: '', copyright_text: '',
                logo_path: '', favicon_path: '', logo_icon: 'fa-solid fa-fire-burner'
            },
            
            deleteId: null,

            get filteredCompanies() {
                let list = this.companies;
                
                if (this.searchQuery.trim() !== '') {
                    const query = this.searchQuery.toLowerCase().trim();
                    list = list.filter(c => {
                        return (c.name && c.name.toLowerCase().includes(query)) ||
                               (c.code && c.code.toLowerCase().includes(query)) ||
                               (c.website && c.website.toLowerCase().includes(query)) ||
                               (c.gst_number && c.gst_number.toLowerCase().includes(query)) ||
                               (c.status && c.status.toLowerCase().includes(query)) ||
                               (c.contact_1 && c.contact_1.toLowerCase().includes(query)) ||
                               (c.email_1 && c.email_1.toLowerCase().includes(query)) ||
                               (c.address_1 && c.address_1.toLowerCase().includes(query));
                    });
                }
                
                return list.slice(0, parseInt(this.entriesLimit));
            },

            openAddModal() {
                this.isEditMode = false;
                this.form = {
                    id: '',
                    code: '',
                    name: '',
                    website: '',
                    gst_number: '',
                    pan_number: '',
                    msme_number: '',
                    theme: 'Theme_1',
                    type: 'Premium',
                    status: 'active',
                    tagline: 'Sivakasi Online Booking',
                    
                    // Contacts
                    contact_1: '', contact_2: '', contact_3: '', contact_4: '', contact_5: '',
                    email_1: '', email_2: '',
                    address_1: '', address_2: '',
                    state: 'Tamilnadu', city: 'Virudhunagar', pincode: '', map_link: '',

                    // Banks
                    bank_qr_1: '', bank_name_1: '', bank_ifsc_1: '', bank_acc_1: '', bank_branch_1: '', bank_type_1: '', bank_holder_1: '',
                    bank_qr_2: '', bank_name_2: '', bank_ifsc_2: '', bank_acc_2: '', bank_branch_2: '', bank_type_2: '', bank_holder_2: '',
                    bank_qr_3: '', bank_name_3: '', bank_ifsc_3: '', bank_acc_3: '', bank_branch_3: '', bank_type_3: '', bank_holder_3: '',

                    // Promos
                    promo_code_1: '', promo_value_1: '',
                    promo_code_2: '', promo_value_2: '',
                    promo_code_3: '', promo_value_3: '',
                    promo_code_4: '', promo_value_4: '',
                    promo_code_5: '', promo_value_5: '',

                    // SMTP
                    smtp_host: '', smtp_port: '', smtp_user: '', smtp_pass: '', smtp_ssl: 'True',

                    // SMS
                    sms_header: '', sms_apikey: '', sms_balance: '',

                    // Other
                    min_purchase: '3800', tax_calc: 'No', delivery_calc: 'No',

                    // Socials Overview
                    fb_link: '', tw_link: '', yt_link: '', wa_link: '', ig_link: '', pin_link: '', copyright_text: '',
                    logo_path: '', favicon_path: '', logo_icon: 'fa-solid fa-fire-burner'
                };
                this.modalOpen = true;
            },

            openEditModal(company) {
                this.isEditMode = true;
                this.form = { ...company };
                this.modalOpen = true;
            },

            clearForm() {
                this.clearFormFields();
            },

            clearFormFields() {
                this.form = {
                    id: '', code: '', name: '', website: '', gst_number: '', pan_number: '', msme_number: '',
                    theme: 'Theme_1', type: 'Premium', status: 'active', tagline: '',
                    contact_1: '', contact_2: '', contact_3: '', contact_4: '', contact_5: '',
                    email_1: '', email_2: '', address_1: '', address_2: '',
                    state: 'Tamilnadu', city: 'Virudhunagar', pincode: '', map_link: '',
                    bank_qr_1: '', bank_name_1: '', bank_ifsc_1: '', bank_acc_1: '', bank_branch_1: '', bank_type_1: '', bank_holder_1: '',
                    bank_qr_2: '', bank_name_2: '', bank_ifsc_2: '', bank_acc_2: '', bank_branch_2: '', bank_type_2: '', bank_holder_2: '',
                    bank_qr_3: '', bank_name_3: '', bank_ifsc_3: '', bank_acc_3: '', bank_branch_3: '', bank_type_3: '', bank_holder_3: '',
                    promo_code_1: '', promo_value_1: '', promo_code_2: '', promo_value_2: '', promo_code_3: '', promo_value_3: '', promo_code_4: '', promo_value_4: '', promo_code_5: '', promo_value_5: '',
                    smtp_host: '', smtp_port: '', smtp_user: '', smtp_pass: '', smtp_ssl: 'True',
                    sms_header: '', sms_apikey: '', sms_balance: '',
                    min_purchase: '3800', tax_calc: 'No', delivery_calc: 'No',
                    fb_link: '', tw_link: '', yt_link: '', wa_link: '', ig_link: '', pin_link: '', copyright_text: '',
                    logo_path: '', favicon_path: '', logo_icon: 'fa-solid fa-fire-burner'
                };
            },

            confirmDelete(id) {
                this.deleteId = id;
                Swal.fire({
                    title: 'Delete Company Domain?',
                    text: "You are about to remove this registered domain profile. This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e51d1d',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('delete-form');
                        form.action = '/admin_sys/company/' + id;
                        form.submit();
                    }
                });
            },

            toggleStatus(company) {
                const oldStatus = company.status;
                const newStatus = (oldStatus === 'active') ? 'inactive' : 'active';
                company.status = newStatus; // Optimistic update
                
                fetch('/admin_sys/company/' + company.id + '/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP error ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        company.status = data.status;
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    } else {
                        company.status = oldStatus;
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to update status',
                            text: data.message || 'An error occurred.'
                        });
                    }
                })
                .catch(err => {
                    company.status = oldStatus;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error or authorization failed.'
                    });
                });
            }
        };
    }
</script>
@endsection
