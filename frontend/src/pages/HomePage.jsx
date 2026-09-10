import React, { useState } from 'react';
import Header from '../components/Header';
import PriceList from './PriceList';
import AdminImageCompressor from './admin/AdminImageCompressor';

export default function HomePage({ initialTab = 'pricelist' }) {
  const [activeTab, setActiveTab] = useState(initialTab);

  return (
    <div className="min-h-screen flex flex-col bg-slate-50 relative overflow-hidden">
      <Header activeTab={activeTab} setActiveTab={setActiveTab} />
      
      <main className="flex-grow relative z-10">
        {activeTab === 'pricelist' ? (
          <PriceList />
        ) : (
          <div className="p-4 sm:p-8 max-w-7xl mx-auto">
            <AdminImageCompressor noLayout={true} />
          </div>
        )}
      </main>
    </div>
  );
}
