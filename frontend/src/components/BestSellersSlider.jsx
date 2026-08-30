import React, { useState, useEffect } from 'react';
import { useStore } from '../context/StoreContext';
import { getImageUrl } from '../utils/imageUrl';

export default function BestSellersSlider({ onPreviewProduct }) {
  const {
    categories,
    settings,
    cart,
    increaseQty,
    decreaseQty,
    updateQty,
    scrollToAndHighlightProduct,
  } = useStore();

  const [itemsPerPage, setItemsPerPage] = useState(() => {
    if (typeof window === 'undefined') return 6;
    if (window.innerWidth < 640) return 3;
    if (window.innerWidth < 1024) return 4;
    return 6;
  });

  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth < 640) {
        setItemsPerPage(3);
      } else if (window.innerWidth < 1024) {
        setItemsPerPage(4);
      } else {
        setItemsPerPage(6);
      }
    };
    handleResize();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Gather all products from categories
  const allProducts = (categories || []).flatMap((cat) =>
    (cat.products || []).map((p) => ({
      ...p,
      categoryName: cat.name,
      categorySlug: cat.slug,
    }))
  );

  // Filter most sold / best seller products
  const flaggedBestsellers = allProducts.filter(
    (p) => p.is_bestseller || p.is_featured || p.is_popular || p.featured || (p.sales_count && p.sales_count > 0)
  );

  // Fallback to top products if no specific bestseller flag is set by admin
  const bestsellers = flaggedBestsellers.length > 0 ? flaggedBestsellers : allProducts.slice(0, 18);

  const totalPages = Math.max(1, Math.ceil(bestsellers.length / itemsPerPage));

  const [activeSlide, setActiveSlide] = useState(0);

  // Auto-slide every 4 seconds
  useEffect(() => {
    if (totalPages <= 1) return;
    const timer = setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % totalPages);
    }, 4000);
    return () => clearInterval(timer);
  }, [totalPages]);

  const handlePrev = () => {
    setActiveSlide((prev) => (prev === 0 ? totalPages - 1 : prev - 1));
  };

  const handleNext = () => {
    setActiveSlide((prev) => (prev + 1) % totalPages);
  };

  const formatCurrency = (val) => {
    return parseFloat(val || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
  };

  const getSlideItems = (page) => {
    const startIdx = page * itemsPerPage;
    return bestsellers.slice(startIdx, startIdx + itemsPerPage);
  };

  if (settings?.enable_most_sold === 'no' || bestsellers.length === 0) return null;

  return (
    <div className="w-full mb-2.5 select-none">
      <div 
        className="rounded-2xl border border-slate-200/80 p-3 sm:p-4 md:p-5 shadow-xs relative overflow-hidden bg-white" 
        style={{ backgroundColor: '#ffffff' }}
      >
        {/* Soft Sparkle Background Details */}
        <div className="absolute inset-0 pointer-events-none opacity-15">
          <div className="absolute top-2 left-6 w-2 h-2 bg-amber-400 rounded-full animate-ping"></div>
          <div className="absolute bottom-4 right-10 w-2.5 h-2.5 bg-crimson-500 rotate-45 animate-pulse"></div>
        </div>

        {/* Section Header */}
        <div className="mb-4 text-center relative z-10 flex items-center justify-center">
          <h3 className="text-lg sm:text-xl md:text-2xl font-black text-slate-900 tracking-tight">
            Most Sold <span className="text-indigo-600 font-black uppercase">PRODUCTS</span>
          </h3>
        </div>

        {/* Products Grid Slider with Left & Right Flanking Buttons */}
        <div className="relative z-10">
          {/* Left Arrow Button */}
          {totalPages > 1 && (
            <button
              type="button"
              onClick={handlePrev}
              className="absolute -left-2 sm:-left-4 top-1/2 -translate-y-1/2 z-20 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/95 border border-amber-200 text-slate-700 hover:bg-crimson-600 hover:text-white hover:border-crimson-600 shadow-md flex items-center justify-center transition-all active:scale-95"
              aria-label="Previous most sold products"
            >
              <i className="fa-solid fa-chevron-left text-[10px] sm:text-xs"></i>
            </button>
          )}

          {/* Products Grid */}
          <div className="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-1.5 sm:gap-3 px-1 sm:px-2">
          {getSlideItems(activeSlide).map((prod) => {
            const cartItem = cart[prod.id];
            const qty = cartItem ? cartItem.qty : 0;
            const discountPercent = settings.discount_percent || 60;
            const mrp = parseFloat(prod.mrp || 0);
            const sellingPrice = parseFloat(prod.selling_price || 0);

            const imgSrc = getImageUrl(prod.image);

            return (
              <div
                key={`${activeSlide}-${prod.id}`}
                onClick={() => {
                  if (onPreviewProduct && prod.image) {
                    onPreviewProduct(prod);
                  } else {
                    scrollToAndHighlightProduct(prod);
                  }
                }}
                className={`border rounded-lg sm:rounded-xl p-1.5 sm:p-2 shadow-2xs hover:shadow-md transition-all duration-300 flex flex-col justify-between relative group cursor-pointer overflow-hidden min-h-[160px] sm:min-h-[200px] ${
                  qty > 0 ? 'border-crimson-500 ring-2 ring-crimson-500/20' : 'border-slate-200/80 hover:border-gold-400'
                }`}
                style={{
                  backgroundImage: imgSrc ? `url("${imgSrc}")` : 'none',
                  backgroundSize: 'contain',
                  backgroundPosition: 'center',
                  backgroundRepeat: 'no-repeat',
                  backgroundColor: '#ffffff'
                }}
              >
                {/* Top Badges */}
                <div className="flex items-center justify-between gap-0.5 z-10">
                  <span className="bg-gradient-to-r from-crimson-600 to-crimson-700 text-white text-[7px] sm:text-[8.5px] font-black px-1 sm:px-1.5 py-0.5 rounded uppercase tracking-tight sm:tracking-wider shadow-2xs flex items-center gap-0.5 truncate">
                    <i className="fa-solid fa-fire text-[6.5px] sm:text-[7.5px] text-gold-400"></i> <span className="hidden sm:inline">Most Sold</span><span className="sm:hidden">Hot</span>
                  </span>
                  {mrp > sellingPrice && (
                    <span className="bg-gold-500 text-slate-950 text-[7px] sm:text-[8.5px] font-black px-1 sm:px-1.5 py-0.5 rounded uppercase tracking-tight sm:tracking-wider shadow-2xs">
                      {discountPercent}%
                    </span>
                  )}
                </div>

                {/* Middle Spacer for full background image display */}
                <div className="flex-1 min-h-[40px] sm:min-h-[60px]"></div>

                {/* Product Title & Quick Add Controls Overlay */}
                <div className="z-10 bg-white/90 backdrop-blur-xs p-1 rounded-md sm:rounded-lg border border-slate-100/60 shadow-2xs space-y-1">
                  <h4 className="font-black text-[9.5px] sm:text-xs text-slate-900 leading-tight line-clamp-1">
                    {prod.name}
                  </h4>

                  {/* Quick Add / Quantity Controls */}
                  <div className="flex justify-center">
                    {prod.stock_status === 'out_of_stock' || ((prod.manage_stock ?? 'yes') !== 'no' && parseInt(prod.stock_quantity ?? 100) <= 0) ? (
                      <span className="bg-rose-50 border border-rose-200 text-rose-700 font-extrabold text-[7.5px] sm:text-[9px] py-0.5 px-2 rounded-full uppercase tracking-wider text-center block">
                        Out of Stock
                      </span>
                    ) : qty === 0 ? (
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          increaseQty(prod);
                          scrollToAndHighlightProduct(prod);
                        }}
                        className="bg-crimson-600 hover:bg-crimson-700 active:scale-95 text-white font-extrabold text-[8px] sm:text-[9.5px] py-0.5 px-2.5 sm:py-1 sm:px-3 rounded-full transition-all shadow-2xs flex items-center justify-center gap-0.5 leading-none"
                      >
                        <i className="fa-solid fa-plus text-[7px] sm:text-[8px]"></i>
                        <span>Add</span>
                      </button>
                    ) : (
                      <div 
                        onClick={(e) => e.stopPropagation()}
                        className="flex items-center justify-between bg-crimson-50 border border-crimson-200 rounded-full p-0.5 gap-1"
                      >
                        <button
                          type="button"
                          onClick={() => decreaseQty(prod.id)}
                          className="w-3.5 h-3.5 sm:w-4.5 sm:h-4.5 bg-white text-crimson-700 border border-crimson-200 rounded-full flex items-center justify-center hover:bg-crimson-600 hover:text-white transition-colors active:scale-95 shadow-2xs"
                        >
                          <i className="fa-solid fa-minus text-[6.5px] sm:text-[7.5px]"></i>
                        </button>
                        <input
                          type="number"
                          min="0"
                          value={qty}
                          onChange={(e) => updateQty(prod, parseInt(e.target.value) || 0)}
                          className="w-4 sm:w-6 text-center font-black text-[8.5px] sm:text-[10px] text-crimson-900 bg-transparent focus:outline-none"
                        />
                        <button
                          type="button"
                          onClick={() => increaseQty(prod)}
                          className="w-3.5 h-3.5 sm:w-4.5 sm:h-4.5 bg-crimson-600 text-white rounded-full flex items-center justify-center hover:bg-crimson-700 transition-colors active:scale-95 shadow-2xs"
                        >
                          <i className="fa-solid fa-plus text-[6.5px] sm:text-[7.5px]"></i>
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>

          {/* Right Arrow Button */}
          {totalPages > 1 && (
            <button
              type="button"
              onClick={handleNext}
              className="absolute -right-2 sm:-right-4 top-1/2 -translate-y-1/2 z-20 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/95 border border-amber-200 text-slate-700 hover:bg-crimson-600 hover:text-white hover:border-crimson-600 shadow-md flex items-center justify-center transition-all active:scale-95"
              aria-label="Next most sold products"
            >
              <i className="fa-solid fa-chevron-right text-[10px] sm:text-xs"></i>
            </button>
          )}
        </div>

        {/* Carousel Dots */}
        {totalPages > 1 && (
          <div className="flex items-center justify-center gap-1.5 mt-2.5 relative z-10">
            {Array.from({ length: totalPages }).map((_, i) => (
              <button
                key={i}
                type="button"
                onClick={() => setActiveSlide(i)}
                className={`h-1.5 rounded-full transition-all duration-300 ${
                  activeSlide === i
                    ? 'w-5 bg-crimson-600'
                    : 'w-1.5 bg-slate-300 hover:bg-slate-400'
                }`}
                aria-label={`Go to slide ${i + 1}`}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
