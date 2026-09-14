/**
 * Helper utility to compress image files down to a target size (default 100KB).
 * Handles images of arbitrary size (e.g., 1MB, 500KB, 8MB) and reduces resolution
 * and quality dynamically until the output file size is <= targetSizeKB.
 */

export async function compressImageToTargetSize(file, targetSizeKB = 100, maxDimension = 1200) {
  if (!file || !(file instanceof File || file instanceof Blob)) {
    return file;
  }

  const targetSizeBytes = targetSizeKB * 1024;

  // If file is already smaller than or equal to target size, return original file
  if (file.size <= targetSizeBytes) {
    return file;
  }

  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);

    reader.onload = (event) => {
      const img = new Image();
      img.src = event.target.result;

      img.onload = async () => {
        let width = img.width;
        let height = img.height;

        // Step 1: Scale down dimensions if greater than maxDimension while preserving aspect ratio
        if (width > maxDimension || height > maxDimension) {
          if (width > height) {
            height = Math.round((height * maxDimension) / width);
            width = maxDimension;
          } else {
            width = Math.round((width * maxDimension) / height);
            height = maxDimension;
          }
        }

        // Determine mime type and format (JPEG or WebP preferred for size compression)
        let mimeType = file.type;
        if (mimeType !== 'image/png' && mimeType !== 'image/jpeg' && mimeType !== 'image/webp') {
          mimeType = 'image/jpeg';
        }

        // PNGs can be large; convert to WebP or JPEG for optimal compression unless explicit
        if (file.type === 'image/png') {
          mimeType = 'image/jpeg'; // JPEG/WebP compresses far better for photos/products
        }

        // Canvas element setup
        const canvas = document.createElement('canvas');
        let ctx = canvas.getContext('2d', { willReadFrequently: true });

        const processCanvas = (currentWidth, currentHeight, quality) => {
          canvas.width = currentWidth;
          canvas.height = currentHeight;
          ctx = canvas.getContext('2d', { willReadFrequently: true });
          ctx.imageSmoothingEnabled = true;
          ctx.imageSmoothingQuality = 'high';

          // Fill white background for JPEG conversion if original had alpha channel
          if (mimeType === 'image/jpeg') {
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, currentWidth, currentHeight);
          }

          ctx.drawImage(img, 0, 0, currentWidth, currentHeight);

          return new Promise((resBlob) => {
            canvas.toBlob(
              (blob) => {
                resBlob(blob);
              },
              mimeType,
              quality
            );
          });
        };

        // Step 2: Binary / iterative search for optimal quality & dimension scaling
        let currentW = width;
        let currentH = height;
        let bestBlob = null;
        const qualities = [0.85, 0.75, 0.65, 0.50, 0.35, 0.25, 0.15];

        for (let q of qualities) {
          const blob = await processCanvas(currentW, currentH, q);
          if (blob) {
            bestBlob = blob;
            if (blob.size <= targetSizeBytes) {
              break;
            }
          }
        }

        // If quality iteration wasn't enough (e.g. extremely huge image), reduce dimensions step-wise
        let dimensionScale = 0.8;
        while (bestBlob && bestBlob.size > targetSizeBytes && currentW > 300 && currentH > 300) {
          currentW = Math.round(currentW * dimensionScale);
          currentH = Math.round(currentH * dimensionScale);
          const blob = await processCanvas(currentW, currentH, 0.40);
          if (blob) {
            bestBlob = blob;
          }
        }

        if (!bestBlob) {
          resolve(file);
          return;
        }

        // Create new File object preserving original filename and extension
        let fileName = file.name || 'compressed_image.jpg';
        if (file.type === 'image/png' && mimeType === 'image/jpeg') {
          fileName = fileName.replace(/\.png$/i, '.jpg');
        }

        const resultFile = new File([bestBlob], fileName, {
          type: mimeType,
          lastModified: Date.now(),
        });

        resolve(resultFile);
      };

      img.onerror = () => {
        resolve(file);
      };
    };

    reader.onerror = () => {
      resolve(file);
    };
  });
}
