import React, { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, useLocation, Navigate } from 'react-router-dom';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { StoreProvider, useStore } from './context/StoreContext';
import LoadingScreen from './components/LoadingScreen';

// Pages
import HomePage from './pages/HomePage';

// Admin imports
import AdminProducts from './pages/admin/AdminProducts';
import AdminImageCompressor from './pages/admin/AdminImageCompressor';

function MainApp() {
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
    <Routes>
      {/* Home Page Routes with Top Tab Switcher */}
      <Route path="/" element={<HomePage initialTab="pricelist" />} />
      <Route path="/price-list" element={<HomePage initialTab="pricelist" />} />
      <Route path="/price_list" element={<HomePage initialTab="pricelist" />} />
      <Route path="/image-compressor" element={<HomePage initialTab="compressor" />} />
      <Route path="/compressor" element={<HomePage initialTab="compressor" />} />

      {/* Admin Routes */}
      <Route path="/admin/products" element={<AdminProducts />} />
      <Route path="/admin/image-compressor" element={<AdminImageCompressor />} />
      <Route path="/admin/compressor" element={<AdminImageCompressor />} />

      {/* Catch-all redirect to Home */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
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
        <MainApp />
      </Router>
    </StoreProvider>
  );
}

export default App;
