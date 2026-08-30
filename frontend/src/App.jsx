import React, { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Outlet, useLocation, Navigate } from 'react-router-dom';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { StoreProvider, useStore } from './context/StoreContext';
import Header from './components/Header';
import PriceList from './pages/PriceList';
import LoadingScreen from './components/LoadingScreen';

// Admin imports
import AdminProducts from './pages/admin/AdminProducts';

function PublicLayout() {
  const { loading, settings } = useStore();

  useEffect(() => {
    if (settings?.enable_aos === 'no') {
      AOS.init({ disable: true });
    } else {
      AOS.init({
        disable: false,
        duration: 800,
        easing: 'ease-out-cubic',
        once: false,
        offset: 40,
      });
      AOS.refresh();
    }
  }, [settings?.enable_aos]);

  if (loading) {
    return <LoadingScreen />;
  }

  return (
    <div className="min-h-screen flex flex-col bg-slate-50 relative overflow-hidden">
      <Header />
      <main className="flex-grow relative z-10">
        <Outlet />
      </main>
    </div>
  );
}

function ScrollToTop() {
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);

  return null;
}

function App() {
  return (
    <StoreProvider>
      <Router>
        <ScrollToTop />
        <Routes>
          {/* Main Application Pages */}
          <Route element={<PublicLayout />}>
            <Route path="/" element={<PriceList />} />
            <Route path="/price-list" element={<PriceList />} />
            <Route path="/price_list" element={<PriceList />} />
          </Route>

          {/* Catch-all redirect to Pricelist Generator */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Router>
    </StoreProvider>
  );
}

export default App;

