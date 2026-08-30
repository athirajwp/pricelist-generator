@extends('layouts.admin')

@section('title', 'Manage Categories | Admin Console')

@section('content')
<div x-data="{ 
    addOpen: false, 
    editOpen: false, 
    editItem: {}, 
    searchQuery: '',
    matches: {},
    isMatch(id, name, slug) {
        const q = this.searchQuery.trim().toLowerCase();
        const matched = !q || 
                        name.toLowerCase().includes(q) || 
                        slug.toLowerCase().includes(q);
        this.matches[id] = matched;
        return matched;
    },
    hasMatches() {
        if (!this.searchQuery.trim()) return true;
        return Object.values(this.matches).some(v => v);
    }
}" class="space-y-8 select-none text-slate-800">
    
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Category Collections Registry</h2>
            <p class="text-[10px] text-slate-500 uppercase tracking-widest leading-none font-semibold">Add, edit, or delete store categories</p>
        </div>
        
        <button @click="addOpen = true" class="bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-750 hover:to-crimson-655 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow transition-all active:scale-95 flex items-center gap-1.5">
            <i class="fa-solid fa-circle-plus"></i> Add Category
        </button>
    </div>

    <!-- Category list table card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        
        <!-- Search and Filters Bar -->
        <div class="mb-5 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="relative w-full sm:max-w-xs group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-crimson-500 transition-colors">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input 
                    x-model="searchQuery" 
                    type="text" 
                    placeholder="Search categories..." 
                    class="w-full bg-slate-50 hover:bg-slate-100/50 border border-slate-200 focus:border-crimson-300 focus:bg-white focus:ring-4 focus:ring-crimson-50/50 rounded-xl pl-9 pr-8 py-2 text-xs font-semibold text-slate-700 focus:outline-none transition-all placeholder:text-slate-400 placeholder:font-medium shadow-sm"
                >
                <!-- Clear Button -->
                <button 
                    x-show="searchQuery.length > 0" 
                    @click="searchQuery = ''" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-655 transition-colors"
                    style="display: none;"
                >
                    <i class="fa-solid fa-circle-xmark text-xs"></i>
                </button>
            </div>
            
            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5 bg-slate-50 border border-slate-150 px-3 py-1.5 rounded-lg shadow-sm">
                <i class="fa-solid fa-filter text-slate-400"></i>
                <span x-text="searchQuery.trim() === '' ? {{ $categories->count() }} : Object.values(matches).filter(Boolean).length" class="text-slate-700 font-extrabold font-mono"></span> of <span class="text-slate-700 font-extrabold font-mono">{{ $categories->count() }}</span> categories matching
            </div>
        </div>
        
        <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-inner">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-4 px-4 w-12">S.No</th>
                        <th class="py-4 px-4">Category Name</th>
                        <th class="py-4 px-4 w-40 text-center">Sort Order Index</th>
                        <th class="py-4 px-4 w-40 text-center">Status</th>
                        <th class="py-4 px-4 w-28 text-center pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 text-slate-650 font-semibold">
                    @if($categories->count() > 0)
                        @php $sno = 1; @endphp
                        @foreach($categories as $category)
                        <tr x-show="isMatch({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->slug) }}')" class="hover:bg-slate-50/50">
                            
                            <!-- Index -->
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-400">{{ $sno++ }}</td>
                            
                            <!-- Name -->
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-800 font-bold text-xs select-all">{{ $category->name }}</strong>
                                <div class="text-[9px] text-slate-450 font-mono select-all">{{ $category->slug }}</div>
                            </td>

                            <!-- Sort order -->
                            <td class="py-3.5 px-4 text-center font-bold font-mono text-slate-500">
                                {{ $category->sort_order }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider 
                                      {{ $category->status === 'active' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-450 border border-slate-200' }}">
                                    {{ $category->status }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-center pr-4">
                                <div class="inline-flex gap-2">
                                    <button @click="editItem = { 
                                                id: {{ $category->id }}, 
                                                name: '{{ addslashes($category->name) }}', 
                                                sort_order: {{ $category->sort_order }}, 
                                                status: '{{ $category->status }}' 
                                            }; editOpen = true" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 w-8 h-8 rounded-lg text-slate-600 hover:text-slate-900 transition-colors shadow-sm" title="Edit Category">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    
                                    <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Deleting a category will delete all products inside it! Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-slate-50 hover:bg-crimson-50 border border-slate-200 hover:border-crimson-200 w-8 h-8 rounded-lg text-slate-400 hover:text-crimson-600 transition-colors shadow-sm" title="Delete Category">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                        @endforeach
                        
                        <!-- Real-time Filter No Results Fallback -->
                        <tr x-show="searchQuery.trim() !== '' && !hasMatches()" style="display: none;">
                            <td colspan="5" class="py-12 px-4 text-center text-slate-450 italic font-semibold">No categories found matching your search term.</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="5" class="py-12 px-4 text-center text-slate-450 italic font-semibold">No categories added yet. Click 'Add Category' to get started!</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </div>

    <!-- Category addition Drawer -->
    <div x-show="addOpen" class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div @click="addOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-md">
                <div class="h-full flex flex-col bg-white border-l border-slate-200 shadow-2xl overflow-y-auto">
                    
                    <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest"><i class="fa-solid fa-circle-plus text-crimson-600 mr-1.5"></i> Add Category</h3>
                            <p class="text-[9px] text-slate-500 font-semibold">Create new booking collection tab</p>
                        </div>
                        <button @click="addOpen = false" class="text-slate-400 hover:text-slate-655 p-2 rounded-lg"><i class="fa-solid fa-xmark text-sm"></i></button>
                    </div>

                    <form action="{{ route('admin.categories.store') }}" method="POST" class="p-6 space-y-4 text-xs font-semibold">
                        @csrf
                        
                        <div>
                            <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Category Name</label>
                            <input type="text" name="name" required placeholder="e.g. Sparklers, Flower Pots" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2 text-slate-700 focus:outline-none transition-all">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sort Order Index</label>
                                <input type="number" name="sort_order" required value="0" class="w-full bg-slate-50 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2 text-slate-700 focus:outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Activity Status</label>
                                <select name="status" required class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2 text-slate-700 focus:outline-none transition-all">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-200">
                            <button type="submit" class="w-full bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white font-extrabold py-3.5 rounded-full text-xs uppercase tracking-wider shadow transform active:scale-95 transition-all">Save Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Category editor Drawer -->
    <div x-show="editOpen" class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div @click="editOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-md">
                <div class="h-full flex flex-col bg-white border-l border-slate-200 shadow-2xl overflow-y-auto">
                    
                    <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest"><i class="fa-solid fa-pen-to-square text-crimson-600 mr-1.5"></i> Edit Category</h3>
                            <p class="text-[9px] text-slate-500 font-semibold">Edit category routing slugs and ordering index</p>
                        </div>
                        <button @click="editOpen = false" class="text-slate-400 hover:text-slate-655 p-2 rounded-lg"><i class="fa-solid fa-xmark text-sm"></i></button>
                    </div>

                    <form :action="`/admin/categories/${editItem.id}`" method="POST" class="p-6 space-y-4 text-xs font-semibold">
                        @csrf
                        @method('PUT')
                        
                        <div>
                            <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Category Name</label>
                            <input type="text" name="name" required :value="editItem.name" class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2 text-slate-700 focus:outline-none transition-all">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sort Order Index</label>
                                <input type="number" name="sort_order" required :value="editItem.sort_order" class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2 text-slate-700 focus:outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Activity Status</label>
                                <select name="status" required x-model="editItem.status" class="w-full bg-slate-55 border border-slate-200 focus:border-slate-350 focus:bg-white rounded-xl px-3 py-2 text-slate-700 focus:outline-none transition-all">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-200">
                            <button type="submit" class="w-full bg-gradient-to-r from-crimson-600 to-crimson-500 hover:from-crimson-700 hover:to-crimson-600 text-white font-extrabold py-3.5 rounded-full text-xs uppercase tracking-wider shadow transform active:scale-95 transition-all">Apply Modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
