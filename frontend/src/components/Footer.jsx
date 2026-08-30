import React from 'react';
import { Link } from 'react-router-dom';
import { useStore } from '../context/StoreContext';
import { getImageUrl } from '../utils/imageUrl';

export default function Footer() {
  const { settings } = useStore();

  const storeName = settings?.store_name || 'Kavin Crackers';
  const storePhone = settings?.store_phone || '(+91) 99449 91600';
  const storeEmail = settings?.store_email || 'jackyjohnson18@gmail.com';
  const storeAddress = settings?.store_address || '9/346/6, Anuppankulam, Sivakasi Satur Main Road, Sivakasi, Anuppankulam, Tamil Nadu - 626 189';
  const licenseNo = settings?.license_no || '----';

  return (
    <footer className="relative bg-[#0B132B] text-white select-none border-t border-slate-800 mt-auto pt-14 pb-8 overflow-hidden print:hidden">
      {/* Soft Background Festive Glow */}
      <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>

      <div className="container mx-auto px-4 sm:px-6 relative z-10">

        {/* TOP SECTION: 3-Column Layout */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">

          {/* COLUMN 1: Store Title & Tagline */}
          <div>
            <h3 className="text-2xl md:text-3xl font-black text-white tracking-tight font-cinzel">
              {storeName}
            </h3>
            <p className="text-xs md:text-sm text-slate-300 leading-relaxed font-medium mt-2">
              {settings?.store_description || `Your trusted destination for premium quality Sivakasi crackers. With over ${settings?.store_experience || '10+'} years of experience, we bring you safe, bright, and affordable fireworks to light up your celebrations. Your safety and happiness are our top priorities. Let's make every festival memorable!`}
            </p>
          </div>

          {/* COLUMN 2: Store Logo Display */}
          <div className="flex flex-col items-center justify-center text-center py-2">
            <Link to="/" className="group inline-block w-full flex justify-center">
              <div className="w-72 sm:w-80 md:w-96 max-h-96 flex items-center justify-center p-2 transition-transform duration-300 group-hover:scale-105">
                {settings?.store_logo ? (
                  <img
                    src={getImageUrl(settings.store_logo)}
                    alt={storeName}
                    className="w-full h-full max-h-80 object-contain drop-shadow-xl"
                  />
                ) : (
                  <div className="flex flex-col items-center justify-center text-gold-400 py-4">
                    <i className="fa-solid fa-fire text-7xl mb-2"></i>
                    <span className="font-black text-2xl text-white tracking-wider uppercase text-center">
                      {storeName}
                    </span>
                  </div>
                )}
              </div>
            </Link>
          </div>

          {/* COLUMN 3: Contact Info */}
          <div className="space-y-4">
            <h3 className="text-xl md:text-2xl font-bold text-white tracking-tight">
              Contact Info
            </h3>

            <div className="space-y-1">
              <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                Shop Location
              </h4>
              <p className="text-xs md:text-sm text-slate-200 font-semibold leading-relaxed">
                {storeAddress}
              </p>
            </div>

            <div className="space-y-1">
              <a
                href={`tel:${storePhone}`}
                className="text-xs md:text-sm text-slate-200 font-bold hover:text-gold-400 transition-colors block"
              >
                <i className="fa-solid fa-phone text-gold-400 mr-2 text-xs"></i>
                {storePhone}
              </a>
            </div>

            <div className="space-y-1">
              <a
                href={`mailto:${storeEmail}`}
                className="text-xs md:text-sm text-slate-200 font-bold hover:text-gold-400 transition-colors block"
              >
                <i className="fa-solid fa-envelope text-gold-400 mr-2 text-xs"></i>
                {storeEmail}
              </a>
            </div>
          </div>

        </div>

        {/* MIDDLE SECTION: Supreme Court Legal Notice */}
        <div className="mt-14 pt-8 border-t border-slate-800 max-w-5xl mx-auto text-center px-2 sm:px-4">
          <p className="text-xs sm:text-[13px] text-slate-300 font-semibold leading-relaxed">
            As per 2018 supreme court order, online sale of firecrackers are not permitted! We value our customers and at the same time, respect jurisdiction. We request you to add your products to the cart and submit the required crackers through the enquiry button. We will contact you within 24 hrs and confirm the order through WhatsApp or phone call. Please add and submit your enquiries and enjoy your Diwali with {storeName}. Our License No.{licenseNo ? ` ${licenseNo}` : '----'}. {storeName} as a company following 100% legal & statutory compliances and all our shops, go-downs are maintained as per the explosive acts. We send the parcels through registered and legal transport service providers as like every other major companies in Sivakasi is doing so.
          </p>
        </div>

        {/* BOTTOM SECTION: Copyright & Admin Link Bar */}
        <div className="mt-8 pt-6 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-400 font-medium gap-3">
          <div>
            Copyright © {new Date().getFullYear()}, {storeName}. All Rights Reserved.
          </div>
          <div className="flex items-center gap-4">
            <Link to="/price-list" className="hover:text-gold-400 transition-colors font-bold">
              Price List Generator
            </Link>
            <span>•</span>
            <Link to="/admin/products" className="hover:text-gold-400 transition-colors font-bold text-amber-300">
              Product Upload (Admin)
            </Link>
          </div>
        </div>

      </div>
    </footer>
  );
}
