/**
 * Utility to calculate matching row background color for a given category color.
 * Supports hex (#RRGGBB, #RGB) and rgb(...) color strings.
 *
 * @param {string} catBg - Category background/banner color (e.g. '#3B41F1', '#00a859')
 * @param {number} index - Row index within the category (0, 1, 2...)
 * @param {string} mode - 'alternating' | 'solid_tint' | 'white_alternate' | 'none' | 'white'
 * @param {number} intensity - Max tint percentage (e.g., 12 for 12%, range 5-30)
 * @returns {string} Hex color string for row background
 */
export function getCategoryRowBg(catBg, index = 0, mode = 'alternating', intensity = 12) {
  if (mode === 'none' || mode === 'white') {
    return index % 2 === 1 ? '#f8fafc' : '#ffffff';
  }

  let colorStr = catBg || '#00a859';
  let r = 0, g = 168, b = 89; // Default fallback green

  if (typeof colorStr === 'string') {
    if (colorStr.startsWith('#')) {
      let hex = colorStr.replace('#', '');
      if (hex.length === 3) {
        hex = hex.split('').map((c) => c + c).join('');
      }
      if (hex.length === 6) {
        const parsedR = parseInt(hex.substring(0, 2), 16);
        const parsedG = parseInt(hex.substring(2, 4), 16);
        const parsedB = parseInt(hex.substring(4, 6), 16);
        if (!isNaN(parsedR) && !isNaN(parsedG) && !isNaN(parsedB)) {
          r = parsedR;
          g = parsedG;
          b = parsedB;
        }
      }
    } else if (colorStr.startsWith('rgb')) {
      const match = colorStr.match(/\d+/g);
      if (match && match.length >= 3) {
        r = parseInt(match[0], 10);
        g = parseInt(match[1], 10);
        b = parseInt(match[2], 10);
      }
    }
  }

  const maxFactor = Math.min(0.4, Math.max(0.02, (Number(intensity) || 12) / 100));

  let factor = maxFactor;
  if (mode === 'alternating') {
    factor = index % 2 === 0 ? maxFactor * 0.35 : maxFactor;
  } else if (mode === 'white_alternate') {
    factor = index % 2 === 0 ? 0.0 : maxFactor;
  } else if (mode === 'solid_tint') {
    factor = maxFactor * 0.7;
  }

  if (factor === 0.0) return '#ffffff';

  const blendedR = Math.round(255 * (1 - factor) + r * factor);
  const blendedG = Math.round(255 * (1 - factor) + g * factor);
  const blendedB = Math.round(255 * (1 - factor) + b * factor);

  const toHex = (c) => Math.min(255, Math.max(0, c)).toString(16).padStart(2, '0');
  return `#${toHex(blendedR)}${toHex(blendedG)}${toHex(blendedB)}`;
}
