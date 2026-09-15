import React, { useState, useEffect, useRef } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useStore } from '../context/StoreContext';
import { getImageUrl } from '../utils/imageUrl';
import AdminProducts from './admin/AdminProducts';
import { generateReactPDFBlob } from '../components/PriceListPDFDocument';
import { sortProductsByCode, sortCategoriesByProductCode, sortCategoriesAndProducts } from '../utils/productSorter';
import { batchTranslateCategoriesToTamil, translateEnglishToTamil } from '../utils/translator';
import { compressImageToTargetSize } from '../utils/imageCompressor';
import { loadProjectsFromStorage, saveProjectsToStorage, saveSingleProjectToStorage, deleteProjectFromStorage } from '../utils/projectStorage';

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
  const [activeFloatSlot, setActiveFloatSlot] = useState(1);

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

  // Column widths state (in px)
  const [colWidths, setColWidths] = useState({
    sno: 45,
    product: 220,
    product_ta: 220,
    unit: 95,
    mrp: 80,
    offer: 105,
    req: 45,
  });

  // Shop Edit Form State
  const [editForm, setEditForm] = useState({
    first_page_layout: 'full',
    gstin: '33ABLFM8150D1ZD',
    store_sub_header_tag: '(ALL Types of Crackers available Whole Sales & Retail)',
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
    show_footer: true,
    show_bank_details: true,
    show_upi_qr: true,
    show_tamil_name: false,
    strikethrough_mrp: true,
    header_product: 'PRODUCT NAME (ENG)',
    header_product_ta: 'பொருள் பெயர் (TAMIL)',
    important_note_1: 'தொடர்ந்து பல ஆண்டுகளாக எங்கள் நிறுவன பட்டாசுகளை வாங்கி தீபாவளியை குடும்பத்தினருடன் கொண்டாடி மகிழும் உங்கள் அனைவருக்கும் இனிய தீபாவளி நல்வாழ்த்துக்கள்!',
    important_note_2: 'வரவிருக்கும் தீபாவளி பண்டிகைக்கான பட்டாசுகளை அக்டோபர் 15 - ஆம் தேதிக்குள் ஆர்டர் செய்து பெற்றுக்கொள்ளுமாறு வேண்டுகிறோம்.',
    store_title_color: '#FFFFFF',
    store_tagline_color: '#FFFFFF',
    store_invocation_color: '#FFFFFF',
    store_badge_color: '#0F172A',
    text_stroke_color: '#000000',
    deity_stroke_color: '#FFFFFF',
    store_deity_image: '',
    store_upi_qr: '',
    store_upi_qr_2: '',
    store_gpay: '9787772038',
    store_gpay_2: '',
    store_upi_name: 'Muthusamy Ganesan',
    store_upi_name_2: '',
    store_qr_1_title: 'GPay / Primary QR',
    store_qr_2_title: 'PhonePe / Secondary QR',
    custom_float_image: '',
    custom_float_x: 15,
    custom_float_y: 15,
    custom_float_scale: 100,
    show_custom_float_image: true,
  });

  const isProjectLoadedRef = useRef(false);
  const initialLoadDone = useRef(false);

  // Load Saved Projects from storage on initialization (IndexedDB + LocalStorage fallback)
  useEffect(() => {
    let isMounted = true;
    loadProjectsFromStorage()
      .then((projects) => {
        if (isMounted && projects) {
          setSavedProjects(projects);

          const activeId = localStorage.getItem('active_project_id');
          let targetProj = null;
          if (activeId) {
            targetProj = projects.find((p) => p.id === activeId);
          }
          if (!targetProj && projects.length > 0) {
            targetProj = projects[0];
          }

          if (targetProj) {
            if (targetProj.editForm) {
              setEditForm((prev) => ({ ...prev, ...targetProj.editForm }));
            }
            if (targetProj.categories && targetProj.categories.length > 0 && setCategories) {
              setCategories(targetProj.categories);
            }
            if (targetProj.colWidths) {
              setColWidths(targetProj.colWidths);
            }
            if (targetProj.showMrp !== undefined) {
              setShowMrp(targetProj.showMrp);
            }
            setActiveProjectId(targetProj.id);
            setActiveProjectName(targetProj.name || '');
            isProjectLoadedRef.current = true;
          }
          initialLoadDone.current = true;
        }
      })
      .catch((err) => {
        console.error('Error loading saved projects from storage:', err);
        initialLoadDone.current = true;
      });
    return () => {
      isMounted = false;
    };
  }, []);

  // Auto-save active project snapshot on state changes (debounced)
  useEffect(() => {
    if (!initialLoadDone.current) return;

    const timer = setTimeout(() => {
      autoSaveActiveProject();
    }, 1000);

    return () => clearTimeout(timer);
  }, [editForm, categories, colWidths, showMrp, activeProjectId, activeProjectName]);

  const autoSaveActiveProject = async () => {
    try {
      const projId = activeProjectId || `proj_${Date.now()}`;
      const projName = activeProjectName || editForm?.store_name || 'My Price List Project';
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

      localStorage.setItem('active_project_id', projId);

      // Save directly into IndexedDB without triggering React state re-renders
      await saveSingleProjectToStorage(snapshot);
    } catch (err) {
      console.warn('Auto-save snapshot error:', err);
    }
  };

  // Save current state into a project snapshot without stripping data
  const handleSaveCurrentProject = async (customName = null) => {
    try {
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

      // Persist full snapshot safely into IndexedDB
      await saveProjectsToStorage(updatedList);

      handleSaveSettings();

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
    } catch (err) {
      console.error('Error saving project:', err);
      if (window.Swal) {
        window.Swal.fire({
          icon: 'error',
          title: 'Save Failed',
          text: 'Failed to save project snapshot: ' + err.message,
        });
      }
    }
  };

  // Open & restore a saved project with deep state merging
  const handleOpenProject = (project) => {
    if (!project) return;
    if (project.editForm) {
      setEditForm((prev) => ({
        ...prev,
        ...project.editForm,
      }));
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
    localStorage.setItem('active_project_id', project.id);
    isProjectLoadedRef.current = true;
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
  const handleDuplicateProject = async (project) => {
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
    await saveProjectsToStorage(updated);
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
      }).then(async (result) => {
        if (result.isConfirmed) {
          const updated = savedProjects.filter((p) => p.id !== projectId);
          setSavedProjects(updated);
          await deleteProjectFromStorage(projectId);
          await saveProjectsToStorage(updated);
          if (activeProjectId === projectId) {
            setActiveProjectId(null);
            setActiveProjectName('');
          }
        }
      });
    }
  };

  // Prompt user to create a new project
  const promptCreateNewProject = () => {
    if (window.Swal) {
      window.Swal.fire({
        title: 'Create New Project',
        text: 'Enter a name for your new price list project:',
        input: 'text',
        inputValue: 'My Price List Project',
        showCancelButton: true,
        confirmButtonText: 'Create Project',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        inputValidator: (value) => {
          if (!value || !value.trim()) {
            return 'Project name cannot be empty!';
          }
        },
      }).then((result) => {
        if (result.isConfirmed && result.value) {
          handleCreateNewProject(result.value);
        }
      });
    } else {
      const name = prompt('Enter a name for your new price list project:', 'My Price List Project');
      if (name && name.trim()) {
        handleCreateNewProject(name);
      }
    }
  };

  // Create a brand new project snapshot and set as active
  const handleCreateNewProject = async (customName = null) => {
    const projName = (customName || 'My Price List Project').trim();
    const projId = `proj_${Date.now()}`;
    const timestamp = new Date().toISOString();

    const newForm = {
      store_name: projName !== 'My Price List Project' ? projName : 'MY CRACKER STORE',
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
      strikethrough_mrp: true,
      header_product: 'PRODUCT NAME (ENG)',
      header_product_ta: 'பொருள் பெயர் (TAMIL)',
      important_note_1: 'தொடர்ந்து பல ஆண்டுகளாக எங்கள் நிறுவன பட்டாசுகளை வாங்கி தீபாவளியை குடும்பத்தினருடன் கொண்டாடி மகிழும் உங்கள் அனைவருக்கும் இனிய தீபாவளி நல்வாழ்த்துக்கள்!',
      important_note_2: 'வரவிருக்கும் தீபாவளி பண்டிகைக்கான பட்டாசுகளை அக்டோபர் 15 - ஆம் தேதிக்குள் ஆர்டர் செய்து பெற்றுக்கொள்ளுமாறு வேண்டுகிறோம்.',
      store_title_color: '#FFFFFF',
      store_tagline_color: '#FFFFFF',
      store_invocation_color: '#FFFFFF',
      store_badge_color: '#0F172A',
      text_stroke_color: '#000000',
      deity_stroke_color: '#FFFFFF',
      store_cover_bg: '/images/cover_bg_orange_burst.jpg',
      store_deity_preset: 'vinayagar',
    };

    setEditForm(newForm);
    setActiveProjectId(projId);
    setActiveProjectName(projName);

    const snapshot = {
      id: projId,
      name: projName,
      createdAt: timestamp,
      updatedAt: timestamp,
      editForm: newForm,
      categories: JSON.parse(JSON.stringify(categories || [])),
      colWidths: { sno: 45, product: 220, product_ta: 220, unit: 95, mrp: 80, offer: 105, req: 45 },
      showMrp: true,
      productCount: (categories || []).reduce((acc, cat) => acc + (cat.products?.length || 0), 0),
    };

    const updatedList = [snapshot, ...savedProjects];
    setSavedProjects(updatedList);
    await saveProjectsToStorage(updatedList);

    setShowProjectsModal(false);

    if (window.Swal) {
      window.Swal.fire({
        icon: 'success',
        title: 'New Project Created!',
        text: `"${projName}" has been created and loaded into the editor.`,
        timer: 1800,
        showConfirmButton: false,
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
    reader.onload = async (event) => {
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
          await saveProjectsToStorage(updated);
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



  useEffect(() => {
    if (settings) {
      setEditForm((prevForm) => {
        const defaultFromSettings = {
          first_page_layout: settings.first_page_layout || 'full',
          gstin: settings.gstin || '33ABLFM8150D1ZD',
          store_sub_header_tag: settings.store_sub_header_tag || '(ALL Types of Crackers available Whole Sales & Retail)',
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
          strikethrough_mrp: settings.strikethrough_mrp !== undefined ? settings.strikethrough_mrp : true,
          header_product: settings.header_product || 'PRODUCT NAME (ENG)',
          header_product_ta: settings.header_product_ta || 'பொருள் பெயர் (TAMIL)',
          bank_name: settings.bank_name || settings.bank_holder || 'Muthusamy Ganesan',
          bank_branch: settings.bank_branch || settings.bank_acc_name || 'IDBI Bank',
          bank_account_no: settings.bank_account_no || settings.bank_acc_no || '1118104000136815',
          bank_ifsc: settings.bank_ifsc || 'IBKL0001118',
          footer_position: settings.footer_position || 'below_table',
          show_footer: settings.show_footer !== undefined ? settings.show_footer : true,
          max_tr_per_page: settings.max_tr_per_page || 30,
          important_note_1: settings.important_note_1 || 'தொடர்ந்து பல ஆண்டுகளாக எங்கள் நிறுவன பட்டாசுகளை வாங்கி தீபாவளியை குடும்பத்தினருடன் கொண்டாடி மகிழும் உங்கள் அனைவருக்கும் இனிய தீபாவளி நல்வாழ்த்துக்கள்!',
          important_note_2: settings.important_note_2 || 'வரவிருக்கும் தீபாவளி பண்டிகைக்கான பட்டாசுகளை அக்டோபர் 15 - ஆம் தேதிக்குள் ஆர்டர் செய்து பெற்றுக்கொள்ளுமாறு வேண்டுகிறோம்.',
          store_title_color: settings.store_title_color || '#FFFFFF',
          store_tagline_color: settings.store_tagline_color || '#FFFFFF',
          store_invocation_color: settings.store_invocation_color || '#FFFFFF',
          store_badge_color: settings.store_badge_color || '#0F172A',
          text_stroke_color: settings.text_stroke_color || '#000000',
          deity_stroke_color: settings.deity_stroke_color || '#FFFFFF',
          custom_float_image: settings.custom_float_image || '',
          custom_float_x: settings.custom_float_x !== undefined ? Number(settings.custom_float_x) : 15,
          custom_float_y: settings.custom_float_y !== undefined ? Number(settings.custom_float_y) : 15,
          custom_float_scale: settings.custom_float_scale !== undefined ? Number(settings.custom_float_scale) : 100,
          show_custom_float_image: settings.show_custom_float_image !== undefined ? (settings.show_custom_float_image === 'false' ? false : Boolean(settings.show_custom_float_image)) : true,
        };

        if (isProjectLoadedRef.current) {
          return {
            ...defaultFromSettings,
            ...prevForm,
          };
        }
        return defaultFromSettings;
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

  const getFloatImgProps = (i) => {
    const suffix = i === 1 ? '' : `_${i}`;
    const rawImage = editForm[`custom_float_image${suffix}`] || '';
    const rawX = editForm[`custom_float_x${suffix}`];
    const rawY = editForm[`custom_float_y${suffix}`];
    const rawScale = editForm[`custom_float_scale${suffix}`];
    const rawShow = editForm[`show_custom_float_image${suffix}`];

    const isSimpler = (editForm.first_page_layout || 'full') === 'simpler';

    const defaultX = isSimpler
      ? (i === 1 ? 72 : i === 2 ? 15 : i === 3 ? 45 : i === 4 ? 30 : 60)
      : (i === 1 ? 15 : i === 2 ? 70 : i === 3 ? 45 : i === 4 ? 20 : 65);

    const defaultY = isSimpler
      ? (i === 1 ? 1.5 : i === 2 ? 1.5 : i === 3 ? 1.5 : i === 4 ? 5 : 5)
      : (i === 1 ? 15 : i === 2 ? 25 : i === 3 ? 55 : i === 4 ? 70 : 80);

    const x = rawX !== undefined ? Number(rawX) : defaultX;
    const y = (rawY !== undefined && (i !== 1 || rawY !== 15)) ? Number(rawY) : defaultY;
    const scale = rawScale !== undefined ? Number(rawScale) : 100;
    const show = rawShow !== undefined ? (rawShow === 'false' ? false : Boolean(rawShow)) : true;

    return { image: rawImage, x, y, scale, show, suffix };
  };

  const [draggingFloatImgIdx, setDraggingFloatImgIdx] = useState(null);
  const [dragStartPos, setDragStartPos] = useState({ clientX: 0, clientY: 0, startX: 15, startY: 15 });

  const handleFloatImgMouseDown = (idx, e) => {
    e.preventDefault();
    e.stopPropagation();
    setDraggingFloatImgIdx(idx);
    const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
    const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);
    const props = getFloatImgProps(idx);
    setDragStartPos({
      clientX: cX,
      clientY: cY,
      startX: props.x,
      startY: props.y,
    });
  };

  useEffect(() => {
    if (draggingFloatImgIdx === null) return;

    const handleMouseMove = (e) => {
      const page1El = document.getElementById('a4-page-1-container');
      if (!page1El) return;
      const rect = page1El.getBoundingClientRect();
      if (!rect.width || !rect.height) return;

      const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
      const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);

      const deltaX = cX - dragStartPos.clientX;
      const deltaY = cY - dragStartPos.clientY;

      const deltaXPercent = (deltaX / rect.width) * 100;
      const deltaYPercent = (deltaY / rect.height) * 100;

      let newX = Math.round(Math.max(0, Math.min(85, dragStartPos.startX + deltaXPercent)));
      let newY = Math.round(Math.max(0, Math.min(85, dragStartPos.startY + deltaYPercent)));

      const suffix = draggingFloatImgIdx === 1 ? '' : `_${draggingFloatImgIdx}`;
      setEditForm((prev) => ({
        ...prev,
        [`custom_float_x${suffix}`]: newX,
        [`custom_float_y${suffix}`]: newY,
      }));
    };

    const handleMouseUp = () => {
      setDraggingFloatImgIdx(null);
    };

    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchmove', handleMouseMove);
    window.addEventListener('touchend', handleMouseUp);

    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
      window.removeEventListener('touchmove', handleMouseMove);
      window.removeEventListener('touchend', handleMouseUp);
    };
  }, [draggingFloatImgIdx, dragStartPos]);

  const [resizingFloatImgIdx, setResizingFloatImgIdx] = useState(null);
  const [resizeStartPos, setResizeStartPos] = useState({ clientX: 0, clientY: 0, startScale: 100 });

  const handleFloatImgResizeMouseDown = (idx, e) => {
    e.preventDefault();
    e.stopPropagation();
    setResizingFloatImgIdx(idx);
    const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
    const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);
    const props = getFloatImgProps(idx);
    setResizeStartPos({
      clientX: cX,
      clientY: cY,
      startScale: props.scale,
    });
  };

  useEffect(() => {
    if (resizingFloatImgIdx === null) return;

    const handleMouseMove = (e) => {
      const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
      const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);

      const deltaX = cX - resizeStartPos.clientX;
      const deltaY = cY - resizeStartPos.clientY;

      const delta = (deltaX + deltaY) / 2;
      const scaleChange = Math.round(delta * 0.8);

      let newScale = Math.max(20, Math.min(300, resizeStartPos.startScale + scaleChange));

      const suffix = resizingFloatImgIdx === 1 ? '' : `_${resizingFloatImgIdx}`;
      setEditForm((prev) => ({
        ...prev,
        [`custom_float_scale${suffix}`]: newScale,
      }));
    };

    const handleMouseUp = () => {
      setResizingFloatImgIdx(null);
    };

    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchmove', handleMouseMove);
    window.addEventListener('touchend', handleMouseUp);

    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
      window.removeEventListener('touchmove', handleMouseMove);
      window.removeEventListener('touchend', handleMouseUp);
    };
  }, [resizingFloatImgIdx, resizeStartPos]);

  const [isDraggingDiscountBadge, setIsDraggingDiscountBadge] = useState(false);
  const [discountBadgeDragStart, setDiscountBadgeDragStart] = useState({ clientX: 0, clientY: 0, startX: 82, startY: 2.2 });

  const handleDiscountBadgeMouseDown = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDraggingDiscountBadge(true);
    const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
    const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);
    const isSimpler = (editForm.first_page_layout || 'full') === 'simpler';
    const defX = isSimpler ? 82 : 75;
    const defY = isSimpler ? 2.2 : 82;
    setDiscountBadgeDragStart({
      clientX: cX,
      clientY: cY,
      startX: editForm.discount_badge_x !== undefined ? Number(editForm.discount_badge_x) : defX,
      startY: editForm.discount_badge_y !== undefined ? Number(editForm.discount_badge_y) : defY,
    });
  };

  useEffect(() => {
    if (!isDraggingDiscountBadge) return;

    const handleMouseMove = (e) => {
      const page1El = document.getElementById('a4-page-1-container');
      if (!page1El) return;
      const rect = page1El.getBoundingClientRect();
      if (!rect.width || !rect.height) return;

      const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
      const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);

      const deltaX = cX - discountBadgeDragStart.clientX;
      const deltaY = cY - discountBadgeDragStart.clientY;

      const deltaXPercent = (deltaX / rect.width) * 100;
      const deltaYPercent = (deltaY / rect.height) * 100;

      let newX = Math.round((Math.max(0, Math.min(90, discountBadgeDragStart.startX + deltaXPercent))) * 10) / 10;
      let newY = Math.round((Math.max(0, Math.min(90, discountBadgeDragStart.startY + deltaYPercent))) * 10) / 10;

      setEditForm((prev) => ({
        ...prev,
        discount_badge_x: newX,
        discount_badge_y: newY,
      }));
    };

    const handleMouseUp = () => {
      setIsDraggingDiscountBadge(false);
    };

    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchmove', handleMouseMove);
    window.addEventListener('touchend', handleMouseUp);

    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
      window.removeEventListener('touchmove', handleMouseMove);
      window.removeEventListener('touchend', handleMouseUp);
    };
  }, [isDraggingDiscountBadge, discountBadgeDragStart]);

  const [isResizingDiscountBadge, setIsResizingDiscountBadge] = useState(false);
  const [discountBadgeResizeStart, setDiscountBadgeResizeStart] = useState({ clientX: 0, clientY: 0, startScale: 100 });

  const handleDiscountBadgeResizeMouseDown = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsResizingDiscountBadge(true);
    const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
    const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);
    setDiscountBadgeResizeStart({
      clientX: cX,
      clientY: cY,
      startScale: editForm.discount_badge_scale !== undefined ? Number(editForm.discount_badge_scale) : (editForm.discount_scale ? Number(editForm.discount_scale) : 100),
    });
  };

  useEffect(() => {
    if (!isResizingDiscountBadge) return;

    const handleMouseMove = (e) => {
      const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
      const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);

      const deltaX = cX - discountBadgeResizeStart.clientX;
      const deltaY = cY - discountBadgeResizeStart.clientY;

      const delta = (deltaX + deltaY) / 2;
      const scaleChange = Math.round(delta * 0.8);

      let newScale = Math.max(30, Math.min(300, discountBadgeResizeStart.startScale + scaleChange));

      setEditForm((prev) => ({
        ...prev,
        discount_badge_scale: newScale,
      }));
    };

    const handleMouseUp = () => {
      setIsResizingDiscountBadge(false);
    };

    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchmove', handleMouseMove);
    window.addEventListener('touchend', handleMouseUp);

    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
      window.removeEventListener('touchmove', handleMouseMove);
      window.removeEventListener('touchend', handleMouseUp);
    };
  }, [isResizingDiscountBadge, discountBadgeResizeStart]);

  const [isDraggingShopInfo, setIsDraggingShopInfo] = useState(false);
  const [shopInfoDragStart, setShopInfoDragStart] = useState({ clientX: 0, clientY: 0, startX: 0, startY: 0 });

  const handleShopInfoMouseDown = (e) => {
    if (e.target.closest('button, input, select, textarea')) return;
    e.preventDefault();
    e.stopPropagation();
    setIsDraggingShopInfo(true);
    const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
    const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);
    setShopInfoDragStart({
      clientX: cX,
      clientY: cY,
      startX: editForm.shop_info_x ? Number(editForm.shop_info_x) : 0,
      startY: editForm.shop_info_y ? Number(editForm.shop_info_y) : 0,
    });
  };

  useEffect(() => {
    if (!isDraggingShopInfo) return;

    const handleMouseMove = (e) => {
      const cX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
      const cY = e.clientY !== undefined ? e.clientY : (e.touches?.[0]?.clientY || 0);

      const deltaX = cX - shopInfoDragStart.clientX;
      const deltaY = cY - shopInfoDragStart.clientY;

      let newX = Math.round(shopInfoDragStart.startX + deltaX);
      let newY = Math.round(shopInfoDragStart.startY + deltaY);

      setEditForm((prev) => ({
        ...prev,
        shop_info_x: newX,
        shop_info_y: newY,
      }));
    };

    const handleMouseUp = () => {
      setIsDraggingShopInfo(false);
    };

    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchmove', handleMouseMove);
    window.addEventListener('touchend', handleMouseUp);

    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
      window.removeEventListener('touchmove', handleMouseMove);
      window.removeEventListener('touchend', handleMouseUp);
    };
  }, [isDraggingShopInfo, shopInfoDragStart]);

  // Column width mouse & touch drag resize handler (Pairwise adjacent column resizing)
  const handleColumnResizeStart = (colKey, e) => {
    const activeCols = [
      editForm.show_col_sno !== false && 'sno',
      editForm.show_col_product !== false && 'product',
      editForm.show_tamil_name === true && 'product_ta',
      editForm.show_col_unit !== false && 'unit',
      showMrp && editForm.show_col_mrp !== false && 'mrp',
      editForm.show_col_offer !== false && 'offer',
      editForm.show_col_req !== false && 'req',
    ].filter(Boolean);

    const colIdx = activeCols.indexOf(colKey);
    if (colIdx === -1 || colIdx >= activeCols.length - 1) return;

    const nextColKey = activeCols[colIdx + 1];

    e.preventDefault();
    e.stopPropagation();
    const startX = e.clientX !== undefined ? e.clientX : (e.touches?.[0]?.clientX || 0);
    const startWidth1 = colWidths[colKey] || 80;
    const startWidth2 = colWidths[nextColKey] || 80;

    const minWidths = { sno: 20, req: 20, unit: 35, mrp: 45, offer: 45, product: 60, product_ta: 60 };
    const minW1 = minWidths[colKey] || 30;
    const minW2 = minWidths[nextColKey] || 30;

    const handleMouseMove = (moveEvent) => {
      const cX = moveEvent.clientX !== undefined ? moveEvent.clientX : (moveEvent.touches?.[0]?.clientX || 0);
      const deltaX = cX - startX;

      const maxDeltaX = startWidth2 - minW2;
      const minDeltaX = -(startWidth1 - minW1);

      const boundedDeltaX = Math.round(Math.max(minDeltaX, Math.min(maxDeltaX, deltaX)));

      const newW1 = startWidth1 + boundedDeltaX;
      const newW2 = startWidth2 - boundedDeltaX;

      setColWidths((prev) => ({
        ...prev,
        [colKey]: newW1,
        [nextColKey]: newW2,
      }));
    };

    const handleMouseUp = () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
      window.removeEventListener('touchmove', handleMouseMove);
      window.removeEventListener('touchend', handleMouseUp);
    };

    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchmove', handleMouseMove);
    window.addEventListener('touchend', handleMouseUp);
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
    const bg = bgPath || '/images/cover_bg_orange_burst.jpg';
    if (bg === 'none') {
      return { textClass: 'text-slate-800', bgClass: 'bg-slate-800', hex: '#1e293b' };
    } else if (bg.includes('cover_bg_blue_burst')) {
      return { textClass: 'text-sky-600', bgClass: 'bg-sky-500', hex: '#0284c7' };
    } else if (bg.includes('cover_bg_red_burst') || bg.includes('cover_bg_1')) {
      return { textClass: 'text-red-600', bgClass: 'bg-red-600', hex: '#dc2626' };
    } else if (bg.includes('cover_bg_orange_burst')) {
      return { textClass: 'text-amber-600', bgClass: 'bg-amber-500', hex: '#d97706' };
    } else if (bg.includes('cover_bg_red_sparkle')) {
      return { textClass: 'text-red-600', bgClass: 'bg-red-600', hex: '#dc2626' };
    } else if (bg.includes('cover_bg_maroon_festive')) {
      return { textClass: 'text-amber-600', bgClass: 'bg-amber-700', hex: '#b45309' };
    } else if (bg.includes('cover_bg_blue_gradient')) {
      return { textClass: 'text-indigo-600', bgClass: 'bg-indigo-600', hex: '#4f46e5' };
    } else if (bg.includes('cover_bg_blue_sparkle') || bg.includes('cover_bg_blue_burst')) {
      return { textClass: 'text-sky-600', bgClass: 'bg-sky-500', hex: '#0284c7' };
    } else if (bg.includes('cover_bg_purple') || bg.includes('cover_bg_4')) {
      return { textClass: 'text-purple-600', bgClass: 'bg-purple-600', hex: '#9333ea' };
    } else if (bg.includes('cover_bg_5')) {
      return { textClass: 'text-sky-600', bgClass: 'bg-sky-500', hex: '#0284c7' };
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
          store_name: editForm.store_name || 'MASS CRACKERS',
          store_phone: editForm.store_phone || '8682942042',
          store_whatsapp: editForm.store_phone || '8682942042',
          store_email: (editForm.store_email && editForm.store_email.includes('@')) ? editForm.store_email : 'info@masscrackers.com',
          store_address: editForm.store_address || 'Virudhunagar to Sivakasi Main Road',
          min_order_value: settings?.min_order_value || 0,
          discount_percent: editForm.discount_percent !== undefined ? editForm.discount_percent : 50,
          enable_min_order: settings?.enable_min_order || 'no',
          enable_promo_codes: settings?.enable_promo_codes || 'no',
          enable_tax_delivery: settings?.enable_tax_delivery || 'no',
          enable_fireworks: settings?.enable_fireworks || 'yes',
          tax_percent: settings?.tax_percent || 0,
          delivery_charge: settings?.delivery_charge || 0,
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
      } else {
        console.warn('Settings backend warning:', data);
        if (setSettings) {
          setSettings((prev) => ({ ...prev, ...editForm }));
        }
        if (window.Swal) {
          window.Swal.fire({
            title: 'Saved Locally',
            text: data.message || 'Updated for live preview.',
            icon: 'info',
            timer: 1800,
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
        try {
          const sfRes = await fetch('/api/storefront');
          const sfData = await sfRes.json();
          if (sfData && sfData.categories && setCategories) {
            setCategories(sortCategoriesAndProducts(sfData.categories));
          }
        } catch (sfErr) {
          console.warn('Failed to refresh storefront data after import:', sfErr);
        }
        setTimeout(() => {
          setImportModalOpen(false);
          setImportFile(null);
          setImportResult(null);
        }, 1500);
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

    let targetCatId = productFormData.category_id;
    if (!targetCatId && categories && categories.length > 0) {
      targetCatId = categories[0].id;
    }

    const postData = new FormData();
    postData.append('category_id', targetCatId || '1');
    if (productFormData.product_code) postData.append('product_code', productFormData.product_code);
    postData.append('name', productFormData.name || 'New Product');
    postData.append('pack_size', productFormData.pack_size || '1 Box');
    postData.append('mrp', productFormData.mrp || 100);
    postData.append('selling_price', productFormData.selling_price || 50);
    if (productFormData.sort_order !== '') postData.append('sort_order', productFormData.sort_order);
    postData.append('status', productFormData.status || 'active');
    postData.append('is_bestseller', productFormData.is_bestseller ? '1' : '0');
    postData.append('stock_quantity', productFormData.stock_quantity ?? 100);
    postData.append('min_stock_alert', productFormData.min_stock_alert ?? 10);
    postData.append('manage_stock', productFormData.manage_stock || 'yes');
    if (productImageFile) {
      try {
        const compressedImg = await compressImageToTargetSize(productImageFile, 100);
        postData.append('image', compressedImg);
      } catch (imgErr) {
        postData.append('image', productImageFile);
      }
    }

    try {
      const res = await fetch('/api/admin/products/store', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: postData,
      });
      const data = await res.json();
      
      const createdProduct = data.product || {
        id: Date.now(),
        category_id: targetCatId,
        product_code: productFormData.product_code || String(Date.now()).slice(-3),
        name: productFormData.name || 'New Product',
        pack_size: productFormData.pack_size || '1 Box',
        mrp: parseFloat(productFormData.mrp) || 100,
        selling_price: parseFloat(productFormData.selling_price) || 50,
        status: 'active',
      };

      if (setCategories) {
        setCategories((prevCategories) => {
          let catFound = false;
          const updated = (prevCategories || []).map((cat) => {
            if (String(cat.id) === String(targetCatId) || cat.id === targetCatId) {
              catFound = true;
              return {
                ...cat,
                products: [...(cat.products || []), createdProduct],
              };
            }
            return cat;
          });

          if (!catFound && prevCategories && prevCategories.length > 0) {
            updated[0].products = [...(updated[0].products || []), createdProduct];
          }

          return realignProductCodesInCategories(updated);
        });
      }

      setProductModalOpen(false);

      if (window.Swal) {
        window.Swal.fire({ icon: 'success', title: 'Product Created!', showConfirmButton: false, timer: 1500 });
      }
    } catch (err) {
      console.error('Error saving product to server:', err);
      // Fallback: Add product to local React state so user workflow is not interrupted!
      const fallbackProd = {
        id: Date.now(),
        category_id: targetCatId || (categories && categories[0] ? categories[0].id : 1),
        product_code: productFormData.product_code || String(Date.now()).slice(-3),
        name: productFormData.name || 'New Product',
        pack_size: productFormData.pack_size || '1 Box',
        mrp: parseFloat(productFormData.mrp) || 100,
        selling_price: parseFloat(productFormData.selling_price) || 50,
        status: 'active',
      };

      if (setCategories) {
        setCategories((prevCategories) => {
          const updated = [...(prevCategories || [])];
          if (updated.length > 0) {
            updated[0].products = [...(updated[0].products || []), fallbackProd];
          }
          return realignProductCodesInCategories(updated);
        });
      }

      setProductModalOpen(false);
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

  // Helper: Renumber product codes sequentially across all categories (1, 2, 3, 4, 5...)
  const realignProductCodesInCategories = (categoriesList) => {
    let globalCodeCounter = 1;
    return (categoriesList || []).map((cat) => ({
      ...cat,
      products: (cat.products || []).map((p) => {
        const updatedCode = String(globalCodeCounter++);
        return {
          ...p,
          product_code: updatedCode,
          sort_order: parseInt(updatedCode, 10),
        };
      }),
    }));
  };

  // Add a new product row at the end of a specific category
  const handleAddRowAtCategoryEnd = (categoryId) => {
    if (!setCategories) return;

    const newProdId = `prod_${Date.now()}_${Math.random().toString(36).substr(2, 4)}`;
    const defaultMrp = 100;
    const currentDisc = editForm?.discount_percent !== undefined ? editForm.discount_percent : 50;
    const defaultOffer = Math.round(defaultMrp * (1 - currentDisc / 100));

    const newProduct = {
      id: newProdId,
      category_id: categoryId,
      product_code: '',
      name: 'New Product Item',
      name_ta: '',
      pack_size: '1 Box',
      mrp: defaultMrp,
      selling_price: defaultOffer,
      req: '',
      status: 'active',
    };

    setCategories((prevCategories) => {
      const updated = (prevCategories || []).map((cat) => {
        if (cat.id === categoryId) {
          return {
            ...cat,
            products: [...(cat.products || []), newProduct],
          };
        }
        return cat;
      });
      return realignProductCodesInCategories(updated);
    });

    // Auto-translate name if Tamil column is active
    if (editForm?.show_tamil_name) {
      translateEnglishToTamil('New Product Item').then((autoTa) => {
        if (autoTa) {
          handleInlineProductChange(newProdId, 'name_ta', autoTa);
        }
      });
    }

    if (window.Swal) {
      const Toast = window.Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
      });
      Toast.fire({
        icon: 'success',
        title: 'New row added & numbers realigned!',
      });
    }
  };

  // Insert a new product row in between existing rows (above or below targetProductId)
  const handleInsertRowInBetween = (categoryId, targetProductId, position = 'below') => {
    if (!setCategories) return;

    const newProdId = `prod_${Date.now()}_${Math.random().toString(36).substr(2, 4)}`;
    const defaultMrp = 100;
    const currentDisc = editForm?.discount_percent !== undefined ? editForm.discount_percent : 50;
    const defaultOffer = Math.round(defaultMrp * (1 - currentDisc / 100));

    const newProduct = {
      id: newProdId,
      category_id: categoryId,
      product_code: '',
      name: 'Inserted Product Item',
      name_ta: '',
      pack_size: '1 Box',
      mrp: defaultMrp,
      selling_price: defaultOffer,
      req: '',
      status: 'active',
    };

    setCategories((prevCategories) => {
      const updated = (prevCategories || []).map((cat) => {
        if (cat.id === categoryId) {
          const prods = [...(cat.products || [])];
          const targetIndex = prods.findIndex((p) => p.id === targetProductId);
          if (targetIndex !== -1) {
            const insertIdx = position === 'above' ? targetIndex : targetIndex + 1;
            prods.splice(insertIdx, 0, newProduct);
          } else {
            prods.push(newProduct);
          }
          return {
            ...cat,
            products: prods,
          };
        }
        return cat;
      });
      return realignProductCodesInCategories(updated);
    });

    if (editForm?.show_tamil_name) {
      translateEnglishToTamil('Inserted Product Item').then((autoTa) => {
        if (autoTa) {
          handleInlineProductChange(newProdId, 'name_ta', autoTa);
        }
      });
    }

    if (window.Swal) {
      const Toast = window.Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
      });
      Toast.fire({
        icon: 'success',
        title: `Row inserted ${position} & numbers realigned!`,
      });
    }
  };

  // Delete a specific row
  const handleDeleteRow = (categoryId, productId) => {
    if (!setCategories) return;
    setCategories((prevCategories) => {
      const updated = (prevCategories || []).map((cat) => {
        if (cat.id === categoryId) {
          return {
            ...cat,
            products: (cat.products || []).filter((p) => p.id !== productId),
          };
        }
        return cat;
      });
      return realignProductCodesInCategories(updated);
    });

    if (productId && typeof productId === 'number') {
      fetch(`/api/admin/products/${productId}/destroy`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json' },
      }).catch((err) => console.warn('Failed to delete product from database:', err));
    }
  };

  // Duplicate a specific row
  const handleDuplicateRow = (categoryId, productId) => {
    if (!setCategories) return;
    setCategories((prevCategories) => {
      const updated = (prevCategories || []).map((cat) => {
        if (cat.id === categoryId) {
          const prods = [...(cat.products || [])];
          const targetIndex = prods.findIndex((p) => p.id === productId);
          if (targetIndex !== -1) {
            const original = prods[targetIndex];
            const duplicate = {
              ...original,
              id: `prod_${Date.now()}_${Math.random().toString(36).substr(2, 4)}`,
              name: `${original.name} (Copy)`,
            };
            prods.splice(targetIndex + 1, 0, duplicate);
          }
          return {
            ...cat,
            products: prods,
          };
        }
        return cat;
      });
      return realignProductCodesInCategories(updated);
    });

    if (window.Swal) {
      const Toast = window.Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
      });
      Toast.fire({
        icon: 'success',
        title: 'Row duplicated & numbers realigned!',
      });
    }
  };

  // Add a brand new category at the end of the price list
  const handleAddNewCategory = () => {
    if (!setCategories) return;
    const newCatId = `cat_${Date.now()}`;
    const newCategory = {
      id: newCatId,
      name: 'NEW CATEGORY SECTION',
      name_ta: '',
      sort_order: (categories?.length || 0) + 1,
      products: [
        {
          id: `prod_${Date.now()}_1`,
          category_id: newCatId,
          product_code: '',
          name: 'First Product Item',
          name_ta: '',
          pack_size: '1 Box',
          mrp: 100,
          selling_price: Math.round(100 * (1 - (editForm?.discount_percent || 50) / 100)),
          req: '',
          status: 'active',
        },
      ],
    };

    setCategories((prev) => realignProductCodesInCategories([...(prev || []), newCategory]));

    if (window.Swal) {
      window.Swal.fire({
        icon: 'success',
        title: 'New Category Added & Numbers Realigned!',
        text: 'Scroll down to see your new category section.',
        timer: 1500,
        showConfirmButton: false,
      });
    }
  };

  // Move product row UP inside its category and realign codes
  const handleMoveRowUp = (categoryId, productId) => {
    if (!setCategories) return;
    setCategories((prevCategories) => {
      const updated = (prevCategories || []).map((cat) => {
        if (cat.id === categoryId) {
          const prods = [...(cat.products || [])];
          const idx = prods.findIndex((p) => p.id === productId);
          if (idx > 0) {
            const temp = prods[idx];
            prods[idx] = prods[idx - 1];
            prods[idx - 1] = temp;
          }
          return { ...cat, products: prods };
        }
        return cat;
      });
      return realignProductCodesInCategories(updated);
    });
  };

  // Move product row DOWN inside its category and realign codes
  const handleMoveRowDown = (categoryId, productId) => {
    if (!setCategories) return;
    setCategories((prevCategories) => {
      const updated = (prevCategories || []).map((cat) => {
        if (cat.id === categoryId) {
          const prods = [...(cat.products || [])];
          const idx = prods.findIndex((p) => p.id === productId);
          if (idx >= 0 && idx < prods.length - 1) {
            const temp = prods[idx];
            prods[idx] = prods[idx + 1];
            prods[idx + 1] = temp;
          }
          return { ...cat, products: prods };
        }
        return cat;
      });
      return realignProductCodesInCategories(updated);
    });
  };

  // Re-align all product codes sequentially across the whole price list
  const handleRealignAllProductCodes = () => {
    if (!setCategories) return;
    setCategories((prevCategories) => realignProductCodesInCategories(prevCategories));
    if (window.Swal) {
      const Toast = window.Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
      });
      Toast.fire({
        icon: 'success',
        title: 'Product codes realigned sequentially!',
      });
    }
  };

  // Drag & Drop State & Handlers for row re-ordering
  const [draggedRowInfo, setDraggedRowInfo] = useState(null); // { categoryId, productId }
  const [dragOverRowId, setDragOverRowId] = useState(null);

  const handleRowDragStart = (e, categoryId, productId) => {
    e.stopPropagation();
    setDraggedRowInfo({ categoryId, productId });
    e.dataTransfer.setData('text/plain', JSON.stringify({ categoryId, productId }));
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleRowDragOver = (e, categoryId, productId) => {
    e.preventDefault();
    e.stopPropagation();
    e.dataTransfer.dropEffect = 'move';
    if (dragOverRowId !== productId) {
      setDragOverRowId(productId);
    }
  };

  const handleRowDragLeave = (e, productId) => {
    e.stopPropagation();
    if (dragOverRowId === productId) {
      setDragOverRowId(null);
    }
  };

  const handleRowDrop = (e, targetCategoryId, targetProductId) => {
    e.preventDefault();
    e.stopPropagation();
    setDragOverRowId(null);

    if (!draggedRowInfo || !setCategories) return;
    const { categoryId: sourceCatId, productId: sourceProdId } = draggedRowInfo;
    if (sourceProdId === targetProductId) return;

    setCategories((prevCategories) => {
      let movedProduct = null;

      // Remove from source category
      const categoriesAfterRemove = (prevCategories || []).map((cat) => {
        if (cat.id === sourceCatId) {
          const prods = (cat.products || []).filter((p) => {
            if (p.id === sourceProdId) {
              movedProduct = p;
              return false;
            }
            return true;
          });
          return { ...cat, products: prods };
        }
        return cat;
      });

      if (!movedProduct) return prevCategories;

      // Insert into target category at targetProductId index
      const categoriesAfterInsert = categoriesAfterRemove.map((cat) => {
        if (cat.id === targetCategoryId) {
          const prods = [...(cat.products || [])];
          const targetIdx = prods.findIndex((p) => p.id === targetProductId);
          if (targetIdx !== -1) {
            prods.splice(targetIdx, 0, { ...movedProduct, category_id: targetCategoryId });
          } else {
            prods.push({ ...movedProduct, category_id: targetCategoryId });
          }
          return { ...cat, products: prods };
        }
        return cat;
      });

      return realignProductCodesInCategories(categoriesAfterInsert);
    });

    setDraggedRowInfo(null);
    if (window.Swal) {
      const Toast = window.Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
      });
      Toast.fire({
        icon: 'success',
        title: 'Row moved and codes realigned!',
      });
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
      if (elementKey === 'simpler_deity' || elementKey === 'deity') {
        handleInputChange('simpler_deity_scale', newScale);
        handleInputChange('deity_scale', newScale);
      }
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
      // 1. Immediately clear products in local React state
      if (setCategories) {
        setCategories((prevCats) => (prevCats || []).map((cat) => ({ ...cat, products: [] })));
      }

      try {
        const res = await fetch('/api/admin/products/delete-all', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();

        if (res.ok && data.success) {
          try {
            const sfRes = await fetch('/api/storefront');
            const sfData = await sfRes.json();
            if (sfData && sfData.categories && setCategories) {
              setCategories(sortCategoriesAndProducts(sfData.categories));
            }
          } catch (sfErr) {
            console.warn('Failed to refresh storefront data after delete all:', sfErr);
          }
          if (window.Swal) {
            window.Swal.fire({
              icon: 'success',
              title: 'All Products Deleted!',
              text: 'Product database cleared successfully.',
              showConfirmButton: false,
              timer: 1500,
            });
          }
        } else {
          // If backend returned error, still clear local state so user can clear work session
          if (setCategories) {
            setCategories((prevCats) => (prevCats || []).map((cat) => ({ ...cat, products: [] })));
          }
          if (window.Swal) {
            window.Swal.fire({
              icon: 'info',
              title: 'Cleared Local Products',
              text: data.error || 'Cleared product list in active workspace.',
              timer: 1500,
            });
          }
        }
      } catch (err) {
        console.error('Delete all error:', err);
        if (setCategories) {
          setCategories((prevCats) => (prevCats || []).map((cat) => ({ ...cat, products: [] })));
        }
        if (window.Swal) {
          window.Swal.fire({
            icon: 'info',
            title: 'Cleared Local Products',
            text: 'Cleared product list in active workspace.',
            timer: 1500,
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

  // 3. Exact TRs per A4 Page Sheet Chunking (supports Full Cover vs Simpler Header Layout)
  const isSimplerLayout = editForm.first_page_layout === 'simpler';
  const MAX_TR_PER_PAGE = parseInt(editForm.max_tr_per_page || 30, 10);
  const SIMPLER_HEADER_TR_COST = 11; // Simpler header box takes height equal to ~11 TR rows

  const productPageChunks = [];
  let currentChunkProducts = [];
  let currentChunkTrCount = 0;
  let currentCatIdInChunk = null;

  allFilteredProducts.forEach((product) => {
    const isFirstChunk = productPageChunks.length === 0;

    // When starting page 1 under simpler layout, offset initial TR count by SIMPLER_HEADER_TR_COST for the header box
    if (currentChunkProducts.length === 0 && isSimplerLayout && isFirstChunk) {
      currentChunkTrCount = SIMPLER_HEADER_TR_COST;
    }

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

  const isFooterEnabled = editForm.show_footer !== false && editForm.footer_position !== 'disabled';
  const hasNewPageFooter = isFooterEnabled && editForm.footer_position === 'new_page';

  const totalDocPages = isSimplerLayout
    ? productPageChunks.length + (hasNewPageFooter ? 1 : 0)
    : 1 + productPageChunks.length + (hasNewPageFooter ? 1 : 0);
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
  const activeColsList = [
    showSno && 'sno',
    showProduct && 'product',
    showTamilName && 'product_ta',
    showUnit && 'unit',
    showMrpCol && 'mrp',
    showOffer && 'offer',
    showReq && 'req',
  ].filter(Boolean);
  const lastActiveCol = activeColsList[activeColsList.length - 1];

  const totalActiveColWidth = activeColsList.reduce((sum, colKey) => {
    const w = colKey === 'product_ta' ? (colWidths.product_ta || 160) : (colWidths[colKey] || 80);
    return sum + w;
  }, 0) || 1;

  const getColPctWidth = (colKey) => {
    const w = colKey === 'product_ta' ? (colWidths.product_ta || 160) : (colWidths[colKey] || 80);
    return `${((w / totalActiveColWidth) * 100).toFixed(2)}%`;
  };

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

              {/* CREATE NEW PROJECT Button */}
              <button
                onClick={promptCreateNewProject}
                className="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-3.5 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer border border-emerald-500"
                title="Create a new blank price list project"
              >
                <i className="fa-solid fa-folder-plus text-emerald-200 text-sm"></i>
                <span>NEW PROJECT</span>
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

              {/* ADD CATEGORY Button */}
              <button
                onClick={handleAddNewCategory}
                className="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold px-4 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
                title="Add a new category section to price list"
              >
                <i className="fa-solid fa-folder-plus text-sm"></i> ADD CATEGORY
              </button>

              {/* REALIGN CODES Button */}
              <button
                onClick={handleRealignAllProductCodes}
                className="bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-extrabold px-3.5 py-2 rounded-full text-xs uppercase tracking-wider shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer"
                title="Realign product codes sequentially (1, 2, 3...)"
              >
                <i className="fa-solid fa-arrow-down-1-9 text-sm"></i> REALIGN CODES
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
            <div className="bg-amber-50/60 border-2 border-amber-300 rounded-2xl p-5 md:p-6 space-y-6 transition-all duration-300 animate-fadeIn">
              {/* Drawer Top Header */}
              <div className="flex flex-wrap justify-between items-center pb-3 border-b border-amber-200 gap-3">
                <h3 className="text-base font-black text-slate-900 flex items-center gap-2">
                  <i className="fa-solid fa-sliders text-amber-600"></i> Edit Price List Content & Details (Real-time Live Preview)
                </h3>
                <button
                  onClick={handleSaveSettings}
                  disabled={savingSettings}
                  className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black px-5 py-2 rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm active:scale-95 cursor-pointer"
                >
                  <i className="fa-solid fa-floppy-disk"></i> {savingSettings ? 'Saving...' : 'Save Changes'}
                </button>
              </div>

              {/* 0. FIRST PAGE LAYOUT MODE SELECTOR */}
              <div className="bg-gradient-to-r from-emerald-50 via-teal-50 to-amber-50 border-2 border-emerald-400 rounded-2xl p-4.5 space-y-3 shadow-sm">
                <div className="flex items-center justify-between border-b border-emerald-200 pb-2">
                  <h4 className="text-xs font-black text-emerald-950 uppercase tracking-wider flex items-center gap-2">
                    <i className="fa-solid fa-layer-group text-emerald-600"></i>
                    First Page Layout Mode
                  </h4>
                  <span className="text-[10px] bg-emerald-700 text-white font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    Template Option
                  </span>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {/* Full Cover Page Option */}
                  <button
                    type="button"
                    onClick={() => handleInputChange('first_page_layout', 'full')}
                    className={`p-3 rounded-xl border-2 text-left transition-all flex items-start gap-3 cursor-pointer ${
                      (editForm.first_page_layout || 'full') === 'full'
                        ? 'bg-white border-emerald-600 shadow-md ring-2 ring-emerald-400'
                        : 'bg-white/70 border-slate-200 hover:bg-emerald-50/50'
                    }`}
                  >
                    <div className="w-10 h-14 bg-gradient-to-b from-red-600 to-red-800 rounded-lg shrink-0 flex flex-col items-center justify-center text-white text-[9px] font-black border border-red-400 p-1 shadow-xs">
                      <i className="fa-solid fa-sparkles mb-0.5 text-yellow-300"></i>
                      <span>FULL</span>
                      <span className="text-[7px]">COVER</span>
                    </div>
                    <div>
                      <div className="font-extrabold text-xs text-slate-900 flex items-center gap-1.5">
                        Full Cover Page
                        {(editForm.first_page_layout || 'full') === 'full' && (
                          <i className="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                        )}
                      </div>
                      <p className="text-[10px] text-slate-500 font-medium leading-snug mt-0.5">
                        Dedicated 210mm x 297mm full-page festive cover sheet with background & cover image.
                      </p>
                    </div>
                  </button>

                  {/* Simpler Header Option */}
                  <button
                    type="button"
                    onClick={() => handleInputChange('first_page_layout', 'simpler')}
                    className={`p-3 rounded-xl border-2 text-left transition-all flex items-start gap-3 cursor-pointer ${
                      editForm.first_page_layout === 'simpler'
                        ? 'bg-white border-emerald-600 shadow-md ring-2 ring-emerald-400'
                        : 'bg-white/70 border-slate-200 hover:bg-emerald-50/50'
                    }`}
                  >
                    <div className="w-10 h-14 bg-white rounded-lg shrink-0 flex flex-col items-center justify-between text-emerald-800 text-[7px] font-black border-2 border-emerald-700 p-0.5 shadow-xs">
                      <div className="w-full bg-emerald-700 text-white text-[6px] text-center font-bold">GSTIN</div>
                      <div className="text-[8px] font-black text-center text-emerald-900 leading-tight">HEADER</div>
                      <div className="w-full border-t border-emerald-500 text-[6px] text-center text-slate-600">TABLE</div>
                    </div>
                    <div>
                      <div className="font-extrabold text-xs text-slate-900 flex items-center gap-1.5">
                        Simpler Version (Classic)
                        {editForm.first_page_layout === 'simpler' && (
                          <i className="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                        )}
                      </div>
                      <p className="text-[10px] text-slate-500 font-medium leading-snug mt-0.5">
                        Compact green double-bordered header with GSTIN & Deity icons. Product table starts right on Page 1.
                      </p>
                    </div>
                  </button>
                </div>
              </div>

              {/* 1. SHOP IDENTITY & INVOCATION SECTION */}
              <div className="bg-white/80 border border-amber-200 rounded-2xl p-4.5 space-y-4 shadow-2xs">
                <div className="flex items-center gap-2 text-xs font-black text-amber-900 uppercase tracking-wider border-b border-amber-100 pb-2">
                  <i className="fa-solid fa-store text-amber-600"></i>
                  <span>Shop Identity & Invocation</span>
                </div>

                {/* Basic Shop Information Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 text-xs">
                  <div className="sm:col-span-2">
                    <label className="block text-slate-700 font-extrabold mb-1">Shop / Company Name</label>
                    <input
                      type="text"
                      value={editForm.store_name}
                      onChange={(e) => handleInputChange('store_name', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Tagline</label>
                    <input
                      type="text"
                      value={editForm.store_tagline}
                      onChange={(e) => handleInputChange('store_tagline', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">GSTIN No.</label>
                    <input
                      type="text"
                      value={editForm.gstin || ''}
                      onChange={(e) => handleInputChange('gstin', e.target.value)}
                      placeholder="e.g. 33ABLFM8150D1ZD"
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Price List Year</label>
                    <input
                      type="text"
                      value={editForm.store_year}
                      onChange={(e) => handleInputChange('store_year', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div className="space-y-2">
                    <div className="flex justify-between items-center">
                      <label className="block text-slate-700 font-extrabold mb-0.5">Discount Offer %</label>
                      <label className="inline-flex items-center gap-1 cursor-pointer select-none">
                        <input
                          type="checkbox"
                          checked={editForm.show_discount_badge !== false}
                          onChange={(e) => handleInputChange('show_discount_badge', e.target.checked)}
                          className="rounded text-sky-600 focus:ring-sky-500 w-3.5 h-3.5"
                        />
                        <span className="text-[10px] text-slate-700 font-extrabold">Show Badge</span>
                      </label>
                    </div>
                    <input
                      type="number"
                      value={editForm.discount_percent}
                      onChange={(e) => handleInputChange('discount_percent', parseFloat(e.target.value) || 0)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-1.5 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 text-xs"
                    />

                    {editForm.show_discount_badge !== false && (
                      <div className="pt-2 border-t border-amber-100 space-y-1.5">
                        <div className="flex justify-between text-[10px] font-bold text-slate-700 mb-0.5">
                          <span>Badge Size / Scale</span>
                          <span className="font-mono text-sky-600">{editForm.discount_badge_scale || editForm.discount_scale || 100}%</span>
                        </div>
                        <input
                          type="range"
                          min="30"
                          max="300"
                          value={editForm.discount_badge_scale || editForm.discount_scale || 100}
                          onChange={(e) => {
                            const val = parseInt(e.target.value, 10);
                            handleInputChange('discount_badge_scale', val);
                            handleInputChange('discount_scale', val);
                          }}
                          className="w-full accent-sky-600 cursor-pointer h-1.5 bg-slate-100 rounded-lg"
                        />
                        <button
                          type="button"
                          onClick={() => {
                            const isSimpler = (editForm.first_page_layout || 'full') === 'simpler';
                            handleInputChange('discount_badge_x', isSimpler ? 82 : 75);
                            handleInputChange('discount_badge_y', isSimpler ? 2.2 : 82);
                          }}
                          className="w-full bg-white hover:bg-slate-100 text-slate-700 font-extrabold text-[10px] py-1 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-1 border border-slate-200 mt-1"
                        >
                          <i className="fa-solid fa-rotate-left text-xs text-sky-600"></i> Reset Offer Badge Pos
                        </button>
                      </div>
                    )}
                  </div>
                </div>

                {/* Invocation Symbol & Tamil Invocation Line Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs items-end">
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Top Invocation Symbol</label>
                    <div className="flex flex-wrap gap-1.5 mb-2">
                      {['உ', '卐', '🕉', '✨', '✝', '☪', 'ੴ', ''].map((sym) => (
                        <button
                          key={sym || 'none'}
                          type="button"
                          onClick={() => handleInputChange('store_invocation_symbol', sym)}
                          className={`px-2.5 py-1 rounded-lg font-black text-xs border transition-all cursor-pointer ${editForm.store_invocation_symbol === sym
                            ? 'bg-amber-500 text-white border-amber-600 shadow-xs'
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
                  <div className="md:col-span-2">
                    <label className="block text-slate-700 font-extrabold mb-1">Tamil Invocation Line</label>
                    <input
                      type="text"
                      value={editForm.store_invocation}
                      onChange={(e) => handleInputChange('store_invocation', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                </div>

                {/* Company Name Font Style Selector */}
                <div className="bg-amber-50/70 p-3.5 rounded-2xl border border-amber-200 space-y-2">
                  <label className="block text-slate-800 font-black text-xs uppercase tracking-wide">
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
                        className={`p-2.5 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center cursor-pointer ${(editForm.store_name_font || 'cinzel') === f.id
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

                {/* Shop Name & Address Size & Position Controls */}
                <div className="bg-emerald-50/70 p-3 rounded-xl border border-emerald-200/80 space-y-2">
                  <div className="flex justify-between items-center text-xs font-bold text-slate-800">
                    <span className="flex items-center gap-1.5 font-extrabold text-emerald-950">
                      <i className="fa-solid fa-up-right-and-down-left-from-center text-emerald-600 text-xs"></i>
                      Shop Name & Address Size & Position
                    </span>
                    <span className="font-mono text-emerald-700 bg-white px-2 py-0.5 rounded-md border border-emerald-200 text-[11px] font-extrabold">
                      {editForm.shop_info_scale || 100}%
                    </span>
                  </div>
                  <input
                    type="range"
                    min="40"
                    max="250"
                    step="5"
                    value={editForm.shop_info_scale || 100}
                    onChange={(e) => handleInputChange('shop_info_scale', parseInt(e.target.value, 10))}
                    className="w-full accent-emerald-600 cursor-pointer h-1.5 bg-slate-200 rounded-lg"
                  />
                  <div className="flex gap-1.5 pt-0.5">
                    <button
                      type="button"
                      onClick={() => {
                        handleInputChange('shop_info_x', 0);
                        handleInputChange('shop_info_y', 0);
                        handleInputChange('shop_info_scale', 100);
                      }}
                      className="w-full bg-white hover:bg-slate-100 text-slate-700 font-extrabold text-[10px] py-1 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-1 border border-slate-200"
                    >
                      <i className="fa-solid fa-rotate-left text-xs text-emerald-600"></i> Reset Shop Info Position & Size
                    </button>
                  </div>
                </div>
              </div>

              {/* 2. COVER PAGE MEDIA & STYLING CUSTOMIZATION */}
              <div className="bg-white/80 border border-amber-200 rounded-2xl p-4.5 space-y-4 shadow-2xs">
                <div className="flex items-center gap-2 text-xs font-black text-amber-900 uppercase tracking-wider border-b border-amber-100 pb-2">
                  <i className="fa-solid fa-palette text-amber-600"></i>
                  <span>Cover Page Media, Background & Colors</span>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 text-xs">
                  {/* Card A: Cover Image & Store Logo Uploads */}
                  <div className="bg-amber-50/60 p-4 rounded-2xl border border-amber-200 space-y-4 flex flex-col justify-between">
                    {/* Upload God / Deity Image 1 (Left Image / Cover) */}
                    <div className="space-y-2">
                      <label className="block text-slate-800 font-black text-xs flex items-center justify-between">
                        <span className="flex items-center gap-1.5">
                          <i className="fa-solid fa-om text-amber-600"></i>
                          God Image 1 (Left / Cover)
                        </span>
                        {editForm.store_deity_image && (
                          <button
                            type="button"
                            onClick={() => {
                              handleInputChange('store_deity_image', '');
                              handleInputChange('store_deity_preset', 'none');
                            }}
                            className="text-[10px] bg-red-100 hover:bg-red-200 text-red-700 font-extrabold px-2 py-0.5 rounded-lg transition-all border border-red-300 cursor-pointer"
                          >
                            <i className="fa-solid fa-trash-can mr-1"></i> Remove 1
                          </button>
                        )}
                      </label>

                      {editForm.store_deity_image ? (
                        <div className="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-amber-300 shadow-2xs">
                          <img
                            src={getImageUrl(editForm.store_deity_image)}
                            alt="God Image 1 Preview"
                            className="h-11 w-11 object-contain rounded-lg border border-slate-200 bg-slate-50 p-0.5"
                          />
                          <div className="space-y-0.5">
                            <p className="text-[11px] font-black text-emerald-700 flex items-center gap-1">
                              <i className="fa-solid fa-circle-check"></i> God Image Active
                            </p>
                            <p className="text-[10px] text-slate-500 font-medium leading-tight">
                              Displayed on Cover Page & Page 1 Header.
                            </p>
                          </div>
                        </div>
                      ) : (
                        <p className="text-[10px] text-slate-600 font-medium">
                          Upload Custom Deity Image (PNG with transparent bg works best).
                        </p>
                      )}

                      <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => {
                          const file = e.target.files[0];
                          if (file) {
                            const reader = new FileReader();
                            reader.onload = (event) => {
                              handleInputChange('store_deity_image', event.target.result);
                              handleInputChange('store_deity_preset', 'custom');
                            };
                            reader.readAsDataURL(file);
                          }
                        }}
                        className="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-amber-500 file:text-white hover:file:bg-amber-600 cursor-pointer border border-amber-300 rounded-xl bg-white p-1"
                      />

                      {/* God / Deity Image Size Controls */}
                      <div className="space-y-1.5 pt-2.5 border-t border-amber-200">
                        <div className="flex justify-between items-center text-xs font-bold text-slate-800">
                          <span className="flex items-center gap-1.5">
                            <i className="fa-solid fa-up-right-and-down-left-from-center text-amber-600 text-xs"></i>
                            God Image Size (Simpler & Cover)
                          </span>
                          <span className="font-mono text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 text-[11px] font-extrabold">
                            {editForm.simpler_deity_scale || editForm.deity_scale || 100}%
                          </span>
                        </div>
                        <input
                          type="range"
                          min="40"
                          max="220"
                          step="5"
                          value={editForm.simpler_deity_scale || editForm.deity_scale || 100}
                          onChange={(e) => {
                            const val = parseInt(e.target.value, 10);
                            handleInputChange('simpler_deity_scale', val);
                            handleInputChange('deity_scale', val);
                          }}
                          className="w-full accent-emerald-600 cursor-pointer h-1.5 bg-slate-200 rounded-lg"
                        />
                        <div className="flex gap-1.5 pt-1">
                          {[
                            { label: 'Small (60%)', val: 60 },
                            { label: 'Medium (100%)', val: 100 },
                            { label: 'Large (140%)', val: 140 },
                            { label: 'XL (180%)', val: 180 },
                          ].map((preset) => (
                            <button
                              key={preset.label}
                              type="button"
                              onClick={() => {
                                handleInputChange('simpler_deity_scale', preset.val);
                                handleInputChange('deity_scale', preset.val);
                              }}
                              className={`flex-1 py-1 text-[9.5px] font-extrabold rounded-lg border transition-all cursor-pointer ${
                                (editForm.simpler_deity_scale || editForm.deity_scale || 100) === preset.val
                                  ? 'bg-emerald-600 text-white border-emerald-700 shadow-xs'
                                  : 'bg-white text-slate-700 border-slate-200 hover:bg-emerald-50'
                              }`}
                            >
                              {preset.label}
                            </button>
                          ))}
                        </div>
                      </div>
                    </div>

                    {/* Upload Store Logo */}
                    <div className="space-y-2 pt-3 border-t border-amber-200">
                      <label className="block text-slate-800 font-extrabold text-xs flex items-center justify-between">
                        <span className="flex items-center gap-1.5">
                          <i className="fa-solid fa-building-flag text-amber-600"></i>
                          Store Logo Image
                        </span>
                        {editForm.store_logo && (
                          <button
                            type="button"
                            onClick={() => handleInputChange('store_logo', '')}
                            className="text-[10px] bg-red-100 hover:bg-red-200 text-red-700 font-extrabold px-2 py-0.5 rounded-lg transition-all border border-red-300 cursor-pointer"
                          >
                            <i className="fa-solid fa-trash-can mr-1"></i> Remove Logo
                          </button>
                        )}
                      </label>
                      {editForm.store_logo && (
                        <div className="flex items-center gap-3 bg-white p-2 rounded-xl border border-amber-300 shadow-2xs">
                          <img
                            src={getImageUrl(editForm.store_logo)}
                            alt="Store Logo"
                            className="h-9 w-9 object-contain rounded-lg border border-amber-300 bg-amber-50"
                          />
                          <p className="text-[11px] font-black text-emerald-700 flex items-center gap-1">
                            <i className="fa-solid fa-circle-check"></i> Logo Active
                          </p>
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
                        className="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-amber-500 file:text-white hover:file:bg-amber-600 cursor-pointer"
                      />
                    </div>
                  </div>

                  {/* Card B: A4 Cover Background Color Options */}
                  <div className="bg-amber-50/60 p-4 rounded-2xl border border-amber-200 space-y-3 flex flex-col justify-between">
                    <div className="space-y-2">
                      <label className="block text-slate-800 font-extrabold text-xs flex items-center gap-1.5">
                        <i className="fa-solid fa-image text-amber-600"></i>
                        A4 Cover Background Color Options
                      </label>
                      <div className="grid grid-cols-3 sm:grid-cols-6 lg:grid-cols-3 xl:grid-cols-6 gap-1.5 mb-2">
                        {[
                          { id: 'none', label: 'None', bg: 'bg-slate-100 border border-slate-300 text-slate-700 font-bold' },
                          { id: '/images/cover_bg_orange_burst.jpg', label: '#1 Orange', bg: 'bg-amber-500' },
                          { id: '/images/cover_bg_red_sparkle.jpg', label: '#2 Red', bg: 'bg-red-600' },
                          { id: '/images/cover_bg_maroon_festive.jpg', label: '#3 Maroon', bg: 'bg-amber-900' },
                          { id: '/images/cover_bg_blue_gradient.jpg', label: '#4 Festive', bg: 'bg-indigo-600' },
                          { id: '/images/cover_bg_blue_sparkle.jpg', label: '#5 Blue Sparkle', bg: 'bg-sky-500' },
                        ].map((item) => (
                          <button
                            key={item.id}
                            type="button"
                            onClick={() => handleInputChange('store_cover_bg', item.id)}
                            className={`p-1 rounded-xl border-2 transition-all flex flex-col items-center justify-center gap-1 cursor-pointer ${
                              (editForm.store_cover_bg || '/images/cover_bg_orange_burst.jpg') === item.id ||
                              (['/images/cover_bg.jpg', '/images/cover_bg_1.jpg'].includes(editForm.store_cover_bg) && item.id === '/images/cover_bg_orange_burst.jpg')
                                ? 'border-amber-600 bg-amber-200 shadow-xs scale-105'
                                : 'border-slate-200 bg-white hover:bg-amber-100'
                            }`}
                          >
                            <div className={`w-full h-7 rounded-lg ${item.bg} overflow-hidden shadow-2xs relative flex items-center justify-center`}>
                              {item.id === 'none' ? (
                                <i className="fa-solid fa-ban text-slate-500 text-xs"></i>
                              ) : (
                                <img src={item.id} alt={item.label} className="w-full h-full object-cover" />
                              )}
                            </div>
                            <span className="text-[9px] font-black truncate max-w-full">{item.label}</span>
                          </button>
                        ))}
                      </div>

                      {editForm.store_cover_bg && editForm.store_cover_bg !== 'none' && !['/images/cover_bg_orange_burst.jpg', '/images/cover_bg_red_sparkle.jpg', '/images/cover_bg_maroon_festive.jpg', '/images/cover_bg_blue_gradient.jpg', '/images/cover_bg_blue_sparkle.jpg', '/images/cover_bg_blue_burst.jpg', '/images/cover_bg_red_burst.jpg', '/images/cover_bg_purple.jpg', '/images/cover_bg_1.jpg', '/images/cover_bg_5.jpg', '/images/cover_bg_red.jpg', '/images/cover_bg.jpg'].includes(editForm.store_cover_bg) && (
                        <div className="flex items-center justify-between gap-2 bg-white p-2 rounded-xl border border-amber-200 mb-2 shadow-2xs">
                          <img
                            src={getImageUrl(editForm.store_cover_bg)}
                            alt="Custom Background"
                            className="h-9 w-14 object-cover rounded-lg border border-amber-300"
                          />
                          <button
                            type="button"
                            onClick={() => handleInputChange('store_cover_bg', '/images/cover_bg_orange_burst.jpg')}
                            className="text-[11px] text-red-600 font-extrabold hover:underline cursor-pointer"
                          >
                            Reset Background
                          </button>
                        </div>
                      )}
                    </div>

                    <div className="space-y-1 pt-2 border-t border-amber-200">
                      <label className="block text-[11px] font-bold text-slate-600">Upload Custom Background</label>
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
                        className="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-amber-500 file:text-white hover:file:bg-amber-600 cursor-pointer"
                      />
                    </div>
                  </div>

                  {/* Card C: Cover Text Font Colors */}
                  <div className="bg-amber-50/60 p-4 rounded-2xl border border-amber-200 space-y-3">
                    <label className="block text-slate-800 font-black text-xs flex items-center justify-between border-b border-amber-200 pb-1.5">
                      <span className="flex items-center gap-1.5 text-slate-900">
                        <i className="fa-solid fa-palette text-amber-600"></i>
                        Cover Text Font Colors
                      </span>
                      <span className="text-[10px] text-amber-700 font-bold">Custom Pickers</span>
                    </label>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                      {/* Store Title Font Color */}
                      <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5 shadow-2xs">
                        <div className="flex justify-between items-center text-[11px] font-bold text-slate-800">
                          <span>Store Title</span>
                          <span className="text-[9px] font-mono text-slate-500">{editForm.store_title_color || '#FFFFFF'}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                          <input
                            type="color"
                            value={editForm.store_title_color || '#FFFFFF'}
                            onChange={(e) => handleInputChange('store_title_color', e.target.value)}
                            className="w-7 h-7 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                          />
                          <div className="flex items-center gap-1 flex-wrap">
                            {['#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626', '#0F172A'].map((c) => (
                              <button
                                key={c}
                                type="button"
                                onClick={() => handleInputChange('store_title_color', c)}
                                className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer shrink-0"
                                style={{ backgroundColor: c, width: '20px', height: '20px' }}
                                title={c}
                              />
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* Tagline Font Color */}
                      <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5 shadow-2xs">
                        <div className="flex justify-between items-center text-[11px] font-bold text-slate-800">
                          <span>Tagline</span>
                          <span className="text-[9px] font-mono text-slate-500">{editForm.store_tagline_color || '#FFFFFF'}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                          <input
                            type="color"
                            value={editForm.store_tagline_color || '#FFFFFF'}
                            onChange={(e) => handleInputChange('store_tagline_color', e.target.value)}
                            className="w-7 h-7 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                          />
                          <div className="flex items-center gap-1 flex-wrap">
                            {['#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626', '#0F172A'].map((c) => (
                              <button
                                key={c}
                                type="button"
                                onClick={() => handleInputChange('store_tagline_color', c)}
                                className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer shrink-0"
                                style={{ backgroundColor: c, width: '20px', height: '20px' }}
                                title={c}
                              />
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* Invocation Font Color */}
                      <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5 shadow-2xs">
                        <div className="flex justify-between items-center text-[11px] font-bold text-slate-800">
                          <span>Deity / Invocation</span>
                          <span className="text-[9px] font-mono text-slate-500">{editForm.store_invocation_color || '#FFFFFF'}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                          <input
                            type="color"
                            value={editForm.store_invocation_color || '#FFFFFF'}
                            onChange={(e) => handleInputChange('store_invocation_color', e.target.value)}
                            className="w-7 h-7 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                          />
                          <div className="flex items-center gap-1 flex-wrap">
                            {['#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626', '#0F172A'].map((c) => (
                              <button
                                key={c}
                                type="button"
                                onClick={() => handleInputChange('store_invocation_color', c)}
                                className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer shrink-0"
                                style={{ backgroundColor: c, width: '20px', height: '20px' }}
                                title={c}
                              />
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* Price List Badge Font Color */}
                      <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5 shadow-2xs">
                        <div className="flex justify-between items-center text-[11px] font-bold text-slate-800">
                          <span>Badge Text</span>
                          <span className="text-[9px] font-mono text-slate-500">{editForm.store_badge_color || '#0F172A'}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                          <input
                            type="color"
                            value={editForm.store_badge_color || '#0F172A'}
                            onChange={(e) => handleInputChange('store_badge_color', e.target.value)}
                            className="w-7 h-7 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                          />
                          <div className="flex items-center gap-1 flex-wrap">
                            {['#0F172A', '#FFFFFF', '#FBBF24', '#FEF08A', '#38BDF8', '#DC2626'].map((c) => (
                              <button
                                key={c}
                                type="button"
                                onClick={() => handleInputChange('store_badge_color', c)}
                                className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer shrink-0"
                                style={{ backgroundColor: c, width: '20px', height: '20px' }}
                                title={c}
                              />
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* Text Outline Stroke Color */}
                      <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5 shadow-2xs col-span-full">
                        <div className="flex justify-between items-center text-[11px] font-bold text-slate-800">
                          <span className="flex items-center gap-1">
                            <i className="fa-solid fa-border-all text-amber-600"></i>
                            Text Outline Stroke
                          </span>
                          <span className="text-[9px] font-mono text-slate-500">{editForm.text_stroke_color || '#000000'}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                          <input
                            type="color"
                            value={editForm.text_stroke_color || '#000000'}
                            onChange={(e) => handleInputChange('text_stroke_color', e.target.value)}
                            className="w-7 h-7 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                          />
                          <div className="flex items-center gap-1 flex-wrap">
                            {['#000000', '#FFFFFF', '#78350F', '#450A0A', '#0F172A', '#1E1B4B', '#064E3B', '#F59E0B'].map((c) => (
                              <button
                                key={c}
                                type="button"
                                onClick={() => handleInputChange('text_stroke_color', c)}
                                className="w-5 h-5 rounded-full border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer shrink-0"
                                style={{ backgroundColor: c, width: '20px', height: '20px' }}
                                title={c}
                              />
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* God Image Outline Color */}
                      <div className="bg-white p-2.5 rounded-xl border border-amber-200 space-y-1.5 shadow-2xs col-span-full">
                        <div className="flex justify-between items-center text-[11px] font-bold text-slate-800">
                          <span className="flex items-center gap-1">
                            <i className="fa-solid fa-image text-amber-600"></i>
                            Cover Image Outline
                          </span>
                          <span className="text-[9px] font-mono text-slate-500">{editForm.deity_stroke_color || '#FFFFFF'}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                          <input
                            type="color"
                            value={editForm.deity_stroke_color === 'transparent' ? '#FFFFFF' : (editForm.deity_stroke_color || '#FFFFFF')}
                            onChange={(e) => handleInputChange('deity_stroke_color', e.target.value)}
                            className="w-7 h-7 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white shrink-0"
                          />
                          <div className="flex items-center gap-1 flex-wrap">
                            {['#FFFFFF', '#000000', '#FDE047', '#F59E0B', '#DC2626', '#38BDF8', '#0F172A', 'transparent'].map((c) => (
                              <button
                                key={c}
                                type="button"
                                onClick={() => handleInputChange('deity_stroke_color', c)}
                                className={`h-5 rounded-md border border-slate-300 shadow-2xs hover:scale-110 transition-transform cursor-pointer flex items-center justify-center text-[9px] font-bold shrink-0 ${c === 'transparent' ? 'px-1 bg-slate-100 text-slate-700' : 'w-5'}`}
                                style={{ backgroundColor: c !== 'transparent' ? c : undefined, width: c !== 'transparent' ? '20px' : undefined, height: '20px' }}
                                title={c === 'transparent' ? 'No Outline' : c}
                              >
                                {c === 'transparent' ? 'None' : ''}
                              </button>
                            ))}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* 3. PAYMENT QR CODES & CUSTOM FLOATING IMAGE */}
              <div className="bg-white/80 border border-amber-200 rounded-2xl p-4.5 space-y-4 shadow-2xs">
                <div className="flex items-center justify-between text-xs font-black text-amber-900 uppercase tracking-wider border-b border-amber-100 pb-2">
                  <span className="flex items-center gap-2">
                    <i className="fa-solid fa-qrcode text-amber-600"></i>
                    Payment QR Codes & Custom Floating Image
                  </span>
                  <span className="text-[10px] text-amber-700 font-bold bg-amber-100 px-2 py-0.5 rounded-lg border border-amber-300">
                    Interactive Preview Controls
                  </span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                  {/* QR CODE 1 CARD */}
                  <div className="bg-white p-3.5 rounded-2xl border border-amber-200 space-y-2.5 shadow-2xs flex flex-col justify-between">
                    <div className="space-y-2">
                      <div className="flex justify-between items-center text-xs font-black text-slate-800 border-b border-slate-100 pb-1.5">
                        <span className="flex items-center gap-1.5 text-sky-600">
                          <i className="fa-solid fa-1"></i> QR Code 1 (Primary / GPay)
                        </span>
                        {editForm.store_upi_qr && (
                          <span className="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">Custom Image</span>
                        )}
                      </div>

                      {editForm.store_upi_qr && (
                        <div className="flex items-center justify-between gap-3 bg-slate-50 p-2 rounded-lg border border-slate-200">
                          <img
                            src={getImageUrl(editForm.store_upi_qr)}
                            alt="Payment QR 1"
                            className="h-10 w-10 object-contain rounded-md border border-slate-300 bg-white"
                          />
                          <button
                            type="button"
                            onClick={() => handleInputChange('store_upi_qr', '')}
                            className="text-[11px] text-red-600 font-extrabold hover:underline cursor-pointer"
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
                    </div>

                    <div className="space-y-2 pt-2 border-t border-slate-100">
                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">QR 1 Account Name</label>
                        <input
                          type="text"
                          value={editForm.store_upi_name || ''}
                          onChange={(e) => handleInputChange('store_upi_name', e.target.value)}
                          placeholder="e.g. Muthusamy Ganesan"
                          className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
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
                  </div>

                  {/* QR CODE 2 CARD */}
                  <div className="bg-white p-3.5 rounded-2xl border border-amber-200 space-y-2.5 shadow-2xs flex flex-col justify-between">
                    <div className="space-y-2">
                      <div className="flex justify-between items-center text-xs font-black text-slate-800 border-b border-slate-100 pb-1.5">
                        <span className="flex items-center gap-1.5 text-indigo-600">
                          <i className="fa-solid fa-2"></i> QR Code 2 (Secondary / PhonePe)
                        </span>
                        {editForm.store_upi_qr_2 && (
                          <span className="text-[10px] bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded font-bold">Custom Image</span>
                        )}
                      </div>

                      {editForm.store_upi_qr_2 && (
                        <div className="flex items-center justify-between gap-3 bg-slate-50 p-2 rounded-lg border border-slate-200">
                          <img
                            src={getImageUrl(editForm.store_upi_qr_2)}
                            alt="Payment QR 2"
                            className="h-10 w-10 object-contain rounded-md border border-slate-300 bg-white"
                          />
                          <button
                            type="button"
                            onClick={() => handleInputChange('store_upi_qr_2', '')}
                            className="text-[11px] text-red-600 font-extrabold hover:underline cursor-pointer"
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
                    </div>

                    <div className="space-y-2 pt-2 border-t border-slate-100">
                      <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">QR 2 Account Name</label>
                        <input
                          type="text"
                          value={editForm.store_upi_name_2 || ''}
                          onChange={(e) => handleInputChange('store_upi_name_2', e.target.value)}
                          placeholder="e.g. Muthusamy Ganesan"
                          className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
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

                  {/* CUSTOM FLOATING IMAGES CARD (PAGE 1 - MULTIPLE SLOTS 1 TO 5) */}
                  <div className="bg-white p-3.5 rounded-2xl border border-sky-300 space-y-3 shadow-2xs">
                    <div className="flex justify-between items-center text-xs font-black text-slate-800 border-b border-slate-100 pb-2">
                      <span className="flex items-center gap-1.5 text-sky-700">
                        <i className="fa-solid fa-layer-group text-sky-500"></i> Floating Images / Stickers (Page 1)
                      </span>
                      <span className="text-[10px] text-sky-600 font-extrabold bg-sky-50 px-2 py-0.5 rounded-full border border-sky-200">
                        Multi-Sticker Support
                      </span>
                    </div>

                    {/* Slot Tabs header (1 to 5) */}
                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1">
                      {[1, 2, 3, 4, 5].map((slotIdx) => {
                        const p = getFloatImgProps(slotIdx);
                        const hasImg = Boolean(p.image);
                        const isActive = activeFloatSlot === slotIdx;
                        return (
                          <button
                            key={slotIdx}
                            type="button"
                            onClick={() => setActiveFloatSlot(slotIdx)}
                            className={`px-2.5 py-1 rounded-lg text-[10px] font-black transition-all flex items-center gap-1 cursor-pointer shrink-0 border ${
                              isActive
                                ? 'bg-sky-600 text-white border-sky-600 shadow-xs'
                                : hasImg
                                ? 'bg-sky-50 text-sky-800 border-sky-300 hover:bg-sky-100'
                                : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'
                            }`}
                          >
                            <span>Img #{slotIdx}</span>
                            {hasImg && <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>}
                          </button>
                        );
                      })}
                    </div>

                    {/* Selected Active Slot Controls */}
                    {(() => {
                      const p = getFloatImgProps(activeFloatSlot);
                      const suffix = activeFloatSlot === 1 ? '' : `_${activeFloatSlot}`;
                      return (
                        <div className="space-y-2.5 bg-slate-50/70 p-2.5 rounded-xl border border-slate-200">
                          <div className="flex justify-between items-center text-xs font-black text-slate-800 border-b border-slate-200/60 pb-1.5">
                            <span className="flex items-center gap-1.5 text-sky-800">
                              Image Slot #{activeFloatSlot}
                            </span>
                            <label className="inline-flex items-center gap-1 cursor-pointer select-none">
                              <input
                                type="checkbox"
                                checked={p.show}
                                onChange={(e) => handleInputChange(`show_custom_float_image${suffix}`, e.target.checked)}
                                className="rounded text-sky-600 focus:ring-sky-500 w-3.5 h-3.5"
                              />
                              <span className="text-[10px] text-slate-700 font-extrabold">Show</span>
                            </label>
                          </div>

                          {p.image && (
                            <div className="flex items-center justify-between gap-2 bg-white p-2 rounded-lg border border-sky-200 shadow-2xs">
                              <div className="flex items-center gap-2">
                                <img
                                  src={getImageUrl(p.image)}
                                  alt={`Custom Floating ${activeFloatSlot}`}
                                  className="h-9 w-9 object-contain rounded-md border border-slate-300 bg-white"
                                />
                                <div className="text-[10px] font-extrabold text-slate-800 leading-tight">
                                  <div>Pos: X: {p.x}%, Y: {p.y}%</div>
                                  <div className="text-[9px] text-sky-700 font-medium">💡 Drag directly on Page 1</div>
                                </div>
                              </div>
                              <button
                                type="button"
                                onClick={() => handleInputChange(`custom_float_image${suffix}`, '')}
                                className="text-[11px] text-red-600 font-extrabold hover:underline cursor-pointer"
                              >
                                Remove
                              </button>
                            </div>
                          )}

                          <div>
                            <label className="block text-[11px] font-bold text-slate-600 mb-1">
                              {p.image ? `Change Image #${activeFloatSlot}` : `Upload Image for Slot #${activeFloatSlot}`}
                            </label>
                            <input
                              type="file"
                              accept="image/*"
                              onChange={(e) => {
                                const file = e.target.files[0];
                                if (file) {
                                  const reader = new FileReader();
                                  reader.onload = (evt) => {
                                    handleInputChange(`custom_float_image${suffix}`, evt.target.result);
                                  };
                                  reader.readAsDataURL(file);
                                }
                              }}
                              className="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-sky-600 file:text-white hover:file:bg-sky-700 cursor-pointer"
                            />
                          </div>

                          {p.image && (
                            <div className="space-y-2 pt-2 border-t border-slate-200/60">
                              <div>
                                <div className="flex justify-between text-[10px] font-bold text-slate-700 mb-0.5">
                                  <span>Scale / Size</span>
                                  <span className="font-mono text-sky-600">{p.scale}%</span>
                                </div>
                                <input
                                  type="range"
                                  min="20"
                                  max="300"
                                  value={p.scale}
                                  onChange={(e) => handleInputChange(`custom_float_scale${suffix}`, parseInt(e.target.value, 10))}
                                  className="w-full accent-sky-600 cursor-pointer h-1.5 bg-slate-200 rounded-lg"
                                />
                              </div>

                              <button
                                type="button"
                                onClick={() => {
                                  const isSimpler = (editForm.first_page_layout || 'full') === 'simpler';
                                  const defX = isSimpler
                                    ? (activeFloatSlot === 1 ? 72 : activeFloatSlot === 2 ? 15 : activeFloatSlot === 3 ? 45 : 30)
                                    : (activeFloatSlot === 1 ? 15 : activeFloatSlot === 2 ? 70 : activeFloatSlot === 3 ? 45 : 20);
                                  const defY = isSimpler
                                    ? (activeFloatSlot === 1 ? 1.5 : activeFloatSlot === 2 ? 1.5 : activeFloatSlot === 3 ? 1.5 : 5)
                                    : (activeFloatSlot === 1 ? 15 : activeFloatSlot === 2 ? 25 : activeFloatSlot === 3 ? 55 : 70);
                                  handleInputChange(`custom_float_x${suffix}`, defX);
                                  handleInputChange(`custom_float_y${suffix}`, defY);
                                }}
                                className="w-full bg-white hover:bg-slate-100 text-slate-700 font-extrabold text-[10px] py-1.5 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-1 border border-slate-200"
                              >
                                <i className="fa-solid fa-rotate-left text-xs text-sky-600"></i> Reset Position Slot #{activeFloatSlot}
                              </button>
                            </div>
                          )}
                        </div>
                      );
                    })()}
                  </div>
                </div>
              </div>

              {/* 4. CONTACT INFO & STORE ADDRESS SECTION */}
              <div className="bg-white/80 border border-amber-200 rounded-2xl p-4.5 space-y-4 shadow-2xs">
                <div className="flex items-center gap-2 text-xs font-black text-amber-900 uppercase tracking-wider border-b border-amber-100 pb-2">
                  <i className="fa-solid fa-address-book text-amber-600"></i>
                  <span>Contact Information & Store Address</span>
                </div>

                {/* 4 Phone Numbers Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Phone 1 (WhatsApp)</label>
                    <input
                      type="text"
                      value={editForm.store_phone || ''}
                      onChange={(e) => handleInputChange('store_phone', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Phone Number 2</label>
                    <input
                      type="text"
                      value={editForm.store_phone_2 || ''}
                      onChange={(e) => handleInputChange('store_phone_2', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Phone Number 3</label>
                    <input
                      type="text"
                      value={editForm.store_phone_3 || ''}
                      onChange={(e) => handleInputChange('store_phone_3', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Phone Number 4</label>
                    <input
                      type="text"
                      value={editForm.store_phone_4 || ''}
                      onChange={(e) => handleInputChange('store_phone_4', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                </div>

                {/* Email, GPay Number & Full Store Address Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Email / Website</label>
                    <input
                      type="text"
                      value={editForm.store_email}
                      onChange={(e) => handleInputChange('store_email', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">GPay / PhonePe Number</label>
                    <input
                      type="text"
                      value={editForm.store_gpay || ''}
                      onChange={(e) => handleInputChange('store_gpay', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Full Store Address</label>
                    <input
                      type="text"
                      value={editForm.store_address}
                      onChange={(e) => handleInputChange('store_address', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                </div>
              </div>

              {/* 5. PRINT & TABLE LAYOUT + BANK ACCOUNT DETAILS */}
              <div className="bg-white/80 border border-amber-200 rounded-2xl p-4.5 space-y-4 shadow-2xs">
                {/* Print Layout Options */}
                <div className="space-y-3">
                  <div className="flex items-center gap-2 text-xs font-black text-amber-900 uppercase tracking-wider border-b border-amber-100 pb-2">
                    <i className="fa-solid fa-sliders text-amber-600"></i>
                    <span>Print & Table Layout Settings</span>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs items-center">
                    <div>
                      <label className="block text-slate-700 font-extrabold mb-1">Rows Per A4 Page (TR Count)</label>
                      <input
                        type="number"
                        min="10"
                        max="50"
                        value={editForm.max_tr_per_page || 30}
                        onChange={(e) => handleInputChange('max_tr_per_page', parseInt(e.target.value) || 30)}
                        className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 text-amber-900"
                      />
                    </div>
                    <div>
                      <label className="block text-slate-700 font-extrabold mb-1">Table Row Height (px)</label>
                      <input
                        type="number"
                        min="14"
                        max="50"
                        value={editForm.table_row_height || 22}
                        onChange={(e) => handleInputChange('table_row_height', parseInt(e.target.value) || 22)}
                        className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 text-amber-900"
                      />
                    </div>
                    <div>
                      <div className="flex items-center justify-between mb-1">
                        <label className="block text-slate-700 font-extrabold text-xs">Footer Section (Bank, QR & Notes)</label>
                        <button
                          type="button"
                          onClick={() => handleInputChange('show_footer', editForm.show_footer === false ? true : false)}
                          className={`flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg font-black text-[11px] transition-all cursor-pointer border ${editForm.show_footer !== false && editForm.footer_position !== 'disabled'
                            ? 'bg-emerald-600 text-white border-emerald-700 hover:bg-emerald-700'
                            : 'bg-slate-200 text-slate-700 border-slate-300 hover:bg-slate-300'
                          }`}
                        >
                          <i className={`fa-solid ${editForm.show_footer !== false && editForm.footer_position !== 'disabled' ? 'fa-toggle-on text-xs' : 'fa-toggle-off text-xs'}`}></i>
                          <span>{editForm.show_footer !== false && editForm.footer_position !== 'disabled' ? 'Enabled' : 'Disabled'}</span>
                        </button>
                      </div>
                      <select
                        value={editForm.show_footer === false ? 'disabled' : (editForm.footer_position || 'below_table')}
                        onChange={(e) => {
                          if (e.target.value === 'disabled') {
                            handleInputChange('show_footer', false);
                            handleInputChange('footer_position', 'disabled');
                          } else {
                            handleInputChange('show_footer', true);
                            handleInputChange('footer_position', e.target.value);
                          }
                        }}
                        className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer"
                      >
                        <option value="below_table">📍 Below Product Table (Next to Table)</option>
                        <option value="new_page">📄 New Dedicated Page (Standalone Page)</option>
                        <option value="disabled">🚫 Disabled / Hidden (No Footer)</option>
                      </select>
                    </div>
                  </div>
                </div>

                {/* Bank Account Details */}
                <div className="pt-3 border-t border-amber-200 space-y-3">
                  <div className="flex flex-wrap items-center justify-between gap-2 border-b border-amber-100 pb-2">
                    <div className="font-extrabold text-amber-900 text-xs uppercase tracking-wider flex items-center gap-2">
                      <i className="fa-solid fa-building-columns text-amber-600"></i>
                      <span>Bank Account Details</span>
                    </div>
                    <button
                      type="button"
                      onClick={() => handleInputChange('show_bank_details', editForm.show_bank_details === false ? true : false)}
                      className={`flex items-center gap-2 px-3 py-1 rounded-xl font-black text-xs transition-all shadow-2xs cursor-pointer border ${editForm.show_bank_details !== false
                        ? 'bg-emerald-600 text-white border-emerald-700 hover:bg-emerald-700'
                        : 'bg-slate-200 text-slate-700 border-slate-300 hover:bg-slate-300'
                        }`}
                    >
                      <i className={`fa-solid ${editForm.show_bank_details !== false ? 'fa-toggle-on text-sm' : 'fa-toggle-off text-sm'}`}></i>
                      <span>{editForm.show_bank_details !== false ? 'Enabled on Document' : 'Disabled on Document'}</span>
                    </button>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
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
                  </div>
                </div>
              </div>

              {/* 6. IMPORTANT NOTE TEXT (பின்குறிப்பு) */}
              <div className="bg-white/80 border border-amber-200 rounded-2xl p-4.5 space-y-3 shadow-2xs">
                <div className="flex items-center gap-2 text-xs font-black text-amber-900 uppercase tracking-wider border-b border-amber-100 pb-2">
                  <i className="fa-solid fa-note-sticky text-amber-600"></i>
                  <span>Important Note Text (பின்குறிப்பு)</span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Note Paragraph 1</label>
                    <textarea
                      rows={4}
                      value={editForm.important_note_1}
                      onChange={(e) => handleInputChange('important_note_1', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-semibold text-xs focus:outline-none focus:ring-2 focus:ring-amber-500"
                    ></textarea>
                  </div>
                  <div>
                    <label className="block text-slate-700 font-extrabold mb-1">Note Paragraph 2</label>
                    <textarea
                      rows={4}
                      value={editForm.important_note_2}
                      onChange={(e) => handleInputChange('important_note_2', e.target.value)}
                      className="w-full bg-white border border-amber-300 rounded-xl px-3 py-2 text-slate-900 font-semibold text-xs focus:outline-none focus:ring-2 focus:ring-amber-500"
                    ></textarea>
                  </div>
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

              {/* 5. Footer Position & Enable Toggle */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-slate-700 font-extrabold text-[11px]">
                    Footer Section
                  </label>
                  <button
                    type="button"
                    onClick={() => handleInputChange('show_footer', editForm.show_footer === false ? true : false)}
                    className={`text-[10px] font-black px-2 py-0.5 rounded transition-all cursor-pointer ${
                      editForm.show_footer !== false && editForm.footer_position !== 'disabled'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-slate-200 text-slate-700'
                    }`}
                  >
                    {editForm.show_footer !== false && editForm.footer_position !== 'disabled' ? 'ON' : 'OFF'}
                  </button>
                </div>
                <select
                  value={editForm.show_footer === false ? 'disabled' : (editForm.footer_position || 'below_table')}
                  onChange={(e) => {
                    if (e.target.value === 'disabled') {
                      handleInputChange('show_footer', false);
                      handleInputChange('footer_position', 'disabled');
                    } else {
                      handleInputChange('show_footer', true);
                      handleInputChange('footer_position', e.target.value);
                    }
                  }}
                  className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer h-[42px] transition-all shadow-2xs"
                >
                  <option value="below_table">📍 Below Table</option>
                  <option value="new_page">📄 New Page</option>
                  <option value="disabled">🚫 Disabled / Hidden</option>
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
                      checked={editForm.strikethrough_mrp !== false}
                      onChange={(e) => handleInputChange('strikethrough_mrp', e.target.checked)}
                      className="rounded text-amber-600 focus:ring-amber-500 w-3.5 h-3.5 cursor-pointer"
                    />
                    <span className="text-slate-800 font-medium">Cross Rate (MRP)</span>
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
          {!isSimplerLayout && (
            <div className="w-full max-w-[210mm] print:w-[210mm]">


            <div
              id="a4-page-1-container"
              className={`a4-page-sheet w-[210mm] h-[297mm] max-h-[297mm] overflow-hidden ${editForm.store_cover_bg === 'none' ? 'text-slate-900 bg-white' : 'text-white'} transition-all duration-300 relative shadow-2xl flex flex-col justify-between p-6 sm:p-8 pb-4 select-none mx-auto break-after-page bg-cover bg-center bg-no-repeat box-border`}
              style={{ backgroundImage: editForm.store_cover_bg === 'none' ? 'none' : `url(${editForm.store_cover_bg ? getImageUrl(editForm.store_cover_bg) : '/images/cover_bg.jpg'})`, pageBreakAfter: 'always' }}
            >
              {/* Custom Draggable Floating Images overlay on Page 1 (Slots 1 to 5) */}
              {[1, 2, 3, 4, 5].map((i) => {
                const props = getFloatImgProps(i);
                if (!props.image || !props.show) return null;
                return (
                  <div
                    key={i}
                    onMouseDown={(e) => handleFloatImgMouseDown(i, e)}
                    onTouchStart={(e) => handleFloatImgMouseDown(i, e)}
                    className="absolute z-40 group cursor-grab active:cursor-grabbing border-2 border-transparent hover:border-sky-400 hover:border-dashed rounded-xl p-1 transition-all select-none"
                    style={{
                      left: `${props.x}%`,
                      top: `${props.y}%`,
                      transform: `scale(${props.scale / 100})`,
                      transformOrigin: 'top left',
                    }}
                  >
                    <img
                      src={getImageUrl(props.image)}
                      alt={`Custom Floating Image ${i}`}
                      className="max-w-[300px] max-h-[300px] object-contain drop-shadow-2xl pointer-events-none"
                    />
                    {/* Position badge / Drag Move Indicator */}
                    <div className="absolute -top-7 left-0 bg-slate-950/90 text-amber-300 font-black text-[10px] px-2 py-0.5 rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5 whitespace-nowrap print:hidden pointer-events-none">
                      <i className="fa-solid fa-arrows-up-down-left-right text-sky-400"></i>
                      <span>Image #{i} (X: {props.x}%, Y: {props.y}%) • Size: {props.scale}%</span>
                    </div>

                    {/* Corner Resize Handle */}
                    <div
                      onMouseDown={(e) => handleFloatImgResizeMouseDown(i, e)}
                      onTouchStart={(e) => handleFloatImgResizeMouseDown(i, e)}
                      className="absolute -bottom-2 -right-2 w-6 h-6 bg-sky-500 hover:bg-sky-600 active:scale-125 rounded-full border-2 border-white cursor-nwse-resize shadow-xl z-50 flex items-center justify-center text-[10px] text-white print:hidden transition-transform"
                      title="Drag corner to resize image size like Canva"
                    >
                      <i className="fa-solid fa-up-right-and-down-left-from-center pointer-events-none"></i>
                    </div>
                  </div>
                );
              })}

              {/* Dynamic Cover Text Styling */}
              {(() => {
                const strokeC = editForm.text_stroke_color || '#000000';
                const deityStrokeC = editForm.deity_stroke_color !== undefined ? editForm.deity_stroke_color : '#FFFFFF';
                const deityFilterStyle = deityStrokeC === 'transparent' || !deityStrokeC
                  ? 'drop-shadow(0 12px 24px rgba(0, 0, 0, 0.6))'
                  : `drop-shadow(-2px -2px 0 ${deityStrokeC}) drop-shadow(2px -2px 0 ${deityStrokeC}) drop-shadow(-2px 2px 0 ${deityStrokeC}) drop-shadow(2px 2px 0 ${deityStrokeC}) drop-shadow(0 12px 24px rgba(0, 0, 0, 0.6))`;

                const titleStyle = {
                  fontSize: '2.8rem',
                  letterSpacing: '0.15em',
                  lineHeight: '1.35',
                  fontFamily: getStoreNameFontFamily(),
                  color: editForm.store_title_color || undefined,
                  textShadow: `-2px -2px 0 ${strokeC}, 2px -2px 0 ${strokeC}, -2px 2px 0 ${strokeC}, 2px 2px 0 ${strokeC}, 0 4px 8px rgba(0,0,0,0.5)`
                };

                const invocationStyle = {
                  color: editForm.store_invocation_color || undefined,
                  textShadow: `-1.5px -1.5px 0 ${strokeC}, 1.5px -1.5px 0 ${strokeC}, -1.5px 1.5px 0 ${strokeC}, 1.5px 1.5px 0 ${strokeC}, 0 2px 4px rgba(0,0,0,0.5)`
                };

                const taglineStyle = {
                  color: editForm.store_tagline_color || undefined,
                  textShadow: `-1.5px -1.5px 0 ${strokeC}, 1.5px -1.5px 0 ${strokeC}, -1.5px 1.5px 0 ${strokeC}, 1.5px 1.5px 0 ${strokeC}, 0 2px 4px rgba(0,0,0,0.5)`
                };

                return (
                  <>
                    {/* Top Invocation Header Section */}
                    <div className="relative text-center z-10 mt-0 flex flex-col items-center justify-center gap-0.5 font-extrabold text-xs sm:text-sm tracking-wide" style={invocationStyle}>
                      {editForm.store_invocation_symbol && (
                        <div className="font-black text-sm sm:text-base leading-none">{editForm.store_invocation_symbol}</div>
                      )}
                      {editForm.store_invocation && (
                        <div className="leading-tight">{editForm.store_invocation}</div>
                      )}
                    </div>

                    {/* Main Brand & Logo Motif Center Section */}
                    <div className="relative z-10 text-center space-y-6 mt-6 mb-2 flex justify-center">
                      <div
                        onMouseDown={handleShopInfoMouseDown}
                        onTouchStart={handleShopInfoMouseDown}
                        className="relative group cursor-grab active:cursor-grabbing border-2 border-transparent hover:border-sky-400 hover:border-dashed rounded-xl p-2 transition-all select-none inline-block"
                        style={{
                          transform: `translate(${editForm.shop_info_x || 0}px, ${editForm.shop_info_y || 0}px) scale(${(editForm.shop_info_scale || 100) / 100})`,
                          transformOrigin: 'center center',
                        }}
                      >
                        {/* Brand Title & Tagline */}
                        <div className="flex flex-col items-center space-y-4 sm:space-y-5">
                          <h1
                            className="font-black text-white uppercase relative z-10"
                            style={titleStyle}
                          >
                            {editForm.store_name}
                          </h1>
                          <p
                            className="text-xl sm:text-2xl font-bold tracking-wide pt-1 pb-1"
                            style={taglineStyle}
                          >
                            "{editForm.store_tagline}"
                          </p>
                          <div
                            className="inline-block text-slate-950 font-black text-2xl sm:text-3xl uppercase tracking-wider pt-2"
                            style={{ textShadow: '-2px -2px 0 #ffffff, 2px -2px 0 #ffffff, -2px 2px 0 #ffffff, 2px 2px 0 #ffffff, 0 4px 8px rgba(0,0,0,0.4)', color: editForm.store_badge_color || undefined }}
                          >
                            PRICE LIST - {editForm.store_year}
                          </div>
                        </div>

                        {/* Drag Move Tooltip Badge */}
                        <div className="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-950/90 text-amber-300 font-black text-[10px] px-2 py-0.5 rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5 whitespace-nowrap print:hidden pointer-events-none z-50">
                          <i className="fa-solid fa-arrows-up-down-left-right text-sky-400"></i>
                          <span>Shop Name (X: {editForm.shop_info_x || 0}px, Y: {editForm.shop_info_y || 0}px)</span>
                        </div>

                        {/* Canva Corner Drag Resize Handle */}
                        <div
                          onMouseDown={(e) => handleElementResizeStart('shop_info', editForm.shop_info_scale || 100, e)}
                          onTouchStart={(e) => handleElementResizeStart('shop_info', editForm.shop_info_scale || 100, e)}
                          className="absolute -bottom-1.5 -right-1.5 w-5 h-5 bg-sky-500 hover:bg-sky-600 active:scale-125 rounded-full border-2 border-white cursor-nwse-resize shadow-xl z-50 flex items-center justify-center text-[9px] text-white print:hidden transition-transform"
                          title="Drag corner to resize Shop Name & Address"
                        >
                          <i className="fa-solid fa-up-right-and-down-left-from-center pointer-events-none"></i>
                        </div>
                      </div>
                    </div>

                    {/* Center Cover Image Section */}
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
                            className="max-h-[540px] sm:max-h-[630px] w-auto object-contain relative z-10 pointer-events-auto"
                            style={{
                              filter: deityFilterStyle
                            }}
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
                  </>
                );
              })()}

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
          )}

          {/* A4 PRODUCT REGISTRY PAGES (210mm x 297mm) */}
          {productPageChunks.map((chunkProducts, chunkIdx) => {
            const docPageIndex = isSimplerLayout ? chunkIdx + 1 : chunkIdx + 2;

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
                  id={(isSimplerLayout && chunkIdx === 0) ? "a4-page-1-container" : undefined}
                  className={`a4-page-sheet w-[210mm] h-[297mm] max-h-[297mm] overflow-hidden text-slate-900 transition-all duration-300 relative shadow-2xl flex flex-col justify-between p-4 sm:p-5 select-none mx-auto break-after-page bg-cover bg-center bg-no-repeat box-border`}
                  style={{ backgroundImage: editForm.store_cover_bg === 'none' ? 'none' : `url(${editForm.store_cover_bg ? getImageUrl(editForm.store_cover_bg) : '/images/cover_bg.jpg'})`, pageBreakAfter: 'always' }}
                >
                  {/* Custom Draggable Floating Images overlay on Page 1 for Simpler Layout (Slots 1 to 5) */}
                  {isSimplerLayout && chunkIdx === 0 && (
                    <>
                      {[1, 2, 3, 4, 5].map((i) => {
                        const props = getFloatImgProps(i);
                        if (!props.image || !props.show) return null;
                        return (
                          <div
                            key={i}
                            onMouseDown={(e) => handleFloatImgMouseDown(i, e)}
                            onTouchStart={(e) => handleFloatImgMouseDown(i, e)}
                            className="absolute z-40 group cursor-grab active:cursor-grabbing border-2 border-transparent hover:border-sky-400 hover:border-dashed rounded-xl p-1 transition-all select-none"
                            style={{
                              left: `${props.x}%`,
                              top: `${props.y}%`,
                              transform: `scale(${props.scale / 100})`,
                              transformOrigin: 'top left',
                            }}
                          >
                            <img
                              src={getImageUrl(props.image)}
                              alt={`Custom Floating Image ${i}`}
                              className="max-w-[300px] max-h-[300px] object-contain drop-shadow-2xl pointer-events-none"
                            />
                            {/* Position badge / Drag Move Indicator */}
                            <div className="absolute -top-7 left-0 bg-slate-950/90 text-amber-300 font-black text-[10px] px-2 py-0.5 rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5 whitespace-nowrap print:hidden pointer-events-none">
                              <i className="fa-solid fa-arrows-up-down-left-right text-sky-400"></i>
                              <span>Image #{i} (X: {props.x}%, Y: {props.y}%) • Size: {props.scale}%</span>
                            </div>

                            {/* Corner Resize Handle */}
                            <div
                              onMouseDown={(e) => handleFloatImgResizeMouseDown(i, e)}
                              onTouchStart={(e) => handleFloatImgResizeMouseDown(i, e)}
                              className="absolute -bottom-2 -right-2 w-6 h-6 bg-sky-500 hover:bg-sky-600 active:scale-125 rounded-full border-2 border-white cursor-nwse-resize shadow-xl z-50 flex items-center justify-center text-[10px] text-white print:hidden transition-transform"
                              title="Drag corner to resize image size like Canva"
                            >
                              <i className="fa-solid fa-up-right-and-down-left-from-center"></i>
                            </div>
                          </div>
                        );
                      })}

                      {/* Interactive Movable & Resizable Offer / Discount Badge Overlay */}
                      {editForm.show_discount_badge !== false && (
                        <div
                          onMouseDown={handleDiscountBadgeMouseDown}
                          onTouchStart={handleDiscountBadgeMouseDown}
                          className="absolute z-40 group cursor-grab active:cursor-grabbing border-2 border-transparent hover:border-sky-400 hover:border-dashed rounded-full p-0.5 transition-all select-none"
                          style={{
                            left: `${editForm.discount_badge_x !== undefined ? editForm.discount_badge_x : 82}%`,
                            top: `${editForm.discount_badge_y !== undefined ? editForm.discount_badge_y : 2.2}%`,
                            transform: `scale(${(editForm.discount_badge_scale || editForm.discount_scale || 100) / 100})`,
                            transformOrigin: 'top left',
                          }}
                        >
                          <div className="flex flex-col items-center justify-center text-center text-emerald-900 shrink-0 px-1 py-0.5">
                            <span className="text-sm sm:text-base font-black leading-none">{editForm.discount_percent || 50}%</span>
                            <span className="text-[8.5px] sm:text-[9.5px] font-extrabold uppercase tracking-tighter">OFF</span>
                          </div>

                          {/* Drag Move Tooltip Badge */}
                          <div className="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-950/90 text-amber-300 font-black text-[10px] px-2 py-0.5 rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5 whitespace-nowrap print:hidden pointer-events-none z-50">
                            <i className="fa-solid fa-arrows-up-down-left-right text-sky-400"></i>
                            <span>Offer Badge (X: {editForm.discount_badge_x ?? 82}%, Y: {editForm.discount_badge_y ?? 2.2}%)</span>
                          </div>

                          {/* Canva Corner Drag Resize Handle */}
                          <div
                            onMouseDown={handleDiscountBadgeResizeMouseDown}
                            onTouchStart={handleDiscountBadgeResizeMouseDown}
                            className="absolute -bottom-1 -right-1 w-5 h-5 bg-sky-500 hover:bg-sky-600 active:scale-125 rounded-full border-2 border-white cursor-nwse-resize shadow-xl z-50 flex items-center justify-center text-[9px] text-white print:hidden transition-transform"
                            title="Drag corner to resize Offer Badge"
                          >
                            <i className="fa-solid fa-up-right-and-down-left-from-center pointer-events-none"></i>
                          </div>
                        </div>
                      )}
                    </>
                  )}
                  {/* Simpler Header Box (Traditional Sivakasi Printed Layout - Mahalakshmi Traders Style) */}
                  {isSimplerLayout && chunkIdx === 0 && (
                    <div className="w-full border-4 border-double border-emerald-800 rounded-xl p-2.5 sm:p-3 mb-3 shadow-xs shrink-0 text-emerald-950 bg-white">
                      {/* Top Row: GSTIN, Invocation, Phone */}
                      <div className="flex flex-wrap justify-between items-center text-[10px] sm:text-[11px] font-extrabold border-b border-emerald-800/40 pb-1 mb-2 gap-1 text-emerald-900">
                        <div>GSTIN No: {editForm.gstin || '33ABLFM8150D1ZD'}</div>
                        <div className="text-center font-black flex flex-col items-center leading-tight">
                          {editForm.store_invocation_symbol && (
                            <span className="text-xs leading-none">{editForm.store_invocation_symbol}</span>
                          )}
                          <span>{editForm.store_invocation || 'Sri Sena Kasava Perumal Thunai'}</span>
                        </div>
                        <div>Call: {[editForm.store_phone, editForm.store_phone_2].filter(Boolean).join(', ')}</div>
                      </div>

                      {/* Main Header Content Grid */}
                      <div className="grid grid-cols-12 items-center gap-2">
                        {/* Left Column: Deity */}
                        <div className="col-span-2 flex items-center justify-start">
                          {getDeityImageUrl() ? (
                            <div className="relative group/deity inline-block shrink-0">
                              <img
                                src={getDeityImageUrl()}
                                alt="Deity"
                                className="object-contain drop-shadow transition-all duration-75"
                                style={{
                                  height: `${Math.round(56 * ((editForm.simpler_deity_scale || editForm.deity_scale || 100) / 100))}px`,
                                  width: 'auto',
                                  maxHeight: '130px',
                                }}
                              />
                              {/* Canva Corner Drag Resize Handle */}
                              <div
                                onMouseDown={(e) => handleElementResizeStart('simpler_deity', editForm.simpler_deity_scale || editForm.deity_scale || 100, e)}
                                className="absolute -bottom-1 -right-1 w-4.5 h-4.5 bg-emerald-600 hover:bg-emerald-700 rounded-full border-2 border-white cursor-se-resize shadow-md opacity-0 group-hover/deity:opacity-100 z-30 transition-opacity print:hidden flex items-center justify-center text-[7.5px] text-white"
                                title="Drag corner to resize God Image"
                              >
                                <i className="fa-solid fa-up-right-and-down-left-from-center"></i>
                              </div>
                            </div>
                          ) : (
                            <div className="h-12 w-12 rounded-full border-2 border-emerald-800 bg-emerald-50 flex items-center justify-center font-black text-xs text-emerald-800">
                              🛕
                            </div>
                          )}
                        </div>

                        {/* Center Column: Movable & Resizable Shop Name, Address, Email & Tagline */}
                        <div className="col-span-8 flex justify-center items-center">
                          <div
                            onMouseDown={handleShopInfoMouseDown}
                            onTouchStart={handleShopInfoMouseDown}
                            className="relative group cursor-grab active:cursor-grabbing border-2 border-transparent hover:border-sky-400 hover:border-dashed rounded-lg p-1 transition-all select-none text-center space-y-0.5"
                            style={{
                              transform: `translate(${editForm.shop_info_x || 0}px, ${editForm.shop_info_y || 0}px) scale(${(editForm.shop_info_scale || 100) / 100})`,
                              transformOrigin: 'center center',
                            }}
                          >
                            <h1
                              className="text-lg sm:text-2xl font-black uppercase tracking-tight leading-tight text-emerald-950"
                              style={{ fontFamily: getStoreNameFontFamily() }}
                            >
                              {editForm.store_name || 'MASS CRACKERS'}
                            </h1>
                            <p className="text-[9.5px] sm:text-[10.5px] font-extrabold text-slate-800 leading-tight">
                              {editForm.store_address}
                            </p>
                            {editForm.store_email && (
                              <p className="text-[9px] font-bold text-emerald-800">Email: {editForm.store_email}</p>
                            )}
                            <p className="text-[9px] font-extrabold text-emerald-900 italic pt-0.5">
                              {editForm.store_sub_header_tag || '(ALL Types of Crackers available Whole Sales & Retail)'}
                            </p>

                            {/* Drag Move Tooltip Badge */}
                            <div className="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-950/90 text-amber-300 font-black text-[10px] px-2 py-0.5 rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5 whitespace-nowrap print:hidden pointer-events-none z-50">
                              <i className="fa-solid fa-arrows-up-down-left-right text-sky-400"></i>
                              <span>Shop Info (X: {editForm.shop_info_x || 0}px, Y: {editForm.shop_info_y || 0}px)</span>
                            </div>

                            {/* Canva Corner Drag Resize Handle */}
                            <div
                              onMouseDown={(e) => handleElementResizeStart('shop_info', editForm.shop_info_scale || 100, e)}
                              onTouchStart={(e) => handleElementResizeStart('shop_info', editForm.shop_info_scale || 100, e)}
                              className="absolute -bottom-1 -right-1 w-5 h-5 bg-sky-500 hover:bg-sky-600 active:scale-125 rounded-full border-2 border-white cursor-nwse-resize shadow-xl z-50 flex items-center justify-center text-[9px] text-white print:hidden transition-transform"
                              title="Drag corner to resize Shop Name & Address"
                            >
                              <i className="fa-solid fa-up-right-and-down-left-from-center pointer-events-none"></i>
                            </div>
                          </div>
                        </div>

                        {/* Right Column: Reserved space for movable Discount Badge */}
                        <div className="col-span-2 flex items-center justify-end">
                          <div className="w-12 h-12 sm:w-14 sm:h-14 opacity-0 pointer-events-none"></div>
                        </div>
                      </div>
                    </div>
                  )}

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
                            {showSno && <col style={{ width: getColPctWidth('sno') }} />}
                            {showProduct && <col style={{ width: getColPctWidth('product') }} />}
                            {showTamilName && <col style={{ width: getColPctWidth('product_ta') }} />}
                            {showUnit && <col style={{ width: getColPctWidth('unit') }} />}
                            {showMrpCol && <col style={{ width: getColPctWidth('mrp') }} />}
                            {showOffer && <col style={{ width: getColPctWidth('offer') }} />}
                            {showReq && <col style={{ width: getColPctWidth('req') }} />}
                          </colgroup>
                          <thead>
                            <tr className={`${theme.tableHeader} font-black text-black uppercase tracking-wider text-[11px] min-h-[34px]`}>
                              {/* S.No Header */}
                              {showSno && (
                                <th
                                  className="py-0.5 text-center border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: getColPctWidth('sno'),
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
                                  {lastActiveCol !== 'sno' && (
                                    <div
                                      className="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize hover:bg-amber-600/80 active:bg-amber-700 z-30 transition-colors print:hidden flex items-center justify-center"
                                      onMouseDown={(e) => handleColumnResizeStart('sno', e)}
                                      onTouchStart={(e) => handleColumnResizeStart('sno', e)}
                                      title="Drag to resize S.No column"
                                    >
                                      <div className="w-0.5 h-3 bg-amber-800/40 rounded-full" />
                                    </div>
                                  )}
                                </th>
                              )}

                              {/* Product Header (English) */}
                              {showProduct && (
                                <th
                                  className="py-0.5 border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: getColPctWidth('product'),
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
                                  {lastActiveCol !== 'product' && (
                                    <div
                                      className="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize hover:bg-amber-600/80 active:bg-amber-700 z-30 transition-colors print:hidden flex items-center justify-center"
                                      onMouseDown={(e) => handleColumnResizeStart('product', e)}
                                      onTouchStart={(e) => handleColumnResizeStart('product', e)}
                                      title="Drag to resize Product column"
                                    >
                                      <div className="w-0.5 h-3 bg-amber-800/40 rounded-full" />
                                    </div>
                                  )}
                                </th>
                              )}

                              {/* Tamil Product Header */}
                              {showTamilName && (
                                <th
                                  className="py-0.5 border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: getColPctWidth('product_ta'),
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
                                  {lastActiveCol !== 'product_ta' && (
                                    <div
                                      className="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize hover:bg-amber-600/80 active:bg-amber-700 z-30 transition-colors print:hidden flex items-center justify-center"
                                      onMouseDown={(e) => handleColumnResizeStart('product_ta', e)}
                                      onTouchStart={(e) => handleColumnResizeStart('product_ta', e)}
                                      title="Drag to resize Tamil Product column"
                                    >
                                      <div className="w-0.5 h-3 bg-amber-800/40 rounded-full" />
                                    </div>
                                  )}
                                </th>
                              )}

                              {/* Unit Header */}
                              {showUnit && (
                                <th
                                  className="py-0.5 text-center border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: getColPctWidth('unit'),
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
                                  {lastActiveCol !== 'unit' && (
                                    <div
                                      className="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize hover:bg-amber-600/80 active:bg-amber-700 z-30 transition-colors print:hidden flex items-center justify-center"
                                      onMouseDown={(e) => handleColumnResizeStart('unit', e)}
                                      onTouchStart={(e) => handleColumnResizeStart('unit', e)}
                                      title="Drag to resize Unit column"
                                    >
                                      <div className="w-0.5 h-3 bg-amber-800/40 rounded-full" />
                                    </div>
                                  )}
                                </th>
                              )}

                              {/* Rate (MRP) Header */}
                              {showMrpCol && (
                                <th
                                  className="py-0.5 text-right border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: getColPctWidth('mrp'),
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
                                  {lastActiveCol !== 'mrp' && (
                                    <div
                                      className="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize hover:bg-amber-600/80 active:bg-amber-700 z-30 transition-colors print:hidden flex items-center justify-center"
                                      onMouseDown={(e) => handleColumnResizeStart('mrp', e)}
                                      onTouchStart={(e) => handleColumnResizeStart('mrp', e)}
                                      title="Drag to resize Rate column"
                                    >
                                      <div className="w-0.5 h-3 bg-amber-800/40 rounded-full" />
                                    </div>
                                  )}
                                </th>
                              )}

                              {/* Offer Rate Header */}
                              {showOffer && (
                                <th
                                  className="py-0.5 text-right border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: getColPctWidth('offer'),
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
                                  {lastActiveCol !== 'offer' && (
                                    <div
                                      className="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize hover:bg-amber-600/80 active:bg-amber-700 z-30 transition-colors print:hidden flex items-center justify-center"
                                      onMouseDown={(e) => handleColumnResizeStart('offer', e)}
                                      onTouchStart={(e) => handleColumnResizeStart('offer', e)}
                                      title="Drag to resize Offer Rate column"
                                    >
                                      <div className="w-0.5 h-3 bg-amber-800/40 rounded-full" />
                                    </div>
                                  )}
                                </th>
                              )}

                              {/* Req Header */}
                              {showReq && (
                                <th
                                  className="py-0.5 text-center border border-slate-400 relative select-none group p-0 align-middle"
                                  style={{
                                    width: getColPctWidth('req'),
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
                                  <div
                                    className="absolute right-0 top-0 bottom-0 w-3 cursor-col-resize hover:bg-amber-600/80 active:bg-amber-700 z-30 transition-colors print:hidden flex items-center justify-center"
                                    onMouseDown={(e) => handleColumnResizeStart('req', e)}
                                    onTouchStart={(e) => handleColumnResizeStart('req', e)}
                                    title="Drag to resize REQ column"
                                  >
                                    <div className="w-0.5 h-3 bg-amber-800/40 rounded-full" />
                                  </div>
                                </th>
                              )}
                            </tr>
                          </thead>
                          <tbody className="font-bold text-slate-900 text-[11px]">
                            {chunkCategories.map((category) => (
                              <React.Fragment key={category.id}>
                                {/* Category Header Bar */}
                                <tr className={`${theme.categoryBar} h-[24px]`}>
                                  <td colSpan={activeColCount || 1} className="py-0.5 text-center text-[11px] font-black tracking-wider uppercase border border-slate-400 p-0 relative group/cat" style={{ paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                    <input
                                      type="text"
                                      value={category.name}
                                      onChange={(e) => handleInlineCategoryChange(category.id, e.target.value)}
                                      onBlur={(e) => handleInlineCategorySave(category.id, e.target.value)}
                                      onFocus={(e) => e.target.select()}
                                      title="Click to edit category name inline like Excel"
                                      className="w-full bg-transparent border-0 text-center text-[11px] font-black tracking-wider uppercase focus:bg-amber-100/90 focus:ring-2 focus:ring-amber-500 rounded px-1 cursor-text hover:bg-black/5 transition-colors focus:outline-none"
                                    />
                                    {/* Category Actions: Add Row (End) */}
                                    <div className="absolute right-1 top-1/2 -translate-y-1/2 hidden group-hover/cat:flex items-center gap-1 z-30 print:hidden">
                                      <button
                                        type="button"
                                        onClick={() => handleAddRowAtCategoryEnd(category.id)}
                                        className="px-2 py-0.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded text-[9.5px] font-extrabold shadow-xs flex items-center gap-1 transition-all active:scale-95 cursor-pointer"
                                        title="Add new row at the end of this category"
                                      >
                                        <i className="fa-solid fa-plus text-[8.5px]"></i> Add Row (End)
                                      </button>
                                    </div>
                                  </td>
                                </tr>

                                {/* Category Product Rows */}
                                {category.products.map((product, idx) => {
                                  const absoluteIndex = allFilteredProducts.findIndex((p) => p.id === product.id);
                                  const currentSno = absoluteIndex !== -1 ? absoluteIndex + 1 : (chunkIdx * pageSize) + idx + 1;
                                  const isDragOver = dragOverRowId === product.id;

                                  return (
                                    <tr
                                      key={product.id}
                                      draggable={!isPdfMode}
                                      onDragStart={(e) => handleRowDragStart(e, category.id, product.id)}
                                      onDragOver={(e) => handleRowDragOver(e, category.id, product.id)}
                                      onDragLeave={(e) => handleRowDragLeave(e, product.id)}
                                      onDrop={(e) => handleRowDrop(e, category.id, product.id)}
                                      className={`hover:bg-amber-50/50 transition-all text-black font-extrabold group/row relative ${
                                        isDragOver ? 'bg-emerald-100/90 outline-2 outline-emerald-500 z-30 shadow-md' : ''
                                      }`}
                                      style={{ height: `${editForm.table_row_height || 22}px` }}
                                    >
                                      {/* S.No / Code Cell */}
                                      {showSno && (
                                        <td className="py-0 text-center text-black font-extrabold border border-slate-400 text-[11px] p-0 relative group/cell" style={{ width: getColPctWidth('sno'), paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
                                          {/* Direct Inline + Insert Row Button (Print Hidden) */}
                                          <button
                                            type="button"
                                            onClick={() => handleInsertRowInBetween(category.id, product.id, 'below')}
                                            className="absolute left-0.5 top-1/2 -translate-y-1/2 hidden group-hover/row:flex items-center justify-center w-4 h-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-md z-30 print:hidden text-[9px] font-black cursor-pointer transition-transform active:scale-95"
                                            title="Insert New Row Below Here"
                                          >
                                            <i className="fa-solid fa-plus text-[7.5px]"></i>
                                          </button>

                                          {/* Floating Row Hover Controls Bar (Floats over right of row, inside table) */}
                                          <div className="absolute right-2 top-1/2 -translate-y-1/2 hidden group-hover/row:flex items-center gap-1 bg-slate-900/95 text-white px-2 py-1 rounded-lg shadow-2xl z-50 print:hidden transition-all duration-150 scale-95 hover:scale-100 whitespace-nowrap border border-slate-700">
                                            {/* Drag Handle */}
                                            <span className="px-1 text-slate-400 cursor-grab active:cursor-grabbing hover:text-white" title="Click & Drag row to reorder anywhere">
                                              <i className="fa-solid fa-grip-vertical text-[10px]"></i>
                                            </span>
                                            {/* Move Up Button */}
                                            <button
                                              type="button"
                                              onClick={() => handleMoveRowUp(category.id, product.id)}
                                              disabled={idx === 0}
                                              className="px-1.5 py-0.5 bg-slate-700 hover:bg-slate-600 disabled:opacity-30 text-white rounded-xs text-[9px] font-bold flex items-center gap-1 cursor-pointer transition-colors shadow-2xs"
                                              title="Move Row Up (Realigns numbers)"
                                            >
                                              <i className="fa-solid fa-chevron-up text-[8px]"></i>
                                            </button>
                                            {/* Move Down Button */}
                                            <button
                                              type="button"
                                              onClick={() => handleMoveRowDown(category.id, product.id)}
                                              disabled={idx === category.products.length - 1}
                                              className="px-1.5 py-0.5 bg-slate-700 hover:bg-slate-600 disabled:opacity-30 text-white rounded-xs text-[9px] font-bold flex items-center gap-1 cursor-pointer transition-colors shadow-2xs"
                                              title="Move Row Down (Realigns numbers)"
                                            >
                                              <i className="fa-solid fa-chevron-down text-[8px]"></i>
                                            </button>
                                            <button
                                              type="button"
                                              onClick={() => handleInsertRowInBetween(category.id, product.id, 'above')}
                                              className="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xs text-[9.5px] font-extrabold flex items-center gap-1 cursor-pointer transition-colors shadow-2xs"
                                              title="Insert New Row Above This Item"
                                            >
                                              <i className="fa-solid fa-arrow-up text-[8.5px]"></i> + Insert Above
                                            </button>
                                            <button
                                              type="button"
                                              onClick={() => handleInsertRowInBetween(category.id, product.id, 'below')}
                                              className="px-2 py-0.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xs text-[9.5px] font-extrabold flex items-center gap-1 cursor-pointer transition-colors shadow-2xs"
                                              title="Insert New Row Below This Item"
                                            >
                                              <i className="fa-solid fa-arrow-down text-[8.5px]"></i> + Insert Below
                                            </button>
                                            <button
                                              type="button"
                                              onClick={() => handleDuplicateRow(category.id, product.id)}
                                              className="px-1.5 py-0.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xs text-[9px] font-bold flex items-center gap-1 cursor-pointer transition-colors shadow-2xs"
                                              title="Duplicate Row"
                                            >
                                              <i className="fa-solid fa-copy text-[8px]"></i> Copy
                                            </button>
                                            <button
                                              type="button"
                                              onClick={() => handleDeleteRow(category.id, product.id)}
                                              className="px-1.5 py-0.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xs text-[9px] font-bold flex items-center gap-1 cursor-pointer transition-colors shadow-2xs"
                                              title="Delete Row"
                                            >
                                              <i className="fa-solid fa-trash-can text-[8px]"></i>
                                            </button>
                                          </div>

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
                                        <td className="py-0 font-extrabold text-black border border-slate-400 leading-tight text-[11px] p-0" style={{ width: getColPctWidth('product'), paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
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
                                        <td className="py-0 font-extrabold text-black border border-slate-400 leading-tight text-[11px] p-0" style={{ width: getColPctWidth('product_ta'), paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
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
                                        <td className="py-0 text-center text-black border border-slate-400 font-extrabold text-[10.5px] leading-tight p-0" style={{ width: getColPctWidth('unit'), paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
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
                                        <td className="py-0 text-right text-black font-extrabold border border-slate-400 text-[11px] p-0" style={{ width: getColPctWidth('mrp'), paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
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
                                            className={`w-full bg-transparent border-0 text-right font-extrabold text-[11px] focus:bg-emerald-50 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 focus:z-20 rounded-xs px-1 cursor-text hover:bg-amber-50/50 transition-all focus:outline-none focus:shadow-md ${editForm.strikethrough_mrp !== false ? 'line-through text-slate-500' : 'text-black'}`}
                                          />
                                        </td>
                                      )}

                                      {/* Offer Rate Cell */}
                                      {showOffer && (
                                        <td className="py-0 text-right font-extrabold text-black border border-slate-400 text-[11px] p-0" style={{ width: getColPctWidth('offer'), paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
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
                                        <td className="py-0 text-center font-extrabold text-black border border-slate-400 text-[11px] p-0" style={{ width: getColPctWidth('req'), paddingLeft: `${editForm.table_col_padding || 4}px`, paddingRight: `${editForm.table_col_padding || 4}px` }}>
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
                    {isFooterEnabled && (editForm.footer_position || 'below_table') === 'below_table' && chunkIdx === productPageChunks.length - 1 && (() => {
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
          {isFooterEnabled && editForm.footer_position === 'new_page' && (
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
                    onClick={promptCreateNewProject}
                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all shadow-xs cursor-pointer"
                    title="Create a new price list project"
                  >
                    <i className="fa-solid fa-folder-plus text-emerald-200"></i> + Create New Project
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
                    <div className="mt-4 flex items-center justify-center gap-3">
                      <button
                        type="button"
                        onClick={promptCreateNewProject}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-4 py-2 rounded-xl text-xs inline-flex items-center gap-2 shadow-xs transition-all cursor-pointer"
                      >
                        <i className="fa-solid fa-folder-plus text-emerald-200"></i> + Create New Project
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setNewProjectNameInput(editForm.store_name || 'My Price List Project');
                          setShowProjectsModal(false);
                          setShowSaveAsModal(true);
                        }}
                        className="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-4 py-2 rounded-xl text-xs inline-flex items-center gap-2 shadow-xs transition-all cursor-pointer"
                      >
                        <i className="fa-solid fa-floppy-disk"></i> Save Current Project Now
                      </button>
                    </div>
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
