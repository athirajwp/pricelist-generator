/**
 * Utility for Auto-Translating English Product Names to Tamil
 */

// Common Fireworks English -> Tamil Term Mapping
const FIREWORKS_DICTIONARY = {
  'flower pot': 'பூச்சட்டி',
  'flower pots': 'பூச்சட்டி',
  'ground chakkar': 'தரை சக்கரம்',
  'ground chakkars': 'தரை சக்கரம்',
  'sparkler': 'மத்தாப்பு',
  'sparklers': 'மத்தாப்பு',
  'rocket': 'ராக்கெட்',
  'rockets': 'ராக்கெட்',
  'one sound': 'ஒரே சவுண்ட்',
  'two sound': 'டூ சவுண்ட்',
  'three sound': 'திரீ சவுண்ட்',
  'sound crackers': 'சவுண்ட் பட்டாசு',
  'bomb': 'பாம்',
  'atom bomb': 'ஆட்டம் பாம்',
  'hydrogen bomb': 'ஹைட்ரஜன் பாம்',
  'deluxe': 'டீலக்ஸ்',
  'special': 'ஸ்பெஷல்',
  'super': 'சூப்பர்',
  'jumbo': 'ஜம்போ',
  'giant': 'ஜயன்ட்',
  'mega': 'மெகா',
  'mini': 'மினி',
  'red': 'சிவப்பு',
  'green': 'பச்சை',
  'gold': 'தங்கம்',
  'silver': 'வெள்ளி',
  'color': 'வர்ண',
  'colour': 'வர்ண',
  'electric': 'எலக்ட்ரிக்',
  'chorsa': 'சரவெடி',
  'garland': 'சரவெடி',
  'pencil': 'பென்சில்',
  'pencils': 'பென்சில்',
  'peacock': 'மயில்',
  'twinkling star': 'மின்னி விண்மீன்',
  'matching': 'மேட்சிங்',
};

/**
 * Translate single English text string to Tamil via Google GTX API
 */
export async function translateEnglishToTamil(text) {
  if (!text || !text.trim()) return '';

  const cleanText = text.trim();

  // Try Google GTX API
  try {
    const res = await fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ta&dt=t&q=${encodeURIComponent(cleanText)}`);
    if (res.ok) {
      const data = await res.json();
      if (data && data[0] && data[0][0] && data[0][0][0]) {
        const translated = data[0][0][0];
        if (translated && translated.trim() !== cleanText) {
          return translated.trim();
        }
      }
    }
  } catch (err) {
    console.warn('Network translation failed, using dictionary fallback:', err);
  }

  // Fallback to local dictionary replace
  let lower = cleanText.toLowerCase();
  let result = cleanText;
  Object.keys(FIREWORKS_DICTIONARY).forEach((key) => {
    if (lower.includes(key)) {
      result = result.replace(new RegExp(key, 'gi'), FIREWORKS_DICTIONARY[key]);
    }
  });

  return result;
}

/**
 * Batch translate categories array of products
 */
export async function batchTranslateCategoriesToTamil(categories, overwriteExisting = false) {
  if (!categories || !Array.isArray(categories)) return categories;

  const newCategories = JSON.parse(JSON.stringify(categories));
  let modifiedCount = 0;

  for (const cat of newCategories) {
    if (!cat.products || !Array.isArray(cat.products)) continue;

    for (const prod of cat.products) {
      if (overwriteExisting || !prod.name_ta || !prod.name_ta.trim()) {
        if (prod.name && prod.name.trim()) {
          const tamil = await translateEnglishToTamil(prod.name);
          if (tamil) {
            prod.name_ta = tamil;
            modifiedCount++;
          }
        }
      }
    }
  }

  return { categories: newCategories, modifiedCount };
}
