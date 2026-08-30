import React from 'react';
import { Link } from 'react-router-dom';

export default function Header() {
  return (
    <header className="print:hidden bg-indigo-600 shadow-md select-none sticky top-0 z-40">
      <div className="container mx-auto px-4 flex items-center justify-between h-14">
        <Link
          to="/"
          className="flex items-center gap-2.5 text-white font-black text-sm uppercase tracking-wider hover:opacity-90 transition-opacity"
        >
          <div className="w-8 h-8 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm shadow-sm">
            <i className="fa-solid fa-fire text-red-950"></i>
          </div>
          <span>Price List Generator</span>
        </Link>
      </div>
    </header>
  );
}
