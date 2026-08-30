import React from 'react';
import { useStore } from '../context/StoreContext';

export default function StatsHighlights() {
  const { settings } = useStore();

  const stats = [
    {
      icon: 'fa-solid fa-clock',
      value: settings?.store_experience || '10+',
      label: 'Year Of Experience',
    },
    {
      icon: 'fa-solid fa-wand-magic-sparkles',
      value: '150 +',
      label: 'Products',
    },
    {
      icon: 'fa-solid fa-users',
      value: '500 +',
      label: 'Happy Customers',
    },
    {
      icon: 'fa-solid fa-heart',
      value: '100%',
      label: 'Satisfaction',
    },
  ];

  return (
    <section className="container mx-auto px-4 py-8 select-none z-10 relative">
      <div
        className="rounded-3xl p-6 sm:p-8 md:p-12 shadow-sm overflow-hidden relative border border-gold-200/80"
        style={{
          backgroundColor: '#ffffff'
        }}
      >
        {/* Soft Floating Theme Gold Sparks */}
        <div className="absolute inset-0 pointer-events-none opacity-20">
          <div className="absolute top-4 left-10 w-2 h-2 bg-gold-400 rounded-full animate-ping"></div>
          <div className="absolute top-12 right-20 w-3 h-3 bg-gold-500 rotate-45 animate-pulse"></div>
          <div className="absolute bottom-6 left-1/4 w-2 h-4 bg-gold-500 -rotate-12"></div>
          <div className="absolute top-10 left-1/3 w-3 h-1.5 bg-gold-400 rotate-45"></div>
          <div className="absolute bottom-10 right-1/3 w-2 h-3 bg-gold-500 rotate-12"></div>
          <div className="absolute top-6 right-10 w-2.5 h-2.5 bg-gold-400 rounded-full animate-bounce"></div>
        </div>

        {/* Top Banner Text matching Wholesale Ordering banner style */}
        <div
          data-aos="zoom-in"
          className="relative z-10 mb-10 md:mb-12 rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-center text-white shadow-lg overflow-hidden bg-gradient-to-r from-crimson-700 via-crimson-600 to-crimson-800 border border-crimson-800"
        >
          {/* Subtle Sparkle Lights */}
          <div className="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
          <div className="absolute -bottom-10 -left-10 w-40 h-40 bg-gold-400/20 rounded-full blur-3xl pointer-events-none"></div>

          <h3 className="relative z-10 text-base sm:text-xl md:text-2xl lg:text-3xl font-black italic tracking-wide leading-relaxed font-sans max-w-4xl mx-auto text-white drop-shadow-md">
            "We are the first and best choice for your cracker's needs. Call us and get your delivery on your convenient day."
          </h3>
        </div>

        {/* 4 Stat Cards Grid with Theme-Synced Icon Badges */}
        <div className="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 md:gap-8 pt-4">
          {stats.map((item, idx) => (
            <div
              key={idx}
              data-aos="fade-up"
              data-aos-delay={(idx + 1) * 100}
              className="group relative bg-white rounded-2xl p-5 pt-8 shadow-lg hover:shadow-2xl border-2 border-slate-100 hover:border-gold-400 flex flex-col items-center text-center transition-all duration-500 hover:-translate-y-2 cursor-pointer"
            >

              {/* Circular Theme Gold Icon Badge with Dark Border */}
              <div className="absolute -top-6 w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-full bg-gold-400 group-hover:bg-gold-300 border-2 border-slate-900 flex items-center justify-center shadow-md group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                <i className={`${item.icon} text-slate-950 text-base sm:text-lg md:text-xl`}></i>
              </div>

              {/* Stat Value */}
              <div className="mt-3 sm:mt-4 text-2xl sm:text-3xl md:text-4xl font-black text-crimson-600 font-sans tracking-tight group-hover:scale-105 transition-transform duration-300">
                {item.value}
              </div>

              {/* Stat Label */}
              <div className="mt-1 text-xs sm:text-sm font-extrabold text-slate-700 tracking-wide">
                {item.label}
              </div>
            </div>
          ))}
        </div>

      </div>
    </section>
  );
}
