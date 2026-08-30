import React, { createContext, useState, useEffect, useContext } from 'react';
import { getImageUrl } from '../utils/imageUrl';

const StoreContext = createContext();

export const useStore = () => useContext(StoreContext);

export const StoreProvider = ({ children }) => {
  const [categories, setCategories] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeCategory, setActiveCategory] = useState('all');
  const [settings, setSettings] = useState({
    store_name: 'Cracker Demo',
    min_order_value: 3800,
    discount_percent: 60,
    store_whatsapp: '919998887776',
    store_phone: '+91 9998887776',
    store_email: 'crackerdemo@gmail.com',
    store_address: 'Virudhunagar to Sivakasi Main Road, Sivakasi',
    enable_min_order: 'yes',
    enable_promo_codes: 'yes',
    enable_tax_delivery: 'no',
    enable_legal_notice: 'yes',
    enable_fireworks: 'yes',
    show_mrp: 'yes',
    card_bg_color: '#FFFFFF',
    default_view_mode: 'flex',
    tax_percent: 18,
    delivery_charge: 150,
  });
  const [loading, setLoading] = useState(true);
  const [cart, setCart] = useState({}); // { productId: { id, qty, mrp, selling_price, name, pack_size } }
  const [checkoutOpen, setCheckoutOpen] = useState(false);
  
  // Promo state
  const [appliedPromo, setAppliedPromo] = useState('');
  const [promoDiscount, setPromoDiscount] = useState(0);
  const [promoMessage, setPromoMessage] = useState('');
  const [promoSuccess, setPromoSuccess] = useState(false);

  // Load store data
  useEffect(() => {
    fetch('/api/storefront')
      .then((res) => res.json())
      .then((data) => {
        if (data.categories) setCategories(data.categories);
        if (data.settings) {
          // Parse numerical settings
          const parsedSettings = {
            ...data.settings,
            min_order_value: parseFloat(data.settings.min_order_value || 3800),
            discount_percent: parseFloat(data.settings.discount_percent || 60),
            tax_percent: parseFloat(data.settings.tax_percent || 18),
            delivery_charge: parseFloat(data.settings.delivery_charge || 150),
          };
          setSettings(parsedSettings);

          // Update dynamic browser favicon if present
          if (data.settings.store_favicon) {
            const faviconUrl = getImageUrl(data.settings.store_favicon);
            const links = document.querySelectorAll("link[rel*='icon']");
            if (links.length > 0) {
              links.forEach((l) => (l.href = faviconUrl));
            } else {
              const link = document.createElement('link');
              link.rel = 'icon';
              link.href = faviconUrl;
              document.head.appendChild(link);
            }
          }

          // Dynamically apply active theme palette in browser
          if (data.settings.admin_theme && window.tailwind) {
            const themeKey = String(data.settings.admin_theme).toLowerCase();
            const themeMaps = {
              theme_1: {
                gold: { 50: '#fffdf0', 100: '#fef7c3', 200: '#fdf196', 300: '#fae459', 400: '#f8d82d', 500: '#e5bf13', 600: '#c2960b', 700: '#9b7009', 800: '#7d560c', 900: '#67460e' },
                crimson: { 50: '#fff1f1', 100: '#ffe1e1', 200: '#ffc7c7', 300: '#ffa0a0', 400: '#ff6969', 500: '#f83b3b', 600: '#e51d1d', 700: '#c01212', 800: '#9f1313', 900: '#831616' }
              },
              theme_2: {
                gold: { 50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f' },
                crimson: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' }
              },
              theme_3: {
                gold: { 50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 400: '#fb923c', 500: '#f97316', 600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12' },
                crimson: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b' }
              },
              theme_4: {
                gold: { 50: '#fefce8', 100: '#fef9c3', 200: '#fef08a', 300: '#fde047', 400: '#facc15', 500: '#eab308', 600: '#ca8a04', 700: '#a16207', 800: '#854d0e', 900: '#713f12' },
                crimson: { 50: '#faf5ff', 100: '#f3e8ff', 200: '#e9d5ff', 300: '#d8b4fe', 400: '#c084fc', 500: '#a855f7', 600: '#9333ea', 700: '#7e22ce', 800: '#6b21a8', 900: '#581c87' }
              },
              theme_5: {
                gold: { 50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e', 800: '#115e59', 900: '#134e4a' },
                crimson: { 50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337' }
              },
              theme_6: {
                gold: { 50: '#fef2f2', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5', 400: '#f87171', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c', 800: '#991b1b', 900: '#7f1d1d' },
                crimson: { 50: '#ecfeff', 100: '#cffafe', 200: '#a5f3fc', 300: '#67e8f9', 400: '#22d3ee', 500: '#06b6d4', 600: '#0891b2', 700: '#0e7490', 800: '#155e75', 900: '#164e63' }
              },
              theme_7: {
                gold: { 50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f' },
                crimson: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d' }
              },
              theme_8: {
                gold: { 50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337' },
                crimson: { 50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e', 800: '#115e59', 900: '#134e4a' }
              },
              theme_9: {
                gold: { 50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f' },
                crimson: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a' }
              },
              theme_10: {
                gold: { 50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 400: '#fb923c', 500: '#f97316', 600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12' },
                crimson: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' }
              }
            };

            if (themeMaps[themeKey]) {
              window.tailwind.config = {
                theme: {
                  extend: {
                    fontFamily: {
                      sans: ['Outfit', 'Poppins', 'sans-serif'],
                    },
                    colors: {
                      gold: themeMaps[themeKey].gold,
                      crimson: themeMaps[themeKey].crimson,
                    }
                  }
                }
              };
            }
          }
        }
        setLoading(false);
      })
      .catch((err) => {
        console.error('Failed to load storefront data:', err);
        setLoading(false);
      });

    // Load cart from local storage
    const savedCart = localStorage.getItem('athi_cart');
    if (savedCart) {
      try {
        setCart(JSON.parse(savedCart));
      } catch (e) {
        setCart({});
      }
    }
  }, []);

  // Save cart to local storage when it changes
  const saveCart = (newCart) => {
    setCart(newCart);
    localStorage.setItem('athi_cart', JSON.stringify(newCart));
  };

  const isProductOutOfStock = (product) => {
    if (!product) return false;
    if (product.stock_status === 'out_of_stock') return true;
    if ((product.manage_stock ?? 'yes') !== 'no' && product.stock_quantity !== null && product.stock_quantity !== undefined) {
      return parseInt(product.stock_quantity) <= 0;
    }
    return false;
  };

  const increaseQty = (product) => {
    if (isProductOutOfStock(product)) {
      if (window.Swal) {
        window.Swal.fire({
          icon: 'warning',
          title: 'Out of Stock',
          text: `Sorry, ${product.name} is currently out of stock.`,
          confirmButtonColor: '#e51d1d',
        });
      }
      return;
    }
    const newCart = { ...cart };
    if (!newCart[product.id]) {
      newCart[product.id] = {
        id: product.id,
        qty: 0,
        mrp: parseFloat(product.mrp),
        selling_price: parseFloat(product.selling_price),
        name: product.name,
        pack_size: product.pack_size,
        image: product.image,
      };
    }
    newCart[product.id].qty += 1;
    saveCart(newCart);
  };

  const decreaseQty = (productId) => {
    const newCart = { ...cart };
    if (newCart[productId]) {
      newCart[productId].qty -= 1;
      if (newCart[productId].qty <= 0) {
        delete newCart[productId];
      }
      saveCart(newCart);
    }
  };

  const updateQty = (product, qty) => {
    const newCart = { ...cart };
    const parsedQty = parseInt(qty);
    if (parsedQty > 0 && isProductOutOfStock(product)) {
      if (window.Swal) {
        window.Swal.fire({
          icon: 'warning',
          title: 'Out of Stock',
          text: `Sorry, ${product.name} is currently out of stock.`,
          confirmButtonColor: '#e51d1d',
        });
      }
      return;
    }
    if (isNaN(parsedQty) || parsedQty <= 0) {
      if (newCart[product.id]) {
        delete newCart[product.id];
        saveCart(newCart);
      }
    } else {
      newCart[product.id] = {
        id: product.id,
        qty: parsedQty,
        mrp: parseFloat(product.mrp),
        selling_price: parseFloat(product.selling_price),
        name: product.name,
        pack_size: product.pack_size,
        image: product.image,
      };
      saveCart(newCart);
    }
  };

  const clearCart = () => {
    saveCart({});
    setAppliedPromo('');
    setPromoDiscount(0);
    setPromoMessage('');
    setPromoSuccess(false);
  };

  // Cart Calculations
  let totalQty = 0;
  let totalMrp = 0;
  let totalNet = 0;
  let totalUniqueProducts = 0;

  Object.values(cart).forEach((item) => {
    if (item.qty > 0) {
      totalQty += item.qty;
      totalMrp += item.mrp * item.qty;
      totalNet += item.selling_price * item.qty;
      totalUniqueProducts += 1;
    }
  });

  const totalDiscount = totalMrp - totalNet;

  // Handle Promo Code logic
  const applyPromoCode = (code) => {
    const cleanCode = code.trim().toUpperCase();
    if (!cleanCode) {
      setAppliedPromo('');
      setPromoDiscount(0);
      setPromoMessage('');
      setPromoSuccess(false);
      return;
    }

    // Find code in settings
    let matchedCode = null;
    let matchedValue = null;

    for (let i = 1; i <= 5; i++) {
      const codeSetting = settings[`promo_code_${i}`];
      if (codeSetting && codeSetting.toUpperCase() === cleanCode) {
        matchedCode = codeSetting;
        matchedValue = settings[`promo_value_${i}`];
        break;
      }
    }

    if (matchedCode) {
      setAppliedPromo(matchedCode);
      setPromoSuccess(true);

      const valStr = matchedValue.trim();
      let discount = 0;
      if (valStr.includes('%')) {
        const pct = parseFloat(valStr.replace('%', ''));
        if (pct > 0) {
          discount = (totalNet * pct) / 100;
        }
      } else {
        discount = parseFloat(valStr);
      }

      const finalDiscount = Math.min(discount, totalNet);
      setPromoDiscount(finalDiscount);
      setPromoMessage(`Code applied! You saved ₹${finalDiscount.toFixed(2)}`);
    } else {
      setAppliedPromo('');
      setPromoDiscount(0);
      setPromoMessage('Invalid promo code.');
      setPromoSuccess(false);
    }
  };

  // Recalculate promo discount when net total changes
  useEffect(() => {
    if (appliedPromo) {
      applyPromoCode(appliedPromo);
    } else {
      setPromoDiscount(0);
    }
  }, [totalNet, appliedPromo]);

  const postPromoNet = Math.max(0, totalNet - promoDiscount);

  const enableTaxDelivery = settings.enable_tax_delivery === 'yes';
  const taxAmount = enableTaxDelivery ? postPromoNet * (settings.tax_percent / 100) : 0;
  const deliveryCharge = (enableTaxDelivery && totalQty > 0) ? settings.delivery_charge : 0;
  const finalPayableAmount = postPromoNet + taxAmount + deliveryCharge;

  // View mode state - directly reflects Admin Panel setting
  const [viewMode, setViewMode] = useState('flex');

  useEffect(() => {
    if (settings.default_view_mode) {
      setViewMode(settings.default_view_mode);
    }
  }, [settings.default_view_mode]);

  const changeViewMode = (mode) => {
    setViewMode(mode);
  };

  // Compute number of filtered products currently shown
  let totalFilteredProductsCount = 0;
  categories.forEach((cat) => {
    if (activeCategory === 'all' || activeCategory === cat.slug) {
      if (Array.isArray(cat.products)) {
        cat.products.forEach((prod) => {
          if (!searchQuery.trim() || prod.name.toLowerCase().includes(searchQuery.toLowerCase())) {
            totalFilteredProductsCount++;
          }
        });
      }
    }
  });

  const [highlightedProductId, setHighlightedProductId] = useState(null);

  const scrollToAndHighlightProduct = (prod) => {
    if (!prod || !prod.id) return;

    // Resolve category slug if missing on product object
    let catSlug = prod.categorySlug;
    if (!catSlug && categories) {
      const foundCat = categories.find((c) => (c.products || []).some((p) => p.id === prod.id));
      if (foundCat) catSlug = foundCat.slug;
    }

    // Reset category filter if it would hide this product
    if (catSlug && activeCategory !== 'all' && activeCategory !== catSlug) {
      setActiveCategory('all');
    }

    // Clear search filter if it excludes this product
    if (searchQuery && prod.name && !prod.name.toLowerCase().includes(searchQuery.toLowerCase())) {
      setSearchQuery('');
    }

    // Set highlight ID
    setHighlightedProductId(prod.id);

    // Smooth scroll to visible product element with retry loop
    const tryScroll = (attemptsLeft = 6) => {
      const elements = document.querySelectorAll(
        `[data-product-id="${prod.id}"], #product-mobile-${prod.id}, #product-row-${prod.id}, #product-grid-${prod.id}, #product-${prod.id}, #product-card-${prod.id}`
      );

      let targetElement = null;
      for (const el of elements) {
        // Find element that is currently visible in DOM layout
        if (el.offsetParent !== null || el.offsetWidth > 0 || el.offsetHeight > 0) {
          targetElement = el;
          break;
        }
      }

      if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else if (attemptsLeft > 0) {
        setTimeout(() => tryScroll(attemptsLeft - 1), 100);
      } else {
        const tableSection = document.getElementById('quick-order');
        if (tableSection) {
          tableSection.scrollIntoView({ behavior: 'smooth' });
        }
      }
    };

    setTimeout(() => tryScroll(6), 100);

    // Remove highlight after 3 seconds
    setTimeout(() => {
      setHighlightedProductId((prev) => (prev === prod.id ? null : prev));
    }, 3000);
  };

  const value = {
    categories,
    setCategories,
    settings,
    loading,
    cart,
    increaseQty,
    decreaseQty,
    updateQty,
    clearCart,
    totalQty,
    totalMrp,
    totalNet,
    totalUniqueProducts,
    totalDiscount,
    appliedPromo,
    promoDiscount,
    promoMessage,
    promoSuccess,
    applyPromoCode,
    postPromoNet,
    taxAmount,
    deliveryCharge,
    finalPayableAmount,
    searchQuery,
    setSearchQuery,
    activeCategory,
    setActiveCategory,
    checkoutOpen,
    setCheckoutOpen,
    viewMode,
    setViewMode,
    changeViewMode,
    totalFilteredProductsCount,
    highlightedProductId,
    scrollToAndHighlightProduct,
  };

  return <StoreContext.Provider value={value}>{children}</StoreContext.Provider>;
};
