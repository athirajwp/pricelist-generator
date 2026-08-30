/**
 * Helper to safely format image URLs (data URIs, http/https URLs, and relative paths)
 * Prevents double-slash bugs when paths start with a leading slash.
 */
export const getImageUrl = (path, fallback = '') => {
  if (!path || typeof path !== 'string' || !path.trim()) {
    return fallback;
  }
  const cleanPath = path.trim();
  let formatted = cleanPath;
  if (!cleanPath.startsWith('data:') && !cleanPath.startsWith('http://') && !cleanPath.startsWith('https://')) {
    formatted = cleanPath.startsWith('/') ? cleanPath : `/${cleanPath}`;
  }
  return encodeURI(formatted);
};
