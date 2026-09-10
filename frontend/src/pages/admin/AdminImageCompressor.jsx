import React, { useState, useRef, useEffect, useMemo } from 'react';
import JSZip from 'jszip';
import AdminLayout from './AdminLayout';

const Swal = window.Swal;

export default function AdminImageCompressor({ noLayout = false }) {
  const [items, setItems] = useState([]);
  const [isCompressing, setIsCompressing] = useState(false);
  const [isZipping, setIsZipping] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFilter, setActiveFilter] = useState('all');
  const [previewItem, setPreviewItem] = useState(null);

  // Compression configuration state
  const [settings, setSettings] = useState({
    format: 'webp', // 'webp' | 'jpeg' | 'png'
    quality: 80, // 10 to 100
    maxWidth: 0, // 0 means original size, else cap width
    maxHeight: 0,
    namingPrefix: '',
    namingSuffix: '_compressed',
    autoCompress: true,
  });

  const fileInputRef = useRef(null);
  const folderInputRef = useRef(null);
  const isCompressingRef = useRef(false);

  // Clean up object URLs when unmounting or clearing items
  useEffect(() => {
    return () => {
      items.forEach((item) => {
        if (item.originalUrl) URL.revokeObjectURL(item.originalUrl);
        if (item.compressedUrl) URL.revokeObjectURL(item.compressedUrl);
      });
    };
  }, []);

  // Format bytes helper
  const formatBytes = (bytes, decimals = 2) => {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
  };

  // Process a single file to extract metadata and prepare item
  const createFileItem = async (file) => {
    const originalUrl = URL.createObjectURL(file);
    let width = 0;
    let height = 0;

    try {
      if ('createImageBitmap' in window) {
        const bmp = await createImageBitmap(file);
        width = bmp.width;
        height = bmp.height;
        bmp.close();
      } else {
        const img = new Image();
        img.src = originalUrl;
        await new Promise((res) => {
          img.onload = () => {
            width = img.width;
            height = img.height;
            res();
          };
          img.onerror = res;
        });
      }
    } catch (e) {
      console.warn('Failed to read image dimensions:', e);
    }

    return {
      id: Math.random().toString(36).substring(2, 11) + Date.now(),
      file,
      name: file.name,
      originalSize: file.size,
      originalWidth: width,
      originalHeight: height,
      originalUrl,
      compressedBlob: null,
      compressedUrl: null,
      compressedSize: null,
      compressedWidth: null,
      compressedHeight: null,
      status: 'queued', // 'queued' | 'processing' | 'done' | 'error'
      errorMsg: null,
      reductionRatio: null,
    };
  };

  // Add files to the state
  const handleFilesAdded = async (fileList) => {
    const rawFiles = Array.from(fileList).filter((f) => f.type.startsWith('image/'));

    if (rawFiles.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Images Found',
        text: 'Please select valid image files (JPG, PNG, WebP, etc.).',
        confirmColor: '#e11d48',
      });
      return;
    }

    const newItems = await Promise.all(rawFiles.map(createFileItem));

    setItems((prev) => [...prev, ...newItems]);

    if (settings.autoCompress) {
      setTimeout(() => {
        startBulkCompression([...newItems]);
      }, 100);
    }
  };

  // Perform canvas compression for a single item
  const compressSingleItem = (item, currentSettings) => {
    return new Promise((resolve) => {
      const img = new Image();
      img.crossOrigin = 'anonymous';

      img.onload = () => {
        let { width, height } = img;
        const maxW = Number(currentSettings.maxWidth);
        const maxH = Number(currentSettings.maxHeight);

        // Calculate resize dimensions while maintaining aspect ratio
        if (maxW > 0 || maxH > 0) {
          let ratio = width / height;
          if (maxW > 0 && width > maxW) {
            width = maxW;
            height = Math.round(width / ratio);
          }
          if (maxH > 0 && height > maxH) {
            height = maxH;
            width = Math.round(height * ratio);
          }
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        // High quality rendering
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';

        // Draw image
        ctx.drawImage(img, 0, 0, width, height);

        let mimeType = 'image/webp';
        if (currentSettings.format === 'jpeg') mimeType = 'image/jpeg';
        else if (currentSettings.format === 'png') mimeType = 'image/png';

        const qFactor = Number(currentSettings.quality);

        // Advanced PNG Color Depth Quantization
        // HTML5 canvas native toBlob('image/png') ignores quality setting by default.
        // We perform pixel color quantization to enable DEFLATE compression for PNGs.
        if (currentSettings.format === 'png' && qFactor < 100) {
          try {
            const imageData = ctx.getImageData(0, 0, width, height);
            const data = imageData.data;
            const step = Math.max(1, Math.round((100 - qFactor) * 0.35));

            if (step > 1) {
              for (let i = 0; i < data.length; i += 4) {
                // Quantize R, G, B channels
                data[i] = Math.min(255, Math.round(data[i] / step) * step);
                data[i + 1] = Math.min(255, Math.round(data[i + 1] / step) * step);
                data[i + 2] = Math.min(255, Math.round(data[i + 2] / step) * step);

                // Quantize alpha channel for transparent PNGs
                if (data[i + 3] > 0 && data[i + 3] < 255) {
                  data[i + 3] = Math.min(255, Math.round(data[i + 3] / 8) * 8);
                }
              }
              ctx.putImageData(imageData, 0, 0);
            }
          } catch (quantErr) {
            console.warn('PNG Quantization skipped:', quantErr);
          }
        }

        const quality = currentSettings.format === 'png' ? 1 : qFactor / 100;

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              resolve({
                ...item,
                status: 'error',
                errorMsg: 'Canvas encoding failed',
              });
              return;
            }

            const compressedUrl = URL.createObjectURL(blob);
            const compressedSize = blob.size;
            const reduction = ((item.originalSize - compressedSize) / item.originalSize) * 100;

            resolve({
              ...item,
              compressedBlob: blob,
              compressedUrl,
              compressedSize,
              compressedWidth: width,
              compressedHeight: height,
              status: 'done',
              reductionRatio: parseFloat(reduction.toFixed(1)),
            });
          },
          mimeType,
          quality
        );
      };

      img.onerror = () => {
        resolve({
          ...item,
          status: 'error',
          errorMsg: 'Could not load image source',
        });
      };

      img.src = item.originalUrl;
    });
  };

  // Bulk compression runner with controlled concurrency
  const startBulkCompression = async (targetItems = null) => {
    if (isCompressingRef.current) return;

    const listToProcess = targetItems || items;
    const queuedItems = listToProcess.filter((i) => i.status !== 'done' || targetItems);

    if (queuedItems.length === 0) {
      Swal.fire({
        icon: 'info',
        title: 'All Compressed',
        text: 'All selected images have already been compressed!',
        confirmColor: '#4f46e5',
      });
      return;
    }

    setIsCompressing(true);
    isCompressingRef.current = true;

    // Mark items as queued/processing
    const queuedIds = new Set(queuedItems.map((i) => i.id));
    setItems((prev) =>
      prev.map((i) => (queuedIds.has(i.id) ? { ...i, status: 'queued', errorMsg: null } : i))
    );

    const CONCURRENCY = 4;
    let pool = [...queuedItems];

    const worker = async () => {
      while (pool.length > 0 && isCompressingRef.current) {
        const currentItem = pool.shift();
        if (!currentItem) break;

        // Set status to processing
        setItems((prev) =>
          prev.map((i) => (i.id === currentItem.id ? { ...i, status: 'processing' } : i))
        );

        const result = await compressSingleItem(currentItem, settings);

        // Update with compressed result
        setItems((prev) => prev.map((i) => (i.id === result.id ? result : i)));
      }
    };

    const workers = Array(Math.min(CONCURRENCY, pool.length))
      .fill(null)
      .map(() => worker());

    await Promise.all(workers);

    setIsCompressing(false);
    isCompressingRef.current = false;
  };

  // Stop current compression queue
  const stopCompression = () => {
    isCompressingRef.current = false;
    setIsCompressing(false);
  };

  // Clear all items and revoke memory URLs
  const clearAll = () => {
    if (isCompressing) stopCompression();
    items.forEach((item) => {
      if (item.originalUrl) URL.revokeObjectURL(item.originalUrl);
      if (item.compressedUrl) URL.revokeObjectURL(item.compressedUrl);
    });
    setItems([]);
    setPreviewItem(null);
  };

  // Single item removal
  const removeItem = (id) => {
    setItems((prev) => {
      const target = prev.find((i) => i.id === id);
      if (target) {
        if (target.originalUrl) URL.revokeObjectURL(target.originalUrl);
        if (target.compressedUrl) URL.revokeObjectURL(target.compressedUrl);
      }
      return prev.filter((i) => i.id !== id);
    });
  };

  // Helper to format output file name
  const getOutputFilename = (item) => {
    const rawName = item.name;
    const lastDotIdx = rawName.lastIndexOf('.');
    const baseName = lastDotIdx !== -1 ? rawName.substring(0, lastDotIdx) : rawName;

    let ext = settings.format;
    if (ext === 'jpeg') ext = 'jpg';

    const prefix = settings.namingPrefix || '';
    const suffix = settings.namingSuffix || '';

    return `${prefix}${baseName}${suffix}.${ext}`;
  };

  // Single item download trigger
  const downloadSingle = (item) => {
    if (!item.compressedUrl && !item.originalUrl) return;
    const a = document.createElement('a');
    a.href = item.compressedUrl || item.originalUrl;
    a.download = getOutputFilename(item);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  };

  // Export all compressed images to a single ZIP archive
  const downloadZip = async () => {
    const doneItems = items.filter((i) => i.status === 'done' && i.compressedBlob);
    if (doneItems.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Compressed Files',
        text: 'Please compress images before creating a ZIP file.',
        confirmColor: '#e11d48',
      });
      return;
    }

    setIsZipping(true);
    const zip = new JSZip();
    const folder = zip.folder('compressed_images');

    doneItems.forEach((item) => {
      const filename = getOutputFilename(item);
      folder.file(filename, item.compressedBlob);
    });

    try {
      const content = await zip.generateAsync({ type: 'blob' });
      const zipUrl = URL.createObjectURL(content);
      const a = document.createElement('a');
      a.href = zipUrl;
      a.download = `compressed_images_${Date.now()}.zip`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(zipUrl);

      Swal.fire({
        icon: 'success',
        title: 'ZIP Downloaded!',
        text: `Successfully bundled ${doneItems.length} images into a ZIP archive.`,
        confirmColor: '#10b981',
      });
    } catch (e) {
      console.error('Failed to generate ZIP archive:', e);
      Swal.fire({
        icon: 'error',
        title: 'ZIP Creation Failed',
        text: 'An error occurred while creating the ZIP archive.',
        confirmColor: '#e11d48',
      });
    } finally {
      setIsZipping(false);
    }
  };

  // Summary Metrics calculations
  const metrics = useMemo(() => {
    const total = items.length;
    const doneItems = items.filter((i) => i.status === 'done');
    const completedCount = doneItems.length;
    const processingCount = items.filter((i) => i.status === 'processing').length;
    const queuedCount = items.filter((i) => i.status === 'queued').length;
    const errorCount = items.filter((i) => i.status === 'error').length;

    const originalTotalBytes = doneItems.reduce((acc, curr) => acc + curr.originalSize, 0);
    const compressedTotalBytes = doneItems.reduce((acc, curr) => acc + (curr.compressedSize || 0), 0);
    const savedBytes = originalTotalBytes - compressedTotalBytes;
    const overallReduction =
      originalTotalBytes > 0 ? ((savedBytes / originalTotalBytes) * 100).toFixed(1) : '0';

    return {
      total,
      completedCount,
      processingCount,
      queuedCount,
      errorCount,
      originalTotalBytes,
      compressedTotalBytes,
      savedBytes,
      overallReduction,
    };
  }, [items]);

  // Filtered & Searched List
  const filteredItems = useMemo(() => {
    return items.filter((item) => {
      const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase());
      if (!matchesSearch) return false;
      if (activeFilter === 'all') return true;
      return item.status === activeFilter;
    });
  }, [items, searchQuery, activeFilter]);

  // Drag and Drop handlers
  const handleDragOver = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      handleFilesAdded(e.dataTransfer.files);
    }
  };

  const content = (
    <div className="space-y-6 max-w-7xl mx-auto pb-16 select-none">
      {/* Hidden inputs for File and Folder uploads */}
      <input
        type="file"
        ref={fileInputRef}
        onChange={(e) => handleFilesAdded(e.target.files)}
        multiple
        accept="image/*"
        className="hidden"
      />
      <input
        type="file"
        ref={folderInputRef}
        onChange={(e) => handleFilesAdded(e.target.files)}
        webkitdirectory="true"
        directory="true"
        multiple
        className="hidden"
      />

      {/* Page Header */}
      <div className="bg-slate-900 text-white p-6 sm:p-8 rounded-3xl shadow-xl border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden">
        <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-crimson-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div className="relative z-10 space-y-1">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-crimson-500/20 text-crimson-400 text-xs font-black uppercase tracking-widest mb-2 border border-crimson-500/30">
            <i className="fa-solid fa-bolt"></i> Ultra Fast Client-Side Processing
          </div>
          <h1 className="text-2xl sm:text-3xl font-black tracking-tight text-white">
            Bulk Image Compressor
          </h1>
          <p className="text-xs sm:text-sm text-slate-400 font-medium">
            Compress 200–300+ images instantly in your browser. Convert formats, resize dimensions, and export all as a ZIP file.
          </p>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-wrap items-center gap-3 relative z-10 w-full md:w-auto">
          <button
            onClick={() => fileInputRef.current?.click()}
            className="flex-1 md:flex-none px-5 py-3 rounded-2xl bg-crimson-600 hover:bg-crimson-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-crimson-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <i className="fa-solid fa-images text-sm"></i> Select Images
          </button>
          <button
            onClick={() => folderInputRef.current?.click()}
            className="flex-1 md:flex-none px-5 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs uppercase tracking-wider border border-slate-700 transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <i className="fa-solid fa-folder-open text-sm text-amber-400"></i> Upload Folder
          </button>
        </div>
      </div>

      {/* Drag & Drop Zone */}
      <div
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => items.length === 0 && fileInputRef.current?.click()}
        className={`border-2 border-dashed rounded-3xl p-8 sm:p-12 text-center transition-all duration-300 cursor-pointer ${
          isDragging
            ? 'border-crimson-500 bg-crimson-500/10 scale-[1.01]'
            : items.length === 0
            ? 'border-slate-300 bg-white hover:border-crimson-400 hover:bg-slate-50/50 shadow-sm'
            : 'border-slate-200 bg-slate-50/50'
        }`}
      >
        <div className="max-w-md mx-auto space-y-3 pointer-events-none">
          <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-crimson-600 to-amber-500 text-white flex items-center justify-center mx-auto text-2xl shadow-xl shadow-crimson-600/20">
            <i className="fa-solid fa-cloud-arrow-up"></i>
          </div>
          <div>
            <h3 className="text-base font-black text-slate-800">
              Drag & Drop 200–300+ Images Here
            </h3>
            <p className="text-xs font-semibold text-slate-400 mt-1">
              Supports JPG, PNG, WebP, AVIF, GIF (Unlimited Files, Processed Locally)
            </p>
          </div>
        </div>
      </div>

      {/* Control Settings Bar */}
      <div className="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-6">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4">
          <div className="flex items-center gap-2">
            <i className="fa-solid fa-sliders text-crimson-600 text-base"></i>
            <h3 className="text-sm font-black uppercase tracking-wider text-slate-800">
              Compression Settings
            </h3>
          </div>

          <div className="flex items-center gap-2">
            <label className="text-xs font-bold text-slate-500 flex items-center gap-2 cursor-pointer select-none">
              <input
                type="checkbox"
                checked={settings.autoCompress}
                onChange={(e) => setSettings({ ...settings, autoCompress: e.target.checked })}
                className="w-4 h-4 rounded text-crimson-600 focus:ring-crimson-500 border-slate-300"
              />
              Auto-compress on add
            </label>
          </div>
        </div>

        {/* Controls Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {/* Format Selection */}
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
              <span>Target Format</span>
              <span className="text-[10px] text-emerald-600 font-extrabold">WebP = Best Compression</span>
            </label>
            <select
              value={settings.format}
              onChange={(e) => setSettings({ ...settings, format: e.target.value })}
              className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-crimson-500"
            >
              <option value="webp">WebP (Recommended)</option>
              <option value="jpeg">JPEG / JPG</option>
              <option value="png">PNG (Quantized / Transparent)</option>
            </select>
          </div>

          {/* Quality Slider */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <label className="text-xs font-bold uppercase tracking-wider text-slate-500">
                Quality Level
              </label>
              <span className="text-xs font-black text-crimson-600 bg-crimson-50 px-2 py-0.5 rounded-md border border-crimson-100">
                {settings.quality}%
              </span>
            </div>
            <input
              type="range"
              min="10"
              max="100"
              value={settings.quality}
              onChange={(e) => setSettings({ ...settings, quality: Number(e.target.value) })}
              className="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-crimson-600"
            />
            <div className="flex justify-between text-[10px] font-bold text-slate-400">
              <span>10% (Smallest)</span>
              <span>80% (Balanced)</span>
              <span>100% (High Quality)</span>
            </div>
          </div>

          {/* Max Dimensions Resize */}
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wider text-slate-500">
              Max Dimension (Width / Height)
            </label>
            <select
              value={settings.maxWidth}
              onChange={(e) => {
                const val = Number(e.target.value);
                setSettings({ ...settings, maxWidth: val, maxHeight: val });
              }}
              className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-crimson-500"
            >
              <option value="0">Original (No Resize)</option>
              <option value="1920">1920px (Full HD)</option>
              <option value="1280">1280px (HD Ready)</option>
              <option value="800">800px (Medium)</option>
              <option value="600">600px (Thumbnail / Mobile)</option>
            </select>
          </div>

          {/* Naming Suffix */}
          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wider text-slate-500">
              File Name Suffix
            </label>
            <input
              type="text"
              value={settings.namingSuffix}
              onChange={(e) => setSettings({ ...settings, namingSuffix: e.target.value })}
              placeholder="_compressed"
              className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-crimson-500"
            />
          </div>
        </div>

        {/* Global Action Bar */}
        {items.length > 0 && (
          <div className="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              {isCompressing ? (
                <button
                  onClick={stopCompression}
                  className="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer shadow-md shadow-amber-500/20"
                >
                  <i className="fa-solid fa-pause"></i> Pause Queue
                </button>
              ) : (
                <button
                  onClick={() => startBulkCompression()}
                  className="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer shadow-md shadow-slate-900/20"
                >
                  <i className="fa-solid fa-play text-emerald-400"></i> Compress All ({items.length})
                </button>
              )}

              <button
                onClick={clearAll}
                className="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer"
              >
                <i className="fa-solid fa-trash-can text-slate-400"></i> Clear All
              </button>
            </div>

            <button
              onClick={downloadZip}
              disabled={metrics.completedCount === 0 || isZipping}
              className={`px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer ${
                metrics.completedCount > 0 && !isZipping
                  ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/30'
                  : 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200'
              }`}
            >
              {isZipping ? (
                <>
                  <i className="fa-solid fa-spinner animate-spin"></i> Bundling ZIP...
                </>
              ) : (
                <>
                  <i className="fa-solid fa-file-zipper text-sm"></i> Download All as ZIP ({metrics.completedCount})
                </>
              )}
            </button>
          </div>
        )}
      </div>

      {/* Summary Metrics Section */}
      {items.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-black">
              <i className="fa-solid fa-images"></i>
            </div>
            <div>
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Images</span>
              <h4 className="text-xl font-black text-slate-800">{metrics.total} Files</h4>
              <span className="text-[10px] font-extrabold text-indigo-600">{metrics.completedCount} Compressed</span>
            </div>
          </div>

          <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-black">
              <i className="fa-solid fa-database"></i>
            </div>
            <div>
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Original Size</span>
              <h4 className="text-xl font-black text-slate-800">{formatBytes(metrics.originalTotalBytes)}</h4>
              <span className="text-[10px] font-bold text-slate-400">Total uncompressed</span>
            </div>
          </div>

          <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black">
              <i className="fa-solid fa-hard-drive"></i>
            </div>
            <div>
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Compressed Size</span>
              <h4 className="text-xl font-black text-emerald-600">{formatBytes(metrics.compressedTotalBytes)}</h4>
              <span className="text-[10px] font-extrabold text-emerald-600">Saved {formatBytes(metrics.savedBytes)}</span>
            </div>
          </div>

          <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 rounded-2xl bg-crimson-50 text-crimson-600 flex items-center justify-center text-lg font-black">
              <i className="fa-solid fa-chart-line"></i>
            </div>
            <div>
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Storage Saved</span>
              <h4 className="text-xl font-black text-crimson-600">
                {metrics.completedCount > 0 ? `${metrics.overallReduction}%` : '0%'}
              </h4>
              <span className="text-[10px] font-bold text-slate-400">Average reduction</span>
            </div>
          </div>
        </div>
      )}

      {/* Queue Table Section */}
      {items.length > 0 && (
        <div className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden space-y-4 p-6">
          {/* Filters & Search */}
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl w-full sm:w-auto">
              {['all', 'done', 'processing', 'queued', 'error'].map((filter) => (
                <button
                  key={filter}
                  onClick={() => setActiveFilter(filter)}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer ${
                    activeFilter === filter
                      ? 'bg-white text-slate-800 shadow-sm'
                      : 'text-slate-500 hover:text-slate-800'
                  }`}
                >
                  {filter}
                </button>
              ))}
            </div>

            <div className="relative w-full sm:w-64">
              <i className="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                type="text"
                placeholder="Search images..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-crimson-500"
              />
            </div>
          </div>

          {/* Table List */}
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-slate-100 text-[10px] font-black uppercase tracking-wider text-slate-400 bg-slate-50/50">
                  <th className="py-3 px-4 rounded-l-xl">Image</th>
                  <th className="py-3 px-4">Original</th>
                  <th className="py-3 px-4">Compressed</th>
                  <th className="py-3 px-4">Savings</th>
                  <th className="py-3 px-4">Status</th>
                  <th className="py-3 px-4 text-right rounded-r-xl">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-xs">
                {filteredItems.map((item) => (
                  <tr key={item.id} className="hover:bg-slate-50/80 transition-colors">
                    {/* Thumbnail & File Name */}
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 relative group">
                          <img
                            src={item.compressedUrl || item.originalUrl}
                            alt={item.name}
                            className="w-full h-full object-cover"
                          />
                        </div>
                        <div className="min-w-0 max-w-[200px]">
                          <p className="font-bold text-slate-800 truncate" title={item.name}>
                            {item.name}
                          </p>
                          <p className="text-[10px] text-slate-400 font-semibold">
                            {item.originalWidth > 0 ? `${item.originalWidth}x${item.originalHeight}` : 'Image'}
                          </p>
                        </div>
                      </div>
                    </td>

                    {/* Original Specs */}
                    <td className="py-3 px-4 font-bold text-slate-600">
                      {formatBytes(item.originalSize)}
                    </td>

                    {/* Compressed Specs */}
                    <td className="py-3 px-4 font-bold text-slate-800">
                      {item.compressedSize ? (
                        <div>
                          <span className="text-emerald-600 font-black">{formatBytes(item.compressedSize)}</span>
                          <p className="text-[10px] text-slate-400 font-semibold">
                            {item.compressedWidth}x{item.compressedHeight} px
                          </p>
                        </div>
                      ) : (
                        <span className="text-slate-400">—</span>
                      )}
                    </td>

                    {/* Reduction Savings */}
                    <td className="py-3 px-4">
                      {item.reductionRatio !== null ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                          <i className="fa-solid fa-arrow-down text-[9px]"></i> {item.reductionRatio}%
                        </span>
                      ) : (
                        <span className="text-slate-400">—</span>
                      )}
                    </td>

                    {/* Status Badge */}
                    <td className="py-3 px-4">
                      {item.status === 'done' && (
                        <span className="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600">
                          <i className="fa-solid fa-circle-check"></i> Done
                        </span>
                      )}
                      {item.status === 'processing' && (
                        <span className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600">
                          <i className="fa-solid fa-spinner animate-spin"></i> Processing...
                        </span>
                      )}
                      {item.status === 'queued' && (
                        <span className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400">
                          <i className="fa-solid fa-clock"></i> Queued
                        </span>
                      )}
                      {item.status === 'error' && (
                        <span className="inline-flex items-center gap-1.5 text-xs font-bold text-crimson-600">
                          <i className="fa-solid fa-triangle-exclamation"></i> Error
                        </span>
                      )}
                    </td>

                    {/* Actions */}
                    <td className="py-3 px-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        {item.status === 'done' && (
                          <>
                            <button
                              onClick={() => setPreviewItem(item)}
                              className="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-all"
                              title="Compare Before & After"
                            >
                              <i className="fa-solid fa-eye text-xs"></i>
                            </button>
                            <button
                              onClick={() => downloadSingle(item)}
                              className="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 flex items-center justify-center transition-all"
                              title="Download Single Image"
                            >
                              <i className="fa-solid fa-download text-xs"></i>
                            </button>
                          </>
                        )}
                        <button
                          onClick={() => removeItem(item.id)}
                          className="w-8 h-8 rounded-lg bg-slate-50 hover:bg-crimson-50 text-slate-400 hover:text-crimson-600 flex items-center justify-center transition-all"
                          title="Remove item"
                        >
                          <i className="fa-solid fa-xmark text-xs"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Side-by-side Modal Preview */}
      {previewItem && (
        <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 sm:p-6">
          <div className="bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button
              onClick={() => setPreviewItem(null)}
              className="absolute top-6 right-6 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-all cursor-pointer"
            >
              <i className="fa-solid fa-xmark text-sm"></i>
            </button>

            <div>
              <h3 className="text-lg font-black text-slate-800">Visual Quality Comparison</h3>
              <p className="text-xs text-slate-400 font-semibold">{previewItem.name}</p>
            </div>

            {/* Split View */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Original */}
              <div className="space-y-3">
                <div className="flex items-center justify-between bg-slate-100 px-4 py-2 rounded-xl text-xs font-bold text-slate-700">
                  <span>Original Image</span>
                  <span className="text-slate-500">{formatBytes(previewItem.originalSize)}</span>
                </div>
                <div className="aspect-square rounded-2xl bg-slate-900 border border-slate-200 overflow-hidden flex items-center justify-center p-2">
                  <img
                    src={previewItem.originalUrl}
                    alt="Original"
                    className="max-w-full max-h-full object-contain"
                  />
                </div>
                <p className="text-[11px] text-slate-500 font-bold text-center">
                  Dimensions: {previewItem.originalWidth} x {previewItem.originalHeight} px
                </p>
              </div>

              {/* Compressed */}
              <div className="space-y-3">
                <div className="flex items-center justify-between bg-emerald-50 px-4 py-2 rounded-xl text-xs font-bold text-emerald-800 border border-emerald-200">
                  <span>Compressed ({settings.format.toUpperCase()})</span>
                  <span className="text-emerald-700 font-black">{formatBytes(previewItem.compressedSize)}</span>
                </div>
                <div className="aspect-square rounded-2xl bg-slate-900 border border-slate-200 overflow-hidden flex items-center justify-center p-2">
                  <img
                    src={previewItem.compressedUrl}
                    alt="Compressed"
                    className="max-w-full max-h-full object-contain"
                  />
                </div>
                <p className="text-[11px] text-emerald-600 font-bold text-center">
                  Saved {previewItem.reductionRatio}% storage ({previewItem.compressedWidth} x {previewItem.compressedHeight} px)
                </p>
              </div>
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end">
              <button
                onClick={() => downloadSingle(previewItem)}
                className="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2 cursor-pointer"
              >
                <i className="fa-solid fa-download"></i> Download Compressed Image
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );

  if (noLayout) return content;
  return <AdminLayout>{content}</AdminLayout>;
}
