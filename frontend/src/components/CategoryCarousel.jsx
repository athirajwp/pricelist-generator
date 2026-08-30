import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useStore } from '../context/StoreContext';

export default function CategoryCarousel() {
  const navigate = useNavigate();
  const { setActiveCategory } = useStore();

  const handleCategoryClick = (categorySlug) => {
    if (setActiveCategory) setActiveCategory(categorySlug);
    navigate('/quick-order');
  };

  const productItems = [
    {
      name: 'Rockets',
      slug: 'rockets',
      image: '/img/categories/rockets.webp',
      fallbackIcon: 'fa-rocket',
      gradient: 'from-amber-400 to-rose-500'
    },
    {
      name: 'Sparklers',
      slug: 'sparklers',
      image: '/img/categories/sparklers.webp',
      fallbackIcon: 'fa-wand-magic-sparkles',
      gradient: 'from-yellow-300 to-amber-500'
    },
    {
      name: 'Fountains',
      slug: 'fountains-novelties',
      image: '/img/categories/fountain.webp',
      fallbackIcon: 'fa-volcano',
      gradient: 'from-emerald-400 to-teal-600'
    },
    {
      name: 'Gift Boxes',
      slug: 'gift-boxes',
      image: '/img/categories/giftbox.webp',
      fallbackIcon: 'fa-box-open',
      gradient: 'from-purple-500 to-indigo-600'
    },
    {
      name: 'Ground Chakkar',
      slug: 'ground-chakkars',
      image: '/img/categories/chakkar.webp',
      fallbackIcon: 'fa-dharmachakra',
      gradient: 'from-pink-500 to-rose-600'
    },
    {
      name: 'Flower Pots',
      slug: 'flower-pots',
      image: '/img/categories/flowerpots.webp',
      fallbackIcon: 'fa-fire-burner',
      gradient: 'from-red-500 to-orange-600'
    },
    {
      name: 'Sound Crackers',
      slug: 'sound-crackers',
      image: '/img/categories/soundcrackers.webp',
      fallbackIcon: 'fa-explosion',
      gradient: 'from-blue-500 to-indigo-600'
    }
  ];

  // Screen size state for responsive items count (3 on mobile, 4 on md+)
  const [isMobile, setIsMobile] = useState(
    typeof window !== 'undefined' ? window.innerWidth < 768 : false
  );

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth < 768);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const itemsPerPage = isMobile ? 3 : 4;
  const totalPages = Math.ceil(productItems.length / itemsPerPage);

  const [activePage, setActivePage] = useState(0);

  // Reset active page if out of bounds on resize
  useEffect(() => {
    if (activePage >= totalPages) {
      setActivePage(0);
    }
  }, [totalPages, activePage]);

  // Auto slide every 3.5 seconds
  useEffect(() => {
    const timer = setInterval(() => {
      setActivePage((prev) => (prev + 1) % totalPages);
    }, 3500);
    return () => clearInterval(timer);
  }, [totalPages]);

  // Always return items per page, wrapping around if needed
  const getPageItems = (page) => {
    const startIdx = page * itemsPerPage;
    return Array.from({ length: itemsPerPage }, (_, i) =>
      productItems[(startIdx + i) % productItems.length]
    );
  };

  return (
    <section className="relative py-8 md:py-10 overflow-hidden select-none">

      <div className="container mx-auto px-4">
        <div className="rounded-3xl p-4 sm:p-6 md:p-10 shadow-sm overflow-hidden relative bg-white border border-slate-200/80" style={{ backgroundColor: '#ffffff' }}>

        {/* Background Soft Floating Gold Sparks */}
        <div className="absolute inset-0 pointer-events-none opacity-20">
          <div className="absolute top-4 left-10 w-2 h-2 bg-amber-400 rounded-full animate-ping"></div>
          <div className="absolute top-12 right-20 w-3 h-3 bg-amber-500 rotate-45 animate-pulse"></div>
          <div className="absolute bottom-6 left-1/4 w-2 h-4 bg-gold-500 -rotate-12"></div>
          <div className="absolute top-10 left-1/3 w-3 h-1.5 bg-amber-400 rotate-45"></div>
          <div className="absolute bottom-10 right-1/3 w-2 h-3 bg-amber-500 rotate-12"></div>
          <div className="absolute top-6 right-10 w-2.5 h-2.5 bg-amber-400 rounded-full animate-bounce"></div>
        </div>

        <div className="relative z-10">

        {/* Section Header */}
        <div className="text-center space-y-2 mb-6 md:mb-12" data-aos="fade-up">
          <h2 className="text-2xl md:text-4xl font-black tracking-tight text-slate-900 font-sans">
            Shop Our <span className="text-crimson-600">Products</span>
          </h2>
          <p className="text-[11px] md:text-sm text-slate-500 font-semibold tracking-wider">
            Click any category to start quick ordering
          </p>
        </div>

        {/* 3 items on mobile (grid-cols-3), 4 items on desktop (md:grid-cols-4) */}
        <div className="grid grid-cols-3 md:grid-cols-4 gap-1.5 sm:gap-4 lg:gap-6 items-start max-w-4xl mx-auto">
          {getPageItems(activePage).map((item, idx) => (
            <div
              key={`${activePage}-${idx}`}
              onClick={() => handleCategoryClick(item.slug)}
              data-aos="zoom-in"
              data-aos-delay={(idx + 1) * 100}
              className="group cursor-pointer flex flex-col items-center transition-transform duration-500 hover:-translate-y-2"
            >

              {/* Circular Pod (20% Larger on mobile: 100px) */}
              <div className="relative w-[100px] h-[100px] sm:w-[118px] sm:h-[118px] md:w-[143px] md:h-[143px] bg-white rounded-full shadow-lg group-hover:shadow-2xl border-4 border-slate-200/80 group-hover:border-gold-400 flex items-center justify-center p-1 sm:p-2 transition-all duration-300 group-hover:scale-105 overflow-hidden mx-auto">

                {/* Soft Background Gradient */}
                <div className="absolute inset-0 bg-gradient-to-b from-white via-amber-50/30 to-amber-100/40 rounded-full"></div>

                {/* Product Image */}
                <div className="relative z-10 w-full h-full flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                  <img
                    src={item.image}
                    alt={item.name}
                    onError={(e) => {
                      e.target.onerror = null;
                      e.target.style.display = 'none';
                      e.target.nextSibling.style.display = 'flex';
                    }}
                    className="w-full h-full object-contain p-0.5 filter drop-shadow-[0_6px_12px_rgba(0,0,0,0.2)]"
                  />
                  <div className="hidden w-12 h-12 rounded-full bg-gradient-to-tr from-amber-400 to-rose-500 items-center justify-center text-white shadow-lg">
                    <i className={`fa-solid ${item.fallbackIcon} text-xl`}></i>
                  </div>
                </div>
              </div>

              {/* Category Label Button (20% Larger on mobile: 98px) */}
              <button
                type="button"
                className="mt-2 sm:mt-3 w-[98px] sm:w-[112px] md:w-[130px] bg-gold-500 hover:bg-gold-400 text-slate-950 font-black py-1 sm:py-1.5 px-1 rounded-full text-[9px] sm:text-[10px] md:text-xs shadow-md group-hover:shadow-gold-500/40 transition-all duration-300 uppercase tracking-wide text-center border border-gold-400 truncate"
              >
                {item.name}
              </button>
            </div>
          ))}
        </div>

        {/* Carousel Navigation Dots */}
        <div className="flex items-center justify-center gap-3 mt-6 md:mt-8">
          {Array.from({ length: totalPages }).map((_, i) => (
            <button
              key={i}
              onClick={() => setActivePage(i)}
              className={`w-3.5 h-3.5 rounded-full transition-all duration-300 ${
                activePage === i
                  ? 'bg-crimson-600 scale-125 shadow-md shadow-crimson-900/30'
                  : 'bg-slate-300 hover:bg-slate-400'
              }`}
              aria-label={`Carousel page ${i + 1}`}
            />
          ))}
        </div>

        </div>

        </div>
      </div>
    </section>
  );
}
