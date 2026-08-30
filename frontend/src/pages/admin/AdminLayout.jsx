import React, { useEffect, useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import LoadingScreen from '../../components/LoadingScreen';

export default function AdminLayout({ children }) {
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  const menuItems = [
    { path: '/admin/products', label: 'Products & Excel Upload', icon: 'fa-box-open' },
  ];

  return (
    <div className="flex flex-col lg:flex-row min-h-screen bg-slate-100 text-slate-800 font-sans relative">
      {/* Mobile Top Navbar (Hidden on desktop) */}
      <header className="lg:hidden w-full bg-slate-900 text-white px-4 py-3 flex items-center justify-between shadow-md select-none sticky top-0 z-40">
        <div className="flex items-center gap-3">
          <button
            onClick={() => setIsMobileMenuOpen(true)}
            className="text-slate-300 hover:text-white p-1 text-lg focus:outline-none"
            title="Open Menu"
          >
            <i className="fa-solid fa-bars"></i>
          </button>
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-crimson-600 flex items-center justify-center text-white text-sm font-black shadow-md">
              <i className="fa-solid fa-screwdriver-wrench"></i>
            </div>
            <h1 className="text-xs font-black uppercase tracking-wider">Admin Console</h1>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span className="text-[9px] font-black text-slate-400 uppercase tracking-wider">
            Live
          </span>
        </div>
      </header>

      {/* Mobile Sidebar Backdrop Overlay */}
      {isMobileMenuOpen && (
        <div
          className="lg:hidden fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 cursor-pointer"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar navigation */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 lg:z-auto w-64 bg-slate-900 text-slate-300 flex flex-col shadow-xl select-none transform transition-transform duration-300 lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:self-start lg:flex-shrink-0 ${
          isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Branding header */}
        <div className="p-6 border-b border-slate-800 flex items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-crimson-600 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-crimson-900/30">
              <i className="fa-solid fa-screwdriver-wrench"></i>
            </div>
            <div>
              <h1 className="text-sm font-black text-white uppercase tracking-wider">Admin Console</h1>
              <span className="text-[10px] text-slate-500 font-bold">Storefront manager</span>
            </div>
          </div>
          {/* Close button for mobile */}
          <button
            onClick={() => setIsMobileMenuOpen(false)}
            className="lg:hidden text-slate-500 hover:text-slate-350 p-1 text-sm"
            title="Close Menu"
          >
            <i className="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        {/* Menu link list */}
        <nav className="flex-grow p-4 space-y-1.5 overflow-y-auto">
          {menuItems.map((item) => {
            const isActive = location.pathname.startsWith(item.path);
            return (
              <Link
                key={item.path}
                to={item.path}
                onClick={() => setIsMobileMenuOpen(false)}
                className={`flex items-center gap-3.5 px-4 py-3 rounded-xl text-xs font-black uppercase tracking-wider transition-all ${
                  isActive
                    ? 'bg-crimson-600 text-white shadow-md shadow-crimson-600/10'
                    : 'hover:bg-slate-800 text-slate-400 hover:text-slate-200'
                }`}
              >
                <i className={`fa-solid ${item.icon} text-sm ${isActive ? 'text-white' : 'text-slate-500'}`}></i>
                <span>{item.label}</span>
              </Link>
            );
          })}
        </nav>

        {/* Footer actions */}
        <div className="p-4 border-t border-slate-800 space-y-2">
          <Link
            to="/price-list"
            className="flex items-center gap-3 px-4 py-2.5 rounded-xl text-[11px] font-bold text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition-all"
          >
            <i className="fa-solid fa-arrow-up-right-from-square"></i>
            <span>View Price List</span>
          </Link>
        </div>
      </aside>

      {/* Main panel container */}
      <main className="flex-grow flex flex-col min-w-0">
        {/* Main top header (Desktop only) */}
        <header className="hidden lg:flex bg-white border-b border-slate-200 px-8 py-5 items-center justify-between select-none shadow-sm">
          <div>
            <h2 className="text-lg font-black text-slate-800">
              {menuItems.find((item) => location.pathname.startsWith(item.path))?.label || 'Products & Excel Upload'}
            </h2>
            <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
              Control Panel Overview
            </span>
          </div>
          <div className="flex items-center gap-3.5">
            <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span className="text-[11px] font-black text-slate-500 uppercase tracking-widest">
              Live Connection
            </span>
          </div>
        </header>

        {/* Mobile Header Banner */}
        <div className="lg:hidden bg-white border-b border-slate-200 px-4 py-3 select-none flex items-center justify-between shadow-sm">
          <h2 className="text-sm font-black text-slate-800 uppercase tracking-wider">
            {menuItems.find((item) => location.pathname.startsWith(item.path))?.label || 'Dashboard'}
          </h2>
          <span className="text-[9px] text-slate-450 font-bold uppercase tracking-wider">
            Admin Console
          </span>
        </div>

        {/* Page contents area */}
        <div className="p-4 sm:p-8 flex-grow overflow-y-auto">{children}</div>
      </main>
    </div>
  );
}
