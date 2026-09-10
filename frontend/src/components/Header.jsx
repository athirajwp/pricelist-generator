import React from 'react';
import { Link } from 'react-router-dom';

export default function Header({ activeTab = 'pricelist', setActiveTab }) {
  return (
    <header className="print:hidden bg-slate-900 text-white shadow-lg select-none sticky top-0 z-40 border-b border-slate-800">
      <div className="container mx-auto px-4 flex flex-col sm:flex-row items-center justify-between py-3 gap-3">
        {/* Brand Logo */}
        <Link
          to="/"
          onClick={() => setActiveTab && setActiveTab('pricelist')}
          className="flex items-center gap-2.5 text-white font-black text-sm uppercase tracking-wider hover:opacity-90 transition-opacity"
        >
          <div className="w-8 h-8 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm shadow-sm">
            <i className="fa-solid fa-fire text-red-950"></i>
          </div>
          <span>Price List Generator</span>
        </Link>

        {/* Top Header Navigation Toggle Buttons */}
        {setActiveTab && (
          <div className="flex items-center gap-1.5 bg-slate-800/90 p-1.5 rounded-2xl border border-slate-700/80 shadow-inner">
            <button
              type="button"
              onClick={() => setActiveTab('pricelist')}
              className={`px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer ${
                activeTab === 'pricelist'
                  ? 'bg-crimson-600 text-white shadow-md shadow-crimson-600/30'
                  : 'text-slate-400 hover:text-white hover:bg-slate-700/50'
              }`}
            >
              <i className="fa-solid fa-file-invoice-dollar text-sm"></i>
              <span>Price List Generator</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('compressor')}
              className={`px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer ${
                activeTab === 'compressor'
                  ? 'bg-crimson-600 text-white shadow-md shadow-crimson-600/30'
                  : 'text-slate-400 hover:text-white hover:bg-slate-700/50'
              }`}
            >
              <i className="fa-solid fa-file-image text-sm"></i>
              <span>Image Compressor</span>
            </button>
          </div>
        )}
      </div>
    </header>
  );
}
