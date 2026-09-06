/**
 * Utility for Auto-Translating/Transliterating English Product Names to Thanglish (Tamil script)
 * e.g., "crackers" -> "கிராக்கர்ஸ்", "10 inch deluxe flower pot" -> "10 இன்ச் டீலக்ஸ் ஃப்ளவர் பாட்"
 */

// Common Fireworks English -> Thanglish (Tamil script) Mapping
const FIREWORKS_THANGLISH_DICTIONARY = {
  'crackers': 'கிராக்கர்ஸ்',
  'cracker': 'கிராக்கர்',
  'flower pot': 'ஃப்ளவர் பாட்',
  'flower pots': 'ஃப்ளவர் பாட்ஸ்',
  'ground chakkar': 'கிரவுண்ட் சக்கர்',
  'ground chakkars': 'கிரவுண்ட் சக்கர்',
  'sparkler': 'ஸ்பார்க்ளர்',
  'sparklers': 'ஸ்பார்க்ளர்ஸ்',
  'rocket': 'ராக்கெட்',
  'rockets': 'ராக்கெட்ஸ்',
  'one sound': 'ஒன் சவுண்ட்',
  'two sound': 'டூ சவுண்ட்',
  'three sound': 'திரீ சவுண்ட்',
  'sound crackers': 'சவுண்ட் கிராக்கர்ஸ்',
  'sound': 'சவுண்ட்',
  'bomb': 'பாம்',
  'bombs': 'பாம்ஸ்',
  'atom bomb': 'ஆட்டம் பாம்',
  'hydrogen bomb': 'ஹைட்ரஜன் பாம்',
  'deluxe': 'டீலக்ஸ்',
  'special': 'ஸ்பெஷல்',
  'super': 'சூப்பர்',
  'jumbo': 'ஜம்போ',
  'giant': 'ஜெயண்ட்',
  'mega': 'மெகா',
  'mini': 'மினி',
  'red': 'ரெட்',
  'green': 'கிரீன்',
  'gold': 'கோல்டு',
  'golden': 'கோல்டன்',
  'silver': 'சில்வர்',
  'color': 'கலர்',
  'colour': 'கலர்',
  'electric': 'எலக்ட்ரிக்',
  'chorsa': 'சோர்சா',
  'garland': 'கார்லேண்ட்',
  'pencil': 'பென்சில்',
  'pencils': 'பென்சில்ஸ்',
  'peacock': 'பீகாக்',
  'twinkling star': 'ட்விங்க்ளிங் ஸ்டார்',
  'twinkling': 'ட்விங்க்ளிங்',
  'star': 'ஸ்டார்',
  'matching': 'மேட்சிங்',
  'shot': 'ஷாட்',
  'shots': 'ஷாட்ஸ்',
  'fountain': 'ஃபவுண்டைன்',
  'fountains': 'ஃபவுண்டைன்ஸ்',
  'whistling': 'விஸ்லிங்',
  'flash': 'ஃப்ளாஷ்',
  'sky': 'ஸ்கை',
  'night': 'நைட்',
  'magic': 'மேஜிக்',
  'spinner': 'ஸ்பின்னர்',
  'wheel': 'வீல்',
  'wheels': 'வீல்ஸ்',
  'crackling': 'க்ராக்கிளிங்',
  'twinkler': 'ட்விங்க்ளர்',
  'siren': 'சைரன்',
  'water': 'வாட்டர்',
  'paper': 'பேப்பர்',
  'classic': 'கிளாசிக்',
  'matrix': 'மேட்ரிக்ஸ்',
  'thunder': 'தண்டர்',
  'light': 'லைட்',
  'lights': 'லைட்ஸ்',
  'disco': 'டிஸ்கோ',
  'multi': 'மல்டி',
  'digital': 'டிஜிட்டல்',
  'single': 'சிங்கிள்',
  'double': 'டபுள்',
  'triple': 'டிரிபிள்',
  'inch': 'இன்ச்',
  'inches': 'இன்சஸ்',
  'cm': 'செ.மீ',
  'mm': 'மி.மீ',
  'pcs': 'பீஸ்',
  'box': 'பாக்ஸ்',
  'pkt': 'பக்கெட்',
  'fancy': 'ஃபேன்சி',
  'prime': 'ப்ரைம்',
  'royal': 'ராயல்',
  'king': 'கிங்',
  'queen': 'க்வீன்',
  'snake': 'ஸ்நேக்',
  'caps': 'கேப்ஸ்',
  'roll': 'ரோல்',
  'dot': 'டாட்',
  'guns': 'கன்ஸ்',
  'gun': 'கன்',
  'match': 'மேட்ச்',
  'pop': 'பாப்',
  'pop pop': 'பாப் பாப்',
};

/**
 * Transliterate a single English word to Tamil letters
 */
async function transliterateWord(word) {
  if (!word || !/^[A-Za-z]+$/.test(word)) return word;

  const lower = word.toLowerCase();
  if (FIREWORKS_THANGLISH_DICTIONARY[lower]) {
    return FIREWORKS_THANGLISH_DICTIONARY[lower];
  }

  // Fallback to Google Input Tools API for dynamic words
  try {
    const res = await fetch(`https://inputtools.google.com/request?text=${encodeURIComponent(word)}&itc=ta-t-i0-und&num=1`);
    if (res.ok) {
      const data = await res.json();
      if (data && data[1] && data[1][0] && data[1][0][1] && data[1][0][1][0]) {
        return data[1][0][1][0];
      }
    }
  } catch (err) {
    console.warn('Network transliteration failed for word:', word, err);
  }

  return word;
}

/**
 * Transliterate single English text string to Thanglish (Tamil script)
 */
export async function translateEnglishToTamil(text) {
  if (!text || !text.trim()) return '';

  let result = text.trim();

  // 1. Replace multi-word dictionary phrases first (case-insensitive)
  const phrases = Object.keys(FIREWORKS_THANGLISH_DICTIONARY)
    .filter((k) => k.includes(' '))
    .sort((a, b) => b.length - a.length);

  for (const phrase of phrases) {
    const reg = new RegExp(`\\b${phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
    result = result.replace(reg, FIREWORKS_THANGLISH_DICTIONARY[phrase]);
  }

  // 2. Tokenize by English word sequences vs numbers/symbols/spaces
  const tokens = result.split(/([a-zA-Z]+)/);
  const processed = await Promise.all(
    tokens.map(async (token) => {
      if (/^[a-zA-Z]+$/.test(token)) {
        return await transliterateWord(token);
      }
      return token;
    })
  );

  return processed.join('');
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

