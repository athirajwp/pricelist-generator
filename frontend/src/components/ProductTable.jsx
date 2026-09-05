import React, { useState, useEffect } from 'react';
import { useStore } from '../context/StoreContext';
import { getImageUrl } from '../utils/imageUrl';
import { sortProductsByCode } from '../utils/productSorter';

export default function ProductTable() {
  const {
    categories,
    settings,
    cart,
    increaseQty,
    decreaseQty,
    updateQty,
    activeCategory,
    setActiveCategory,
    searchQuery,
    setSearchQuery,
    viewMode,
    highlightedProductId,
  } = useStore();

  const isOutOfStock = (prod) => {
    if (!prod) return false;
    if (prod.stock_status === 'out_of_stock') return true;
    if ((prod.manage_stock ?? 'yes') !== 'no' && prod.stock_quantity !== null && prod.stock_quantity !== undefined) {
      return parseInt(prod.stock_quantity) <= 0;
    }
    return false;
  };

  const [popProduct, setPopProduct] = useState(null);
  const [mobileCategoryOpen, setMobileCategoryOpen] = useState(false);

  const [collapsedCategories, setCollapsedCategories] = useState(new Set());

  // Auto-expand category if highlighted product is inside it
  useEffect(() => {
    if (highlightedProductId && categories) {
      for (const cat of categories) {
        if ((cat.products || []).some((p) => p.id === highlightedProductId)) {
          setCollapsedCategories((prev) => {
            if (prev.has(cat.slug)) {
              const next = new Set(prev);
              next.delete(cat.slug);
              return next;
            }
            return prev;
          });
          break;
        }
      }
    }
  }, [highlightedProductId, categories]);

  const toggleCategoryCollapse = (slug) => {
    const newCollapsed = new Set(collapsedCategories);
    if (newCollapsed.has(slug)) {
      newCollapsed.delete(slug);
    } else {
      newCollapsed.add(slug);
    }
    setCollapsedCategories(newCollapsed);
  };

  const handleCategorySelect = (slug) => {
    setActiveCategory(slug);
    setMobileCategoryOpen(false);
    
    // Uncollapse if collapsed
    if (collapsedCategories.has(slug)) {
      const newCollapsed = new Set(collapsedCategories);
      newCollapsed.delete(slug);
      setCollapsedCategories(newCollapsed);
    }

    const targetId = slug === 'all' ? 'quick-order' : `category-row-${slug}`;
    const el = document.getElementById(targetId);
    if (el) {
      const yOffset = -90;
      const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
      window.scrollTo({ top: y, behavior: 'smooth' });
    }
  };

  const shouldShowCategory = (slug) => {
    return true;
  };

  const shouldShowProduct = (categorySlug, product) => {
    if (collapsedCategories.has(categorySlug)) {
      return false;
    }
    if (searchQuery.trim() !== '') {
      return product.name.toLowerCase().includes(searchQuery.toLowerCase());
    }
    return true;
  };

  // Helper to format currency
  const formatCurrency = (val) => {
    return parseFloat(val).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
  };

  // Compute number of filtered products currently shown
  let totalFilteredProductsCount = 0;
  categories.forEach((cat) => {
    if (shouldShowCategory(cat.slug)) {
      cat.products.forEach((prod) => {
        if (shouldShowProduct(cat.slug, prod)) {
          totalFilteredProductsCount++;
        }
      });
    }
  });

  const cardBgStyle = { backgroundColor: settings?.card_bg_color || '#FFFFFF' };

  return (
    <section id="quick-order" className="container mx-auto px-4 pt-2 pb-4 select-none">
      {/* Main Quick Order Table & Category Sidebar Layout */}
      <div className="flex flex-col lg:flex-row gap-3 sm:gap-4 lg:gap-8 items-start">
        {/* Mobile Custom Theme-Synced Categories Dropdown (Visible on Mobile & Tablet < 1024px) */}
      <div className="block lg:hidden w-full select-none mb-2.5 relative z-30">
        <div
          onClick={() => setMobileCategoryOpen(!mobileCategoryOpen)}
          className="w-full bg-gradient-to-r from-crimson-700 via-crimson-600 to-crimson-800 text-white border border-crimson-700 rounded-2xl p-3.5 shadow-md flex items-center justify-between cursor-pointer active:scale-[0.99] transition-all"
        >
          <div className="flex items-center gap-2.5 min-w-0">
            <i className="fa-solid fa-boxes-stacked text-gold-400 text-sm flex-shrink-0"></i>
            <span className="text-xs font-black uppercase tracking-wider text-slate-100 flex-shrink-0">
              Categories:
            </span>
            <span className="bg-gold-500 text-slate-950 text-[10.5px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow-xs truncate">
              {activeCategory === 'all'
                ? 'All Products'
                : categories.find((c) => c.slug === activeCategory)?.name || activeCategory}
            </span>
          </div>
          <div className="flex items-center gap-2 text-gold-400 flex-shrink-0 ml-2">
            <i className="fa-solid fa-filter text-xs"></i>
            <i className={`fa-solid fa-chevron-down text-xs transition-transform duration-300 ${mobileCategoryOpen ? 'rotate-180 text-gold-300' : ''}`}></i>
          </div>
        </div>

        {/* Custom Theme-Synced Dropdown Panel */}
        {mobileCategoryOpen && (
          <div className="absolute left-0 right-0 top-full mt-2 bg-white border-2 border-gold-400/90 rounded-2xl shadow-2xl p-2 z-50 animate-fade-in max-h-80 overflow-y-auto divide-y divide-slate-100">
            <div className="p-2 pb-1.5 flex items-center justify-between text-slate-700 font-extrabold text-xs uppercase tracking-wider">
              <span className="flex items-center gap-1.5 text-crimson-700">
                <i className="fa-solid fa-layer-group text-xs"></i> Select Category
              </span>
              <span className="text-[10px] text-slate-400 font-bold">
                {categories.length + 1} Options
              </span>
            </div>

            <div className="pt-1.5 space-y-1">
              <button
                type="button"
                onClick={() => handleCategorySelect('all')}
                className={`w-full text-left px-3.5 py-3 rounded-xl text-xs flex items-center justify-between transition-all ${
                  activeCategory === 'all'
                    ? 'bg-crimson-600 text-white font-black shadow-sm'
                    : 'text-slate-700 hover:bg-crimson-50 hover:text-crimson-700 font-bold'
                }`}
              >
                <span className="flex items-center gap-2.5">
                  <i className={`fa-solid fa-boxes-stacked text-xs ${activeCategory === 'all' ? 'text-gold-400' : 'text-slate-400'}`}></i>
                  <span>All Products</span>
                </span>
                {activeCategory === 'all' && (
                  <i className="fa-solid fa-circle-check text-gold-400 text-sm"></i>
                )}
              </button>

              {categories.map((cat) => {
                const isActive = activeCategory === cat.slug;
                return (
                  <button
                    key={cat.id}
                    type="button"
                    onClick={() => handleCategorySelect(cat.slug)}
                    className={`w-full text-left px-3.5 py-3 rounded-xl text-xs flex items-center justify-between transition-all ${
                      isActive
                        ? 'bg-crimson-600 text-white font-black shadow-sm'
                        : 'text-slate-700 hover:bg-crimson-50 hover:text-crimson-700 font-bold'
                    }`}
                  >
                    <span className="flex items-center gap-2.5">
                      <i className={`fa-solid fa-fire-flame-curved text-xs ${isActive ? 'text-gold-400' : 'text-slate-400'}`}></i>
                      <span>{cat.name}</span>
                    </span>
                    {isActive && (
                      <i className="fa-solid fa-circle-check text-gold-400 text-sm"></i>
                    )}
                  </button>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {/* Left: Category sidebar filters (Hidden on Mobile, Sticky on Desktop) */}
      <aside className="hidden lg:block lg:w-64 flex-shrink-0 lg:sticky lg:top-24 space-y-4 select-none">
        <div className="border border-slate-200/80 p-4 rounded-2xl shadow-sm" style={cardBgStyle}>
          <h3 className="text-sm font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-2.5 mb-3 flex justify-between items-center">
            <span>Categories</span>
            <i className="fa-solid fa-filter text-slate-400 text-xs"></i>
          </h3>
          <div className="flex flex-col gap-1">
            <button
              onClick={() => handleCategorySelect('all')}
              className={`w-full text-left px-3.5 py-2.5 rounded-xl text-xs flex items-center gap-2 whitespace-nowrap transition-all duration-200 cursor-pointer ${
                activeCategory === 'all'
                  ? 'bg-crimson-600 text-white font-extrabold shadow'
                  : 'text-slate-655 hover:bg-crimson-50 hover:text-crimson-700'
              }`}
            >
              <i className="fa-solid fa-boxes-stacked text-[11px] opacity-80"></i> All Products
            </button>
            {categories.map((cat) => (
              <button
                key={cat.id}
                onClick={() => handleCategorySelect(cat.slug)}
                className={`w-full text-left px-3.5 py-2.5 rounded-xl text-xs flex items-center gap-2 whitespace-nowrap transition-all duration-200 cursor-pointer ${
                  activeCategory === cat.slug
                    ? 'bg-crimson-600 text-white font-extrabold shadow'
                    : 'text-slate-655 hover:bg-crimson-50 hover:text-crimson-700'
                }`}
              >
                <i className="fa-solid fa-fire-flame-curved text-[11px] opacity-80"></i> {cat.name}
              </button>
            ))}
          </div>
        </div>
      </aside>

      {/* Right: Product list spreadsheet */}
      <div className="flex-grow w-full space-y-6">
        {/* Conditional Layout Rendering */}
        {viewMode === 'flex' ? (
          <div className="border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm" style={cardBgStyle}>
            
            {/* Desktop View Table */}
            <div className="hidden sm:block overflow-x-auto sm:overflow-visible">
              <table className="w-full border-collapse text-left text-xs">
                <thead>
                  <tr className="bg-gradient-to-r from-crimson-700 via-crimson-600 to-crimson-800 border-b border-crimson-900 text-white font-extrabold uppercase tracking-wider text-[10.5px] select-none shadow-sm">
                    <th className="py-4 px-3 sm:px-4">Cracker Details</th>
                    <th className="hidden sm:table-cell py-4 px-4 w-28 text-center">Unit / Box</th>
                    <th className="py-4 px-3 sm:px-4 w-24 sm:w-36 text-right">Price (₹)</th>
                    <th className="py-4 px-3 sm:px-4 w-28 sm:w-40 text-center">Order Qty</th>
                    <th className="hidden md:table-cell py-4 px-4 w-28 text-right pr-6">Total (₹)</th>
                    <th className="py-4 px-3 sm:px-4 w-28 text-right pr-4">Sub Total (₹)</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-150">
                  {categories.map((cat) => {
                    if (!shouldShowCategory(cat.slug)) return null;

                    const isCollapsed = collapsedCategories.has(cat.slug);
                    
                    return (
                      <React.Fragment key={cat.id}>
                        {/* Category Header Row */}
                        <tr
                          id={`category-row-${cat.slug}`}
                          onClick={() => toggleCategoryCollapse(cat.slug)}
                          className="bg-crimson-50/90 font-black text-crimson-900 border-y border-crimson-200/60 select-none cursor-pointer hover:bg-crimson-100/90 transition-colors"
                        >
                          <td colSpan={6} className="py-3 px-3 sm:px-4 flex items-center justify-between tracking-wider">
                            <div className="flex items-center gap-2">
                              <i className="fa-solid fa-fire text-xs text-crimson-600"></i>
                              <span>{cat.name}</span>
                            </div>
                            <i
                              className={`fa-solid fa-chevron-down text-[10px] text-crimson-600/70 transition-transform duration-200 ${
                                isCollapsed ? '-rotate-90' : 'rotate-0'
                              }`}
                            ></i>
                          </td>
                        </tr>

                        {/* Products list within Category */}
                        {!isCollapsed && sortProductsByCode(cat.products).map((prod) => {
                          if (!shouldShowProduct(cat.slug, prod)) return null;

                          const cartItem = cart[prod.id];
                          const qty = cartItem ? cartItem.qty : 0;
                          const rowTotal = qty * parseFloat(prod.selling_price);

                          return (
                            <tr
                              key={prod.id}
                              id={`product-row-${prod.id}`}
                              data-product-id={prod.id}
                              className={`border-b border-slate-100/90 transition-all duration-300 scroll-mt-24 sm:scroll-mt-28 ${
                                highlightedProductId === prod.id
                                  ? 'bg-amber-100/90 ring-2 ring-amber-400 font-bold animate-pulse shadow-md z-20'
                                  : qty > 0 ? 'bg-crimson-50/20' : 'hover:bg-crimson-50/30'
                              }`}
                            >
                              {/* Product Info */}
                              <td className="py-3.5 px-3 sm:px-4">
                                <div className="flex flex-col gap-1.5">
                                  {/* Title on top */}
                                  <h4 className={`font-extrabold text-xs sm:text-sm leading-normal whitespace-nowrap truncate ${qty > 0 ? 'text-crimson-950 font-black' : 'text-slate-900'}`}>{prod.name}</h4>
                                  
                                  {/* Image + Info below title */}
                                  <div className="flex items-center gap-3">
                                    {/* Left Image */}
                                     {(() => {
                                       const imgSrc = getImageUrl(prod.image);
                                       return (
                                         <div 
                                           className={`flex w-10 h-10 rounded-lg bg-white border border-crimson-200/60 shadow-sm items-center justify-center text-slate-400 overflow-hidden flex-shrink-0 ${imgSrc ? 'cursor-pointer hover:opacity-80 transition-opacity' : ''}`}
                                           onClick={imgSrc ? () => setPopProduct({ prod, catName: cat.name }) : undefined}
                                           style={imgSrc ? {
                                             backgroundImage: `url("${imgSrc}")`,
                                             backgroundSize: 'contain',
                                             backgroundPosition: 'center',
                                             backgroundRepeat: 'no-repeat'
                                           } : {}}
                                         >
                                           {!imgSrc && (
                                             <i className="fa-solid fa-sparkles text-sm text-crimson-450/40"></i>
                                           )}
                                         </div>
                                       );
                                     })()}
                                    
                                    {/* Right Specs */}
                                    <div className="flex flex-col items-start gap-1">
                                      <span className="text-[8px] sm:text-[9px] font-extrabold text-crimson-700 bg-crimson-50/90 border border-crimson-200/70 px-1.5 py-0.5 rounded uppercase tracking-wider">{cat.name}</span>
                                      <span className="sm:hidden text-[9px] font-semibold text-slate-600 bg-slate-100/80 border border-slate-200 px-2 py-0.5 rounded-lg shadow-sm">
                                        {prod.pack_size}
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              </td>

                              {/* Pack size */}
                              <td className="hidden sm:table-cell py-3.5 px-4 text-center text-slate-700 font-bold font-mono">
                                {prod.pack_size}
                              </td>

                              {/* Prices */}
                              <td className="py-3.5 px-3 sm:px-4 text-right">
                                {settings?.show_mrp !== 'no' && (
                                  <div className="text-slate-400 text-[10px] line-through font-semibold">₹{formatCurrency(prod.mrp)}</div>
                                )}
                                <div className="text-crimson-800 font-black text-xs sm:text-sm">₹{formatCurrency(prod.selling_price)}</div>
                              </td>

                              {/* Qty selectors */}
                              <td className="py-3.5 px-3 sm:px-4 text-center">
                                {isOutOfStock(prod) ? (
                                  <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[10px] sm:text-xs font-black uppercase tracking-wider bg-rose-50 border border-rose-200 text-rose-700 shadow-2xs select-none">
                                    <i className="fa-solid fa-ban text-[9px]"></i> Out of Stock
                                  </span>
                                ) : (
                                  <div className={`inline-flex items-center rounded-xl p-0.5 sm:p-1 select-none border transition-all duration-200 ${
                                    qty > 0 ? 'bg-crimson-500/10 border-crimson-300 shadow-sm' : 'bg-slate-100/90 border-slate-200/90 hover:border-crimson-300/60'
                                  }`}>
                                    <button
                                      onClick={() => decreaseQty(prod.id)}
                                      className={`w-6 h-6 sm:w-7 sm:h-7 rounded-lg flex items-center justify-center font-bold text-xs transition-all active:scale-95 shadow-sm ${
                                        qty > 0
                                          ? 'bg-crimson-200 text-crimson-950 hover:bg-crimson-600 hover:text-white border border-crimson-300'
                                          : 'bg-crimson-100/90 text-crimson-800 hover:bg-crimson-600 hover:text-white border border-crimson-200/90'
                                      }`}
                                    >
                                      <i className="fa-solid fa-minus text-[8px] sm:text-[9px]"></i>
                                    </button>
                                    <input
                                      type="number"
                                      value={qty || ''}
                                      onChange={(e) => updateQty(prod, e.target.value)}
                                      placeholder="0"
                                      className={`w-8 sm:w-12 text-center bg-transparent border-0 text-xs font-black placeholder-slate-400 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${
                                        qty > 0 ? 'text-crimson-900 font-extrabold' : 'text-slate-700'
                                      }`}
                                    />
                                    <button
                                      onClick={() => increaseQty(prod)}
                                      className="w-6 h-6 sm:w-7 sm:h-7 bg-crimson-600 text-white hover:bg-crimson-700 rounded-lg flex items-center justify-center font-bold text-xs transition-all active:scale-95 shadow-sm"
                                    >
                                      <i className="fa-solid fa-plus text-[8px] sm:text-[9px]"></i>
                                    </button>
                                  </div>
                                )}
                              </td>

                              {/* Row Total (Desktop only) */}
                              <td className="hidden md:table-cell py-3.5 px-4 text-right font-black text-slate-900 pr-6">
                                ₹{formatCurrency(rowTotal)}
                              </td>

                              {/* Sub Total (always visible) */}
                              <td className="py-3.5 px-3 sm:px-4 text-right pr-4">
                                <span className={`font-black text-xs sm:text-sm ${qty > 0 ? 'text-crimson-700 font-extrabold' : 'text-slate-400'}`}>
                                  {qty > 0 ? `₹${formatCurrency(rowTotal)}` : '—'}
                                </span>
                              </td>
                            </tr>
                          );
                        })}
                      </React.Fragment>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {/* Mobile View List */}
            <div className="block sm:hidden divide-y divide-slate-150">
              {categories.map((cat) => {
                if (!shouldShowCategory(cat.slug)) return null;

                const isCollapsed = collapsedCategories.has(cat.slug);
                const filteredProducts = sortProductsByCode(cat.products.filter((prod) => shouldShowProduct(cat.slug, prod)));
                if (filteredProducts.length === 0) return null;

                return (
                  <React.Fragment key={cat.id}>
                    {/* Category Header (Mobile) */}
                    <div
                      onClick={() => toggleCategoryCollapse(cat.slug)}
                      className="bg-crimson-50/90 font-black text-crimson-900 border-y border-crimson-200/60 select-none cursor-pointer hover:bg-crimson-100/90 transition-colors py-3 px-3.5 flex items-center justify-between tracking-wider"
                    >
                      <div className="flex items-center gap-2">
                        <i className="fa-solid fa-fire text-xs text-crimson-600"></i>
                        <span>{cat.name}</span>
                      </div>
                      <i
                        className={`fa-solid fa-chevron-down text-[10px] text-crimson-600/70 transition-transform duration-200 ${
                          isCollapsed ? '-rotate-90' : 'rotate-0'
                        }`}
                      ></i>
                    </div>

                    {/* Products list within Category (Mobile) */}
                    {!isCollapsed && filteredProducts.map((prod) => {
                      const cartItem = cart[prod.id];
                      const qty = cartItem ? cartItem.qty : 0;
                      const rowTotal = qty * parseFloat(prod.selling_price);

                      return (
                        <div
                          key={prod.id}
                          id={`product-mobile-${prod.id}`}
                          data-product-id={prod.id}
                          className={`p-3 sm:p-4 flex flex-col gap-2 transition-all duration-300 border-b border-slate-150 last:border-b-0 scroll-mt-24 sm:scroll-mt-28 ${
                            highlightedProductId === prod.id
                              ? 'bg-amber-100/90 ring-2 ring-amber-400 font-bold animate-pulse shadow-md z-20'
                              : qty > 0 ? 'bg-crimson-50/20' : 'hover:bg-crimson-50/30'
                          }`}
                        >
                          {/* Title on top */}
                          <h4 className={`font-extrabold text-[11px] sm:text-xs leading-normal whitespace-nowrap truncate ${qty > 0 ? 'text-crimson-950 font-black' : 'text-slate-900'}`}>{prod.name}</h4>
                          
                          {/* Specs, price, qty, subtotal underneath */}
                          <div className="flex items-center justify-between gap-1">
                            
                            {/* Image + Specs */}
                            <div className="flex items-center gap-2 min-w-0">
                              {(() => {
                                const imgSrc = getImageUrl(prod.image);
                                return (
                                  <div 
                                    className={`flex w-9 h-9 rounded-lg bg-white border border-crimson-200/60 shadow-sm items-center justify-center text-slate-400 overflow-hidden flex-shrink-0 ${imgSrc ? 'cursor-pointer hover:opacity-80 transition-opacity' : ''}`}
                                    onClick={imgSrc ? () => setPopProduct({ prod, catName: cat.name }) : undefined}
                                    style={imgSrc ? {
                                      backgroundImage: `url("${imgSrc}")`,
                                      backgroundSize: 'contain',
                                      backgroundPosition: 'center',
                                      backgroundRepeat: 'no-repeat'
                                    } : {}}
                                  >
                                    {!imgSrc && (
                                      <i className="fa-solid fa-sparkles text-xs text-crimson-450/40"></i>
                                    )}
                                  </div>
                                );
                              })()}
                              <div className="flex flex-col items-start min-w-0">
                                <span className="text-[7.5px] font-extrabold text-crimson-700 bg-crimson-50/90 border border-crimson-200/70 px-1 py-0.5 rounded uppercase tracking-wider truncate max-w-[65px] leading-tight mb-0.5">{cat.name}</span>
                                <span className="text-[8.5px] font-semibold text-slate-600 bg-slate-100/80 border border-slate-200 px-1.5 py-0.5 rounded-lg shadow-sm whitespace-nowrap leading-none">
                                  {prod.pack_size}
                                </span>
                              </div>
                            </div>

                            {/* Price */}
                            <div className="text-left flex-shrink-0 px-1">
                              {settings?.show_mrp !== 'no' && (
                                <div className="text-slate-400 text-[9px] line-through font-semibold leading-tight">₹{formatCurrency(prod.mrp)}</div>
                              )}
                              <div className="text-crimson-800 font-extrabold text-[11px] leading-tight">₹{formatCurrency(prod.selling_price)}</div>
                            </div>

                            {/* Qty selectors */}
                            {isOutOfStock(prod) ? (
                              <span className="inline-flex items-center gap-1 px-2 py-1 rounded-xl text-[9px] font-black uppercase tracking-wider bg-rose-50 border border-rose-200 text-rose-700 flex-shrink-0 select-none">
                                <i className="fa-solid fa-ban text-[8px]"></i> Out of Stock
                              </span>
                            ) : (
                              <div className={`inline-flex items-center rounded-xl p-0.5 select-none flex-shrink-0 border transition-all duration-200 ${
                                qty > 0 ? 'bg-crimson-500/10 border-crimson-300 shadow-sm' : 'bg-slate-100/90 border-slate-200/90'
                              }`}>
                                <button
                                  onClick={() => decreaseQty(prod.id)}
                                  className={`w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs transition-all active:scale-95 shadow-sm ${
                                    qty > 0
                                      ? 'bg-crimson-200 text-crimson-950 hover:bg-crimson-600 hover:text-white border border-crimson-300'
                                      : 'bg-crimson-100/90 text-crimson-800 hover:bg-crimson-600 hover:text-white border border-crimson-200/90'
                                  }`}
                                >
                                  <i className="fa-solid fa-minus text-[8px]"></i>
                                </button>
                                <input
                                  type="number"
                                  value={qty || ''}
                                  onChange={(e) => updateQty(prod, e.target.value)}
                                  placeholder="0"
                                  className={`w-7 text-center bg-transparent border-0 text-[11px] font-black placeholder-slate-400 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${
                                    qty > 0 ? 'text-crimson-900 font-extrabold' : 'text-slate-700'
                                  }`}
                                />
                                <button
                                  onClick={() => increaseQty(prod)}
                                  className="w-6 h-6 bg-crimson-600 text-white hover:bg-crimson-700 rounded-lg flex items-center justify-center font-bold text-xs transition-all active:scale-95 shadow-sm"
                                >
                                  <i className="fa-solid fa-plus text-[8px]"></i>
                                </button>
                              </div>
                            )}

                            {/* Row total */}
                            <div className="text-right flex-shrink-0 min-w-[3.5rem]">
                              <span className="font-extrabold text-[11px] text-crimson-600">
                                {qty > 0 ? `₹${formatCurrency(rowTotal)}` : '—'}
                              </span>
                            </div>

                          </div>
                        </div>
                      );
                    })}
                  </React.Fragment>
                );
              })}
            </div>

          </div>
        ) : (
          /* Grid View Layout */
          <div className="space-y-4">
            {categories.map((cat) => {
              if (!shouldShowCategory(cat.slug)) return null;

              const isCollapsed = collapsedCategories.has(cat.slug);

              // Find products that match active filter / search query
              const filteredProducts = sortProductsByCode(cat.products.filter((prod) => shouldShowProduct(cat.slug, prod)));
              if (filteredProducts.length === 0) return null;

              return (
                <div key={cat.id} className="space-y-2.5">
                  {/* Category Section Header */}
                  <div
                    onClick={() => toggleCategoryCollapse(cat.slug)}
                    className="border border-crimson-200/80 bg-crimson-50/80 hover:bg-crimson-100/80 rounded-2xl p-4 flex items-center justify-between text-crimson-900 font-extrabold tracking-wider cursor-pointer transition-colors select-none shadow-sm"
                    style={cardBgStyle}
                  >
                    <div className="flex items-center gap-2">
                      <i className="fa-solid fa-fire text-xs text-crimson-600 animate-pulse"></i>
                      <span>{cat.name}</span>
                    </div>
                    <i
                      className={`fa-solid fa-chevron-down text-xs text-crimson-600/70 transition-transform duration-200 ${
                        isCollapsed ? '-rotate-90' : 'rotate-0'
                      }`}
                    ></i>
                  </div>

                  {/* Grid of Product Cards */}
                  {!isCollapsed && (
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                      {filteredProducts.map((prod) => {
                        const cartItem = cart[prod.id];
                        const qty = cartItem ? cartItem.qty : 0;
                        const rowTotal = qty * parseFloat(prod.selling_price);

                        return (
                          <div
                            key={prod.id}
                            id={`product-grid-${prod.id}`}
                            data-product-id={prod.id}
                            style={qty > 0 ? undefined : cardBgStyle}
                            className={`border rounded-2xl p-2.5 sm:p-3 flex flex-col justify-between hover:shadow-md transition-all duration-300 relative overflow-hidden group scroll-mt-24 sm:scroll-mt-28 ${
                              highlightedProductId === prod.id
                                ? 'ring-4 ring-gold-500 bg-amber-50/90 border-gold-400 scale-[1.03] shadow-xl animate-pulse z-20'
                                : qty > 0 ? 'border-crimson-300 ring-1 ring-crimson-200/50 bg-crimson-50/20' : 'border-slate-200'
                            }`}
                          >
                            {/* Upper Card Area: Image + Details */}
                            <div className="space-y-1.5 sm:space-y-2">
                              {/* Image Container with Hover Effect */}
                              {(() => {
                                const imgSrc = getImageUrl(prod.image);
                                return (
                                  <div 
                                    className={`w-full h-36 sm:h-40 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center overflow-hidden relative ${imgSrc ? 'cursor-pointer hover:opacity-95 transition-opacity' : ''}`}
                                    onClick={imgSrc ? () => setPopProduct({ prod, catName: cat.name }) : undefined}
                                    style={imgSrc ? {
                                      backgroundImage: `url("${imgSrc}")`,
                                      backgroundSize: 'contain',
                                      backgroundPosition: 'center',
                                      backgroundRepeat: 'no-repeat'
                                    } : {}}
                                  >
                                    {!imgSrc && (
                                      <i className="fa-solid fa-sparkles text-2xl text-crimson-450/30"></i>
                                    )}

                                    {/* Category Label Overlay */}
                                    <span className="absolute top-1.5 left-1.5 text-[7px] sm:text-[8px] font-black text-slate-700 bg-white/90 backdrop-blur border border-slate-250 px-1.5 py-0.5 rounded-full uppercase tracking-wider">
                                      {cat.name}
                                    </span>

                                    {/* Pack size Label Overlay */}
                                    <span className="absolute top-1.5 right-1.5 text-[7px] sm:text-[8.5px] font-bold text-slate-500 bg-slate-100/90 border border-slate-200 px-1.5 py-0.5 rounded-lg font-mono">
                                      {prod.pack_size}
                                    </span>

                                    {/* Out of Stock Overlay Badge */}
                                    {isOutOfStock(prod) && (
                                      <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px] flex items-center justify-center">
                                        <span className="bg-rose-600 text-white font-black text-[9px] sm:text-xs px-2.5 py-1 rounded-full uppercase tracking-wider shadow-md">
                                          Out of Stock
                                        </span>
                                      </div>
                                    )}
                                  </div>
                                );
                              })()}

                              {/* Title & Info */}
                              <div className="pt-0.5">
                                <h4 className="font-extrabold text-slate-800 text-xs sm:text-xs leading-snug line-clamp-2">
                                  {prod.name}
                                </h4>
                              </div>
                            </div>

                            {/* Lower Card Area: Pricing & Actions */}
                            <div className="mt-2 pt-2 border-t border-slate-200/70 space-y-1.5">
                              {/* Prices Row */}
                              <div className="flex items-baseline justify-between gap-1">
                                <span className="text-[9px] sm:text-[10px] text-slate-400 font-semibold">Price:</span>
                                <div className="text-right">
                                  {settings?.show_mrp !== 'no' && (
                                    <span className="text-slate-400 text-[9px] sm:text-[10px] line-through mr-1 font-bold">₹{formatCurrency(prod.mrp)}</span>
                                  )}
                                  <span className="text-crimson-650 font-black text-xs sm:text-sm">₹{formatCurrency(prod.selling_price)}</span>
                                </div>
                              </div>

                              {/* Qty Selector */}
                              <div className="flex items-center justify-between gap-1 pt-0.5">
                                {isOutOfStock(prod) ? (
                                  <div className="w-full py-1.5 rounded-xl text-[10px] sm:text-xs font-black uppercase tracking-wider bg-rose-50 border border-rose-200 text-rose-700 text-center select-none shadow-2xs">
                                    Out of Stock
                                  </div>
                                ) : (
                                  <div className={`inline-flex items-center rounded-xl p-0.5 select-none w-full justify-between border transition-all duration-200 ${
                                    qty > 0 ? 'bg-crimson-500/10 border-crimson-300 shadow-sm' : 'bg-slate-100/90 border-slate-200/90'
                                  }`}>
                                    <button
                                      type="button"
                                      onClick={() => decreaseQty(prod.id)}
                                      className={`w-6 h-6 sm:w-7 sm:h-7 rounded-lg flex items-center justify-center font-bold text-xs transition-all active:scale-95 shadow-sm ${
                                        qty > 0
                                          ? 'bg-crimson-200 text-crimson-950 hover:bg-crimson-600 hover:text-white border border-crimson-300'
                                          : 'bg-crimson-100/90 text-crimson-800 hover:bg-crimson-600 hover:text-white border border-crimson-200/90'
                                      }`}
                                    >
                                      <i className="fa-solid fa-minus text-[8px] sm:text-[9px]"></i>
                                    </button>
                                    <input
                                      type="number"
                                      value={qty || ''}
                                      onChange={(e) => updateQty(prod, e.target.value)}
                                      placeholder="0"
                                      className={`w-7 sm:w-10 text-center bg-transparent border-0 text-[11px] sm:text-xs font-black placeholder-slate-400 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${
                                        qty > 0 ? 'text-crimson-900 font-extrabold' : 'text-slate-700'
                                      }`}
                                    />
                                    <button
                                      type="button"
                                      onClick={() => increaseQty(prod)}
                                      className="w-6 h-6 sm:w-7 sm:h-7 bg-crimson-600 text-white hover:bg-crimson-700 rounded-lg flex items-center justify-center font-bold text-xs transition-all active:scale-95 shadow-sm"
                                    >
                                      <i className="fa-solid fa-plus text-[8px] sm:text-[9px]"></i>
                                    </button>
                                  </div>
                                )}
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* Product Detailed Quick View Modal */}
      {popProduct && (() => {
        const prod = popProduct.prod;
        const catName = popProduct.catName;
        const qty = cart[prod.id]?.qty || 0;
        const rowTotal = qty * parseFloat(prod.selling_price);
        const discountPercent = Math.round(((parseFloat(prod.mrp) - parseFloat(prod.selling_price)) / parseFloat(prod.mrp)) * 100);

        return (
          <div 
            className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4 cursor-pointer"
            onClick={() => setPopProduct(null)}
          >
            <div 
              className="relative bg-white border border-slate-200 rounded-3xl p-5 md:p-6 max-w-3xl w-full shadow-2xl select-none cursor-default animate-scale-up"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Close Button */}
              <button
                onClick={() => setPopProduct(null)}
                className="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-crimson-600 hover:bg-crimson-500 text-white flex items-center justify-center shadow-lg transition-all hover:scale-110 active:scale-95 z-10"
              >
                <i className="fa-solid fa-xmark text-sm"></i>
              </button>

              {/* Grid Split Content */}
              <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                
                {/* Left Column: Image */}
                {(() => {
                  const modalImgSrc = getImageUrl(popProduct?.prod?.image);
                  return (
                    <div 
                      className="md:col-span-5 bg-slate-50 border border-slate-150 p-2.5 rounded-2xl flex items-center justify-center h-64 md:h-80 overflow-hidden shadow-inner relative"
                      style={modalImgSrc ? {
                        backgroundImage: `url("${modalImgSrc}")`,
                        backgroundSize: 'contain',
                        backgroundPosition: 'center',
                        backgroundRepeat: 'no-repeat'
                      } : {}}
                    >
                      {!modalImgSrc && (
                        <i className="fa-solid fa-sparkles text-4xl text-crimson-450/30"></i>
                      )}
                    </div>
                  );
                })()}

                {/* Right Column: Detailed Info & Cart Selectors */}
                <div className="md:col-span-7 space-y-4 text-left">
                  {/* Badges */}
                  <div className="flex flex-wrap gap-2 items-center">
                    <span className="text-[9px] font-black text-crimson-600 bg-crimson-50 border border-crimson-100 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                      {catName}
                    </span>
                    <span className="text-[9px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full font-mono">
                      {prod.pack_size}
                    </span>
                  </div>

                  {/* Title */}
                  <h3 className="text-lg md:text-xl font-black text-slate-900 tracking-tight leading-snug">
                    {prod.name}
                  </h3>

                  {/* Prices & Savings */}
                  <div className="bg-slate-50 border border-slate-150 rounded-2xl p-4 space-y-2">
                    {settings?.show_mrp !== 'no' && (
                      <div className="flex justify-between items-baseline">
                        <span className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">Original MRP:</span>
                        <span className="text-slate-400 font-bold line-through text-xs sm:text-sm">₹{formatCurrency(prod.mrp)}</span>
                      </div>
                    )}
                    <div className="flex justify-between items-baseline">
                      <span className="text-[10px] text-slate-550 font-extrabold uppercase tracking-wider">Wholesale Price:</span>
                      <span className="text-crimson-600 font-black text-lg sm:text-xl">₹{formatCurrency(prod.selling_price)}</span>
                    </div>
                    {discountPercent > 0 && (
                      <div className="flex justify-between items-center pt-1 border-t border-slate-200 text-[10px]">
                        <span className="text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-md font-bold">
                          Flat {discountPercent}% Off
                        </span>
                        <span className="text-slate-500 font-semibold">
                          Save ₹{formatCurrency(parseFloat(prod.mrp) - parseFloat(prod.selling_price))} per item!
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Quantity & Subtotal Row */}
                  <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-1">
                    <div className="space-y-1.5 w-full sm:w-auto">
                      <span className="text-[9px] text-slate-400 font-black uppercase tracking-wider block">Order Quantity</span>
                      <div className="inline-flex items-center bg-slate-100 border border-slate-250 rounded-xl p-1 select-none">
                        <button
                          type="button"
                          onClick={() => decreaseQty(prod.id)}
                          className="w-8 h-8 text-slate-655 hover:text-slate-900 hover:bg-white rounded-lg flex items-center justify-center font-bold text-sm transition-all shadow-sm"
                        >
                          <i className="fa-solid fa-minus text-[10px]"></i>
                        </button>
                        <input
                          type="number"
                          value={qty || ''}
                          onChange={(e) => updateQty(prod, e.target.value)}
                          placeholder="0"
                          className="w-12 text-center bg-transparent border-0 text-sm font-black text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                        />
                        <button
                          type="button"
                          onClick={() => increaseQty(prod)}
                          className="w-8 h-8 text-slate-655 hover:text-slate-900 hover:bg-white rounded-lg flex items-center justify-center font-bold text-sm transition-all shadow-sm"
                        >
                          <i className="fa-solid fa-plus text-[10px]"></i>
                        </button>
                      </div>
                    </div>

                    <div className="text-left sm:text-right w-full sm:w-auto">
                      {qty > 0 ? (
                        <>
                          <div className="text-[9px] text-slate-450 font-black uppercase tracking-wider">Subtotal</div>
                          <span className="font-black text-base text-crimson-600 block">
                            ₹{formatCurrency(rowTotal)}
                          </span>
                        </>
                      ) : (
                        <span className="text-[10px] text-slate-400 font-semibold italic">Not added to booking yet</span>
                      )}
                    </div>
                  </div>

                  {/* PESO / Quality guidelines banner info */}
                  <div className="text-[9px] text-slate-400 leading-normal flex items-start gap-1.5 pt-2 border-t border-slate-100">
                    <i className="fa-solid fa-shield-halved text-slate-350 mt-0.5"></i>
                    <span>
                      Sivakasi manufactured premium crackers. Store in cool, dry place. Keep out of reach of children. Ignite under adult supervision.
                    </span>
                  </div>

                </div>

              </div>

            </div>
          </div>
        );
      })()}
      </div>
    </section>
  );
}
