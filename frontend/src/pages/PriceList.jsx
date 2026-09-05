import React, { useState, useEffect, useRef } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useStore } from '../context/StoreContext';
import { getImageUrl } from '../utils/imageUrl';
import AdminProducts from './admin/AdminProducts';
import { generateReactPDFBlob } from '../components/PriceListPDFDocument';
import { sortProductsByCode, sortCategoriesByProductCode } from '../utils/productSorter';
import { batchTranslateCategoriesToTamil, translateEnglishToTamil } from '../utils/translator';

export default function PriceList({ defaultTab }) {
  const location = useLocation();
  const navigate = useNavigate();
  const { categories, setCategories, settings, setSettings, loading } = useStore();

  const [selectedCategory, setSelectedCategory] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [showMrp, setShowMrp] = useState(true);
  const [currentTheme, setCurrentTheme] = useState('royal_festive');

  const isPdfMode = typeof window !== 'undefined' && (window.location.search.includes('print_pdf=1') || window.location.search.includes('puppeteer=1'));

  // Edit Details Drawer state
  const [showEditDrawer, setShowEditDrawer] = useState(false);
  const [savingSettings, setSavingSettings] = useState(false);

  // Product Action Modals State
  const [productModalOpen, setProductModalOpen] = useState(false);
  const [productImageFile, setProductImageFile] = useState(null);
  const [productFormData, setProductFormData] = useState({
    category_id: '',
    product_code: '',
    name: '',
    pack_size: '',
    mrp: 0,
    discount_percent: 60,
    selling_price: 0,
    sort_order: '',
    status: 'active',
    is_bestseller: false,
    stock_quantity: 100,
    min_stock_alert: 10,
    manage_stock: 'yes',
  });

  // Excel Import state
  const [importModalOpen, setImportModalOpen] = useState(false);
  const [importFile, setImportFile] = useState(null);
  const [importing, setImporting] = useState(false);
  const [importResult, setImportResult] = useState(null);
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef(null);

  // Project Management States & Refs
  const [savedProjects, setSavedProjects] = useState([]);
  const [activeProjectId, setActiveProjectId] = useState(null);
  const [activeProjectName, setActiveProjectName] = useState('');
  const [showProjectsModal, setShowProjectsModal] = useState(false);
  const [showSaveAsModal, setShowSaveAsModal] = useState(false);
  const [newProjectNameInput, setNewProjectNameInput] = useState('');
  const [projectSearchQuery, setProjectSearchQuery] = useState('');
  const projectFileInputRef = useRef(null);

  // Load Saved Projects from localStorage on initialization
  useEffect(() => {
    try {
      const stored = localStorage.getItem('pricelist_saved_projects');
      if (stored) {
        setSavedProjects(JSON.parse(stored));
      }
    } catch (err) {
      console.error('Error loading saved projects from localStorage:', err);
    }
  }, []);

  // Save current state into a project snapshot
  const handleSaveCurrentProject = (customName = null) => {
    const projName = (customName || activeProjectName || editForm.store_name || 'My Price List Project').trim();
    const projId = activeProjectId || `proj_${Date.now()}`;
    const timestamp = new Date().toISOString();

    const snapshot = {
      id: projId,
      name: projName,
      createdAt: activeProjectId ? (savedProjects.find((p) => p.id === activeProjectId)?.createdAt || timestamp) : timestamp,
      updatedAt: timestamp,
      editForm: { ...editForm },
      categories: JSON.parse(JSON.stringify(categories || [])),
      colWidths: { ...colWidths },
      showMrp: showMrp,
      productCount: (categories || []).reduce((acc, cat) => acc + (cat.products?.length || 0), 0),
    };

    let updatedList;
    const existingIdx = savedProjects.findIndex((p) => p.id === projId);
    if (existingIdx >= 0) {
      updatedList = [...savedProjects];
      updatedList[existingIdx] = snapshot;
    } else {
      updatedList = [snapshot, ...savedProjects];
    }

    setSavedProjects(updatedList);
    setActiveProjectId(projId);
    setActiveProjectName(projName);
    localStorage.setItem('pricelist_saved_projects', JSON.stringify(updatedList));

    if (window.Swal) {
      window.Swal.fire({
        icon: 'success',
        title: 'Project Saved!',
        text: `"${projName}" has been saved successfully.`,
        timer: 1800,
        showConfirmButton: false,
      });
    }
    setShowSaveAsModal(false);
  };

  // Open & restore a saved project
  const handleOpenProject = (project) => {
    if (!project) return;
    if (project.editForm) {
      setEditForm(project.editForm);
    }
    if (project.categories && setCategories) {
      setCategories(project.categories);
    }
    if (project.colWidths) {
      setColWidths(project.colWidths);
    }
    if (project.showMrp !== undefined) {
      setShowMrp(project.showMrp);
    }
    setActiveProjectId(project.id);
    setActiveProjectName(project.name);
    setShowProjectsModal(false);

    if (window.Swal) {
      window.Swal.fire({
        icon: 'success',
        title: 'Project Loaded!',
        text: `"${project.name}" has been loaded into the editor.`,
        timer: 1800,
        showConfirmButton: false,
      });
    }
  };

  // Duplicate a project
  const handleDuplicateProject = (project) => {
    const copyName = `${project.name} (Copy)`;
    const copyId = `proj_${Date.now()}`;
    const copy = {
      ...project,
      id: copyId,
      name: copyName,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    };
    const updated = [copy, ...savedProjects];
    setSavedProjects(updated);
    localStorage.setItem('pricelist_saved_projects', JSON.stringify(updated));
  };

  // Delete a project
  const handleDeleteProject = (projectId) => {
    if (window.Swal) {
      window.Swal.fire({
        title: 'Delete Saved Project?',
        text: 'Are you sure you want to delete this project snapshot?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Delete',
      }).then((result) => {
        if (result.isConfirmed) {
          const updated = savedProjects.filter((p) => p.id !== projectId);
          setSavedProjects(updated);
          localStorage.setItem('pricelist_saved_projects', JSON.stringify(updated));
          if (activeProjectId === projectId) {
            setActiveProjectId(null);
            setActiveProjectName('');
          }
        }
      });
    }
  };

  // Export project snapshot as JSON file download
  const handleExportProjectJson = (project) => {
    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(project, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute('href', dataStr);
    downloadAnchor.setAttribute('download', `${project.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_project.json`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
  };

  // Import project from JSON file
  const handleImportProjectFile = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      try {
        const parsed = JSON.parse(event.target.result);
        if (parsed && (parsed.editForm || parsed.categories)) {
          const importedProj = {
            ...parsed,
            id: `proj_${Date.now()}`,
            name: parsed.name ? `${parsed.name} (Imported)` : 'Imported Project',
            updatedAt: new Date().toISOString(),
          };
          const updated = [importedProj, ...savedProjects];
          setSavedProjects(updated);
          localStorage.setItem('pricelist_saved_projects', JSON.stringify(updated));
          handleOpenProject(importedProj);
        } else {
          alert('Invalid project JSON file format.');
        }
      } catch (err) {
        alert('Failed to parse project JSON file.');
      }
    };
    reader.readAsText(file);
    e.target.value = null;
  };

  // Excel-style draggable column widths state (in px)
  const [colWidths, setColWidths] = useState({
    sno: 45,
    product: 220,
    product_ta: 220,
    unit: 95,
    mrp: 80,
    offer: 105,
    req: 45,
  });

  const handleColumnResizeStart = (dividerKey, e) => {
    e.preventDefault();
    e.stopPropagation();

    const startX = e.clientX;
    const startWidths = { ...colWidths };

    const minWidths = {
      sno: 25,
      product: 80,
      product_ta: 80,
      unit: 40,
      mrp: 40,
      offer: 50,
      req: 25,
    };

    let leftCol = dividerKey;
    let rightCol = null;
    if (dividerKey === 'sno') rightCol = 'product';
    else if (dividerKey === 'product') rightCol = editForm.show_tamil_name ? 'product_ta' : 'unit';
    else if (dividerKey === 'product_ta') rightCol = 'unit';
    else if (dividerKey === 'unit') rightCol = showMrp ? 'mrp' : 'offer';
    else if (dividerKey === 'mrp') rightCol = 'offer';
    else if (dividerKey === 'offer') rightCol = 'req';

    if (!rightCol) return;

    const leftMin = minWidths[leftCol] || 25;
    const rightMin = minWidths[rightCol] || 25;

    const startLeftW = startWidths[leftCol] || 80;
    const startRightW = startWidths[rightCol] || 80;

    const maxDeltaRight = startRightW - rightMin;
    const maxDeltaLeft = leftMin - startLeftW;

    const onMouseMove = (moveEvent) => {
      const deltaX = moveEvent.clientX - startX;
      const clampedDelta = Math.max(maxDeltaLeft, Math.min(maxDeltaRight, deltaX));

      setColWidths((prev) => ({
        ...prev,
        [leftCol]: startLeftW + clampedDelta,
        [rightCol]: startRightW - clampedDelta,
      }));
    };

    const onMouseUp = () => {
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup', onMouseUp);
    };

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
  };

  const [editForm, setEditForm] = useState({
    store_name: 'MASS CRACKERS',
    store_tagline: 'Ready for the Sparkle',
    store_invocation_symbol: 'உ',
    store_invocation: 'சங்கிலி மாடசாமி துணை, கொமண்டியம்மன் துணை',
    store_year: String(new Date().getFullYear()),
    store_email: 'www.masscrackers.com',
    store_phone: '63837 22887',
    store_phone_2: '97877 72038',
    store_phone_3: '97877 72038',
    store_address: 'Virudhunagar to Sivakasi Main Road, Opposite to Nayagara Petrol Bulk, Amathur - 626005.',
    discount_percent: 50,
    bank_name: 'Muthusamy Ganesan',
    bank_branch: 'IDBI Bank',
    bank_account_no: '1118104000136815',
    bank_ifsc: 'IBKL0001118',
    footer_position: 'below_table',
    show_bank_details: true,
    show_upi_qr: true,
    show_tamil_name: false,
    header_product: 'PRODUCT NAME (ENG)',
    header_product_ta: 'பொருள் பெயர் (TAMIL)',
    important_note_1: 'தொடர்ந்து பல ஆண்டுகளாக எங்கள் நிறுவன பட்டாசுகளை வாங்கி தீபாவளியை குடும்பத்தினருடன் கொண்டாடி மகிழும் உங்கள் அனைவருக்கும் இனிய தீபாவளி நல்வாழ்த்துக்கள்!',
    important_note_2: 'வரவிருக்கும் தீபாவளி பண்டிகைக்கான பட்டாசுகளை அக்டோபர் 15 - ஆம் தேதிக்குள் ஆர்டர் செய்து பெற்றுக்கொள்ளுமாறு வேண்டுகிறோம்.',
    store_title_color: '#FFFFFF',
    store_tagline_color: '#FFFFFF',
    store_invocation_color: '#FFFFFF',
    store_badge_color: '#0F172A',
    store_upi_qr: '',
    store_upi_qr_2: '',
    store_gpay: '9787772038',
    store_gpay_2: '',
    store_upi_name: 'Muthusamy Ganesan',
    store_upi_name_2: '',
    store_qr_1_title: 'GPay / Primary QR',
    store_qr_2_title: 'PhonePe / Secondary QR',
  });

  useEffect(() => {
    if (settings) {
      setEditForm({
        store_name: settings.store_name || 'MASS CRACKERS',
        store_tagline: settings.store_tagline || 'Ready for the Sparkle',
        store_invocation_symbol: settings.store_invocation_symbol !== undefined ? settings.store_invocation_symbol : 'உ',
        store_invocation: settings.store_invocation || 'சங்கிலி மாடசாமி துணை, கொமண்டியம்மன் துணை',
        store_name_font: settings.store_name_font || 'cinzel',
        store_deity_preset: settings.store_deity_preset || 'vinayagar',
        store_deity_image: settings.store_deity_image || '',
        store_cover_bg: settings.store_cover_bg || '',
        store_logo: settings.store_logo || '',
        store_upi_qr: settings.store_upi_qr || '',
        store_upi_qr_2: settings.store_upi_qr_2 || '',
        store_year: settings.store_year || String(new Date().getFullYear()),
        store_email: settings.store_email || 'www.masscrackers.com',
        store_phone: settings.store_phone || '8682942042',
        store_phone_2: settings.store_phone_2 || '8682942042',
        store_phone_3: settings.store_phone_3 || '',
        store_phone_4: settings.store_phone_4 || '',
        store_gpay: settings.store_gpay || '9787772038',
        store_gpay_2: settings.store_gpay_2 || '',
        store_upi_name: settings.store_upi_name !== undefined ? settings.store_upi_name : 'Muthusamy Ganesan',
        store_upi_name_2: settings.store_upi_name_2 || '',
        store_qr_1_title: settings.store_qr_1_title || 'GPay / Primary QR',
        store_qr_2_title: settings.store_qr_2_title || 'PhonePe / Secondary QR',
        table_row_height: settings.table_row_height || 22,
        table_col_padding: settings.table_col_padding || 4,
        store_address: settings.store_address || 'Virudhunagar to Sivakasi Main Road, Opposite to Nayagara Petrol Bulk, Amathur - 626005.',
        discount_percent: settings.discount_percent !== undefined ? settings.discount_percent : 50,
        show_bank_details: settings.show_bank_details !== undefined ? settings.show_bank_details : true,
        show_upi_qr: settings.show_upi_qr !== undefined ? settings.show_upi_qr : true,
        show_tamil_name: settings.show_tamil_name !== undefined ? settings.show_tamil_name : false,
        header_product: settings.header_product || 'PRODUCT NAME (ENG)',
        header_product_ta: settings.header_product_ta || 'பொருள் பெயர் (TAMIL)',
        bank_name: settings.bank_name || settings.bank_holder || 'Muthusamy Ganesan',
        bank_branch: settings.bank_branch || settings.bank_acc_name || 'IDBI Bank',
        bank_account_no: settings.bank_account_no || settings.bank_acc_no || '1118104000136815',
        bank_ifsc: settings.bank_ifsc || 'IBKL0001118',
        footer_position: settings.footer_position || 'below_table',
        max_tr_per_page: settings.max_tr_per_page || 30,
        important_note_1: settings.important_note_1 || 'தொடர்ந்து பல ஆண்டுகளாக எங்கள் நிறுவன பட்டாசுகளை வாங்கி தீபாவளியை குடும்பத்தினருடன் கொண்டாடி மகிழும் உங்கள் அனைவருக்கும் இனிய தீபாவளி நல்வாழ்த்துக்கள்!',
        important_note_2: settings.important_note_2 || 'வரவிருக்கும் தீபாவளி பண்டிகைக்கான பட்டாசுகளை அக்டோபர் 15 - ஆம் தேதிக்குள் ஆர்டர் செய்து பெற்றுக்கொள்ளுமாறு வேண்டுகிறோம்.',
        store_title_color: settings.store_title_color || '#FFFFFF',
        store_tagline_color: settings.store_tagline_color || '#FFFFFF',
        store_invocation_color: settings.store_invocation_color || '#FFFFFF',
        store_badge_color: settings.store_badge_color || '#0F172A',
      });
    }
  }, [settings]);

  const [translatingTamil, setTranslatingTamil] = useState(false);

  const handleAutoTranslateTamil = async (overwrite = false, currentCats = categories) => {
    const targetCats = currentCats || categories;
    if (!targetCats || !targetCats.length) return;
    setTranslatingTamil(true);

    if (window.Swal) {
      window.Swal.fire({
        title: 'Translating Product Names...',
        text: 'Generating Tamil translations for English product names',
        allowOutsideClick: false,
        didOpen: () => {
          window.Swal.showLoading();
        },
      });
    }

    try {
      const { categories: updatedCategories, modifiedCount } = await batchTranslateCategoriesToTamil(targetCats, overwrite);
      if (setCategories) {
        setCategories(updatedCategories);
      }

      // Collect updated products payload to persist to backend
      const bulkPayload = [];
      updatedCategories.forEach((cat) => {
        cat.products?.forEach((prod) => {
          if (prod.id && prod.name_ta) {
            bulkPayload.push({
              id: prod.id,
              name_ta: prod.name_ta,
            });
          }
        });
      });

      if (bulkPayload.length > 0) {
        try {
          await fetch('/api/admin/products/bulk-update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ products: bulkPayload }),
          });
        } catch (apiErr) {
          console.warn('Backend persistence error:', apiErr);
        }
      }

      if (window.Swal) {
        window.Swal.fire({
          icon: 'success',
          title: 'Tamil Names Generated!',
          text: `Auto-generated Tamil names for ${modifiedCount > 0 ? modifiedCount + ' products' : 'all products'}.`,
          timer: 2000,
          showConfirmButton: false,
        });
      }
    } catch (err) {
      console.error('Auto translation error:', err);
      if (window.Swal) {
        window.Swal.fire({
          icon: 'error',
          title: 'Translation Failed',
          text: 'Unable to auto-translate names right now.',
        });
      }
    } finally {
      setTranslatingTamil(false);
    }
  };

  const handleInputChange = (field, value) => {
    setEditForm((prev) => ({ ...prev, [field]: value }));
  };

  const getDeityImageUrl = () => {
    if (editForm.store_deity_image) {
      return getImageUrl(editForm.store_deity_image);
    }
    return null;
  };

  const getDeityFilterStyle = () => {
    return {
      filter: 'drop-shadow(0 20px 35px rgba(0, 0, 0, 0.85))',
    };
  };

  const getThemeAccentColor = (bgPath) => {
    const bg = bgPath || '/images/cover_bg_1.jpg';
    if (bg === 'none') {
      return { textClass: 'text-slate-800', bgClass: 'bg-slate-800', hex: '#1e293b' };
    } else if (bg.includes('cover_bg_2.jpg')) {
      return { textClass: 'text-blue-600', bgClass: 'bg-blue-600', hex: '#2563eb' };
    } else if (bg.includes('cover_bg_3.jpg')) {
      return { textClass: 'text-emerald-600', bgClass: 'bg-emerald-600', hex: '#059669' };
    } else if (bg.includes('cover_bg_4.jpg')) {
      return { textClass: 'text-purple-600', bgClass: 'bg-purple-600', hex: '#9333ea' };
    } else if (bg.includes('cover_bg_5.jpg')) {
      return { textClass: 'text-sky-600', bgClass: 'bg-sky-500', hex: '#0284c7' };
    } else if (bg.includes('cover_bg_6.jpg')) {
      return { textClass: 'text-amber-600', bgClass: 'bg-amber-500', hex: '#d97706' };
    } else if (bg.includes('cover_bg_7.jpg')) {
      return { textClass: 'text-indigo-600', bgClass: 'bg-indigo-700', hex: '#4338ca' };
    } else if (bg.includes('cover_bg_8.jpg')) {
      return { textClass: 'text-rose-600', bgClass: 'bg-rose-600', hex: '#e11d48' };
    } else if (bg.includes('cover_bg_blue.jpg')) {
      return { textClass: 'text-slate-900', bgClass: 'bg-slate-800', hex: '#0f172a' };
    } else if (bg.includes('cover_bg_green.jpg')) {
      return { textClass: 'text-teal-600', bgClass: 'bg-teal-600', hex: '#0d9488' };
    }
    return { textClass: 'text-red-600', bgClass: 'bg-red-600', hex: '#dc2626' };
  };

  const getStoreNameFontFamily = () => {
    const font = editForm.store_name_font || 'cinzel';
    switch (font) {
      case 'cinzel':
        return "'Cinzel Decorative', 'Cinzel', serif";
      case 'black':
        return "'Montserrat', 'Inter', sans-serif";
      case 'playfair':
        return "'Playfair Display', serif";
      case 'outfit':
        return "'Outfit', sans-serif";
      default:
        return "'Cinzel Decorative', serif";
    }
  };

  const renderFormattedText = (text, defaultClass = "") => {
    if (!text) return null;
    let safeText = String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
    let formatted = safeText
      .replace(/\*\*\*(.*?)\*\*\*/g, "<strong><em>$1</em></strong>")
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      .replace(/__(.*?)__/g, "<strong>$1</strong>")
      .replace(/\*(.*?)\*/g, "<em>$1</em>")
      .replace(/_(.*?)_/g, "<em>$1</em>");
    return (
      <div
        className={`whitespace-pre-wrap ${defaultClass}`}
        dangerouslySetInnerHTML={{ __html: formatted }}
      />
    );
  };

  const handleSaveSettings = async (e) => {
    e?.preventDefault();
    setSavingSettings(true);

    try {
      const response = await fetch('/api/admin/settings/update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          ...settings,
          ...editForm,
          min_order_value: settings?.min_order_value || 0,
          store_whatsapp: editForm.store_phone,
          enable_min_order: settings?.enable_min_order || 'no',
          enable_promo_codes: settings?.enable_promo_codes || 'no',
          enable_tax_delivery: settings?.enable_tax_delivery || 'no',
        }),
      });

      const data = await response.json();
      if (data.success || response.ok) {
        if (setSettings) {
          setSettings((prev) => ({ ...prev, ...editForm }));
        }
        if (window.Swal) {
          window.Swal.fire({
            title: 'Details Saved!',
            text: 'Price List shop details updated successfully.',
            icon: 'success',
            confirmButtonColor: '#e51d1d',
            timer: 2000,
            showConfirmButton: false,
          });
        }
      }
    } catch (err) {
      console.log('Saved locally:', err);
      if (setSettings) {
        setSettings((prev) => ({ ...prev, ...editForm }));
      }
      if (window.Swal) {
        window.Swal.fire({
          title: 'Details Updated!',
          text: 'Price List details updated for live preview.',
          icon: 'success',
          confirmButtonColor: '#e51d1d',
          timer: 1500,
          showConfirmButton: false,
        });
      }
    } finally {
      setSavingSettings(false);
    }
  };

  // Product Action Handlers
  const handleDownloadTemplate = () => {
    window.location.href = '/api/admin/products/export?include_data=false';
  };

  const handleExportProducts = () => {
    window.location.href = '/api/admin/products/export?include_data=true';
  };

  const handleOpenImportModal = () => {
    setImportFile(null);
    setImportResult(null);
    setImportModalOpen(true);
  };

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      setImportFile(e.dataTransfer.files[0]);
      setImportResult(null);
    }
  };

  const handleFileSelect = (e) => {
    if (e.target.files && e.target.files[0]) {
      setImportFile(e.target.files[0]);
      setImportResult(null);
    }
  };

  const handleImportSubmit = async () => {
    if (!importFile) return;
    setImporting(true);
    setImportResult(null);

    const postData = new FormData();
    postData.append('file', importFile);

    try {
      const res = await fetch('/api/admin/products/import', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
        },
        body: postData,
      });
      const data = await res.json();
      if (res.ok) {
        setImportResult(data);
        setTimeout(() => {
          window.location.reload();
        }, 1200);
      } else {
        setImportResult({ error: data.error || data.message || 'Import failed. Please check your Excel file.' });
      }
    } catch (err) {
      setImportResult({ error: 'Network error. Could not connect to server.' });
    } finally {
      setImporting(false);
    }
  };

  const handleOpenAddModal = () => {
    setProductFormData({
      category_id: categories[0]?.id || '',
      product_code: '',
      name: '',
      pack_size: '',
      mrp: 0,
      discount_percent: 60,
      selling_price: 0,
      sort_order: '',
      status: 'active',
      is_bestseller: false,
      stock_quantity: 100,
      min_stock_alert: 10,
      manage_stock: 'yes',
    });
    setProductImageFile(null);
    setProductModalOpen(true);
  };

  const handleProductSubmit = async (e) => {
    e.preventDefault();
    const postData = new FormData();
    postData.append('category_id', productFormData.category_id);
    if (productFormData.product_code) postData.append('product_code', productFormData.product_code);
    postData.append('name', productFormData.name);
    postData.append('pack_size', productFormData.pack_size);
    postData.append('mrp', productFormData.mrp);
    postData.append('selling_price', productFormData.selling_price);
    if (productFormData.sort_order !== '') postData.append('sort_order', productFormData.sort_order);
    postData.append('status', productFormData.status);
    postData.append('is_bestseller', productFormData.is_bestseller ? '1' : '0');
    postData.append('stock_quantity', productFormData.stock_quantity ?? 100);
    postData.append('min_stock_alert', productFormData.min_stock_alert ?? 10);
    postData.append('manage_stock', productFormData.manage_stock || 'yes');
    if (productImageFile) postData.append('image', productImageFile);

    try {
      const res = await fetch('/api/admin/products/store', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: postData,
      });
      const data = await res.json();
      if (res.ok) {
        setProductModalOpen(false);
        if (window.Swal) {
          window.Swal.fire({ icon: 'success', title: 'Product Created!', showConfirmButton: false, timer: 1500 });
        }
        window.location.reload();
      } else {
        if (window.Swal) {
          window.Swal.fire({ icon: 'error', title: 'Operation Failed', text: data.error || data.message || 'Check input fields.' });
        }
      }
    } catch (err) {
      if (window.Swal) {
        window.Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save product.' });
      }
    }
  };

  // Excel-Style Inline Cell Editing Handlers
  const handleInlineProductChange = (productId, field, value) => {
    if (setCategories) {
      setCategories((prevCategories) =>
        prevCategories.map((cat) => ({
          ...cat,
          products: cat.products.map((p) =>
            p.id === productId ? { ...p, [field]: value } : p
          ),
        }))
      );
    }
  };

  const handleInlineMrpChange = (productId, value) => {
    const numericMrp = parseFloat(value) || 0;
    const currentDisc = editForm.discount_percent !== undefined ? editForm.discount_percent : 50;
    const newOffer = Math.round(numericMrp * (1 - currentDisc / 100));
    if (setCategories) {
      setCategories((prevCategories) =>
        prevCategories.map((cat) => ({
          ...cat,
          products: cat.products.map((p) =>
            p.id === productId
              ? { ...p, mrp: value, selling_price: newOffer }
              : p
          ),
        }))
      );
    }
  };

  const handleInlineOfferChange = (productId, value) => {
    if (setCategories) {
      setCategories((prevCategories) =>
        prevCategories.map((cat) => ({
          ...cat,
          products: cat.products.map((p) =>
            p.id === productId ? { ...p, selling_price: value } : p
          ),
        }))
      );
    }
  };

  const handleInlineCategoryChange = (categoryId, value) => {
    if (setCategories) {
      setCategories((prevCategories) =>
        prevCategories.map((cat) =>
          cat.id === categoryId ? { ...cat, name: value } : cat
        )
      );
    }
  };

  const handleInlineProductSave = async (productId, field, value) => {
    try {
      const payload = { [field]: value };
      if (field === 'name' && editForm.show_tamil_name && value && value.trim()) {
        let targetProd = null;
        categories?.forEach((c) => {
          const found = c.products?.find((p) => p.id === productId);
          if (found) targetProd = found;
        });
        if (targetProd && (!targetProd.name_ta || !targetProd.name_ta.trim())) {
          const autoTa = await translateEnglishToTamil(value);
          if (autoTa) {
            payload.name_ta = autoTa;
            handleInlineProductChange(productId, 'name_ta', autoTa);
          }
        }
      }

      await fetch(`/api/admin/products/${productId}/quick-update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
    } catch (err) {
      console.error('Inline save error:', err);
    }
  };

  const handleInlineMrpSave = async (productId, mrpValue) => {
    const numericMrp = parseFloat(mrpValue) || 0;
    const currentDisc = editForm.discount_percent !== undefined ? editForm.discount_percent : 50;
    const newOffer = Math.round(numericMrp * (1 - currentDisc / 100));
    try {
      await fetch(`/api/admin/products/${productId}/quick-update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mrp: numericMrp, selling_price: newOffer }),
      });
    } catch (err) {
      console.error('Inline MRP save error:', err);
    }
  };

  const handleInlineCategorySave = async (categoryId, value) => {
    try {
      await fetch(`/api/admin/categories/${categoryId}/quick-update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: value }),
      });
    } catch (err) {
      console.error('Category inline save error:', err);
    }
  };

  // Excel Grid Keyboard Navigation Handler (Enter, Tab, Arrow keys)
  const handleExcelGridKeyDown = (e, rowIdx, colIdx) => {
    const maxCol = (showMrp ? 5 : 4) + (editForm.show_tamil_name ? 1 : 0);

    if (e.key === 'Enter') {
      e.preventDefault();
      const targetRow = e.shiftKey ? rowIdx - 1 : rowIdx + 1;
      const targetEl = document.querySelector(`[data-excel-row="${targetRow}"][data-excel-col="${colIdx}"]`);
      if (targetEl) {
        targetEl.focus();
        if (targetEl.select) targetEl.select();
      }
    } else if (e.key === 'Tab') {
      let targetRow = rowIdx;
      let targetCol = e.shiftKey ? colIdx - 1 : colIdx + 1;
      if (targetCol < 0) {
        targetCol = maxCol;
        targetRow = rowIdx - 1;
      } else if (targetCol > maxCol) {
        targetCol = 0;
        targetRow = rowIdx + 1;
      }
      const targetEl = document.querySelector(`[data-excel-row="${targetRow}"][data-excel-col="${targetCol}"]`);
      if (targetEl) {
        e.preventDefault();
        targetEl.focus();
        if (targetEl.select) targetEl.select();
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const targetEl = document.querySelector(`[data-excel-row="${rowIdx - 1}"][data-excel-col="${colIdx}"]`);
      if (targetEl) {
        targetEl.focus();
        if (targetEl.select) targetEl.select();
      }
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      const targetEl = document.querySelector(`[data-excel-row="${rowIdx + 1}"][data-excel-col="${colIdx}"]`);
      if (targetEl) {
        targetEl.focus();
        if (targetEl.select) targetEl.select();
      }
    } else if (e.key === 'ArrowLeft') {
      const isAtStart = e.target.selectionStart === 0 && e.target.selectionEnd === 0;
      if (isAtStart && colIdx > 0) {
        e.preventDefault();
        const targetEl = document.querySelector(`[data-excel-row="${rowIdx}"][data-excel-col="${colIdx - 1}"]`);
        if (targetEl) {
          targetEl.focus();
          if (targetEl.select) targetEl.select();
        }
      }
    } else if (e.key === 'ArrowRight') {
      const isAtEnd = e.target.selectionStart === (e.target.value || '').length;
      if (isAtEnd && colIdx < maxCol) {
        e.preventDefault();
        const targetEl = document.querySelector(`[data-excel-row="${rowIdx}"][data-excel-col="${colIdx + 1}"]`);
        if (targetEl) {
          targetEl.focus();
          if (targetEl.select) targetEl.select();
        }
      }
    } else if (e.key === 'Escape') {
      e.target.blur();
    }
  };

  // Excel Clipboard TSV Multi-Cell / Multi-Row Paste Handler
  const handleExcelGridPaste = async (e, startRowIdx, startColIdx) => {
    const text = e.clipboardData ? e.clipboardData.getData('text/plain') : '';
    if (!text || (!text.includes('\t') && !text.includes('\n'))) {
      return;
    }

    e.preventDefault();

    const rawRows = text.split(/\r?\n/).filter((r) => r.length > 0);
    const matrix = rawRows.map((r) => r.split('\t'));

    if (matrix.length === 0 || !allFilteredProducts.length) return;

    const fields = editForm.show_tamil_name
      ? (showMrp
        ? ['product_code', 'name', 'name_ta', 'pack_size', 'mrp', 'selling_price', 'req']
        : ['product_code', 'name', 'name_ta', 'pack_size', 'selling_price', 'req'])
      : (showMrp
        ? ['product_code', 'name', 'pack_size', 'mrp', 'selling_price', 'req']
        : ['product_code', 'name', 'pack_size', 'selling_price', 'req']);

    const currentDisc = editForm.discount_percent !== undefined ? editForm.discount_percent : 50;

    const updatedProductsMap = {};
    const bulkSavePayload = [];

    matrix.forEach((rowCells, rOffset) => {
      const targetRowIdx = startRowIdx + rOffset;
      if (targetRowIdx >= allFilteredProducts.length) return;

      const targetProduct = allFilteredProducts[targetRowIdx];
      if (!targetProduct) return;

      const prodUpdate = updatedProductsMap[targetProduct.id] || { id: targetProduct.id };

      rowCells.forEach((cellVal, cOffset) => {
        const targetColIdx = startColIdx + cOffset;
        if (targetColIdx >= fields.length) return;

        const fieldName = fields[targetColIdx];
        const trimmedVal = cellVal.trim();

        if (fieldName === 'mrp') {
          const numericMrp = parseFloat(trimmedVal) || 0;
          const newOffer = Math.round(numericMrp * (1 - currentDisc / 100));
          prodUpdate.mrp = trimmedVal;
          prodUpdate.selling_price = newOffer;
        } else {
          prodUpdate[fieldName] = trimmedVal;
        }
      });

      updatedProductsMap[targetProduct.id] = prodUpdate;
      bulkSavePayload.push(prodUpdate);
    });

    if (setCategories) {
      setCategories((prevCategories) =>
        prevCategories.map((cat) => ({
          ...cat,
          products: cat.products.map((p) =>
            updatedProductsMap[p.id] ? { ...p, ...updatedProductsMap[p.id] } : p
          ),
        }))
      );
    }

    try {
      await fetch('/api/admin/products/bulk-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ products: bulkSavePayload }),
      });
      if (window.Swal) {
        window.Swal.fire({
          icon: 'success',
          title: 'Pasted from Excel!',
          text: `Updated ${bulkSavePayload.length} row(s) and ${matrix[0].length} column(s).`,
          timer: 1800,
          showConfirmButton: false,
        });
      }
    } catch (err) {
      console.error('Error batch updating products from paste:', err);
    }
  };

  // Re-number all S.No sequentially 1 to N
  const handleAutoNumberSno = async () => {
    let globalCounter = 1;
    const bulkSavePayload = [];

    const newCategories = (categories || []).map((cat) => ({
      ...cat,
      products: (cat.products || []).map((p) => {
        const newCode = String(globalCounter++);
        bulkSavePayload.push({ id: p.id, product_code: newCode });
        return { ...p, product_code: newCode };
      }),
    }));

    if (setCategories) {
      setCategories(newCategories);
    }

    try {
      await fetch('/api/admin/products/bulk-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ products: bulkSavePayload }),
      });
      if (window.Swal) {
        window.Swal.fire({
          icon: 'success',
          title: 'S.No Re-Sequenced!',
          text: `Auto-numbered ${bulkSavePayload.length} products sequentially (1 to ${bulkSavePayload.length}).`,
          timer: 1800,
          showConfirmButton: false,
        });
      }
    } catch (err) {
      console.error('Error re-sequencing S.No:', err);
    }
  };
  // Canva-style Drag Resize Handler for Header Elements (Logo, Contact, Discount, Address)
  const handleElementResizeStart = (elementKey, currentScale, e) => {
    e.preventDefault();
    e.stopPropagation();
    const startX = e.clientX;
    const startY = e.clientY;
    const startScale = currentScale || 100;

    const onMouseMove = (moveEvent) => {
      const deltaX = moveEvent.clientX - startX;
      const deltaY = moveEvent.clientY - startY;
      const delta = Math.abs(deltaX) > Math.abs(deltaY) ? deltaX : deltaY;
      const newScale = Math.min(250, Math.max(40, Math.round(startScale + delta * 0.5)));
      handleInputChange(`${elementKey}_scale`, newScale);
    };

    const onMouseUp = () => {
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup', onMouseUp);
    };

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
  };



  const handleDeleteAllProducts = async () => {
    const confirmDelete = window.Swal
      ? await window.Swal.fire({
        title: 'Delete All Products?',
        text: 'This will permanently delete ALL products from your database. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete All!',
        cancelButtonText: 'Cancel',
      })
      : { isConfirmed: window.confirm('Are you sure you want to delete ALL products? This action cannot be undone!') };

    if (confirmDelete.isConfirmed) {
      try {
        const res = await fetch('/api/admin/products/delete-all', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (res.ok && data.success) {
          if (window.Swal) {
            window.Swal.fire({
              icon: 'success',
              title: 'All Products Deleted!',
              text: 'Product database cleared successfully.',
              showConfirmButton: false,
              timer: 1500,
            });
          }
          window.location.reload();
        } else {
          if (window.Swal) {
            window.Swal.fire({
              icon: 'error',
              title: 'Failed to Delete',
              text: data.error || data.message || 'Could not delete products.',
            });
          }
        }
      } catch (err) {
        if (window.Swal) {
          window.Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Network error while deleting products.',
          });
        }
      }
    }
  };

  const downloadPDF = () => {
    window.print();
  };

  const themes = {
    royal_festive: {
      name: 'Royal Festive',
      icon: 'fa-dharmachakra',
      bannerGradient: 'from-amber-600 via-red-600 to-crimson-800',
      categoryBar: 'bg-gradient-to-r from-amber-400 via-amber-300 to-amber-400 text-slate-950 font-black border-y border-amber-600',
      tableHeader: 'bg-gradient-to-r from-amber-100 via-amber-50 to-amber-100 text-black font-black border-b-2 border-amber-400',
      docBorder: 'border-0',
      cardBg: '#FFFFFF',
      accentText: 'text-crimson-700',
      badgeBg: 'bg-red-600 text-white',
    },
    modern_minimalist: {
      name: 'Modern Minimalist',
      icon: 'fa-gem',
      bannerGradient: 'from-slate-800 via-indigo-900 to-slate-900',
      categoryBar: 'bg-gradient-to-r from-amber-400 via-amber-300 to-amber-400 text-slate-950 font-black border-y border-amber-600',
      tableHeader: 'bg-gradient-to-r from-amber-100 via-amber-50 to-amber-100 text-black font-black border-b-2 border-amber-400',
      docBorder: 'border-0',
      cardBg: '#FFFFFF',
      accentText: 'text-indigo-700',
      badgeBg: 'bg-indigo-600 text-white',
    },
    emerald_festival: {
      name: 'Emerald Festival',
      icon: 'fa-leaf',
      bannerGradient: 'from-emerald-700 via-teal-800 to-emerald-950',
      categoryBar: 'bg-gradient-to-r from-amber-400 via-amber-300 to-amber-400 text-slate-950 font-black border-y border-amber-600',
      tableHeader: 'bg-gradient-to-r from-amber-100 via-amber-50 to-amber-100 text-black font-black border-b-2 border-amber-400',
      docBorder: 'border-0',
      cardBg: '#FFFFFF',
      accentText: 'text-emerald-700',
      badgeBg: 'bg-emerald-600 text-white',
    },
    golden_deluxe: {
      name: 'Golden Deluxe',
      icon: 'fa-crown',
      bannerGradient: 'from-slate-950 via-amber-950 to-slate-900',
      categoryBar: 'bg-gradient-to-r from-amber-400 via-amber-300 to-amber-400 text-slate-950 font-black border-y border-amber-600',
      tableHeader: 'bg-gradient-to-r from-amber-100 via-amber-50 to-amber-100 text-black font-black border-b-2 border-amber-400',
      docBorder: 'border-0',
      cardBg: '#FFFFFF',
      accentText: 'text-amber-800',
      badgeBg: 'bg-amber-500 text-slate-950',
    },
  };

  const theme = themes[currentTheme] || themes.royal_festive;

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <i className="fa-solid fa-spinner animate-spin text-3xl text-crimson-600"></i>
        <p className="text-sm font-semibold text-slate-500">Loading application...</p>
      </div>
    );
  }

  // 1. Filter Categories by search query & category selection
  const filteredCategories = categories.map((cat) => {
    if (selectedCategory !== 'all' && String(cat.id) !== String(selectedCategory)) {
      return null;
    }
    const filteredProducts = cat.products.filter((p) => {
      const q = searchQuery.toLowerCase().trim();
      if (!q) return true;
      return (
        p.name.toLowerCase().includes(q) ||
        (p.product_code && String(p.product_code).toLowerCase().includes(q))
      );
    });

    if (filteredProducts.length === 0) return null;
    return { ...cat, products: sortProductsByCode(filteredProducts) };
  }).filter(Boolean);

  const sortedFilteredCategories = sortCategoriesByProductCode(filteredCategories);

  // 2. Flatten all filtered products into a single ordered array (preserving exact S.No order)
  const allFilteredProducts = [];
  sortedFilteredCategories.forEach((cat) => {
    cat.products.forEach((p) => {
      allFilteredProducts.push({
        ...p,
        category_id: cat.id,
        category_name: cat.name,
      });
    });
  });

  // 3. Exact 30 TRs per A4 Page Sheet Chunking
  const MAX_TR_PER_PAGE = parseInt(editForm.max_tr_per_page || 30, 10);
  const productPageChunks = [];

  let currentChunkProducts = [];
  let currentChunkTrCount = 0;
  let currentCatIdInChunk = null;

  allFilteredProducts.forEach((product) => {
    const needsNewCatHeader = product.category_id !== currentCatIdInChunk;
    const trCostForThisProduct = (needsNewCatHeader ? 1 : 0) + 1;

    if (currentChunkTrCount + trCostForThisProduct > MAX_TR_PER_PAGE && currentChunkProducts.length > 0) {
      productPageChunks.push(currentChunkProducts);
      currentChunkProducts = [];
      currentChunkTrCount = 0;
      currentCatIdInChunk = null;
    }

    currentChunkProducts.push(product);
    if (product.category_id !== currentCatIdInChunk) {
      currentChunkTrCount += 1;
      currentCatIdInChunk = product.category_id;
    }
    currentChunkTrCount += 1;
  });

  if (currentChunkProducts.length > 0) {
    productPageChunks.push(currentChunkProducts);
  }

  if (productPageChunks.length === 0) {
    productPageChunks.push([]);
  }

  const totalDocPages = 1 + productPageChunks.length + (editForm.footer_position === 'new_page' ? 1 : 0);
  const cardBgStyle = { backgroundColor: settings?.card_bg_color || '#FFFFFF' };
  const discountPercent = editForm.discount_percent !== undefined ? editForm.discount_percent : (settings?.discount_percent || 50);

  const showSno = editForm.show_col_sno !== false;
  const showProduct = editForm.show_col_product !== false;
  const showTamilName = editForm.show_tamil_name === true;
  const showUnit = editForm.show_col_unit !== false;
  const showMrpCol = showMrp && editForm.show_col_mrp !== false;
  const showOffer = editForm.show_col_offer !== false;
  const showReq = editForm.show_col_req !== false;
  const activeColCount = (showSno ? 1 : 0) + (showProduct ? 1 : 0) + (showTamilName ? 1 : 0) + (showUnit ? 1 : 0) + (showMrpCol ? 1 : 0) + (showOffer ? 1 : 0) + (showReq ? 1 : 0);

  return (
    <div className="w-full max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 py-6 select-none print:p-0 print:m-0 print:max-w-none">
      {/* Enforce Strict International A4 Sheet Dimensions (210mm x 297mm) */}
      <style>{`
        .a4-page-sheet {
          width: 210mm !important;
          height: 297mm !important;
          min-height: 297mm !important;
          max-height: 297mm !important;
          box-sizing: border-box !important;
          overflow: hidden !important;
        }
        @page {
          size: A4 portrait;
          margin: 0 !important;
        }
        @media print {
          html, body, #root, main, div, section {
            overflow: visible !important;
            max-height: none !important;
          }
          html, body, #root, main {
            background: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: auto !important;
            min-height: auto !important;
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
          }
          [data-aos] {
            opacity: 1 !important;
            transform: none !important;
            visibility: visible !important;
            transition: none !important;
            animation: none !important;
          }
          header, footer, nav, aside, button, select, textarea, [role="dialog"], .no-print, .print\\:hidden, .generator-control-panel {
            display: none !important;
          }
          #price-list-document, #price-list-document * {
            visibility: visible !important;
          }
          #price-list-document input {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            color: #000000 !important;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            font-weight: inherit !important;
            font-size: inherit !important;
            text-align: inherit !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
          }
          #price-list-document {
            position: relative !important;
            left: 0 !important;
            top: 0 !important;
            width: 210mm !important;
            margin: 0 auto !important;
            padding: 0 !important;
            display: block !important;
            box-shadow: none !important;
            border: none !important;
            float: none !important;
          }
          #price-list-document > div {
            display: block !important;
            width: 210mm !important;
            max-width: 210mm !important;
            margin: 0 auto !important;
            padding: 0 !important;
          }
          .a4-page-sheet {
            box-sizing: border-box !important;
            width: 210mm !important;
            height: 297mm !important;
            min-height: 297mm !important;
            max-height: 297mm !important;
            margin: 0 auto !important;
            padding: 8mm 10mm !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            overflow: hidden !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
          }
          .a4-page-sheet:first-child {
            page-break-before: avoid !important;
            break-before: avoid !important;
          }
          .a4-page-sheet:last-child {
            page-break-after: avoid !important;
            break-after: avoid !important;
          }
        }
      `}</style>

      <>
        {/* Generator Control Panel */}
        <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm mb-8 space-y-6 print:hidden" style={cardBgStyle}>
          <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-4 border-b border-slate-100">
            <div>
              <h2 className="text-2xl font-black text-slate-900 tracking-tight flex flex-wrap items-center gap-2">
                <i className="fa-solid fa-file-invoice-dollar text-crimson-600"></i> Price List Generator
                {activeProjectName && (
                  <span className="inline-flex items-center gap-1.5 bg-amber-100 border border-amber-300 text-amber-900 font-extrabold text-[11px] px-3 py-1 rounded-full ml-1">
                    <i className="fa-solid fa-folder-check text-amber-600"></i>
                    <span>Project: <strong>{activeProjectName}</strong></span>
                    <button
                      type="button"
                      onClick={() => {
                        setNewProjectNameInput(activeProjectName);
                        setShowSaveAsModal(true);
                      }}
                      className="text-[10px] text-indigo-700 hover:underline font-black ml-1 cursor-pointer uppercase"
                    >
                      Save As
                    </button>
                  </span>
                )}
              </h2>
              <p className="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1">
                A4 Sheet Sized Catalogue ({allFilteredProducts.length} Products across {totalDocPages} A4 Pages • 210mm × 297mm)
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
              {/* SAVE PROJECT Button */}
              <button
                onClick={() => {
                  if (activeProjectId) {
                    handleSaveCurrentProject(activeProjectName);
                  } else {
                    setNewProjectNameInput(editForm.store_name || 'My Price List Project');
                    setShowSaveAsModal(true);
                  }
                }}
                className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold px-4 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
                title="Save current project configuration & products"
              >
                <i className="fa-solid fa-floppy-disk text-sm"></i>
                {activeProjectId ? 'UPDATE PROJECT' : 'SAVE PROJECT'}
              </button>

              {/* SAVED PROJECTS Manager Button */}
              <button
                onClick={() => setShowProjectsModal(true)}
                className="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-3.5 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer border border-amber-400"
                title="View & manage saved projects"
              >
                <i className="fa-solid fa-folder-open text-slate-950 text-sm"></i>
                <span>PROJECTS</span>
                <span className="bg-slate-950 text-amber-400 text-[10px] font-black px-1.5 py-0.5 rounded-full ml-0.5">
                  {savedProjects.length}
                </span>
              </button>

              {/* 1. TEMPLATE Button */}
              <button
                onClick={handleDownloadTemplate}
                className="bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-extrabold px-3 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
                title="Download Excel template"
              >
                <i className="fa-solid fa-file-excel text-emerald-600 text-sm"></i> TEMPLATE
              </button>

              {/* 2. EXPORT Button */}
              <button
                onClick={handleExportProducts}
                className="bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-extrabold px-3 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
                title="Export all products as Excel"
              >
                <i className="fa-solid fa-download text-indigo-600 text-sm"></i> EXPORT
              </button>

              {/* 3. IMPORT EXCEL Button */}
              <button
                onClick={handleOpenImportModal}
                className="bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 text-white font-extrabold px-4 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
              >
                <i className="fa-solid fa-file-arrow-up text-sm"></i> IMPORT EXCEL
              </button>

              {/* 4. ADD PRODUCT Button */}
              <button
                onClick={handleOpenAddModal}
                className="bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 text-white font-extrabold px-4.5 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
              >
                <i className="fa-solid fa-circle-plus text-sm"></i> ADD PRODUCT
              </button>

              {/* 5. DELETE ALL PRODUCTS Button */}
              <button
                onClick={handleDeleteAllProducts}
                className="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-extrabold px-4 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
                title="Permanently delete all products from database"
              >
                <i className="fa-solid fa-trash-can text-sm"></i> DELETE ALL
              </button>


              {/* Edit Details Drawer Button */}
              <button
                onClick={() => setShowEditDrawer(!showEditDrawer)}
                className="flex-1 md:flex-none flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-4 py-2 rounded-xl text-xs uppercase tracking-wider transition-all shadow-xs active:scale-95 border border-amber-400 cursor-pointer"
              >
                <i className="fa-solid fa-pen-to-square"></i> {showEditDrawer ? 'Close Edit Panel' : 'Edit Shop Details'}
              </button>

              {/* Download PDF button */}
              <button
                onClick={downloadPDF}
                className="flex-1 md:flex-none flex items-center justify-center gap-2 bg-crimson-600 hover:bg-crimson-700 text-white font-extrabold px-4 py-2 rounded-xl text-xs uppercase tracking-wider transition-all shadow-xs active:scale-95 cursor-pointer"
              >
                <i className="fa-solid fa-file-pdf"></i> Download PDF
              </button>
            </div>
          </div>

          {/* EXPANDABLE EDIT SHOP DETAILS FORM PANEL */}
          {showEditDrawer && (
            <div className="bg-amber-50/60 border-2 border-amber-300 rounded-2xl p-6 space-y-6 transition-all duration-300 animate-fadeIn">
              <div className="flex justify-between items-center pb-3 border-b border-amber-200">
                <h3 className="text-base font-black text-slate-900 flex items-center gap-2">
                  <i className="fa-solid fa-sliders text-amber-600"></i> Edit Price List Content & Details (Real-time Live Preview)
                </h3>
                <button
                  onClick={handleSaveSettings}
                  disabled={savingSettings}
                  className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black px-5 py-2 rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm active:scale-95"
                >
                  <i className="fa-solid fa-floppy-disk"></i> {savingSettings ? 'Saving...' : 'Save Changes'}
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                {/* Shop Name */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Shop / Company Name</label>
                  <input
                    type="text"
                    value={editForm.store_name}
                    onChange={(e) => handleInputChange('store_name', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Tagline */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Tagline</label>
                  <input
                    type="text"
                    value={editForm.store_tagline}
                    onChange={(e) => handleInputChange('store_tagline', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Company Name Font Style Selector */}
                <div className="md:col-span-2 lg:col-span-3 bg-amber-50/90 p-3.5 rounded-2xl border-2 border-amber-300 space-y-2">
                  <label className="block text-slate-800 font-black text-sm">
                    Company Name Font Style
                  </label>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    {[
                      { id: 'cinzel', label: 'Font Cinzel', family: "'Cinzel Decorative', 'Cinzel', serif" },
                      { id: 'black', label: 'Font Black', family: "'Montserrat', sans-serif" },
                      { id: 'playfair', label: 'Font Playfair', family: "'Playfair Display', serif" },
                      { id: 'outfit', label: 'Font Outfit', family: "'Outfit', sans-serif" },
                    ].map((f) => (
                      <button
                        key={f.id}
                        type="button"
                        onClick={() => handleInputChange('store_name_font', f.id)}
                        className={`p-2.5 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center ${(editForm.store_name_font || 'cinzel') === f.id
                          ? 'bg-amber-500 text-white border-amber-600 shadow-md scale-105'
                          : 'bg-white text-slate-800 border-slate-200 hover:bg-amber-100'
                          }`}
                      >
                        <span className="text-base font-black truncate max-w-full" style={{ fontFamily: f.family }}>
                          ABC Traders
                        </span>
                        <span className="text-[10px] font-bold opacity-90 mt-0.5">{f.label}</span>
                      </button>
                    ))}
                  </div>
                </div>

                {/* Top Invocation Symbol */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Top Invocation Symbol</label>
                  <div className="flex flex-wrap gap-1.5 mb-2">
                    {['உ', '卐', '🕉', '✨', '✝', '☪', 'ੴ', ''].map((sym) => (
                      <button
                        key={sym || 'none'}
                        type="button"
                        onClick={() => handleInputChange('store_invocation_symbol', sym)}
                        className={`px-2.5 py-1 rounded-lg font-black text-xs border transition-all ${editForm.store_invocation_symbol === sym
                          ? 'bg-amber-500 text-white border-amber-600 shadow'
                          : 'bg-white text-slate-800 border-slate-300 hover:bg-amber-50'
                          }`}
                      >
                        {sym || 'None'}
                      </button>
                    ))}
                  </div>
                  <input
                    type="text"
                    value={editForm.store_invocation_symbol || ''}
                    onChange={(e) => handleInputChange('store_invocation_symbol', e.target.value)}
                    placeholder="e.g. உ"
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Tamil Invocation Header */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Tamil Invocation Line</label>
                  <input
                    type="text"
                    value={editForm.store_invocation}
                    onChange={(e) => handleInputChange('store_invocation', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Cover Page Custom Upload Image Section */}
                <div className="bg-amber-50/90 p-4 rounded-2xl border-2 border-amber-300 space-y-3 shadow-sm">
                  <label className="block text-slate-800 font-black text-sm flex items-center justify-between">
                    <span className="flex items-center gap-2">
                      <i className="fa-solid fa-cloud-arrow-up text-amber-600 text-base"></i>
                      Upload Cover Page Image
                    </span>
                    {editForm.store_deity_image && (
                      <button
                        type="button"
                        onClick={() => {
                          handleInputChange('store_deity_image', '');
                          handleInputChange('store_deity_preset', 'none');
                        }}
                        className="text-xs bg-red-100 hover:bg-red-200 text-red-700 font-extrabold px-3 py-1 rounded-xl transition-all border border-red-300 cursor-pointer"
                      >
                        <i className="fa-solid fa-trash-can mr-1"></i> Remove Image
                      </button>
                    )}
                  </label>

                  {editForm.store_deity_image ? (
                    <div className="flex items-center gap-4 bg-white p-3 rounded-xl border-2 border-amber-300 shadow-sm">
                      <img
                        src={getImageUrl(editForm.store_deity_image)}
                        alt="Cover Image Preview"
                        className="h-16 w-16 object-contain rounded-lg border border-slate-200 bg-slate-50 p-1"
                      />
                      <div className="space-y-1">
                        <p className="text-xs font-black text-emerald-700 flex items-center gap-1.5">
                          <i className="fa-solid fa-circle-check"></i> Image Uploaded & Displaying
                        </p>
                        <p className="text-[11px] text-slate-500 font-medium">
                          Displaying live in the center of your cover sheet.
                        </p>
                      </div>
                    </div>
                  ) : (
                    <p className="text-xs text-slate-600 font-semibold">
                      Upload any image file (PNG / JPG / WEBP).
                    </p>
                  )}

                  <input
                    type="file"
                    accept="image/*"
                    onChange={async (e) => {
                      const file = e.target.files[0];
                      if (file) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                          handleInputChange('store_deity_image', event.target.result);
                          handleInputChange('store_deity_preset', 'custom');
                        };
                        reader.readAsDataURL(file);

                        const formData = new FormData();
                        formData.append('store_deity_image', file);
                        try {
                          const res = await fetch('/api/admin/settings/update', {
                            method: 'POST',
                            body: formData,
                          });
                          const data = await res.json();
                          if (data.path) {
                            handleInputChange('store_deity_image', data.path);
                            handleInputChange('store_deity_preset', 'custom');
                          }
                        } catch (err) {
                          console.error(err);
                        }
                      }
                    }}
                    className="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600 cursor-pointer border border-amber-300 rounded-xl bg-white p-1"
                  />
                </div>
                <div className="bg-amber-50/90 p-3.5 rounded-2xl border-2 border-amber-300 space-y-2">
                  <label className="block text-slate-800 font-extrabold text-xs flex items-center gap-2">
                    <i className="fa-solid fa-building-flag text-amber-600"></i>
                    Upload Store Logo (Custom Logo Image)
                  </label>
                  {editForm.store_logo && (
                    <div className="flex items-center gap-3 bg-white p-2 rounded-xl border border-amber-200">
                      <img
                        src={getImageUrl(editForm.store_logo)}
                        alt="Store Logo"
                        className="h-10 w-10 object-contain rounded-lg border border-amber-300 bg-amber-50"
                      />
                      <button
                        type="button"
                        onClick={() => handleInputChange('store_logo', '')}
                        className="text-xs text-red-600 font-extrabold hover:underline"
                      >
                        Remove Logo
                      </button>
                    </div>
                  )}
                  <input
                    type="file"
                    accept="image/*"
                    onChange={(e) => {
                      const file = e.target.files[0];
                      if (file) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                          handleInputChange('store_logo', event.target.result);
                        };
                        reader.readAsDataURL(file);
                      }
                    }}
                    className="block w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-white hover:file:bg-amber-600 cursor-pointer"
                  />
                </div>

                {/* Upload Custom Cover / A4 Background Image */}
                <div className="bg-amber-50/90 p-3.5 rounded-2xl border-2 border-amber-300 space-y-2">
                  <label className="block text-slate-800 font-extrabold text-xs flex items-center gap-2">
                    <i className="fa-solid fa-image text-amber-600"></i>
                    A4 Cover Background Color Options
                  </label>
                  <div className="grid grid-cols-4 sm:grid-cols-6 gap-1.5 mb-2">
                    {[
                      { id: 'none', label: 'None', bg: 'bg-slate-100 border border-slate-300 text-slate-700 font-bold' },
                      { id: '/images/cover_bg_1.jpg', label: '#1 Red', bg: 'bg-red-600' },
                      { id: '/images/cover_bg_2.jpg', label: '#2 Blue', bg: 'bg-blue-600' },
                      { id: '/images/cover_bg_3.jpg', label: '#3 Emerald', bg: 'bg-emerald-600' },
                      { id: '/images/cover_bg_4.jpg', label: '#4 Purple', bg: 'bg-purple-600' },
                      { id: '/images/cover_bg_5.jpg', label: '#5 Sky', bg: 'bg-sky-400' },
                      { id: '/images/cover_bg_6.jpg', label: '#6 Gold', bg: 'bg-amber-500' },
                      { id: '/images/cover_bg_7.jpg', label: '#7 Indigo', bg: 'bg-indigo-700' },
                      { id: '/images/cover_bg_8.jpg', label: '#8 Rose', bg: 'bg-rose-500' },
                      { id: '/images/cover_bg_blue.jpg', label: '#9 Navy', bg: 'bg-slate-800' },
                      { id: '/images/cover_bg_green.jpg', label: '#10 Teal', bg: 'bg-teal-600' },
                    ].map((item) => (
                      <button
                        key={item.id}
                        type="button"
                        onClick={() => handleInputChange('store_cover_bg', item.id)}
                        className={`p-1 rounded-xl border-2 transition-all flex flex-col items-center justify-center gap-1 ${
                          (editForm.store_cover_bg || '/images/cover_bg_1.jpg') === item.id ||
                          (['/images/cover_bg.jpg', '/images/cover_bg_red.jpg'].includes(editForm.store_cover_bg) && item.id === '/images/cover_bg_1.jpg')
                            ? 'border-amber-600 bg-amber-200 shadow-sm scale-105'
                            : 'border-slate-200 bg-white hover:bg-amber-100'
                        }`}
                      >
                        <div className={`w-full h-8 rounded-lg ${item.bg} overflow-hidden shadow-xs relative flex items-center justify-center`}>
                          {item.id === 'none' ? (
                            <i className="fa-solid fa-ban text-slate-500 text-sm"></i>
                          ) : (
                            <img src={item.id} alt={item.label} className="w-full h-full object-cover" />
                          )}
                        </div>
                        <span className="text-[9px] font-black">{item.label}</span>
                      </button>
                    ))}
                  </div>
                  {editForm.store_cover_bg && editForm.store_cover_bg !== 'none' && !['/images/cover_bg_1.jpg', '/images/cover_bg_2.jpg', '/images/cover_bg_3.jpg', '/images/cover_bg_4.jpg', '/images/cover_bg_5.jpg', '/images/cover_bg_6.jpg', '/images/cover_bg_7.jpg', '/images/cover_bg_8.jpg', '/images/cover_bg_blue.jpg', '/images/cover_bg_green.jpg', '/images/cover_bg_red.jpg', '/images/cover_bg.jpg'].includes(editForm.store_cover_bg) && (
                    <div className="flex items-center gap-3 bg-white p-2 rounded-xl border border-amber-200 mb-2">
                      <img
                        src={getImageUrl(editForm.store_cover_bg)}
                        alt="Custom Background"
                        className="h-10 w-16 object-cover rounded-lg border border-amber-300"
                      />
                      <button
                        type="button"
                        onClick={() => handleInputChange('store_cover_bg', '/images/cover_bg_1.jpg')}
                        className="text-xs text-red-600 font-extrabold hover:underline"
                      >
                        Reset Background
                      </button>
                    </div>
                  )}
                  <input
                    type="file"
                    accept="image/*"
                    onChange={(e) => {
                      const file = e.target.files[0];
                      if (file) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                          handleInputChange('store_cover_bg', event.target.result);
                        };
                        reader.readAsDataURL(file);
                      }
                    }}
                    className="block w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-white hover:file:bg-amber-600 cursor-pointer"
                  />
                </div>

                {/* Cover Text Font Colors */}
                <div className="bg-amber-50/90 p-3.5 rounded-2xl border-2 border-amber-300 space-y-3">
                  <label className="block text-slate-800 font-extrabold text-xs flex items-center justify-between">
                    <span className="flex items-center gap-2 text-slate-900 font-black">
                      <i className="fa-solid fa-palette text-amber-600"></i>
                      Cover Text Font Colors
                    </span>
                    <span className="text-[10px] text-amber-700 font-bold">Pick custom text colors</span>
                  </label>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {/* Store Title Font Color */}
                    <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5">
                      <div className="flex justify-between items-center text-xs font-bold text-slate-800">
                        <span>Store Title Color</span>
                        <span className="text-[10px] font-mono text-slate-500">{editForm.store_title_color || '#FFFFFF'}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <input
                          type="color"
                          value={editForm.store_title_color || '#FFFFFF'}
                          onChange={(e) => handleInputChange('store_title_color', e.target.value)}
                          className="w-9 h-8 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                        />
                        <div className="flex items-center gap-1 flex-wrap">
                          {['#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626', '#0F172A'].map((c) => (
                            <button
                              key={c}
                              type="button"
                              onClick={() => handleInputChange('store_title_color', c)}
                              className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer"
                              style={{ backgroundColor: c }}
                              title={c}
                            />
                          ))}
                        </div>
                      </div>
                    </div>

                    {/* Tagline Font Color */}
                    <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5">
                      <div className="flex justify-between items-center text-xs font-bold text-slate-800">
                        <span>Tagline Color</span>
                        <span className="text-[10px] font-mono text-slate-500">{editForm.store_tagline_color || '#FFFFFF'}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <input
                          type="color"
                          value={editForm.store_tagline_color || '#FFFFFF'}
                          onChange={(e) => handleInputChange('store_tagline_color', e.target.value)}
                          className="w-9 h-8 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                        />
                        <div className="flex items-center gap-1 flex-wrap">
                          {['#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626', '#0F172A'].map((c) => (
                            <button
                              key={c}
                              type="button"
                              onClick={() => handleInputChange('store_tagline_color', c)}
                              className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer"
                              style={{ backgroundColor: c }}
                              title={c}
                            />
                          ))}
                        </div>
                      </div>
                    </div>

                    {/* Invocation Font Color */}
                    <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5">
                      <div className="flex justify-between items-center text-xs font-bold text-slate-800">
                        <span>Deity / Invocation Color</span>
                        <span className="text-[10px] font-mono text-slate-500">{editForm.store_invocation_color || '#FFFFFF'}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <input
                          type="color"
                          value={editForm.store_invocation_color || '#FFFFFF'}
                          onChange={(e) => handleInputChange('store_invocation_color', e.target.value)}
                          className="w-9 h-8 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                        />
                        <div className="flex items-center gap-1 flex-wrap">
                          {['#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626', '#0F172A'].map((c) => (
                            <button
                              key={c}
                              type="button"
                              onClick={() => handleInputChange('store_invocation_color', c)}
                              className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer"
                              style={{ backgroundColor: c }}
                              title={c}
                            />
                          ))}
                        </div>
                      </div>
                    </div>

                    {/* Price List Badge Font Color */}
                    <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5">
                      <div className="flex justify-between items-center text-xs font-bold text-slate-800">
                        <span>Price List Badge Text Color</span>
                        <span className="text-[10px] font-mono text-slate-500">{editForm.store_badge_color || '#0F172A'}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <input
                          type="color"
                          value={editForm.store_badge_color || '#0F172A'}
                          onChange={(e) => handleInputChange('store_badge_color', e.target.value)}
                          className="w-9 h-8 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                        />
                        <div className="flex items-center gap-1 flex-wrap">
                          {['#0F172A', '#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626'].map((c) => (
                            <button
                              key={c}
                              type="button"
                              onClick={() => handleInputChange('store_badge_color', c)}
                              className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer"
                              style={{ backgroundColor: c }}
                              title={c}
                            />
                          ))}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Dual Payment QR Codes Section (2 QR Code Options) */}
                <div className="bg-amber-50/90 p-3.5 rounded-2xl border-2 border-amber-300 space-y-3 col-span-full">
                  <label className="block text-slate-800 font-black text-xs uppercase tracking-wide flex items-center justify-between">
                    <span className="flex items-center gap-2">
                      <i className="fa-solid fa-qrcode text-amber-600 text-sm"></i>
                      Payment QR Codes (2 Options Available)
                    </span>
                    <span className="text-[10px] text-amber-700 font-bold bg-amber-100 px-2 py-0.5 rounded border border-amber-300">
                      Upload 1 or 2 QR Codes
                    </span>
                  </label>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    {/* QR CODE 1 CARD */}
                    <div className="bg-white p-3 rounded-xl border border-amber-200 space-y-2">
                      <div className="flex justify-between items-center text-xs font-black text-slate-800 border-b border-slate-100 pb-1.5">
                        <span className="flex items-center gap-1.5 text-sky-600">
                          <i className="fa-solid fa-1"></i> QR Code 1 (Primary / GPay)
                        </span>
                        {editForm.store_upi_qr && (
                          <span className="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">Custom Image</span>
                        )}
                      </div>

                      {editForm.store_upi_qr && (
                        <div className="flex items-center gap-3 bg-slate-50 p-2 rounded-lg border border-slate-200">
                          <img
                            src={getImageUrl(editForm.store_upi_qr)}
                            alt="Payment QR 1"
                            className="h-12 w-12 object-contain rounded-md border border-slate-300 bg-white"
                          />
                          <button
                            type="button"
                            onClick={() => handleInputChange('store_upi_qr', '')}
                            className="text-xs text-red-600 font-extrabold hover:underline"
                          >
                            Reset QR 1 Image
                          </button>
                        </div>
                      )}

                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Upload QR 1 Image</label>
                        <input
                          type="file"
                          accept="image/*"
                          onChange={(e) => {
                            const file = e.target.files[0];
                            if (file) {
                              const reader = new FileReader();
                              reader.onload = (event) => {
                                handleInputChange('store_upi_qr', event.target.result);
                              };
                              reader.readAsDataURL(file);
                            }
                          }}
                          className="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-sky-500 file:text-white hover:file:bg-sky-600 cursor-pointer"
                        />
                      </div>

                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">QR 1 Account Name</label>
                        <input
                          type="text"
                          value={editForm.store_upi_name || ''}
                          onChange={(e) => handleInputChange('store_upi_name', e.target.value)}
                          placeholder="e.g. Muthusamy Ganesan"
                          className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 mb-2"
                        />
                      </div>

                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">QR 1 Mobile / UPI Number</label>
                        <input
                          type="text"
                          value={editForm.store_gpay || ''}
                          onChange={(e) => handleInputChange('store_gpay', e.target.value)}
                          placeholder="e.g. 97877 72038"
                          className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        />
                      </div>
                    </div>

                    {/* QR CODE 2 CARD */}
                    <div className="bg-white p-3 rounded-xl border border-amber-200 space-y-2">
                      <div className="flex justify-between items-center text-xs font-black text-slate-800 border-b border-slate-100 pb-1.5">
                        <span className="flex items-center gap-1.5 text-indigo-600">
                          <i className="fa-solid fa-2"></i> QR Code 2 (Secondary / PhonePe)
                        </span>
                        {editForm.store_upi_qr_2 && (
                          <span className="text-[10px] bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded font-bold">Custom Image</span>
                        )}
                      </div>

                      {editForm.store_upi_qr_2 && (
                        <div className="flex items-center gap-3 bg-slate-50 p-2 rounded-lg border border-slate-200">
                          <img
                            src={getImageUrl(editForm.store_upi_qr_2)}
                            alt="Payment QR 2"
                            className="h-12 w-12 object-contain rounded-md border border-slate-300 bg-white"
                          />
                          <button
                            type="button"
                            onClick={() => handleInputChange('store_upi_qr_2', '')}
                            className="text-xs text-red-600 font-extrabold hover:underline"
                          >
                            Remove QR 2 Image
                          </button>
                        </div>
                      )}

                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Upload QR 2 Image</label>
                        <input
                          type="file"
                          accept="image/*"
                          onChange={(e) => {
                            const file = e.target.files[0];
                            if (file) {
                              const reader = new FileReader();
                              reader.onload = (event) => {
                                handleInputChange('store_upi_qr_2', event.target.result);
                              };
                              reader.readAsDataURL(file);
                            }
                          }}
                          className="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer"
                        />
                      </div>

                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">QR 2 Account Name</label>
                        <input
                          type="text"
                          value={editForm.store_upi_name_2 || ''}
                          onChange={(e) => handleInputChange('store_upi_name_2', e.target.value)}
                          placeholder="e.g. Muthusamy Ganesan"
                          className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 mb-2"
                        />
                      </div>

                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">QR 2 Mobile / UPI Number</label>
                        <input
                          type="text"
                          value={editForm.store_gpay_2 || ''}
                          onChange={(e) => handleInputChange('store_gpay_2', e.target.value)}
                          placeholder="e.g. 86829 42042"
                          className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                {/* Price List Year */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Price List Year</label>
                  <input
                    type="text"
                    value={editForm.store_year}
                    onChange={(e) => handleInputChange('store_year', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Email / Website */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Email / Website</label>
                  <input
                    type="text"
                    value={editForm.store_email}
                    onChange={(e) => handleInputChange('store_email', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Primary WhatsApp / Phone */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Phone Number 1 (WhatsApp)</label>
                  <input
                    type="text"
                    value={editForm.store_phone || ''}
                    onChange={(e) => handleInputChange('store_phone', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Phone Number 2 */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Phone Number 2</label>
                  <input
                    type="text"
                    value={editForm.store_phone_2 || ''}
                    onChange={(e) => handleInputChange('store_phone_2', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Phone Number 3 */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Phone Number 3</label>
                  <input
                    type="text"
                    value={editForm.store_phone_3 || ''}
                    onChange={(e) => handleInputChange('store_phone_3', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Phone Number 4 */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Phone Number 4</label>
                  <input
                    type="text"
                    value={editForm.store_phone_4 || ''}
                    onChange={(e) => handleInputChange('store_phone_4', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Dedicated GPay Number */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">GPay / PhonePe Number</label>
                  <input
                    type="text"
                    value={editForm.store_gpay || ''}
                    onChange={(e) => handleInputChange('store_gpay', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Discount Offer % */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Discount Offer %</label>
                  <input
                    type="number"
                    value={editForm.discount_percent}
                    onChange={(e) => handleInputChange('discount_percent', parseFloat(e.target.value) || 0)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Rows Per Page (TR Count) */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Rows Per A4 Page (TR Count)</label>
                  <input
                    type="number"
                    min="10"
                    max="50"
                    value={editForm.max_tr_per_page || 30}
                    onChange={(e) => handleInputChange('max_tr_per_page', parseInt(e.target.value) || 30)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 font-black text-amber-900"
                  />
                </div>

                {/* Table Row Height (px) */}
                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Table Row Height (px)</label>
                  <input
                    type="number"
                    min="14"
                    max="50"
                    value={editForm.table_row_height || 22}
                    onChange={(e) => handleInputChange('table_row_height', parseInt(e.target.value) || 22)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 font-black text-amber-900"
                  />
                </div>

                {/* Store Address */}
                <div className="md:col-span-2 lg:col-span-3">
                  <label className="block text-slate-700 font-extrabold mb-1">Full Store Address</label>
                  <input
                    type="text"
                    value={editForm.store_address}
                    onChange={(e) => handleInputChange('store_address', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                {/* Bank Details Section with Enable / Disable Toggle */}
                <div className="md:col-span-2 lg:col-span-3 pt-3 border-t border-amber-200 flex flex-wrap items-center justify-between gap-2">
                  <div className="font-extrabold text-amber-900 text-xs uppercase tracking-wider flex items-center gap-2">
                    <i className="fa-solid fa-building-columns text-amber-600"></i>
                    <span>Bank Account Details</span>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleInputChange('show_bank_details', editForm.show_bank_details === false ? true : false)}
                    className={`flex items-center gap-2 px-3 py-1 rounded-xl font-black text-xs transition-all shadow-xs cursor-pointer border ${editForm.show_bank_details !== false
                      ? 'bg-emerald-600 text-white border-emerald-700 hover:bg-emerald-700'
                      : 'bg-slate-200 text-slate-700 border-slate-300 hover:bg-slate-300'
                      }`}
                  >
                    <i className={`fa-solid ${editForm.show_bank_details !== false ? 'fa-toggle-on text-sm' : 'fa-toggle-off text-sm'}`}></i>
                    <span>{editForm.show_bank_details !== false ? 'Enabled on Document' : 'Disabled on Document'}</span>
                  </button>
                </div>

                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Account Name</label>
                  <input
                    type="text"
                    value={editForm.bank_name}
                    onChange={(e) => handleInputChange('bank_name', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Bank Name / Branch</label>
                  <input
                    type="text"
                    value={editForm.bank_branch}
                    onChange={(e) => handleInputChange('bank_branch', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">Account Number</label>
                  <input
                    type="text"
                    value={editForm.bank_account_no}
                    onChange={(e) => handleInputChange('bank_account_no', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 font-mono"
                  />
                </div>

                <div>
                  <label className="block text-slate-700 font-extrabold mb-1">IFSC Code</label>
                  <input
                    type="text"
                    value={editForm.bank_ifsc}
                    onChange={(e) => handleInputChange('bank_ifsc', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 font-mono"
                  />
                </div>

                {/* Footer Position Selector (Input Style) */}
                <div className="md:col-span-2 lg:col-span-3 bg-amber-100/70 p-3.5 rounded-2xl border-2 border-amber-300 space-y-2">
                  <label className="block text-slate-800 font-black text-xs uppercase tracking-wide flex items-center gap-1.5">
                    <i className="fa-solid fa-square-poll-vertical text-amber-600 text-sm"></i>
                    Footer Position (Bank Info & Notes Placement)
                  </label>
                  <select
                    value={editForm.footer_position || 'below_table'}
                    onChange={(e) => handleInputChange('footer_position', e.target.value)}
                    className="w-full bg-white border-2 border-amber-300 rounded-xl px-3.5 py-2.5 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer shadow-xs"
                  >
                    <option value="below_table">📍 Below Product Table (Next to Table)</option>
                    <option value="new_page">📄 New Dedicated Page (Standalone Page)</option>
                  </select>
                </div>

                {/* Tamil Terms & Note */}
                <div className="md:col-span-2 lg:col-span-3 pt-3 border-t border-amber-200 font-extrabold text-amber-900 text-xs uppercase tracking-wider">
                  📜 Important Note Text (பின்குறிப்பு)
                </div>

                <div className="md:col-span-2 lg:col-span-3">
                  <textarea
                    rows={4}
                    value={editForm.important_note_1}
                    onChange={(e) => handleInputChange('important_note_1', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-semibold text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 mb-2"
                  ></textarea>
                  <textarea
                    rows={4}
                    value={editForm.important_note_2}
                    onChange={(e) => handleInputChange('important_note_2', e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-semibold text-xs focus:outline-none focus:ring-2 focus:ring-amber-500"
                  ></textarea>
                </div>
              </div>
            </div>
          )}

          {/* Useful Document Controls Dashboard Bar */}
          <div className="bg-slate-50/90 border border-slate-200 rounded-2xl p-3.5 space-y-3.5 shadow-2xs">
            {/* Top Row: 5 Perfectly Aligned Input Columns */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs font-semibold items-end">
              {/* 1. Cover Image Quick Status */}
              <div>
                <label className="block text-slate-700 mb-1 font-extrabold flex items-center justify-between text-[11px]">
                  <span>Cover Image</span>
                  <span className="text-amber-700 font-black text-[10px] uppercase">
                    {editForm.store_deity_image ? 'Uploaded' : 'None'}
                  </span>
                </label>
                <div className="flex items-center gap-2 bg-white border border-slate-200 rounded-xl p-1 h-[42px] shadow-2xs">
                  {editForm.store_deity_image ? (
                    <div className="flex items-center justify-between w-full px-2 text-xs font-black text-emerald-700">
                      <span className="truncate flex items-center gap-1"><i className="fa-solid fa-image"></i> Image Active</span>
                      <button
                        type="button"
                        onClick={() => handleInputChange('store_deity_image', '')}
                        className="text-[10px] bg-red-100 hover:bg-red-200 text-red-700 font-bold px-2 py-0.5 rounded-md transition-all cursor-pointer shrink-0"
                      >
                        Clear
                      </button>
                    </div>
                  ) : (
                    <button
                      type="button"
                      onClick={() => setShowEditDrawer(true)}
                      className="w-full h-full text-[10px] font-black text-slate-700 hover:text-amber-700 bg-white hover:bg-amber-50 rounded-lg flex items-center justify-center gap-1 transition-all cursor-pointer"
                    >
                      <i className="fa-solid fa-upload"></i> Upload Image
                    </button>
                  )}
                </div>
              </div>

              {/* 2. Rows Per Page (TR Count) */}
              <div>
                <label className="block text-slate-700 mb-1 font-extrabold flex items-center justify-between text-[11px]">
                  <span>Rows / Page</span>
                  <span className="text-amber-700 font-black text-[10px] bg-amber-100 px-1.5 py-0.5 rounded border border-amber-200">{editForm.max_tr_per_page || 30} TRs</span>
                </label>
                <div className="relative">
                  <input
                    type="number"
                    min={10}
                    max={60}
                    value={editForm.max_tr_per_page || 30}
                    onChange={(e) => handleInputChange('max_tr_per_page', parseInt(e.target.value, 10) || 30)}
                    className="w-full bg-white border border-slate-200 rounded-xl px-3 py-1 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 h-[42px] transition-all shadow-2xs pr-10"
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">TRs</span>
                </div>
              </div>

              {/* 3. Row Height */}
              <div>
                <label className="block text-slate-700 mb-1 font-extrabold flex items-center justify-between text-[11px]">
                  <span>Row Height</span>
                  <span className="text-amber-700 font-black text-[10px] bg-amber-100 px-1.5 py-0.5 rounded border border-amber-200">{editForm.table_row_height || 22}px</span>
                </label>
                <div className="relative">
                  <input
                    type="number"
                    min={14}
                    max={45}
                    value={editForm.table_row_height || 22}
                    onChange={(e) => handleInputChange('table_row_height', parseInt(e.target.value, 10) || 22)}
                    className="w-full bg-white border border-slate-200 rounded-xl px-3 py-1 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 h-[42px] transition-all shadow-2xs pr-10"
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">px</span>
                </div>
              </div>

              {/* 4. Discount Offer % */}
              <div>
                <label className="block text-slate-700 mb-1 font-extrabold flex items-center justify-between text-[11px]">
                  <span>Discount Offer</span>
                  <span className="text-amber-700 font-black text-[10px] bg-amber-100 px-1.5 py-0.5 rounded border border-amber-200">{editForm.discount_percent !== undefined ? editForm.discount_percent : 50}% OFF</span>
                </label>
                <div className="relative">
                  <input
                    type="number"
                    min={0}
                    max={100}
                    value={editForm.discount_percent !== undefined ? editForm.discount_percent : 50}
                    onChange={(e) => handleInputChange('discount_percent', parseFloat(e.target.value) || 0)}
                    className="w-full bg-white border border-slate-200 rounded-xl px-3 py-1 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 h-[42px] transition-all shadow-2xs pr-12"
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">% OFF</span>
                </div>
              </div>

              {/* 5. Footer Position */}
              <div>
                <label className="block text-slate-700 mb-1 font-extrabold flex items-center justify-between text-[11px]">
                  <span>Footer Position</span>
                </label>
                <select
                  value={editForm.footer_position || 'below_table'}
                  onChange={(e) => handleInputChange('footer_position', e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer h-[42px] transition-all shadow-2xs"
                >
                  <option value="below_table">📍 Below Table</option>
                  <option value="new_page">📄 New Page</option>
                </select>
              </div>
            </div>

            {/* Bottom Row: 2 Balanced Side-by-Side Control Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5 items-stretch">
              {/* Display Options & Column Visibility Card */}
              <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs flex flex-col justify-between">
                <label className="block text-slate-800 font-extrabold text-xs mb-2 flex items-center justify-between">
                  <span className="flex items-center gap-1.5 text-slate-800"><i className="fa-solid fa-eye text-amber-500"></i> Display Options & Column Visibility</span>
                </label>
                <div className="grid grid-cols-3 gap-2 font-extrabold text-[11px]">
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_col_sno !== false}
                      onChange={(e) => handleInputChange('show_col_sno', e.target.checked)}
                      className="rounded text-amber-600 focus:ring-amber-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">S.No</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_col_product !== false}
                      onChange={(e) => handleInputChange('show_col_product', e.target.checked)}
                      className="rounded text-amber-600 focus:ring-amber-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">Product</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_col_unit !== false}
                      onChange={(e) => handleInputChange('show_col_unit', e.target.checked)}
                      className="rounded text-amber-600 focus:ring-amber-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">Unit</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={showMrp}
                      onChange={(e) => {
                        setShowMrp(e.target.checked);
                        handleInputChange('show_col_mrp', e.target.checked);
                      }}
                      className="rounded text-amber-600 focus:ring-amber-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">MRP (Rate)</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_col_offer !== false}
                      onChange={(e) => handleInputChange('show_col_offer', e.target.checked)}
                      className="rounded text-amber-600 focus:ring-amber-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">Offer Rate</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_col_req !== false}
                      onChange={(e) => handleInputChange('show_col_req', e.target.checked)}
                      className="rounded text-amber-600 focus:ring-amber-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">REQ</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_discount_badge !== false}
                      onChange={(e) => handleInputChange('show_discount_badge', e.target.checked)}
                      className="rounded text-rose-600 focus:ring-rose-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">Discount Badge</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none col-span-3 mt-1 p-2 bg-amber-50/80 border border-amber-300 rounded-lg text-amber-950 font-extrabold shadow-2xs hover:bg-amber-100/80 transition-colors">
                    <input
                      type="checkbox"
                      checked={editForm.show_tamil_name === true}
                      onChange={(e) => {
                        const isChecked = e.target.checked;
                        handleInputChange('show_tamil_name', isChecked);
                        setColWidths((prev) => ({
                          ...prev,
                          sno: isChecked ? 35 : 40,
                          product: isChecked ? 165 : 240,
                          product_ta: 165,
                          unit: isChecked ? 75 : 90,
                          mrp: isChecked ? 65 : 75,
                          offer: isChecked ? 90 : 105,
                          req: isChecked ? 35 : 40,
                        }));
                        if (isChecked) {
                          handleAutoTranslateTamil(false);
                        }
                      }}
                      className="rounded text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer"
                    />
                    <span className="flex items-center gap-1.5 text-xs">
                      <i className="fa-solid fa-language text-amber-600 text-sm"></i>
                      <span>Split Product Name Column (Enable 2 Columns: English & Tamil)</span>
                    </span>
                  </label>
                  {editForm.show_tamil_name && (
                    <div className="col-span-3 -mt-1 mb-1 p-2 bg-amber-100/70 border border-amber-300 rounded-lg flex items-center justify-between gap-2">
                      <span className="text-[11px] font-bold text-amber-950 flex items-center gap-1">
                        <i className="fa-solid fa-wand-magic-sparkles text-amber-600"></i>
                        <span>Auto-translate English to Tamil</span>
                      </span>
                      <button
                        type="button"
                        disabled={translatingTamil}
                        onClick={() => handleAutoTranslateTamil(true)}
                        className="px-2.5 py-1 text-xs font-black text-white bg-amber-600 hover:bg-amber-700 active:scale-95 transition-all rounded-md flex items-center gap-1.5 shadow-xs cursor-pointer disabled:opacity-50"
                      >
                        <i className={`fa-solid ${translatingTamil ? 'fa-spinner fa-spin' : 'fa-language'}`}></i>
                        <span>{translatingTamil ? 'Translating...' : 'Auto-Generate Tamil Names'}</span>
                      </button>
                    </div>
                  )}
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_bank_details !== false}
                      onChange={(e) => handleInputChange('show_bank_details', e.target.checked)}
                      className="rounded text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">Bank Details</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_upi_qr !== false}
                      onChange={(e) => handleInputChange('show_upi_qr', e.target.checked)}
                      className="rounded text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">UPI QR</span>
                  </label>
                  <label className="inline-flex items-center gap-1.5 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={editForm.show_discount_badge !== false}
                      onChange={(e) => handleInputChange('show_discount_badge', e.target.checked)}
                      className="rounded text-rose-600 focus:ring-rose-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800">Discount Box</span>
                  </label>
                </div>
              </div>

              {/* Canva-Style Element Scaling Controls Card */}
              <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs space-y-2">
                <label className="block text-slate-800 font-extrabold text-xs flex items-center justify-between">
                  <span className="flex items-center gap-1.5 text-sky-700"><i className="fa-solid fa-expand text-sky-500"></i> Canva Header Element Resizing</span>
                  <span className="text-[10px] text-slate-400 font-normal">Drag handles or use sliders</span>
                </label>

                <div className="grid grid-cols-2 gap-x-4 gap-y-2">
                  {/* Logo Scale Slider */}
                  <div>
                    <div className="flex justify-between text-[10px] font-bold text-slate-700 mb-0.5">
                      <span>🖼️ Logo / Brand</span>
                      <span className="font-mono text-sky-600">{editForm.logo_scale || 100}%</span>
                    </div>
                    <input
                      type="range"
                      min="40"
                      max="200"
                      value={editForm.logo_scale || 100}
                      onChange={(e) => handleInputChange('logo_scale', parseInt(e.target.value, 10))}
                      className="w-full accent-sky-600 cursor-pointer h-1.5 bg-slate-100 rounded-lg"
                    />
                  </div>

                  {/* Contact Scale Slider */}
                  <div>
                    <div className="flex justify-between text-[10px] font-bold text-slate-700 mb-0.5">
                      <span>📞 Contact Details</span>
                      <span className="font-mono text-sky-600">{editForm.contact_scale || 100}%</span>
                    </div>
                    <input
                      type="range"
                      min="40"
                      max="200"
                      value={editForm.contact_scale || 100}
                      onChange={(e) => handleInputChange('contact_scale', parseInt(e.target.value, 10))}
                      className="w-full accent-sky-600 cursor-pointer h-1.5 bg-slate-100 rounded-lg"
                    />
                  </div>

                  {/* Discount Box Scale Slider */}
                  <div>
                    <div className="flex justify-between text-[10px] font-bold text-slate-700 mb-0.5">
                      <span>🏷️ Discount Box</span>
                      <span className="font-mono text-sky-600">{editForm.discount_scale || 100}%</span>
                    </div>
                    <input
                      type="range"
                      min="40"
                      max="200"
                      value={editForm.discount_scale || 100}
                      onChange={(e) => handleInputChange('discount_scale', parseInt(e.target.value, 10))}
                      className="w-full accent-sky-600 cursor-pointer h-1.5 bg-slate-100 rounded-lg"
                    />
                  </div>

                  {/* Address Scale Slider */}
                  <div>
                    <div className="flex justify-between text-[10px] font-bold text-slate-700 mb-0.5">
                      <span>📍 Address Row</span>
                      <span className="font-mono text-sky-600">{editForm.address_scale || 100}%</span>
                    </div>
                    <input
                      type="range"
                      min="40"
                      max="200"
                      value={editForm.address_scale || 100}
                      onChange={(e) => handleInputChange('address_scale', parseInt(e.target.value, 10))}
                      className="w-full accent-sky-600 cursor-pointer h-1.5 bg-slate-100 rounded-lg"
                    />
                  </div>

                  {/* God / Deity Image Scale Slider */}
                  <div className="col-span-2">
                    <div className="flex justify-between text-[10px] font-bold text-slate-700 mb-0.5">
                      <span>🕉️ God / Deity Image Size</span>
                      <span className="font-mono text-sky-600">{editForm.deity_scale || 100}%</span>
                    </div>
                    <input
                      type="range"
                      min="40"
                      max="200"
                      value={editForm.deity_scale || 100}
                      onChange={(e) => handleInputChange('deity_scale', parseInt(e.target.value, 10))}
                      className="w-full accent-sky-600 cursor-pointer h-1.5 bg-slate-100 rounded-lg"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* A4 STANDALONE PAGES DOCUMENT CONTAINER */}
        <div
          id="price-list-document"
          className="w-full flex flex-col items-center gap-8 print:block print:space-y-0 print:w-[210mm] mx-auto"
        >

          {/* A4 PAGE 1: DEDICATED FULL FESTIVE COVER SHEET (210mm x 297mm) */}
          <div className="w-full max-w-[210mm] print:w-[210mm]">


            <div
              className={`a4-page-sheet w-[210mm] h-[297mm] max-h-[297mm] overflow-hidden ${editForm.store_cover_bg === 'none' ? 'text-slate-900 bg-white' : 'text-white'} transition-all duration-300 relative shadow-2xl flex flex-col justify-between p-6 sm:p-8 pb-4 select-none mx-auto break-after-page bg-cover bg-center bg-no-repeat box-border`}
              style={{ backgroundImage: editForm.store_cover_bg === 'none' ? 'none' : `url(${editForm.store_cover_bg ? getImageUrl(editForm.store_cover_bg) : '/images/cover_bg.jpg'})`, pageBreakAfter: 'always' }}
            >

              {/* Top Invocation Header Section (Flush to top, 30% reduced font size) */}
              <div className="relative text-center z-10 mt-0 space-y-0.5">
                {editForm.store_invocation_symbol && (
                  <div className="text-white font-extrabold text-xs sm:text-sm tracking-wider" style={{ color: editForm.store_invocation_color || undefined }}>
                    {editForm.store_invocation_symbol}
                  </div>
                )}
                {editForm.store_invocation && (
                  <div className="text-white font-extrabold text-[10px] sm:text-xs tracking-wide drop-shadow-md" style={{ color: editForm.store_invocation_color || undefined }}>
                    {editForm.store_invocation}
                  </div>
                )}
              </div>

              {/* Main Brand & Logo Motif Center Section */}
              <div className="relative z-10 text-center space-y-6 mt-8 mb-2">
                {/* Brand Title & Tagline */}
                <div className="flex flex-col items-center space-y-4 sm:space-y-5 mt-6">
                  <h1
                    className="font-black text-white uppercase"
                    style={{
                      fontSize: '2.8rem',
                      letterSpacing: '0.15em',
                      lineHeight: '1.35',
                      fontFamily: getStoreNameFontFamily(),
                      textShadow: '-3px -3px 0 #000000, 3px -3px 0 #000000, -3px 3px 0 #000000, 3px 3px 0 #000000, -4px 0 0 #000000, 4px 0 0 #000000, 0 4px 0 #000000, 0 8px 20px rgba(0,0,0,0.95)',
                      color: editForm.store_title_color || undefined,
                    }}
                  >
                    {editForm.store_name}
                  </h1>
                  <p className="text-xl sm:text-2xl font-bold text-white tracking-wide drop-shadow-md pt-1 pb-1" style={{ color: editForm.store_tagline_color || undefined }}>
                    "{editForm.store_tagline}"
                  </p>
                  <div
                    className="inline-block text-slate-950 font-black text-2xl sm:text-3xl uppercase tracking-wider pt-2"
                    style={{ textShadow: '-2px -2px 0 #ffffff, 2px -2px 0 #ffffff, -2px 2px 0 #ffffff, 2px 2px 0 #ffffff, 0 4px 8px rgba(0,0,0,0.6)', color: editForm.store_badge_color || undefined }}
                  >
                    PRICE LIST - {editForm.store_year}
                  </div>
                </div>
              </div>

              {/* Center Cover Image Section (Natural Unmodified Colors - Canva Resizable) */}
              {getDeityImageUrl() && (
                <div className="relative z-10 flex-1 min-h-0 my-auto flex justify-center items-center py-1 overflow-hidden">
                  <div
                    className="relative group border border-transparent hover:border-sky-400 hover:border-dashed rounded-2xl p-1.5 transition-all flex items-center justify-center"
                    style={{
                      transform: `scale(${(editForm.deity_scale || 100) / 100})`,
                      transformOrigin: 'center center',
                    }}
                  >
                    <img
                      src={getDeityImageUrl()}
                      alt="Cover Image"
                      className="max-h-[540px] sm:max-h-[630px] w-auto object-contain relative z-10 drop-shadow-[0_20px_40px_rgba(0,0,0,0.85)] pointer-events-auto"
                    />
                    {/* Canva Resize Handle */}
                    <div
                      onMouseDown={(e) => handleElementResizeStart('deity', editForm.deity_scale || 100, e)}
                      className="absolute bottom-2 right-2 w-5 h-5 bg-sky-500 hover:bg-sky-600 rounded-full border-2 border-white cursor-se-resize shadow-lg opacity-0 group-hover:opacity-100 z-30 transition-opacity print:hidden flex items-center justify-center text-[9px] text-white"
                      title="Drag corner to resize God Image like Canva"
                    >
                      <i className="fa-solid fa-up-right-and-down-left-from-center"></i>
                    </div>
                  </div>
                </div>
              )}

              {/* For Order Full-Width Banner Card (Spans Start to End Flush Across Sheet - Compact & High-Legibility) */}
              <div className="relative z-10 w-full mx-0 mt-auto mb-0">
                <div className="bg-white text-slate-950 p-2.5 sm:p-3 rounded-2xl shadow-2xl border-2 border-amber-400 grid grid-cols-1 sm:grid-cols-12 gap-2 items-center px-3 sm:px-4">
                  {/* Left: Store Logo (Direct clean logo without circle ring with Canva Resize Handle) */}
                  <div className="sm:col-span-3 flex justify-center sm:justify-start items-center">
                    <div
                      className="relative group border border-transparent hover:border-sky-400 hover:border-dashed rounded-lg p-1 transition-all"
                      style={{
                        transform: `scale(${(editForm.logo_scale || 100) / 100})`,
                        transformOrigin: 'left center',
                      }}
                    >
                      {editForm.store_logo || settings?.store_logo ? (
                        <img
                          src={getImageUrl(editForm.store_logo || settings?.store_logo)}
                          alt={editForm.store_name}
                          className="max-h-16 sm:max-h-20 max-w-[130px] sm:max-w-[160px] object-contain drop-shadow-xs"
                        />
                      ) : (
                        <div className="flex items-center gap-1.5 text-amber-600 font-black text-lg">
                          <i className="fa-solid fa-fire text-xl text-red-600"></i>
                          <span>{editForm.store_name}</span>
                        </div>
                      )}
                      {/* Canva Resize Handle */}
                      <div
                        onMouseDown={(e) => handleElementResizeStart('logo', editForm.logo_scale || 100, e)}
                        className="absolute -bottom-1.5 -right-1.5 w-4 h-4 bg-sky-500 hover:bg-sky-600 rounded-full border-2 border-white cursor-se-resize shadow-md opacity-0 group-hover:opacity-100 z-30 transition-opacity print:hidden flex items-center justify-center text-[8px] text-white"
                        title="Drag corner to resize Logo like Canva"
                      >
                        <i className="fa-solid fa-up-right-and-down-left-from-center"></i>
                      </div>
                    </div>
                  </div>

                  {/* Center: Contact Details (Website, 4 Phone Numbers, GPay with Canva Resize Handle) */}
                  <div className={`${editForm.show_discount_badge !== false ? 'sm:col-span-6' : 'sm:col-span-9'} flex flex-col items-center sm:items-start text-center sm:text-left`}>
                    <div
                      className="relative group border border-transparent hover:border-sky-400 hover:border-dashed rounded-lg p-1 transition-all space-y-1 sm:space-y-1.5"
                      style={{
                        transform: `scale(${(editForm.contact_scale || 100) / 100})`,
                        transformOrigin: 'left center',
                      }}
                    >
                      {editForm.store_email && (
                        <div className="flex items-center gap-2 text-slate-950 text-xs sm:text-sm font-black tracking-wide">
                          <div className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-black text-white flex items-center justify-center text-[10px] sm:text-xs shrink-0 shadow-xs">
                            <i className="fa-solid fa-globe"></i>
                          </div>
                          <span className="truncate">{editForm.store_email}</span>
                        </div>
                      )}
                      <div className="flex items-center gap-2 text-slate-950 text-xs sm:text-sm font-black tracking-wide">
                        <div className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] sm:text-xs shrink-0 shadow-xs">
                          <i className="fa-solid fa-phone"></i>
                        </div>
                        <span className="leading-snug">
                          {[editForm.store_phone, editForm.store_phone_2, editForm.store_phone_3, editForm.store_phone_4].filter(Boolean).join(' , ')}
                        </span>
                      </div>
                      {(editForm.store_gpay || editForm.store_phone_3) && (
                        <div className="flex items-center gap-2 text-slate-950 text-xs sm:text-sm font-black tracking-wide">
                          <div className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-sky-500 text-white flex items-center justify-center text-[9px] sm:text-[10px] font-black shrink-0 shadow-xs">
                            GPay
                          </div>
                          <span className="font-mono">{editForm.store_gpay || editForm.store_phone_3}</span>
                        </div>
                      )}
                      {/* Canva Resize Handle */}
                      <div
                        onMouseDown={(e) => handleElementResizeStart('contact', editForm.contact_scale || 100, e)}
                        className="absolute -bottom-1.5 -right-1.5 w-4 h-4 bg-sky-500 hover:bg-sky-600 rounded-full border-2 border-white cursor-se-resize shadow-md opacity-0 group-hover:opacity-100 z-30 transition-opacity print:hidden flex items-center justify-center text-[8px] text-white"
                        title="Drag corner to resize Contact Details like Canva"
                      >
                        <i className="fa-solid fa-up-right-and-down-left-from-center"></i>
                      </div>
                    </div>
                  </div>

                  {/* Right: Dynamic Mega Sale Discount Offer Badge & Rocket (Optional with Canva Resize Handle) */}
                  {editForm.show_discount_badge !== false && (
                    <div className="sm:col-span-3 flex justify-center sm:justify-end items-center">
                      <div
                        className="relative group border border-transparent hover:border-sky-400 hover:border-dashed rounded-lg p-1 transition-all"
                        style={{
                          transform: `scale(${(editForm.discount_scale || 100) / 100})`,
                          transformOrigin: 'right center',
                        }}
                      >
                        <div className="flex items-center gap-2 sm:gap-3">
                          <div className="flex flex-col items-center justify-center text-center">
                            {/* 3D MEGA SALE Header */}
                            <div
                              className="text-amber-500 font-black text-base sm:text-lg uppercase tracking-wider leading-none"
                              style={{ textShadow: '-1.5px -1.5px 0 #78350f, 1.5px -1.5px 0 #78350f, -1.5px 1.5px 0 #78350f, 1.5px 1.5px 0 #78350f, 0 2px 4px rgba(0,0,0,0.3)' }}
                            >
                              MEGA SALE
                            </div>
                            {/* Dynamic Offer Percentage Value */}
                            <div
                              className="text-3xl sm:text-4xl font-black my-0.5 leading-none transition-colors duration-300"
                              style={{
                                color: getThemeAccentColor(editForm.store_cover_bg).hex,
                                textShadow: '-2px -2px 0 #ffffff, 2px -2px 0 #ffffff, -2px 2px 0 #ffffff, 2px 2px 0 #ffffff, 0 3px 6px rgba(0,0,0,0.4)'
                              }}
                            >
                              {discountPercent}%
                            </div>
                            {/* DISCOUNT Badge */}
                            <div
                              className="text-white text-[9px] sm:text-[10px] font-black uppercase px-2.5 py-0.5 rounded-md shadow tracking-widest transition-colors duration-300"
                              style={{
                                backgroundColor: getThemeAccentColor(editForm.store_cover_bg).hex
                              }}
                            >
                              DISCOUNT
                            </div>
                          </div>
                          {/* Skyrocket Clipart */}
                          <div className="text-2xl sm:text-3xl">
                            🚀
                          </div>
                        </div>
                        {/* Canva Resize Handle */}
                        <div
                          onMouseDown={(e) => handleElementResizeStart('discount', editForm.discount_scale || 100, e)}
                          className="absolute -bottom-1.5 -right-1.5 w-4 h-4 bg-sky-500 hover:bg-sky-600 rounded-full border-2 border-white cursor-se-resize shadow-md opacity-0 group-hover:opacity-100 z-30 transition-opacity print:hidden flex items-center justify-center text-[8px] text-white"
                          title="Drag corner to resize Discount Box like Canva"
                        >
                          <i className="fa-solid fa-up-right-and-down-left-from-center"></i>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Bottom Centered Address Row with Canva Resize Handle */}
                  <div className="sm:col-span-12 flex items-center justify-center pt-1.5 border-t border-slate-200 text-center w-full mt-0.5">
                    <div
                      className="relative group border border-transparent hover:border-sky-400 hover:border-dashed rounded-lg p-1 transition-all flex items-center justify-center gap-1.5 text-slate-950 text-[11px] sm:text-xs font-black"
                      style={{
                        transform: `scale(${(editForm.address_scale || 100) / 100})`,
                        transformOrigin: 'center center',
                      }}
                    >
                      <div
                        className="w-4.5 h-4.5 rounded-full text-white flex items-center justify-center text-[9px] shrink-0 shadow-xs transition-colors duration-300"
                        style={{
                          backgroundColor: getThemeAccentColor(editForm.store_cover_bg).hex
                        }}
                      >
                        <i className="fa-solid fa-location-dot"></i>
                      </div>
                      <span>{editForm.store_address || 'Virudhunagar to Sivakasi Main Road, Sivakasi'}</span>
                      {/* Canva Resize Handle */}
                      <div
                        onMouseDown={(e) => handleElementResizeStart('address', editForm.address_scale || 100, e)}
                        className="absolute -bottom-1.5 -right-1.5 w-4 h-4 bg-sky-500 hover:bg-sky-600 rounded-full border-2 border-white cursor-se-resize shadow-md opacity-0 group-hover:opacity-100 z-30 transition-opacity print:hidden flex items-center justify-center text-[8px] text-white"
                        title="Drag corner to resize Address Row like Canva"
                      >
                        <i className="fa-solid fa-up-right-and-down-left-from-center"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* A4 PAGES 2 ONWARDS: PRODUCT REGISTRY PAGES (25 PRODUCTS PER A4 SHEET - 210mm x 297mm) */}
          {productPageChunks.map((chunkProducts, chunkIdx) => {
            const docPageIndex = chunkIdx + 2; // Page 2, Page 3...

            // Group chunk products by category for this page
            const chunkCategories = [];
            chunkProducts.forEach((prod) => {
              let catGroup = chunkCategories.find((c) => c.id === prod.category_id);
              if (!catGroup) {
                catGroup = { id: prod.category_id, name: prod.category_name, products: [] };
                chunkCategories.push(catGroup);
              }
              catGroup.products.push(prod);
            });

            return (
              <div key={chunkIdx} className="w-full max-w-[210mm] print:w-[210mm]">
                <div
                  className={`a4-page-sheet w-[210mm] h-[297mm] max-h-[297mm] overflow-hidden text-slate-900 transition-all duration-300 relative shadow-2xl flex flex-col justify-between p-4 sm:p-5 select-none mx-auto break-after-page bg-cover bg-center bg-no-repeat box-border`}
                  style={{ backgroundImage: editForm.store_cover_bg === 'none' ? 'none' : `url(${editForm.store_cover_bg ? getImageUrl(editForm.store_cover_bg) : '/images/cover_bg.jpg'})`, pageBreakAfter: 'always' }}
                >
                  {/* Product Table Container */}
                  <div className="w-full flex-1">
                    {chunkProducts.length === 0 ? (
                      <div className="text-center py-12 text-slate-500 font-semibold">
                        <i className="fa-solid fa-box-open text-4xl mb-3 text-slate-300"></i>
                        <p>No products match your search criteria.</p>
                      </div>
                    ) : (
                      <div className="border-2 border-slate-700 rounded-xl overflow-hidden shadow-sm bg-white print:border-0">
                        <table className="w-full text-left border-collapse print:table table-fixed">
                          <colgroup>
                            {showSno && <col style={{ width: `${colWidths.sno}px` }} />}
                            {showProduct && <col style={{ width: `${colWidths.product}px` }} />}
                            {showTamilName && <col style={{ width: `${colWidths.product_ta || 160}px` }} />}
                            {showUnit && <col style={{ width: `${colWidths.unit}px` }} />}
                            {showMrpCol && <col style={{ width: `${colWidths.mrp}px` }} />}
                            {showOffer && <col style={{ width: `${colWidths.offer}px` }} />}
                            {showReq && <col style={{ width: `${colWidths.req}px` }} />}
                          </colgroup>
                          <thead>
                            <tr className={`${theme.tableHeader} font-black text-black uppercase tracking-wider text-[11px] min-h-[34px]`}>
                              {/* S.No Header */}
                              {showSno && (
                                <th
                                  className="py-0.5 text-center border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: `${colWidths.sno}px`,
                                    paddingLeft: `${editForm.table_col_padding || 4}px`,
                                    paddingRight: `${editForm.table_col_padding || 4}px`,
                                  }}
                                >
                                  <span className="hidden print:block w-full text-center font-black uppercase text-[10px] leading-tight py-0.5 break-words">
                                    {editForm.header_sno || 'S.No'}
                                  </span>
                                  <textarea
                                    rows={2}
                                    value={editForm.header_sno || 'S.No'}
                                    onChange={(e) => handleInputChange('header_sno', e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.blur(); } }}
                                    title="Click to edit header"
                                    className="print:hidden w-full h-full bg-transparent border-0 text-center font-black uppercase text-[10px] leading-tight resize-none whitespace-pre-wrap break-words overflow-hidden focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-0.5 cursor-text hover:bg-black/5 transition-colors focus:outline-none py-1"
                                  />
                                  <div
                                    className="absolute right-0 top-0 bottom-0 w-2.5 cursor-col-resize hover:bg-amber-600/70 active:bg-amber-700 z-20 transition-colors print:hidden"
                                    onMouseDown={(e) => handleColumnResizeStart('sno', e)}
                                    title="Drag to resize S.No column"
                                  />
                                </th>
                              )}

                              {/* Product Header (English) */}
                              {showProduct && (
                                <th
                                  className="py-0.5 border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: `${colWidths.product}px`,
                                    paddingLeft: `${editForm.table_col_padding || 4}px`,
                                    paddingRight: `${editForm.table_col_padding || 4}px`,
                                  }}
                                >
                                  <span className="hidden print:block w-full text-left font-black uppercase text-[10px] leading-tight py-0.5 break-words px-1">
                                    {editForm.header_product || (showTamilName ? 'PRODUCT NAME (ENG)' : 'PRODUCT')}
                                  </span>
                                  <textarea
                                    rows={2}
                                    value={editForm.header_product || (showTamilName ? 'PRODUCT NAME (ENG)' : 'PRODUCT')}
                                    onChange={(e) => handleInputChange('header_product', e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.blur(); } }}
                                    title="Click to edit header"
                                    className="print:hidden w-full h-full bg-transparent border-0 text-left font-black uppercase text-[10px] leading-tight resize-none whitespace-pre-wrap break-words overflow-hidden focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-1 cursor-text hover:bg-black/5 transition-colors focus:outline-none py-1"
                                  />
                                  <div
                                    className="absolute right-0 top-0 bottom-0 w-2.5 cursor-col-resize hover:bg-amber-600/70 active:bg-amber-700 z-20 transition-colors print:hidden"
                                    onMouseDown={(e) => handleColumnResizeStart('product', e)}
                                    title="Drag to resize Product column"
                                  />
                                </th>
                              )}

                              {/* Tamil Product Header */}
                              {showTamilName && (
                                <th
                                  className="py-0.5 border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: `${colWidths.product_ta || 160}px`,
                                    paddingLeft: `${editForm.table_col_padding || 4}px`,
                                    paddingRight: `${editForm.table_col_padding || 4}px`,
                                  }}
                                >
                                  <span className="hidden print:block w-full text-left font-black uppercase text-[10px] leading-tight py-0.5 break-words px-1">
                                    {editForm.header_product_ta || 'பொருள் பெயர் (TAMIL)'}
                                  </span>
                                  <textarea
                                    rows={2}
                                    value={editForm.header_product_ta || 'பொருள் பெயர் (TAMIL)'}
                                    onChange={(e) => handleInputChange('header_product_ta', e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.blur(); } }}
                                    title="Click to edit Tamil header"
                                    className="print:hidden w-full h-full bg-transparent border-0 text-left font-black uppercase text-[10px] leading-tight resize-none whitespace-pre-wrap break-words overflow-hidden focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-1 cursor-text hover:bg-black/5 transition-colors focus:outline-none py-1"
                                  />
                                  <div
                                    className="absolute right-0 top-0 bottom-0 w-2.5 cursor-col-resize hover:bg-amber-600/70 active:bg-amber-700 z-20 transition-colors print:hidden"
                                    onMouseDown={(e) => handleColumnResizeStart('product_ta', e)}
                                    title="Drag to resize Tamil Product column"
                                  />
                                </th>
                              )}

                              {/* Unit Header */}
                              {showUnit && (
                                <th
                                  className="py-0.5 text-center border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: `${colWidths.unit}px`,
                                    paddingLeft: `${editForm.table_col_padding || 4}px`,
                                    paddingRight: `${editForm.table_col_padding || 4}px`,
                                  }}
                                >
                                  <span className="hidden print:block w-full text-center font-black uppercase text-[10px] leading-tight py-0.5 break-words">
                                    {editForm.header_unit || 'UNIT'}
                                  </span>
                                  <textarea
                                    rows={2}
                                    value={editForm.header_unit || 'UNIT'}
                                    onChange={(e) => handleInputChange('header_unit', e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.blur(); } }}
                                    title="Click to edit header"
                                    className="print:hidden w-full h-full bg-transparent border-0 text-center font-black uppercase text-[10px] leading-tight resize-none whitespace-pre-wrap break-words overflow-hidden focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-0.5 cursor-text hover:bg-black/5 transition-colors focus:outline-none py-1"
                                  />
                                  <div
                                    className="absolute right-0 top-0 bottom-0 w-2.5 cursor-col-resize hover:bg-amber-600/70 active:bg-amber-700 z-20 transition-colors print:hidden"
                                    onMouseDown={(e) => handleColumnResizeStart('unit', e)}
                                    title="Drag to resize Unit column"
                                  />
                                </th>
                              )}

                              {/* Rate (MRP) Header */}
                              {showMrpCol && (
                                <th
                                  className="py-0.5 text-right border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: `${colWidths.mrp}px`,
                                    paddingLeft: `${editForm.table_col_padding || 4}px`,
                                    paddingRight: `${editForm.table_col_padding || 4}px`,
                                  }}
                                >
                                  <span className="hidden print:block w-full text-right font-black uppercase text-[10px] leading-tight py-0.5 break-words px-1">
                                    {editForm.header_mrp || 'RATE (₹)'}
                                  </span>
                                  <textarea
                                    rows={2}
                                    value={editForm.header_mrp || 'Rate (₹)'}
                                    onChange={(e) => handleInputChange('header_mrp', e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.blur(); } }}
                                    title="Click to edit header"
                                    className="print:hidden w-full h-full bg-transparent border-0 text-right font-black uppercase text-[10px] leading-tight resize-none whitespace-pre-wrap break-words overflow-hidden focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-1 cursor-text hover:bg-black/5 transition-colors focus:outline-none py-1"
                                  />
                                  <div
                                    className="absolute right-0 top-0 bottom-0 w-2.5 cursor-col-resize hover:bg-amber-600/70 active:bg-amber-700 z-20 transition-colors print:hidden"
                                    onMouseDown={(e) => handleColumnResizeStart('mrp', e)}
                                    title="Drag to resize Rate column"
                                  />
                                </th>
                              )}

                              {/* Offer Rate Header */}
                              {showOffer && (
                                <th
                                  className="py-0.5 text-right border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: `${colWidths.offer}px`,
                                    paddingLeft: `${editForm.table_col_padding || 4}px`,
                                    paddingRight: `${editForm.table_col_padding || 4}px`,
                                  }}
                                >
                                  <span className="hidden print:block w-full text-right font-black uppercase text-[10px] leading-tight py-0.5 break-words px-1">
                                    {editForm.header_offer || `${discountPercent}% OFFER RATE (₹)`}
                                  </span>
                                  <textarea
                                    rows={2}
                                    value={editForm.header_offer || `${discountPercent}% Offer Rate (₹)`}
                                    onChange={(e) => handleInputChange('header_offer', e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.blur(); } }}
                                    title="Click to edit header"
                                    className="print:hidden w-full h-full bg-transparent border-0 text-right font-black uppercase text-[10px] leading-tight resize-none whitespace-pre-wrap break-words overflow-hidden focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-1 cursor-text hover:bg-black/5 transition-colors focus:outline-none py-1"
                                  />
                                  <div
                                    className="absolute right-0 top-0 bottom-0 w-2.5 cursor-col-resize hover:bg-amber-600/70 active:bg-amber-700 z-20 transition-colors print:hidden"
                                    onMouseDown={(e) => handleColumnResizeStart('offer', e)}
                                    title="Drag to resize Offer Rate column"
                                  />
                                </th>
                              )}

                              {/* Req Header */}
                              {showReq && (
                                <th
                                  className="py-0.5 text-center border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: `${colWidths.req}px`,
                                    paddingLeft: `${editForm.table_col_padding || 4}px`,
                                    paddingRight: `${editForm.table_col_padding || 4}px`,
                                  }}
                                >
                                  <span className="hidden print:block w-full text-center font-black uppercase text-[10px] leading-tight py-0.5 break-words">
                                    {editForm.header_req || 'REQ'}
                                  </span>
                                  <textarea
                                    rows={2}
                                    value={editForm.header_req || 'Req'}
                                    onChange={(e) => handleInputChange('header_req', e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); e.target.blur(); } }}
                                    title="Click to edit header"
                                    className="print:hidden w-full h-full bg-transparent border-0 text-center font-black uppercase text-[10px] leading-tight resize-none whitespace-pre-wrap break-words overflow-hidden focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-0.5 cursor-text hover:bg-black/5 transition-colors focus:outline-none py-1"
                                  />
                                </th>
                              )}
                            </tr>
                          </thead>
                          <tbody className="font-bold text-slate-900 text-[11px]">
                            {chunkCategories.map((category) => (
                              <React.Fragment key={category.id}>
                                <tr className={`${theme.categoryBar} h-[24px]`}>
                                  <td colSpan={activeColCount || 1} className="py-0.5 text-center text-[11px] font-black tracking-wider uppercase border border-slate-400 p-0" style={{ paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                    <input
                                      type="text"
                                      value={category.name}
                                      onChange={(e) => handleInlineCategoryChange(category.id, e.target.value)}
                                      onBlur={(e) => handleInlineCategorySave(category.id, e.target.value)}
                                      onFocus={(e) => e.target.select()}
                                      title="Click to edit category name inline like Excel"
                                      className="w-full bg-transparent border-0 text-center text-[11px] font-black tracking-wider uppercase focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-1 cursor-text hover:bg-black/5 transition-colors focus:outline-none"
                                    />
                                  </td>
                                </tr>

                                {category.products.map((product, idx) => {
                                  const absoluteIndex = allFilteredProducts.findIndex((p) => p.id === product.id);
                                  const currentSno = absoluteIndex !== -1 ? absoluteIndex + 1 : (chunkIdx * pageSize) + idx + 1;

                                  return (
                                    <tr key={product.id} className="hover:bg-amber-50/40 transition-colors text-black font-extrabold" style={{ height: `${editForm.table_row_height || 22}px` }}>
                                      {/* S.No / Code Cell */}
                                      {showSno && (
                                        <td className="py-0 text-center text-black font-extrabold border border-slate-400 text-[11px] p-0" style={{ width: `${colWidths.sno}px`, paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          <input
                                            type="text"
                                            data-excel-row={absoluteIndex}
                                            data-excel-col={0}
                                            value={product.product_code !== null && product.product_code !== undefined ? product.product_code : currentSno}
                                            onChange={(e) => handleInlineProductChange(product.id, 'product_code', e.target.value)}
                                            onBlur={(e) => handleInlineProductSave(product.id, 'product_code', e.target.value)}
                                            onFocus={(e) => e.target.select()}
                                            onKeyDown={(e) => handleExcelGridKeyDown(e, absoluteIndex, 0)}
                                            onPaste={(e) => handleExcelGridPaste(e, absoluteIndex, 0)}
                                            className="w-full bg-transparent border-0 text-center text-black font-extrabold text-[11px] focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-0.5 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md"
                                          />
                                        </td>
                                      )}

                                      {/* Product Name Cell (English) */}
                                      {showProduct && (
                                        <td className="py-0 font-extrabold text-black border border-slate-400 leading-tight text-[11px] p-0" style={{ width: `${colWidths.product}px`, paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          <input
                                            type="text"
                                            data-excel-row={absoluteIndex}
                                            data-excel-col={1}
                                            value={product.name}
                                            onChange={(e) => handleInlineProductChange(product.id, 'name', e.target.value)}
                                            onBlur={(e) => handleInlineProductSave(product.id, 'name', e.target.value)}
                                            onFocus={(e) => e.target.select()}
                                            onKeyDown={(e) => handleExcelGridKeyDown(e, absoluteIndex, 1)}
                                            onPaste={(e) => handleExcelGridPaste(e, absoluteIndex, 1)}
                                            className="w-full bg-transparent border-0 font-extrabold text-black text-[11px] leading-tight focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-1 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md"
                                          />
                                        </td>
                                      )}

                                      {/* Tamil Product Name Cell */}
                                      {showTamilName && (
                                        <td className="py-0 font-extrabold text-black border border-slate-400 leading-tight text-[11px] p-0" style={{ width: `${colWidths.product_ta || 160}px`, paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          <input
                                            type="text"
                                            data-excel-row={absoluteIndex}
                                            data-excel-col={2}
                                            value={product.name_ta || ''}
                                            onChange={(e) => handleInlineProductChange(product.id, 'name_ta', e.target.value)}
                                            onBlur={(e) => handleInlineProductSave(product.id, 'name_ta', e.target.value)}
                                            onFocus={(e) => e.target.select()}
                                            onKeyDown={(e) => handleExcelGridKeyDown(e, absoluteIndex, 2)}
                                            onPaste={(e) => handleExcelGridPaste(e, absoluteIndex, 2)}
                                            placeholder="பொருள் பெயர்"
                                            className="w-full bg-transparent border-0 font-extrabold text-black text-[11px] leading-tight focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-1 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md"
                                          />
                                        </td>
                                      )}

                                      {/* Unit / Pack Size Cell */}
                                      {showUnit && (
                                        <td className="py-0 text-center text-black border border-slate-400 font-extrabold text-[10.5px] leading-tight p-0" style={{ width: `${colWidths.unit}px`, paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          <input
                                            type="text"
                                            data-excel-row={absoluteIndex}
                                            data-excel-col={showTamilName ? 3 : 2}
                                            value={product.pack_size}
                                            onChange={(e) => handleInlineProductChange(product.id, 'pack_size', e.target.value)}
                                            onBlur={(e) => handleInlineProductSave(product.id, 'pack_size', e.target.value)}
                                            onFocus={(e) => e.target.select()}
                                            onKeyDown={(e) => handleExcelGridKeyDown(e, absoluteIndex, showTamilName ? 3 : 2)}
                                            onPaste={(e) => handleExcelGridPaste(e, absoluteIndex, showTamilName ? 3 : 2)}
                                            className="w-full bg-transparent border-0 text-center font-extrabold text-black text-[10.5px] leading-tight focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-0.5 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md"
                                          />
                                        </td>
                                      )}

                                      {/* Rate (MRP) Cell */}
                                      {showMrpCol && (
                                        <td className="py-0 text-right text-black font-extrabold border border-slate-400 text-[11px] p-0" style={{ width: `${colWidths.mrp}px`, paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          <input
                                            type="text"
                                            data-excel-row={absoluteIndex}
                                            data-excel-col={showTamilName ? 4 : 3}
                                            value={product.mrp}
                                            onChange={(e) => handleInlineMrpChange(product.id, e.target.value)}
                                            onBlur={(e) => handleInlineMrpSave(product.id, e.target.value)}
                                            onFocus={(e) => e.target.select()}
                                            onKeyDown={(e) => handleExcelGridKeyDown(e, absoluteIndex, showTamilName ? 4 : 3)}
                                            onPaste={(e) => handleExcelGridPaste(e, absoluteIndex, showTamilName ? 4 : 3)}
                                            className="w-full bg-transparent border-0 text-right font-extrabold text-black text-[11px] focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-1 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md"
                                          />
                                        </td>
                                      )}

                                      {/* Offer Rate Cell */}
                                      {showOffer && (
                                        <td className="py-0 text-right font-extrabold text-black border border-slate-400 text-[11px] p-0" style={{ width: `${colWidths.offer}px`, paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          <input
                                            type="text"
                                            data-excel-row={absoluteIndex}
                                            data-excel-col={showTamilName ? 5 : 4}
                                            value={product.selling_price}
                                            onChange={(e) => handleInlineOfferChange(product.id, e.target.value)}
                                            onBlur={(e) => handleInlineProductSave(product.id, 'selling_price', e.target.value)}
                                            onFocus={(e) => e.target.select()}
                                            onKeyDown={(e) => handleExcelGridKeyDown(e, absoluteIndex, showTamilName ? 5 : 4)}
                                            onPaste={(e) => handleExcelGridPaste(e, absoluteIndex, showTamilName ? 5 : 4)}
                                            className="w-full bg-transparent border-0 text-right font-extrabold text-black text-[11px] focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-1 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md"
                                          />
                                        </td>
                                      )}

                                      {/* Req Cell */}
                                      {showReq && (
                                        <td className="py-0 text-center font-extrabold text-black border border-slate-400 text-[11px] p-0" style={{ width: `${colWidths.req}px`, paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          <input
                                            type="text"
                                            data-excel-row={absoluteIndex}
                                            data-excel-col={showTamilName ? 6 : 5}
                                            value={product.req || ''}
                                            onChange={(e) => handleInlineProductChange(product.id, 'req', e.target.value)}
                                            onBlur={(e) => handleInlineProductSave(product.id, 'req', e.target.value)}
                                            onFocus={(e) => e.target.select()}
                                            onKeyDown={(e) => handleExcelGridKeyDown(e, absoluteIndex, showTamilName ? 6 : 5)}
                                            onPaste={(e) => handleExcelGridPaste(e, absoluteIndex, showTamilName ? 6 : 5)}
                                            className="w-full bg-transparent border-0 text-center font-extrabold text-black text-[11px] focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-0.5 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md"
                                          />
                                        </td>
                                      )}
                                    </tr>
                                  );
                                })}
                              </React.Fragment>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}

                    {/* RIGHT AFTER TABLE ENDS: CLEAN TABLE-MATCHING PAYMENT BLOCK */}
                    {(editForm.footer_position || 'below_table') === 'below_table' && chunkIdx === productPageChunks.length - 1 && (() => {
                      const hasQr2 = !!(editForm.store_upi_qr_2 || editForm.store_gpay_2);
                      const showQr = editForm.show_upi_qr !== false;
                      const showBank = editForm.show_bank_details !== false;

                      return (
                        <div className="mt-2 bg-white border-2 border-slate-700 rounded-xl overflow-hidden shadow-sm p-2 font-sans space-y-2">
                          {/* Top Row: QR Codes */}
                          {showQr && (
                            <div className={`grid ${hasQr2 ? 'grid-cols-2' : (showBank ? 'grid-cols-2' : 'grid-cols-1')} gap-3 text-slate-900 items-stretch`}>
                              {/* QR Code Card 1 */}
                              <div className="flex flex-col items-center justify-center text-center space-y-1 p-1.5 border border-slate-200 rounded-lg bg-slate-50/50">
                                <div className={`${theme.tableHeader} text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded w-full flex items-center justify-center gap-1.5`}>
                                  <i className="fa-solid fa-qrcode"></i> SCAN & PAY VIA UPI
                                </div>
                                <div className="flex flex-col items-center justify-center w-full my-0.5">
                                  <div className="p-1 bg-white border border-slate-300 rounded-lg shadow-xs">
                                    <img
                                      src={
                                        editForm.store_upi_qr || settings?.store_upi_qr
                                          ? getImageUrl(editForm.store_upi_qr || settings.store_upi_qr)
                                          : `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=${encodeURIComponent(editForm.store_gpay || '9787772038')}%40okicici&pn=${encodeURIComponent(editForm.store_name)}`
                                      }
                                      alt="UPI QR Code 1"
                                      className="w-22 h-22 sm:w-26 sm:h-26 object-contain"
                                    />
                                  </div>
                                  {editForm.store_upi_name && (
                                    <p className="text-[9px] font-black text-slate-900 mt-1">
                                      {editForm.store_upi_name}
                                    </p>
                                  )}
                                  <p className="text-[9px] font-black font-mono text-slate-900 mt-0.5">
                                    UPI: {editForm.store_gpay || editForm.store_phone_3 || '9787772038'}
                                  </p>
                                </div>
                              </div>

                              {/* QR Code Card 2 (If uploaded / present) */}
                              {hasQr2 && (
                                <div className="flex flex-col items-center justify-center text-center space-y-1 p-1.5 border border-slate-200 rounded-lg bg-slate-50/50">
                                  <div className={`${theme.tableHeader} text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded w-full flex items-center justify-center gap-1.5`}>
                                    <i className="fa-solid fa-qrcode"></i> SCAN & PAY VIA UPI
                                  </div>
                                  <div className="flex flex-col items-center justify-center w-full my-0.5">
                                    <div className="p-1 bg-white border border-slate-300 rounded-lg shadow-xs">
                                      <img
                                        src={
                                          editForm.store_upi_qr_2
                                            ? getImageUrl(editForm.store_upi_qr_2)
                                            : `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=${encodeURIComponent(editForm.store_gpay_2)}%40okicici&pn=${encodeURIComponent(editForm.store_name)}`
                                        }
                                        alt="UPI QR Code 2"
                                        className="w-22 h-22 sm:w-26 sm:h-26 object-contain"
                                      />
                                    </div>
                                    {editForm.store_upi_name_2 && (
                                      <p className="text-[9px] font-black text-slate-900 mt-1">
                                        {editForm.store_upi_name_2}
                                      </p>
                                    )}
                                    {editForm.store_gpay_2 && (
                                      <p className="text-[9px] font-black font-mono text-slate-900 mt-0.5">
                                        UPI: {editForm.store_gpay_2}
                                      </p>
                                    )}
                                  </div>
                                </div>
                              )}

                              {/* Bank Details on Right if ONLY 1 QR code */}
                              {!hasQr2 && showBank && (
                                <div className="flex flex-col justify-between space-y-1.5 p-1.5 border border-slate-200 rounded-lg bg-slate-50/50 h-full">
                                  <div className={`${theme.tableHeader} text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded w-full flex items-center gap-1.5`}>
                                    <i className="fa-solid fa-building-columns"></i> BANK ACCOUNT INFO
                                  </div>
                                  <div className="border border-slate-300 rounded-md overflow-hidden bg-white text-[10px]">
                                    <div className="flex justify-between border-b border-slate-200 px-2 py-1">
                                      <span className="text-slate-500 font-bold">Account Name</span>
                                      <span className="font-black text-slate-900 text-right">{editForm.bank_name}</span>
                                    </div>
                                    <div className="flex justify-between border-b border-slate-200 px-2 py-1">
                                      <span className="text-slate-500 font-bold">Bank / Branch</span>
                                      <span className="font-black text-slate-900 text-right">{editForm.bank_branch}</span>
                                    </div>
                                    <div className="flex justify-between border-b border-slate-200 px-2 py-1">
                                      <span className="text-slate-500 font-bold">Account No</span>
                                      <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_account_no}</span>
                                    </div>
                                    <div className="flex justify-between px-2 py-1">
                                      <span className="text-slate-500 font-bold">IFSC Code</span>
                                      <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_ifsc}</span>
                                    </div>
                                  </div>
                                  <div className="text-center text-slate-700 text-[9.5px] font-bold">
                                    ⚡ Quick Bank Transfer / IMPS Available
                                  </div>
                                </div>
                              )}
                            </div>
                          )}

                          {/* Bank Details BELOW QR cards if HAS QR 2 */}
                          {hasQr2 && showBank && (
                            <div className="flex flex-col justify-between space-y-1.5 p-1.5 border border-slate-200 rounded-lg bg-slate-50/50 w-full">
                              <div className={`${theme.tableHeader} text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded w-full flex items-center gap-1.5`}>
                                <i className="fa-solid fa-building-columns"></i> BANK ACCOUNT INFO
                              </div>
                              <div className="grid grid-cols-2 gap-2 border border-slate-300 rounded-md overflow-hidden bg-white text-[10px] p-1.5">
                                <div className="flex justify-between border-b border-slate-100 pb-1">
                                  <span className="text-slate-500 font-bold">Account Name:</span>
                                  <span className="font-black text-slate-900">{editForm.bank_name}</span>
                                </div>
                                <div className="flex justify-between border-b border-slate-100 pb-1">
                                  <span className="text-slate-500 font-bold">Bank / Branch:</span>
                                  <span className="font-black text-slate-900">{editForm.bank_branch}</span>
                                </div>
                                <div className="flex justify-between pt-1">
                                  <span className="text-slate-500 font-bold">Account No:</span>
                                  <span className="font-mono font-black text-slate-900">{editForm.bank_account_no}</span>
                                </div>
                                <div className="flex justify-between pt-1">
                                  <span className="text-slate-500 font-bold">IFSC Code:</span>
                                  <span className="font-mono font-black text-slate-900">{editForm.bank_ifsc}</span>
                                </div>
                              </div>
                              <div className="text-center text-slate-700 text-[9.5px] font-bold">
                                ⚡ Quick Bank Transfer / IMPS Available
                              </div>
                            </div>
                          )}

                          {/* Bank Details full width if NO QR code */}
                          {!showQr && showBank && (
                            <div className="flex flex-col justify-between space-y-1.5 p-1.5 border border-slate-200 rounded-lg bg-slate-50/50 w-full">
                              <div className={`${theme.tableHeader} text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded w-full flex items-center gap-1.5`}>
                                <i className="fa-solid fa-building-columns"></i> BANK ACCOUNT INFO
                              </div>
                              <div className="border border-slate-300 rounded-md overflow-hidden bg-white text-[10px]">
                                <div className="flex justify-between border-b border-slate-200 px-2 py-1">
                                  <span className="text-slate-500 font-bold">Account Name</span>
                                  <span className="font-black text-slate-900 text-right">{editForm.bank_name}</span>
                                </div>
                                <div className="flex justify-between border-b border-slate-200 px-2 py-1">
                                  <span className="text-slate-500 font-bold">Bank / Branch</span>
                                  <span className="font-black text-slate-900 text-right">{editForm.bank_branch}</span>
                                </div>
                                <div className="flex justify-between border-b border-slate-200 px-2 py-1">
                                  <span className="text-slate-500 font-bold">Account No</span>
                                  <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_account_no}</span>
                                </div>
                                <div className="flex justify-between px-2 py-1">
                                  <span className="text-slate-500 font-bold">IFSC Code</span>
                                  <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_ifsc}</span>
                                </div>
                              </div>
                              <div className="text-center text-slate-700 text-[9.5px] font-bold">
                                ⚡ Quick Bank Transfer / IMPS Available
                              </div>
                            </div>
                          )}

                          {/* Tamil Festive Greeting Message Notice Banner */}
                          {editForm.store_notice && (
                            <div className="mt-1.5 p-2 rounded-lg border-2 border-amber-400 bg-amber-50/70 text-center font-bold text-[10px] leading-snug text-amber-950">
                              {renderFormattedText(editForm.store_notice)}
                            </div>
                          )}

                          {/* Bottom Tamil Important Notes Box matching reference screenshot */}
                          {(editForm.important_note_1 || editForm.important_note_2) && (
                            <div className="mt-3 bg-amber-50/90 border-2 border-amber-300 rounded-xl p-3.5 text-center shadow-xs space-y-1.5">
                              {editForm.important_note_1 && (
                                renderFormattedText(
                                  editForm.important_note_1,
                                  "text-slate-900 font-black text-xs sm:text-[12px] leading-relaxed"
                                )
                              )}
                              {editForm.important_note_2 && (
                                renderFormattedText(
                                  editForm.important_note_2,
                                  "text-red-700 font-black text-xs sm:text-[12px] leading-relaxed"
                                )
                              )}
                            </div>
                          )}
                        </div>
                      );
                    })()}
                  </div>
                </div>
              </div>
            );
          })}

          {/* A4 STANDALONE BACK COVER / FOOTER PAGE */}
          {editForm.footer_position === 'new_page' && (
            <div className="w-full max-w-[210mm] print:w-[210mm]">
              <div
                className="a4-page-sheet w-[210mm] h-[297mm] max-h-[297mm] overflow-hidden text-slate-900 transition-all duration-300 relative shadow-2xl flex flex-col justify-between p-4 sm:p-5 select-none mx-auto break-after-page bg-cover bg-center bg-no-repeat box-border"
                style={{ backgroundImage: `url(${editForm.store_cover_bg ? getImageUrl(editForm.store_cover_bg) : '/images/cover_bg.jpg'})`, pageBreakAfter: 'always' }}
              >
                {/* Content Starts Right at Top (Same Level as Product Tables) */}
                <div className="w-full space-y-4 mt-0">
                  {(() => {
                    const hasQr2 = !!(editForm.store_upi_qr_2 || editForm.store_gpay_2);
                    const showQr = editForm.show_upi_qr !== false;
                    const showBank = editForm.show_bank_details !== false;

                    return (
                      <div className="space-y-4 w-full">
                        {/* Top Row: QR Codes */}
                        {showQr && (
                          <div className={`grid ${hasQr2 ? 'grid-cols-2' : (showBank ? 'grid-cols-2' : 'grid-cols-1')} gap-4 items-stretch`}>
                            {/* QR Code Card 1 */}
                            <div className="flex flex-col items-center justify-center text-center space-y-3 p-5 border-2 border-amber-400 rounded-2xl bg-white/95 shadow-md backdrop-blur-xs">
                              <div className={`${theme.tableHeader} text-slate-950 text-xs font-black uppercase px-4 py-1.5 rounded-full flex items-center justify-center gap-2 border border-amber-400`}>
                                <i className="fa-solid fa-qrcode"></i> SCAN & PAY VIA UPI
                              </div>
                              <div className="flex flex-col items-center justify-center space-y-1 w-full my-1">
                                <div className="p-2 bg-white border-2 border-amber-300 rounded-xl shadow-sm">
                                  <img
                                    src={
                                      editForm.store_upi_qr || settings?.store_upi_qr
                                        ? getImageUrl(editForm.store_upi_qr || settings.store_upi_qr)
                                        : `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=${encodeURIComponent(editForm.store_gpay || '9787772038')}%40okicici&pn=${encodeURIComponent(editForm.store_name)}`
                                    }
                                    alt="UPI QR Code 1"
                                    className="w-32 h-32 object-contain"
                                  />
                                </div>
                                {editForm.store_upi_name && (
                                  <p className="text-xs font-black text-slate-900 mt-1">
                                    {editForm.store_upi_name}
                                  </p>
                                )}
                                <p className="text-xs font-black font-mono text-slate-900 mt-0.5">
                                  UPI: {editForm.store_gpay || editForm.store_phone_3 || '9787772038'}
                                </p>
                              </div>
                            </div>

                            {/* QR Code Card 2 (If uploaded / present) */}
                            {hasQr2 && (
                              <div className="flex flex-col items-center justify-center text-center space-y-3 p-5 border-2 border-amber-400 rounded-2xl bg-white/95 shadow-md backdrop-blur-xs">
                                <div className={`${theme.tableHeader} text-slate-950 text-xs font-black uppercase px-4 py-1.5 rounded-full flex items-center justify-center gap-2 border border-amber-400`}>
                                  <i className="fa-solid fa-qrcode"></i> SCAN & PAY VIA UPI
                                </div>
                                <div className="flex flex-col items-center justify-center space-y-1 w-full my-1">
                                  <div className="p-2 bg-white border-2 border-amber-300 rounded-xl shadow-sm">
                                    <img
                                      src={
                                        editForm.store_upi_qr_2
                                          ? getImageUrl(editForm.store_upi_qr_2)
                                          : `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=${encodeURIComponent(editForm.store_gpay_2)}%40okicici&pn=${encodeURIComponent(editForm.store_name)}`
                                      }
                                      alt="UPI QR Code 2"
                                      className="w-32 h-32 object-contain"
                                    />
                                  </div>
                                  {editForm.store_upi_name_2 && (
                                    <p className="text-xs font-black text-slate-900 mt-1">
                                      {editForm.store_upi_name_2}
                                    </p>
                                  )}
                                  {editForm.store_gpay_2 && (
                                    <p className="text-xs font-black font-mono text-slate-900 mt-0.5">
                                      UPI: {editForm.store_gpay_2}
                                    </p>
                                  )}
                                </div>
                              </div>
                            )}

                            {/* Bank Details on Right if ONLY 1 QR code */}
                            {!hasQr2 && showBank && (
                              <div className="flex flex-col justify-between space-y-3 p-5 border-2 border-amber-400 rounded-2xl bg-white/95 shadow-md backdrop-blur-xs h-full">
                                <div className={`${theme.tableHeader} text-slate-950 text-xs font-black uppercase px-4 py-1.5 rounded-full flex items-center gap-2 border border-amber-400`}>
                                  <i className="fa-solid fa-building-columns"></i> BANK ACCOUNT INFO
                                </div>
                                <div className="border border-slate-300 rounded-xl overflow-hidden bg-white text-xs">
                                  <div className="flex justify-between border-b border-slate-200 p-2.5">
                                    <span className="text-slate-500 font-bold">Account Name</span>
                                    <span className="font-black text-slate-900 text-right">{editForm.bank_name}</span>
                                  </div>
                                  <div className="flex justify-between border-b border-slate-200 p-2.5">
                                    <span className="text-slate-500 font-bold">Bank / Branch</span>
                                    <span className="font-black text-slate-900 text-right">{editForm.bank_branch}</span>
                                  </div>
                                  <div className="flex justify-between border-b border-slate-200 p-2.5">
                                    <span className="text-slate-500 font-bold">Account No</span>
                                    <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_account_no}</span>
                                  </div>
                                  <div className="flex justify-between p-2.5">
                                    <span className="text-slate-500 font-bold">IFSC Code</span>
                                    <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_ifsc}</span>
                                  </div>
                                </div>
                                <div className="text-center text-slate-700 text-xs font-bold bg-amber-100/80 p-2 rounded-lg border border-amber-300">
                                  ⚡ Quick Bank Transfer / IMPS Available
                                </div>
                              </div>
                            )}
                          </div>
                        )}

                        {/* Bank Details BELOW QR cards if HAS QR 2 */}
                        {hasQr2 && showBank && (
                          <div className="flex flex-col justify-between space-y-3 p-5 border-2 border-amber-400 rounded-2xl bg-white/95 shadow-md backdrop-blur-xs w-full">
                            <div className={`${theme.tableHeader} text-slate-950 text-xs font-black uppercase px-4 py-1.5 rounded-full flex items-center gap-2 border border-amber-400`}>
                              <i className="fa-solid fa-building-columns"></i> BANK ACCOUNT INFO
                            </div>
                            <div className="grid grid-cols-2 gap-3 border border-slate-300 rounded-xl overflow-hidden bg-white text-xs p-3">
                              <div className="flex justify-between border-b border-slate-200 pb-2">
                                <span className="text-slate-500 font-bold">Account Name:</span>
                                <span className="font-black text-slate-900">{editForm.bank_name}</span>
                              </div>
                              <div className="flex justify-between border-b border-slate-200 pb-2">
                                <span className="text-slate-500 font-bold">Bank / Branch:</span>
                                <span className="font-black text-slate-900">{editForm.bank_branch}</span>
                              </div>
                              <div className="flex justify-between pt-2">
                                <span className="text-slate-500 font-bold">Account No:</span>
                                <span className="font-mono font-black text-slate-900">{editForm.bank_account_no}</span>
                              </div>
                              <div className="flex justify-between pt-2">
                                <span className="text-slate-500 font-bold">IFSC Code:</span>
                                <span className="font-mono font-black text-slate-900">{editForm.bank_ifsc}</span>
                              </div>
                            </div>
                            <div className="text-center text-slate-700 text-xs font-bold bg-amber-100/80 p-2 rounded-lg border border-amber-300">
                              ⚡ Quick Bank Transfer / IMPS Available
                            </div>
                          </div>
                        )}

                        {/* Bank Details full width if NO QR code */}
                        {!showQr && showBank && (
                          <div className="flex flex-col justify-between space-y-3 p-5 border-2 border-amber-400 rounded-2xl bg-white/95 shadow-md backdrop-blur-xs w-full">
                            <div className={`${theme.tableHeader} text-slate-950 text-xs font-black uppercase px-4 py-1.5 rounded-full flex items-center gap-2 border border-amber-400`}>
                              <i className="fa-solid fa-building-columns"></i> BANK ACCOUNT INFO
                            </div>
                            <div className="border border-slate-300 rounded-xl overflow-hidden bg-white text-xs">
                              <div className="flex justify-between border-b border-slate-200 p-2.5">
                                <span className="text-slate-500 font-bold">Account Name</span>
                                <span className="font-black text-slate-900 text-right">{editForm.bank_name}</span>
                              </div>
                              <div className="flex justify-between border-b border-slate-200 p-2.5">
                                <span className="text-slate-500 font-bold">Bank / Branch</span>
                                <span className="font-black text-slate-900 text-right">{editForm.bank_branch}</span>
                              </div>
                              <div className="flex justify-between border-b border-slate-200 p-2.5">
                                <span className="text-slate-500 font-bold">Account No</span>
                                <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_account_no}</span>
                              </div>
                              <div className="flex justify-between p-2.5">
                                <span className="text-slate-500 font-bold">IFSC Code</span>
                                <span className="font-mono font-black text-slate-900 text-right">{editForm.bank_ifsc}</span>
                              </div>
                            </div>
                            <div className="text-center text-slate-700 text-xs font-bold bg-amber-100/80 p-2 rounded-lg border border-amber-300">
                              ⚡ Quick Bank Transfer / IMPS Available
                            </div>
                          </div>
                        )}
                      </div>
                    );
                  })()}

                  {/* Important Notes */}
                  {(editForm.important_note_1 || editForm.important_note_2) && (
                    <div className="bg-white/95 border-2 border-amber-400 rounded-2xl p-4 text-center shadow-md backdrop-blur-xs space-y-1.5">
                      {editForm.important_note_1 && (
                        renderFormattedText(
                          editForm.important_note_1,
                          "text-slate-900 font-black text-xs sm:text-sm leading-relaxed"
                        )
                      )}
                      {editForm.important_note_2 && (
                        renderFormattedText(
                          editForm.important_note_2,
                          "text-red-700 font-black text-xs sm:text-sm leading-relaxed"
                        )
                      )}
                    </div>
                  )}
                </div>

              </div>
            </div>
          )}

        </div>
        {/* ─── Modal: Add Product ─────────────────────────────────────────── */}
        {productModalOpen && (
          <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="bg-white border border-slate-200 w-full max-w-xl rounded-3xl p-6 shadow-2xl space-y-5 overflow-y-auto max-h-[90vh] animate-scale-up">
              <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 className="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                  <i className="fa-solid fa-circle-plus text-indigo-600"></i> Add New Product
                </h3>
                <button
                  onClick={() => setProductModalOpen(false)}
                  className="text-slate-400 hover:text-slate-600"
                >
                  <i className="fa-solid fa-circle-xmark text-lg"></i>
                </button>
              </div>

              <form onSubmit={handleProductSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-black text-slate-700 block mb-1">Category <span className="text-red-500">*</span></label>
                    <select
                      value={productFormData.category_id}
                      onChange={(e) => setProductFormData({ ...productFormData, category_id: e.target.value })}
                      required
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                      <option value="">Select Category</option>
                      {categories.map((cat) => (
                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="text-xs font-black text-slate-700 block mb-1">Product Code</label>
                    <input
                      type="text"
                      value={productFormData.product_code}
                      onChange={(e) => setProductFormData({ ...productFormData, product_code: e.target.value })}
                      placeholder="e.g. 001"
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                  </div>
                </div>

                <div>
                  <label className="text-xs font-black text-slate-700 block mb-1">Product Name <span className="text-red-500">*</span></label>
                  <input
                    type="text"
                    value={productFormData.name}
                    onChange={(e) => setProductFormData({ ...productFormData, name: e.target.value })}
                    required
                    placeholder="e.g. 7cm Sparklers"
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="text-xs font-black text-slate-700 block mb-1">Pack Size <span className="text-red-500">*</span></label>
                    <input
                      type="text"
                      value={productFormData.pack_size}
                      onChange={(e) => setProductFormData({ ...productFormData, pack_size: e.target.value })}
                      required
                      placeholder="e.g. 1 Box (10 Pcs)"
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-black text-slate-700 block mb-1">MRP (₹) <span className="text-red-500">*</span></label>
                    <input
                      type="number"
                      value={productFormData.mrp}
                      onChange={(e) => {
                        const val = parseFloat(e.target.value) || 0;
                        const disc = productFormData.discount_percent || 60;
                        const calcSelling = Math.round(val * (1 - disc / 100));
                        setProductFormData({ ...productFormData, mrp: val, selling_price: calcSelling });
                      }}
                      required
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-black text-slate-700 block mb-1">Offer Price (₹) <span className="text-red-500">*</span></label>
                    <input
                      type="number"
                      value={productFormData.selling_price}
                      onChange={(e) => setProductFormData({ ...productFormData, selling_price: parseFloat(e.target.value) || 0 })}
                      required
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-black text-indigo-700"
                    />
                  </div>
                </div>

                <div className="flex justify-end gap-3 pt-3 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setProductModalOpen(false)}
                    className="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow"
                  >
                    Save Product
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* ─── Modal: Excel Import ────────────────────────────────────────── */}
        {importModalOpen && (
          <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="bg-white border border-slate-200 w-full max-w-xl rounded-3xl p-6 shadow-2xl space-y-5 overflow-y-auto max-h-[90vh] animate-scale-up">
              <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 className="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                  <i className="fa-solid fa-file-excel text-emerald-500"></i> Import Products from Excel
                </h3>
                <button
                  onClick={() => setImportModalOpen(false)}
                  className="text-slate-400 hover:text-slate-600"
                >
                  <i className="fa-solid fa-circle-xmark text-lg"></i>
                </button>
              </div>

              <div className="bg-amber-50/80 border border-amber-200 rounded-2xl p-4 space-y-3">
                <p className="text-[11px] font-black text-amber-900 flex items-center gap-1.5 uppercase tracking-wide">
                  <i className="fa-solid fa-file-excel text-emerald-600 text-sm"></i> Required Excel Columns Format
                </p>
                <div className="flex flex-wrap gap-1.5">
                  {[
                    { name: 'Category', req: true },
                    { name: 'S.No / Code', req: false },
                    { name: 'Product Name', req: true },
                    { name: 'Unit', req: true },
                    { name: 'Rate (MRP)', req: true },
                    { name: 'Offer Rate', req: true },
                  ].map((col) => (
                    <span key={col.name} className="bg-white border border-amber-300 px-2.5 py-1 rounded-xl font-black text-amber-950 text-[10px] shadow-xs">
                      {col.name} {col.req && <span className="text-red-600">*</span>}
                    </span>
                  ))}
                </div>

                {/* Mini Excel Preview Table */}
                <div className="overflow-hidden rounded-xl border border-amber-300 shadow-xs bg-white text-[10px]">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="bg-amber-100 text-amber-950 font-black border-b border-amber-300">
                        <th className="p-1.5 border-r border-amber-200">Category</th>
                        <th className="p-1.5 border-r border-amber-200 text-center">S.No</th>
                        <th className="p-1.5 border-r border-amber-200">Product Name</th>
                        <th className="p-1.5 border-r border-amber-200">Unit</th>
                        <th className="p-1.5 border-r border-amber-200 text-right">Rate (₹)</th>
                        <th className="p-1.5 text-right">Offer Rate (₹)</th>
                      </tr>
                    </thead>
                    <tbody className="font-semibold text-slate-800">
                      <tr className="border-b border-slate-100 bg-amber-50/40">
                        <td className="p-1.5 border-r border-slate-200 font-bold text-amber-900">SPARKLERS</td>
                        <td className="p-1.5 border-r border-slate-200 text-center">1</td>
                        <td className="p-1.5 border-r border-slate-200 font-bold">7cm Electric Sparklers</td>
                        <td className="p-1.5 border-r border-slate-200">1 Packet (6 Pcs)</td>
                        <td className="p-1.5 border-r border-slate-200 text-right">3570.00</td>
                        <td className="p-1.5 text-right font-black text-emerald-700">892.50</td>
                      </tr>
                      <tr className="bg-white">
                        <td className="p-1.5 border-r border-slate-200 font-bold text-amber-900">GROUND CHAKKARS</td>
                        <td className="p-1.5 border-r border-slate-200 text-center">20</td>
                        <td className="p-1.5 border-r border-slate-200 font-bold">Ground Chakkars Baby</td>
                        <td className="p-1.5 border-r border-slate-200">1 Box (8 Pcs)</td>
                        <td className="p-1.5 border-r border-slate-200 text-right">4100.00</td>
                        <td className="p-1.5 text-right font-black text-emerald-700">1025.00</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="flex flex-wrap gap-2">
                <button
                  onClick={handleDownloadTemplate}
                  className="bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 font-bold px-3 py-2 rounded-xl text-[10px] uppercase tracking-wider transition-all active:scale-95 flex items-center gap-1.5"
                >
                  <i className="fa-solid fa-file-arrow-down"></i> Blank Template
                </button>
                <button
                  onClick={handleExportProducts}
                  className="bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 font-bold px-3 py-2 rounded-xl text-[10px] uppercase tracking-wider transition-all active:scale-95 flex items-center gap-1.5"
                >
                  <i className="fa-solid fa-download"></i> Export Existing Products
                </button>
              </div>

              <div
                className={`relative border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer ${dragActive ? 'border-emerald-400 bg-emerald-50' : importFile ? 'border-emerald-300 bg-emerald-50/50' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/50'
                  }`}
                onDragEnter={handleDrag}
                onDragLeave={handleDrag}
                onDragOver={handleDrag}
                onDrop={handleDrop}
                onClick={() => fileInputRef.current?.click()}
              >
                <input
                  ref={fileInputRef}
                  type="file"
                  accept=".xlsx,.xls,.csv"
                  onChange={handleFileSelect}
                  className="hidden"
                />
                {importFile ? (
                  <div className="space-y-2">
                    <i className="fa-solid fa-file-excel text-3xl text-emerald-600"></i>
                    <p className="text-sm font-bold text-slate-800">{importFile.name}</p>
                    <p className="text-[10px] text-slate-400 font-semibold">{(importFile.size / 1024).toFixed(1)} KB</p>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        setImportFile(null);
                        setImportResult(null);
                      }}
                      className="text-[10px] text-rose-500 font-bold underline"
                    >
                      Remove file
                    </button>
                  </div>
                ) : (
                  <div className="space-y-2">
                    <i className="fa-solid fa-cloud-arrow-up text-3xl text-slate-400"></i>
                    <p className="text-xs font-bold text-slate-700">Drag & drop your Excel file here</p>
                    <p className="text-[10px] text-slate-400 font-semibold">or <span className="text-emerald-600 underline">click to browse</span></p>
                  </div>
                )}
              </div>

              {importResult && (
                <div className={`rounded-xl p-4 border ${importResult.error ? 'bg-rose-50 border-rose-200' : 'bg-emerald-50 border-emerald-200'}`}>
                  {importResult.error ? (
                    <p className="text-xs font-bold text-rose-700">{importResult.error}</p>
                  ) : (
                    <p className="text-xs font-bold text-emerald-700">Import Completed Successfully! Added {importResult.imported || 0} products.</p>
                  )}
                </div>
              )}

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setImportModalOpen(false)}
                  className="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                >
                  Cancel
                </button>
                {!importResult?.success && (
                  <button
                    type="button"
                    onClick={handleImportSubmit}
                    disabled={!importFile || importing}
                    className={`px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-2 ${!importFile || importing ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-500 text-white'
                      }`}
                  >
                    {importing ? <i className="fa-solid fa-spinner animate-spin"></i> : <i className="fa-solid fa-file-import"></i>}
                    Import Now
                  </button>
                )}
              </div>
            </div>
          </div>
        )}

        {/* SAVED PROJECTS MANAGER MODAL */}
        {showProjectsModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-fadeIn">
            <div className="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
              {/* Modal Header */}
              <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-lg shadow-sm">
                    <i className="fa-solid fa-folder-open"></i>
                  </div>
                  <div>
                    <h3 className="text-lg font-black text-slate-900 tracking-tight">Saved Projects Manager</h3>
                    <p className="text-xs text-slate-500 font-bold">Open, update, duplicate, export, or manage your price list projects</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setShowProjectsModal(false)}
                  className="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-700 flex items-center justify-center text-sm font-bold transition-all cursor-pointer"
                >
                  <i className="fa-solid fa-xmark"></i>
                </button>
              </div>

              {/* Search & Actions Bar */}
              <div className="p-4 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-3">
                <div className="relative w-full sm:w-72">
                  <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                  <input
                    type="text"
                    value={projectSearchQuery}
                    onChange={(e) => setProjectSearchQuery(e.target.value)}
                    placeholder="Search projects..."
                    className="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-3 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
                  <input
                    type="file"
                    accept=".json"
                    ref={projectFileInputRef}
                    onChange={handleImportProjectFile}
                    className="hidden"
                  />
                  <button
                    type="button"
                    onClick={() => projectFileInputRef.current?.click()}
                    className="bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 font-black px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer shadow-xs"
                  >
                    <i className="fa-solid fa-file-import text-indigo-600"></i> Import Project (.json)
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setNewProjectNameInput(editForm.store_name || 'My Price List Project');
                      setShowProjectsModal(false);
                      setShowSaveAsModal(true);
                    }}
                    className="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-4 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all shadow-xs cursor-pointer"
                  >
                    <i className="fa-solid fa-plus"></i> Save Current as New
                  </button>
                </div>
              </div>

              {/* Projects List Container */}
              <div className="p-6 overflow-y-auto flex-1 space-y-3">
                {savedProjects.length === 0 ? (
                  <div className="text-center py-16 bg-slate-50/50 rounded-2xl border-2 border-dashed border-slate-200">
                    <div className="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-2xl mx-auto mb-3">
                      <i className="fa-solid fa-folder-plus"></i>
                    </div>
                    <h4 className="text-base font-black text-slate-900">No Saved Projects Yet</h4>
                    <p className="text-xs text-slate-500 max-w-sm mx-auto mt-1 font-semibold">
                      Save your current shop details, products, and configurations to reopen and edit them anytime.
                    </p>
                    <button
                      type="button"
                      onClick={() => {
                        setNewProjectNameInput(editForm.store_name || 'My Price List Project');
                        setShowProjectsModal(false);
                        setShowSaveAsModal(true);
                      }}
                      className="mt-4 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-5 py-2 rounded-xl text-xs inline-flex items-center gap-2 shadow-xs transition-all cursor-pointer"
                    >
                      <i className="fa-solid fa-floppy-disk"></i> Save Current Project Now
                    </button>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {savedProjects
                      .filter((p) => p.name.toLowerCase().includes(projectSearchQuery.toLowerCase()))
                      .map((proj) => {
                        const isActive = activeProjectId === proj.id;
                        return (
                          <div
                            key={proj.id}
                            className={`p-4 rounded-2xl border-2 transition-all flex flex-col justify-between space-y-3 ${
                              isActive
                                ? 'bg-amber-50/80 border-amber-500 shadow-md ring-2 ring-amber-400/50'
                                : 'bg-white border-slate-200 hover:border-amber-300 hover:shadow-md'
                            }`}
                          >
                            <div className="flex items-start justify-between gap-2">
                              <div>
                                <div className="flex items-center gap-2">
                                  <h4 className="font-black text-slate-900 text-sm">{proj.name}</h4>
                                  {isActive && (
                                    <span className="bg-amber-500 text-slate-950 font-black text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider">
                                      Active Editor
                                    </span>
                                  )}
                                </div>
                                <p className="text-[11px] text-slate-500 font-extrabold mt-0.5">
                                  Store: {proj.editForm?.store_name || 'N/A'} • {proj.productCount || 0} Products
                                </p>
                                <p className="text-[10px] text-slate-400 font-semibold mt-1">
                                  Updated: {new Date(proj.updatedAt || proj.createdAt).toLocaleString()}
                                </p>
                              </div>

                              <button
                                type="button"
                                onClick={() => handleExportProjectJson(proj)}
                                title="Export project as .json file"
                                className="text-slate-400 hover:text-indigo-600 p-1.5 rounded-lg hover:bg-indigo-50 transition-all cursor-pointer"
                              >
                                <i className="fa-solid fa-download text-xs"></i>
                              </button>
                            </div>

                            {/* Project Actions */}
                            <div className="flex items-center justify-between pt-3 border-t border-slate-100 gap-2">
                              <button
                                type="button"
                                onClick={() => handleOpenProject(proj)}
                                className={`flex-1 py-1.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                                  isActive
                                    ? 'bg-amber-500 text-slate-950 shadow-xs'
                                    : 'bg-slate-900 hover:bg-slate-800 text-white'
                                }`}
                              >
                                <i className="fa-solid fa-folder-open text-xs"></i>
                                {isActive ? 'Currently Active' : 'Open Project'}
                              </button>

                              <button
                                type="button"
                                onClick={() => handleDuplicateProject(proj)}
                                title="Duplicate Project"
                                className="p-2 text-slate-600 hover:text-amber-700 bg-slate-100 hover:bg-amber-100 rounded-xl text-xs transition-all cursor-pointer"
                              >
                                <i className="fa-solid fa-copy"></i>
                              </button>

                              <button
                                type="button"
                                onClick={() => handleDeleteProject(proj.id)}
                                title="Delete Project"
                                className="p-2 text-slate-400 hover:text-red-600 bg-slate-100 hover:bg-red-100 rounded-xl text-xs transition-all cursor-pointer"
                              >
                                <i className="fa-solid fa-trash-can"></i>
                              </button>
                            </div>
                          </div>
                        );
                      })}
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* SAVE AS PROJECT NAME MODAL */}
        {showSaveAsModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-fadeIn">
            <div className="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-md p-6 space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-base font-black text-slate-900 flex items-center gap-2">
                  <i className="fa-solid fa-floppy-disk text-amber-500"></i> Save Project Snapshot
                </h3>
                <button
                  type="button"
                  onClick={() => setShowSaveAsModal(false)}
                  className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold transition-all cursor-pointer"
                >
                  <i className="fa-solid fa-xmark"></i>
                </button>
              </div>

              <div>
                <label className="block text-xs font-extrabold text-slate-700 mb-1">
                  Project Title / Name
                </label>
                <input
                  type="text"
                  value={newProjectNameInput}
                  onChange={(e) => setNewProjectNameInput(e.target.value)}
                  placeholder="e.g. Diwali Wholesale 2026"
                  className="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
                  autoFocus
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowSaveAsModal(false)}
                  className="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => handleSaveCurrentProject(newProjectNameInput)}
                  className="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-5 py-2 rounded-xl text-xs uppercase tracking-wider shadow-xs transition-all cursor-pointer"
                >
                  Save Project
                </button>
              </div>
            </div>
          </div>
        )}
      </>
    </div>
  );
}
