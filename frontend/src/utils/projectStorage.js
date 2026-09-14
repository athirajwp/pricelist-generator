/**
 * IndexedDB Storage Helper for Price List Projects
 * Prevents LocalStorage quota errors and data stripping (logos, deity images, QR codes, floating images, categories, etc.)
 */

const DB_NAME = 'PricelistGeneratorDB';
const DB_VERSION = 1;
const STORE_NAME = 'projects';
const LOCAL_STORAGE_KEY = 'pricelist_saved_projects';

// Initialize or open IndexedDB database connection
const openDB = () => {
  return new Promise((resolve, reject) => {
    if (!window.indexedDB) {
      reject(new Error('IndexedDB is not supported in this browser environment.'));
      return;
    }

    const request = window.indexedDB.open(DB_NAME, DB_VERSION);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { keyPath: 'id' });
      }
    };

    request.onsuccess = (event) => {
      resolve(event.target.result);
    };

    request.onerror = (event) => {
      reject(event.target.error || new Error('Failed to open IndexedDB database.'));
    };
  });
};

/**
 * Load all saved projects from IndexedDB.
 * Fallback to localStorage and auto-migrate legacy data if IndexedDB is empty.
 */
export const loadProjectsFromStorage = async () => {
  try {
    const db = await openDB();
    const transaction = db.transaction(STORE_NAME, 'readonly');
    const store = transaction.objectStore(STORE_NAME);
    const getAllRequest = store.getAll();

    const dbProjects = await new Promise((resolve, reject) => {
      getAllRequest.onsuccess = () => resolve(getAllRequest.result || []);
      getAllRequest.onerror = () => reject(getAllRequest.error);
    });

    if (dbProjects && dbProjects.length > 0) {
      // Sort by updatedAt descending (newest first)
      return dbProjects.sort((a, b) => new Date(b.updatedAt || 0) - new Date(a.updatedAt || 0));
    }

    // Fallback: Check localStorage for legacy projects and migrate them to IndexedDB
    const storedLs = localStorage.getItem(LOCAL_STORAGE_KEY);
    if (storedLs) {
      try {
        const parsedLs = JSON.parse(storedLs);
        if (Array.isArray(parsedLs) && parsedLs.length > 0) {
          console.log('Migrating saved projects from LocalStorage to IndexedDB...');
          await saveProjectsToStorage(parsedLs);
          return parsedLs;
        }
      } catch (lsErr) {
        console.error('Error parsing legacy localStorage projects:', lsErr);
      }
    }

    return [];
  } catch (err) {
    console.warn('IndexedDB read failed, falling back to LocalStorage:', err);
    try {
      const storedLs = localStorage.getItem(LOCAL_STORAGE_KEY);
      return storedLs ? JSON.parse(storedLs) : [];
    } catch (lsErr) {
      console.error('LocalStorage load fallback failed:', lsErr);
      return [];
    }
  }
};

/**
 * Save complete list of project snapshots to IndexedDB.
 * Syncs lightweight backup to localStorage without modifying real data.
 */
export const saveProjectsToStorage = async (projectsList) => {
  if (!Array.isArray(projectsList)) return;

  // 1. Primary Save: Store FULL project snapshots in IndexedDB
  try {
    const db = await openDB();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);

    // Clear and write updated list
    await new Promise((resolve, reject) => {
      const clearReq = store.clear();
      clearReq.onsuccess = () => resolve();
      clearReq.onerror = () => reject(clearReq.error);
    });

    for (const project of projectsList) {
      if (project && project.id) {
        store.put(project);
      }
    }

    await new Promise((resolve, reject) => {
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => reject(transaction.error);
    });
  } catch (dbErr) {
    console.error('IndexedDB save failed:', dbErr);
  }

  // 2. Backup Sync: Attempt saving to localStorage (without modifying original objects)
  try {
    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(projectsList));
  } catch (quotaErr) {
    console.warn('LocalStorage full, stripping backup images for LocalStorage index only:', quotaErr);
    try {
      // Strip large base64 images ONLY for localStorage fallback index, keeping IndexedDB intact!
      const compactBackup = projectsList.map((p) => ({
        ...p,
        editForm: p.editForm
          ? {
              ...p.editForm,
              store_logo: (p.editForm.store_logo && p.editForm.store_logo.length > 1000) ? '' : p.editForm.store_logo,
              store_deity_image: (p.editForm.store_deity_image && p.editForm.store_deity_image.length > 1000) ? '' : p.editForm.store_deity_image,
              store_upi_qr: (p.editForm.store_upi_qr && p.editForm.store_upi_qr.length > 1000) ? '' : p.editForm.store_upi_qr,
              store_upi_qr_2: (p.editForm.store_upi_qr_2 && p.editForm.store_upi_qr_2.length > 1000) ? '' : p.editForm.store_upi_qr_2,
              custom_float_image: (p.editForm.custom_float_image && p.editForm.custom_float_image.length > 1000) ? '' : p.editForm.custom_float_image,
            }
          : p.editForm,
      }));
      localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(compactBackup));
    } catch (compactErr) {
      // Ignore localStorage backup failures since IndexedDB holds the primary full data
      console.warn('LocalStorage backup sync skipped (IndexedDB holds primary state).');
    }
  }
};

/**
 * Save or update a single project in IndexedDB.
 */
export const saveSingleProjectToStorage = async (project) => {
  if (!project || !project.id) return;
  try {
    const db = await openDB();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    store.put(project);

    await new Promise((resolve, reject) => {
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => reject(transaction.error);
    });
  } catch (err) {
    console.error('Error saving single project to IndexedDB:', err);
  }
};

/**
 * Delete a project by ID from IndexedDB.
 */
export const deleteProjectFromStorage = async (projectId) => {
  if (!projectId) return;
  try {
    const db = await openDB();
    const transaction = db.transaction(STORE_NAME, 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    store.delete(projectId);

    await new Promise((resolve, reject) => {
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => reject(transaction.error);
    });
  } catch (err) {
    console.error('Error deleting project from IndexedDB:', err);
  }
};
