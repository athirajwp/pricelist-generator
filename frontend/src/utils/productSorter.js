/**
 * Natural product sorting helper by S.No / Code (`product_code`).
 * Handles numeric strings (1, 2, 3... 10, 11), alphanumeric codes (A1, A2, B1),
 * and falls back to sort_order and id.
 */
export function sortProductsByCode(products) {
  if (!Array.isArray(products)) return [];
  return [...products].sort((a, b) => {
    const codeA = (a && a.product_code !== null && a.product_code !== undefined) ? String(a.product_code).trim() : '';
    const codeB = (b && b.product_code !== null && b.product_code !== undefined) ? String(b.product_code).trim() : '';

    if (codeA !== '' && codeB !== '') {
      const numA = Number(codeA);
      const numB = Number(codeB);
      if (!isNaN(numA) && !isNaN(numB)) {
        if (numA !== numB) return numA - numB;
      } else {
        const cmp = codeA.localeCompare(codeB, undefined, { numeric: true, sensitivity: 'base' });
        if (cmp !== 0) return cmp;
      }
    } else if (codeA !== '') {
      return -1;
    } else if (codeB !== '') {
      return 1;
    }

    const sortA = Number(a?.sort_order ?? 0);
    const sortB = Number(b?.sort_order ?? 0);
    if (sortA !== sortB) return sortA - sortB;

    const idA = Number(a?.id ?? 0);
    const idB = Number(b?.id ?? 0);
    return idA - idB;
  });
}

export function getMinProductCode(products) {
  if (!Array.isArray(products) || products.length === 0) return 999999;
  let min = 999999;
  for (const p of products) {
    const code = (p && p.product_code !== null && p.product_code !== undefined) ? String(p.product_code).trim() : '';
    if (code !== '' && !isNaN(Number(code))) {
      const val = Number(code);
      if (val < min) min = val;
    }
  }
  return min;
}

export function sortCategoriesByProductCode(categories) {
  if (!Array.isArray(categories)) return [];
  return [...categories].sort((catA, catB) => {
    const minA = getMinProductCode(catA?.products);
    const minB = getMinProductCode(catB?.products);
    if (minA !== minB) return minA - minB;

    const sortA = Number(catA?.sort_order ?? 0);
    const sortB = Number(catB?.sort_order ?? 0);
    if (sortA !== sortB) return sortA - sortB;

    return Number(catA?.id ?? 0) - Number(catB?.id ?? 0);
  });
}

export function sortCategoriesAndProducts(categories) {
  if (!Array.isArray(categories)) return [];
  const sortedCategories = sortCategoriesByProductCode(categories);
  return sortedCategories.map((cat) => ({
    ...cat,
    products: sortProductsByCode(cat?.products || []),
  }));
}
