import React from 'react';
import { Document, Page, Text, View, Image, StyleSheet, pdf, Svg, Path, Line, Circle } from '@react-pdf/renderer';
import { sortProductsByCode, sortCategoriesByProductCode } from '../utils/productSorter';
import { getCategoryRowBg } from '../utils/colorUtils';

const renderTermsContentPDF = (content, fontSize = 6, textColor = '#334155') => {
  if (!content) return null;
  const lines = content.split('\n');

  return (
    <View style={{ flexDirection: 'column', gap: 1 }}>
      {lines.map((line, idx) => {
        const trimmed = line.trim();
        if (!trimmed) return null;

        const leadingSpaces = line.search(/\S/);
        const isIndented = leadingSpaces >= 2 || line.startsWith('\t');

        const isBulletSymbol = /^[\u2022\u2023\u25E6\u2043\u2219\-\*•]/;
        const startsWithBullet = isBulletSymbol.test(trimmed);
        const startsWithNumber = /^\d+[\.\)]/.test(trimmed);

        let bulletText = '';
        let mainText = trimmed;

        if (startsWithBullet) {
          bulletText = '•';
          mainText = trimmed.replace(isBulletSymbol, '').trim();
        } else if (startsWithNumber) {
          const match = trimmed.match(/^(\d+[\.\)])\s*(.*)/);
          if (match) {
            bulletText = match[1];
            mainText = match[2];
          }
        } else if (isIndented) {
          bulletText = '•';
          mainText = trimmed;
        }

        if (bulletText) {
          return (
            <View key={idx} style={{ flexDirection: 'row', paddingLeft: isIndented ? 12 : 0, marginBottom: 1.5, alignItems: 'flex-start' }}>
              <Text style={{ fontSize: fontSize, color: textColor, fontWeight: 'bold', minWidth: isIndented ? 10 : (startsWithNumber ? 14 : 10), marginRight: 2 }}>
                {bulletText}
              </Text>
              <Text style={{ fontSize: fontSize, color: textColor, flex: 1, lineHeight: 1.25 }}>
                {mainText}
              </Text>
            </View>
          );
        }

        return (
          <Text key={idx} style={{ fontSize: fontSize, color: textColor, lineHeight: 1.25, paddingLeft: isIndented ? 12 : 0, marginBottom: 1.5 }}>
            {line}
          </Text>
        );
      })}
    </View>
  );
};

const getPDFTermsSections = (form) => {
  if (Array.isArray(form?.terms_sections) && form.terms_sections.length > 0) {
    return form.terms_sections;
  }
  if (typeof form?.terms_sections === 'string' && form.terms_sections.trim()) {
    try {
      const parsed = JSON.parse(form.terms_sections);
      if (Array.isArray(parsed) && parsed.length > 0) return parsed;
    } catch (e) {}
  }
  return [
    {
      id: 'sec_default',
      show: form?.show_terms !== false,
      title: form?.terms_title || 'TERMS & CONDITIONS',
      title_color: form?.terms_title_color || '#78350f',
      title_font_size: form?.terms_title_font_size ? parseFloat(form.terms_title_font_size) : 12,
      content: form?.terms_content || '1. Goods once sold will not be taken back or exchanged.\n2. Transport charges extra as applicable.\n3. Minimum order value applies for parcel dispatch.',
      text_color: form?.terms_text_color || '#1e293b',
      text_font_size: form?.terms_text_font_size ? parseFloat(form.terms_text_font_size) : 10.5,
      bg_color: form?.terms_bg_color || '#fffbeb',
      border_color: form?.terms_border_color || '#fde047',
    },
  ];
};

const styles = StyleSheet.create({
  page: {
    padding: 12,
    fontSize: 9,
    fontFamily: 'Helvetica',
    backgroundColor: '#ffffff',
  },
  coverPage: {
    padding: 0,
    position: 'relative',
    height: '100%',
    width: '100%',
    backgroundColor: '#991b1b',
  },
  coverBg: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    width: '100%',
    height: '100%',
    objectFit: 'cover',
  },
  coverOverlay: {
    position: 'relative',
    zIndex: 10,
    height: '100%',
    width: '100%',
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'space-between',
    padding: 25,
  },
  coverTop: {
    textAlign: 'center',
    marginTop: 5,
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
  },
  invocationSymbol: {
    fontSize: 13,
    color: '#fde047',
    fontWeight: 'bold',
    marginBottom: 2,
    textAlign: 'center',
  },
  invocationText: {
    fontSize: 9,
    color: '#ffffff',
    fontWeight: 'bold',
    textAlign: 'center',
  },
  coverCenter: {
    textAlign: 'center',
    marginVertical: 10,
    alignItems: 'center',
  },
  storeName: {
    fontSize: 32,
    fontWeight: 'black',
    color: '#ffffff',
    textTransform: 'uppercase',
    letterSpacing: 2,
    marginBottom: 4,
    textAlign: 'center',
  },
  storeTagline: {
    fontSize: 15,
    color: '#ffffff',
    marginBottom: 8,
    fontStyle: 'italic',
    fontWeight: 'bold',
  },
  priceListBadge: {
    backgroundColor: '#ffffff',
    color: '#0f172a',
    fontSize: 13,
    fontWeight: 'bold',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 4,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  deityImageContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 10,
    height: 390,
  },
  deityImg: {
    maxHeight: 390,
    maxWidth: 390,
    objectFit: 'contain',
  },
  coverBanner: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 10,
    display: 'flex',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 2,
    borderColor: '#fbbf24',
  },
  coverBannerLeft: {
    width: '30%',
    alignItems: 'flex-start',
  },
  coverLogo: {
    maxHeight: 50,
    maxWidth: 110,
    objectFit: 'contain',
  },
  coverBannerCenter: {
    width: '45%',
    alignItems: 'flex-start',
  },
  contactItem: {
    fontSize: 8,
    color: '#0f172a',
    fontWeight: 'bold',
    marginBottom: 2,
  },
  coverBannerRight: {
    width: '25%',
    alignItems: 'center',
  },
  megaSaleText: {
    fontSize: 10,
    color: '#d97706',
    fontWeight: 'bold',
    marginBottom: 1,
  },
  discountVal: {
    fontSize: 22,
    fontWeight: 'black',
    color: '#dc2626',
    marginBottom: 2,
  },
  discountBadge: {
    backgroundColor: '#dc2626',
    color: '#ffffff',
    fontSize: 7,
    fontWeight: 'bold',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 3,
    textTransform: 'uppercase',
  },
  addressRow: {
    marginTop: 6,
    paddingTop: 4,
    borderTopWidth: 1,
    borderTopColor: '#e2e8f0',
    textAlign: 'center',
    fontSize: 8,
    color: '#1e293b',
    fontWeight: 'bold',
    width: '100%',
  },

  // Table Page Styles
  header: {
    display: 'flex',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 2,
    borderBottomColor: '#d97706',
    paddingBottom: 6,
    marginBottom: 8,
  },
  headerLeft: {
    width: '60%',
  },
  headerStoreName: {
    fontSize: 16,
    fontWeight: 'black',
    color: '#991b1b',
    textTransform: 'uppercase',
  },
  headerSub: {
    fontSize: 8,
    color: '#475569',
    marginTop: 1,
  },
  headerRight: {
    width: '40%',
    alignItems: 'flex-end',
  },
  headerPhone: {
    fontSize: 8,
    fontWeight: 'bold',
    color: '#0f172a',
  },
  table: {
    display: 'table',
    width: 'auto',
    borderStyle: 'solid',
    borderWidth: 1,
    borderColor: '#94a3b8',
    marginBottom: 8,
  },
  tableRow: {
    margin: 'auto',
    flexDirection: 'row',
    minHeight: 18,
    alignItems: 'center',
  },
  tableHeaderRow: {
    backgroundColor: '#991b1b',
    color: '#ffffff',
    fontWeight: 'bold',
    minHeight: 22,
    alignItems: 'center',
    paddingVertical: 2,
  },
  catRow: {
    backgroundColor: '#d97706',
    color: '#ffffff',
    fontWeight: 'bold',
    textAlign: 'center',
    fontSize: 9,
    paddingVertical: 2.5,
    width: '100%',
    textTransform: 'uppercase',
  },
  colSno: {
    width: '8%',
    textAlign: 'center',
    borderRightWidth: 1,
    borderRightColor: '#cbd5e1',
    padding: 2,
    fontSize: 8,
  },
  colReq: {
    width: '8%',
    textAlign: 'center',
    padding: 2,
    fontSize: 8,
  },
  thText: {
    color: '#ffffff',
    fontWeight: 'bold',
    fontSize: 9,
    lineHeight: 1.15,
    textAlign: 'center',
  },
  footer: {
    position: 'absolute',
    bottom: 12,
    left: 20,
    right: 20,
    display: 'flex',
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: '#e2e8f0',
    paddingTop: 4,
    fontSize: 7,
    color: '#64748b',
  },
  paymentSection: {
    display: 'flex',
    flexDirection: 'row',
    borderWidth: 1,
    borderColor: '#94a3b8',
    borderRadius: 6,
    padding: 6,
    marginTop: 6,
    backgroundColor: '#f8fafc',
  },
  paymentLeft: {
    width: '48%',
    alignItems: 'center',
    paddingRight: 6,
    borderRightWidth: 1,
    borderRightColor: '#cbd5e1',
  },
  paymentRight: {
    width: '48%',
    paddingLeft: 6,
  },
  paymentFull: {
    width: '100%',
    alignItems: 'center',
  },
  payTitle: {
    fontSize: 8,
    fontWeight: 'bold',
    color: '#991b1b',
    marginBottom: 3,
    textTransform: 'uppercase',
  },
  qrImg: {
    width: 65,
    height: 65,
    marginVertical: 2,
  },
  bankRow: {
    display: 'flex',
    flexDirection: 'row',
    justifyContent: 'space-between',
    fontSize: 7,
    marginBottom: 2,
  },
  bankLabel: {
    color: '#64748b',
  },
  bankValue: {
    fontWeight: 'bold',
    color: '#0f172a',
  },
});

export const PriceListPDFDocument = ({ editForm, productPageChunks, showMrp, getImageUrl, colWidths }) => {
  const resolveUrl = (path) => {
    if (!path) return null;
    const url = getImageUrl(path);
    if (!url) return null;
    if (url.startsWith('http') || url.startsWith('data:')) return url;
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    return origin + (url.startsWith('/') ? url : '/' + url);
  };

  const coverBgUrl = editForm.store_cover_bg === 'none' ? null : resolveUrl(editForm.store_cover_bg || '/images/cover_bg_1.jpg');
  const deityUrl = resolveUrl(editForm.store_deity_image);
  const logoUrl = resolveUrl(editForm.store_logo);
  const upiQrUrl = resolveUrl(editForm.store_upi_qr);
  const upiQrUrl2 = resolveUrl(editForm.store_upi_qr_2);
  const getFloatImgPropsPdf = (i) => {
    const suffix = i === 1 ? '' : `_${i}`;
    const rawImage = editForm[`custom_float_image${suffix}`] || '';
    const url = resolveUrl(rawImage);
    const rawX = editForm[`custom_float_x${suffix}`];
    const rawY = editForm[`custom_float_y${suffix}`];
    const rawScale = editForm[`custom_float_scale${suffix}`];
    const rawShow = editForm[`show_custom_float_image${suffix}`];

    const isSimpler = editForm.first_page_layout === 'simpler';

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

    return { url, x, y, scale, show };
  };

  const showSnoPdf = editForm.show_col_sno !== false;
  const showProductPdf = editForm.show_col_product !== false;
  const showTamilPdf = editForm.show_tamil_name === true;
  const showUnitPdf = editForm.show_col_unit !== false;
  const showMrpPdf = showMrp && editForm.show_col_mrp !== false;
  const showOfferPdf = editForm.show_col_offer !== false;
  const showReqPdf = editForm.show_col_req !== false;

  const totalCustomWidth =
    (showSnoPdf ? (colWidths?.sno || 45) : 0) +
    (showProductPdf ? (colWidths?.product || 220) : 0) +
    (showTamilPdf ? (colWidths?.product_ta || 160) : 0) +
    (showUnitPdf ? (colWidths?.unit || 95) : 0) +
    (showMrpPdf ? (colWidths?.mrp || 80) : 0) +
    (showOfferPdf ? (colWidths?.offer || 105) : 0) +
    (showReqPdf ? (colWidths?.req || 45) : 0);

  const getColPct = (key, defaultPct) => {
    if (colWidths && colWidths[key] && totalCustomWidth > 0) {
      return `${((colWidths[key] / totalCustomWidth) * 100).toFixed(1)}%`;
    }
    return defaultPct;
  };

  const colSnoWidth = showSnoPdf ? getColPct('sno', '7%') : '0%';
  const colReqWidth = showReqPdf ? getColPct('req', '7%') : '0%';
  const colPackWidth = showUnitPdf ? getColPct('unit', '14%') : '0%';
  const colMrpWidth = showMrpPdf ? getColPct('mrp', '13%') : '0%';
  const colOfferWidth = showOfferPdf ? getColPct('offer', '15%') : '0%';
  const colNameWidth = showProductPdf ? getColPct('product', showTamilPdf ? '22%' : '44%') : '0%';
  const colTamilWidth = showTamilPdf ? getColPct('product_ta', '22%') : '0%';

  return (
    <Document title={`${editForm.store_name || 'PriceList'}_Catalogue`}>
      {/* Cover Page */}
      {editForm.first_page_layout !== 'simpler' && editForm.first_page_layout !== 'none' && editForm.first_page_layout !== 'hidden' && (
        editForm.first_page_layout === 'custom_image' ? (
          <Page size="A4" style={{ padding: 0, margin: 0, position: 'relative', height: '100%', width: '100%', backgroundColor: '#000000' }}>
            {editForm.custom_first_page_image ? (
              <Image
                src={resolveUrl(editForm.custom_first_page_image)}
                style={{ width: '100%', height: '100%', objectFit: editForm.custom_first_page_fit || 'cover' }}
              />
            ) : null}
          </Page>
        ) : (
          <Page size="A4" style={[styles.coverPage, editForm.store_cover_bg === 'none' ? { backgroundColor: '#ffffff' } : null]}>
            {coverBgUrl && <Image src={coverBgUrl} style={styles.coverBg} />}
            {/* Custom Floating Images overlay on Cover Page (Slots 1 to 5) */}
            {[1, 2, 3, 4, 5].map((i) => {
              const p = getFloatImgPropsPdf(i);
              if (!p.url || !p.show) return null;
              return (
                <Image
                  key={i}
                  src={p.url}
                  style={{
                    position: 'absolute',
                    left: `${p.x}%`,
                    top: `${p.y}%`,
                    width: `${Math.round(200 * (p.scale / 100))}px`,
                    height: 'auto',
                    zIndex: 35,
                  }}
                />
              );
            })}
            <View style={styles.coverOverlay}>
              {/* Top Invocation */}
              <View style={styles.coverTop}>
                {editForm.store_invocation_symbol ? (
                  <Text style={[styles.invocationSymbol, editForm.store_invocation_color ? { color: editForm.store_invocation_color } : null]}>{editForm.store_invocation_symbol}</Text>
                ) : null}
                {editForm.store_invocation ? (
                  <Text style={[styles.invocationText, editForm.store_invocation_color ? { color: editForm.store_invocation_color } : null]}>{editForm.store_invocation}</Text>
                ) : null}
              </View>

              {/* Center Brand */}
              <View style={styles.coverCenter}>
                <Text style={[styles.storeName, editForm.store_title_color ? { color: editForm.store_title_color } : null]}>{editForm.store_name || 'MASS CRACKERS'}</Text>
                {editForm.store_tagline ? (
                  <Text style={[styles.storeTagline, editForm.store_tagline_color ? { color: editForm.store_tagline_color } : null]}>"{editForm.store_tagline}"</Text>
                ) : null}
                <Text style={[styles.priceListBadge, editForm.store_badge_color ? { color: editForm.store_badge_color } : null]}>PRICE LIST - {editForm.store_year || '2026'}</Text>
              </View>

              {/* Center Deity Motif Image */}
              {deityUrl ? (
                <View style={styles.deityImageContainer}>
                  <Image src={deityUrl} style={[styles.deityImg, { maxHeight: 390 * ((editForm.deity_scale || 100) / 100), maxWidth: 390 * ((editForm.deity_scale || 100) / 100) }]} />
                </View>
              ) : (
                <View style={{ flex: 1 }} />
              )}

              {/* Bottom Order Banner */}
              <View style={{ width: '100%' }}>
                <View style={styles.coverBanner}>
                  <View style={styles.coverBannerLeft}>
                    {logoUrl ? (
                      <Image src={logoUrl} style={[styles.coverLogo, { height: 45 * ((editForm.logo_scale || 100) / 100), width: 85 * ((editForm.logo_scale || 100) / 100) }]} />
                    ) : (
                      <Text style={{ fontSize: 12 * ((editForm.logo_scale || 100) / 100), fontWeight: 'bold', color: '#991b1b' }}>{editForm.store_name}</Text>
                    )}
                  </View>
                  <View style={styles.coverBannerCenter}>
                    {editForm.store_email ? (
                      <Text style={[styles.contactItem, { fontSize: 8 * ((editForm.contact_scale || 100) / 100) }]}>🌐 {editForm.store_email}</Text>
                    ) : null}
                    <Text style={[styles.contactItem, { fontSize: 8 * ((editForm.contact_scale || 100) / 100) }]}>
                      📞 {[editForm.store_phone, editForm.store_phone_2].filter(Boolean).join(', ')}
                    </Text>
                    {editForm.store_gpay ? (
                      <Text style={[styles.contactItem, { fontSize: 8 * ((editForm.contact_scale || 100) / 100) }]}>💳 GPay: {editForm.store_gpay}</Text>
                    ) : null}
                  </View>
                  {editForm.show_discount_badge !== false && (
                    <View style={styles.coverBannerRight}>
                      <Text style={[styles.megaSaleText, { fontSize: 9 * ((editForm.discount_scale || 100) / 100) }]}>MEGA SALE</Text>
                      <Text style={[styles.discountVal, { fontSize: 22 * ((editForm.discount_scale || 100) / 100) }]}>{editForm.discount_percent || 50}%</Text>
                      <Text style={[styles.discountBadge, { fontSize: 7 * ((editForm.discount_scale || 100) / 100) }]}>DISCOUNT</Text>
                    </View>
                  )}
                </View>
                {editForm.store_address ? (
                  <Text style={[styles.addressRow, { fontSize: 8 * ((editForm.address_scale || 100) / 100) }]}>📍 {editForm.store_address}</Text>
                ) : null}
              </View>
            </View>
          </Page>
        )
      )}

      {/* Catalogue Product Table Pages */}
      {productPageChunks.map((chunkItem, chunkIdx) => {
        // Normalize chunkCategories whether chunkItem is an array of raw products or pre-grouped category objects
        const chunkCategories = [];
        const rawItems = Array.isArray(chunkItem) ? chunkItem : [];
        rawItems.forEach((item) => {
          if (!item) return;
          if (item.products && Array.isArray(item.products)) {
            chunkCategories.push(item);
          } else {
            let catGroup = chunkCategories.find((c) => c.id === item.category_id);
            if (!catGroup) {
              catGroup = {
                id: item.category_id || 'general',
                name: item.category_name || 'Category',
                products: [],
              };
              chunkCategories.push(catGroup);
            }
            catGroup.products.push(item);
          }
        });

        chunkCategories.forEach((cat) => {
          if (cat && cat.products) {
            cat.products = sortProductsByCode(cat.products);
          }
        });

        const sortedChunkCategories = sortCategoriesByProductCode(chunkCategories);

        let globalSno = 1;
        for (let c = 0; c < chunkIdx; c++) {
          const prevChunk = productPageChunks[c];
          if (Array.isArray(prevChunk)) {
            globalSno += prevChunk.length;
          }
        }

        return (
          <Page key={chunkIdx} size="A4" style={styles.page}>
            {/* Custom Floating Images overlay on Page 1 for Simpler Layout (Slots 1 to 5) */}
            {editForm.first_page_layout === 'simpler' && chunkIdx === 0 && (
              <>
                {[1, 2, 3, 4, 5].map((i) => {
                  const p = getFloatImgPropsPdf(i);
                  if (!p.url || !p.show) return null;
                  return (
                    <Image
                      key={i}
                      src={p.url}
                      style={{
                        position: 'absolute',
                        left: `${p.x}%`,
                        top: `${p.y}%`,
                        width: `${Math.round(200 * (p.scale / 100))}px`,
                        height: 'auto',
                        zIndex: 35,
                      }}
                    />
                  );
                })}

                {/* Movable & Resizable Offer / Discount Badge Overlay in PDF */}
                {editForm.show_discount_badge !== false && (
                  <View
                    style={{
                      position: 'absolute',
                      left: `${editForm.discount_badge_x !== undefined ? editForm.discount_badge_x : 82}%`,
                      top: `${editForm.discount_badge_y !== undefined ? editForm.discount_badge_y : 2.2}%`,
                      width: Math.round(44 * ((editForm.discount_badge_scale || editForm.discount_scale || 100) / 100)),
                      height: Math.round(44 * ((editForm.discount_badge_scale || editForm.discount_scale || 100) / 100)),
                      alignItems: 'center',
                      justifyContent: 'center',
                      zIndex: 40,
                    }}
                  >
                    <Text style={{ fontSize: Math.round(12 * ((editForm.discount_badge_scale || editForm.discount_scale || 100) / 100)), fontWeight: 'bold', color: '#064e3b' }}>
                      {editForm.discount_percent || 50}%
                    </Text>
                    <Text style={{ fontSize: Math.round(7 * ((editForm.discount_badge_scale || editForm.discount_scale || 100) / 100)), fontWeight: 'bold', color: '#065f46', textTransform: 'uppercase' }}>
                      OFF
                    </Text>
                  </View>
                )}
              </>
            )}
            {/* Simpler Header Box on Page 1 when layout is simpler */}
            {editForm.first_page_layout === 'simpler' && chunkIdx === 0 ? (
              <View style={{ border: '2pt double #065f46', borderRadius: 4, padding: 6, marginBottom: 8, backgroundColor: '#ffffff' }}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', borderBottomWidth: 1, borderBottomColor: '#065f46', paddingBottom: 3, marginBottom: 4 }}>
                  <Text style={{ fontSize: 7, fontWeight: 'bold', color: '#064e3b' }}>GSTIN No: {editForm.gstin || '33ABLFM8150D1ZD'}</Text>
                  <View style={{ flexDirection: 'column', alignItems: 'center' }}>
                    {editForm.store_invocation_symbol ? (
                      <Text style={{ fontSize: 8, fontWeight: 'bold', color: '#064e3b', marginBottom: 1 }}>{editForm.store_invocation_symbol}</Text>
                    ) : null}
                    <Text style={{ fontSize: 7, fontWeight: 'bold', color: '#064e3b' }}>{editForm.store_invocation || 'Sri Sena Kasava Perumal Thunai'}</Text>
                  </View>
                  <Text style={{ fontSize: 7, fontWeight: 'bold', color: '#064e3b' }}>Call: {[editForm.store_phone, editForm.store_phone_2].filter(Boolean).join(', ')}</Text>
                </View>
                <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                  <View style={{ width: '22%', flexDirection: 'row', alignItems: 'center' }}>
                    {deityUrl ? (
                      <Image
                        src={deityUrl}
                        style={{
                          width: Math.min(60, Math.max(20, 36 * ((editForm.simpler_deity_scale || editForm.deity_scale || 100) / 100))),
                          height: Math.min(60, Math.max(20, 36 * ((editForm.simpler_deity_scale || editForm.deity_scale || 100) / 100))),
                          objectFit: 'contain'
                        }}
                      />
                    ) : null}
                  </View>
                  <View style={{
                    width: '56%',
                    textAlign: 'center',
                    transform: `translate(${editForm.shop_info_x || 0}pt, ${editForm.shop_info_y || 0}pt)`,
                  }}>
                    <Text style={{ fontSize: Math.round(13 * ((editForm.shop_info_scale || 100) / 100)), fontWeight: 'bold', color: '#064e3b', textTransform: 'uppercase' }}>{editForm.store_name || 'MASS CRACKERS'}</Text>
                    <Text style={{ fontSize: Math.round(7.5 * ((editForm.shop_info_scale || 100) / 100)), color: '#0f172a', marginTop: 1 }}>{editForm.store_address}</Text>
                    {editForm.store_email ? <Text style={{ fontSize: Math.round(7 * ((editForm.shop_info_scale || 100) / 100)), color: '#065f46', marginTop: 1 }}>Email: {editForm.store_email}</Text> : null}
                    <Text style={{ fontSize: Math.round(7 * ((editForm.shop_info_scale || 100) / 100)), fontWeight: 'bold', color: '#064e3b', fontStyle: 'italic', marginTop: 1 }}>{editForm.store_sub_header_tag || '(ALL Types of Crackers available Whole Sales & Retail)'}</Text>
                  </View>
                  <View style={{ width: '22%', flexDirection: 'row', alignItems: 'center', justifyContent: 'flex-end' }}>
                    <View style={{ width: 30, height: 30, opacity: 0 }} />
                    {deityUrl ? (
                      <Image
                        src={deityUrl}
                        style={{
                          width: Math.min(60, Math.max(20, 36 * ((editForm.simpler_deity_scale || editForm.deity_scale || 100) / 100))),
                          height: Math.min(60, Math.max(20, 36 * ((editForm.simpler_deity_scale || editForm.deity_scale || 100) / 100))),
                          objectFit: 'contain'
                        }}
                      />
                    ) : null}
                  </View>
                </View>
              </View>
            ) : (
              /* Standard Header */
              <View style={styles.header}>
                <View style={styles.headerLeft}>
                  <Text style={styles.headerStoreName}>{editForm.store_name || 'MASS CRACKERS'}</Text>
                  <Text style={styles.headerSub}>OFFICIAL PRICE LIST - {editForm.store_year || '2026'}</Text>
                </View>
                <View style={styles.headerRight}>
                  <Text style={styles.headerPhone}>📞 {[editForm.store_phone, editForm.store_phone_2].filter(Boolean).join(', ')}</Text>
                  {editForm.store_email ? <Text style={{ fontSize: 7, color: '#64748b' }}>{editForm.store_email}</Text> : null}
                </View>
              </View>
            )}

            {/* Table */}
            <View style={styles.table}>
              {/* Header Row */}
              <View style={[styles.tableRow, styles.tableHeaderRow, { backgroundColor: editForm.table_header_bg_color || '#fef3c7' }]}>
                {showSnoPdf && <Text style={[styles.colSno, styles.thText, { color: editForm.table_header_text_color || '#000000', fontSize: editForm.table_header_font_size ? parseFloat(editForm.table_header_font_size) * 0.75 : 9 }]}>{editForm.header_sno || 'S.No'}</Text>}
                {showProductPdf && <Text style={[{ width: colNameWidth, textAlign: 'left', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, paddingLeft: 4 }, styles.thText, { color: editForm.table_header_text_color || '#000000', fontSize: editForm.table_header_font_size ? parseFloat(editForm.table_header_font_size) * 0.75 : 9 }]}>{editForm.header_product || (showTamilPdf ? 'Product Name (ENG)' : 'Product Name')}</Text>}
                {showTamilPdf && <Text style={[{ width: colTamilWidth, textAlign: 'left', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, paddingLeft: 4 }, styles.thText, { color: editForm.table_header_text_color || '#000000', fontSize: editForm.table_header_font_size ? parseFloat(editForm.table_header_font_size) * 0.75 : 9 }]}>{editForm.header_product_ta || 'பொருள் பெயர் (TAMIL)'}</Text>}
                {showUnitPdf && <Text style={[{ width: colPackWidth, textAlign: 'center', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2 }, styles.thText, { color: editForm.table_header_text_color || '#000000', fontSize: editForm.table_header_font_size ? parseFloat(editForm.table_header_font_size) * 0.75 : 9 }]}>{editForm.header_unit || 'Unit'}</Text>}
                {showMrpPdf && <Text style={[{ width: colMrpWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2 }, styles.thText, { color: editForm.table_header_text_color || '#000000', fontSize: editForm.table_header_font_size ? parseFloat(editForm.table_header_font_size) * 0.75 : 9 }]}>{editForm.header_mrp || 'Rate (₹)'}</Text>}
                {showOfferPdf && <Text style={[{ width: colOfferWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2 }, styles.thText, { color: editForm.table_header_text_color || '#000000', fontSize: editForm.table_header_font_size ? parseFloat(editForm.table_header_font_size) * 0.75 : 9 }]}>{editForm.header_offer || `${editForm.discount_percent || 50}% Rate`}</Text>}
                {showReqPdf && <Text style={[styles.colReq, styles.thText, { color: editForm.table_header_text_color || '#000000', fontSize: editForm.table_header_font_size ? parseFloat(editForm.table_header_font_size) * 0.75 : 9 }]}>{editForm.header_req || 'Req'}</Text>}
              </View>

              {/* Category Rows & Products */}
              {sortedChunkCategories.map((category) => {
                const catStyle = editForm.category_header_style || 'airplane_banner';
                const catBg = category.color || category.bg_color || editForm.category_bg_color || '#00a859';
                const rowBgMode = editForm.category_row_bg_mode || 'alternating';
                const rowBgIntensity = editForm.category_row_bg_intensity !== undefined ? editForm.category_row_bg_intensity : 12;

                return (
                  <React.Fragment key={category.id}>
                    {catStyle === 'airplane_banner' ? (
                      <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', width: '100%', minHeight: 28, marginVertical: 2, paddingVertical: 2 }}>
                        <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                          {/* Pill Body */}
                          <View style={{ backgroundColor: catBg, borderTopLeftRadius: 9, borderBottomLeftRadius: 9, minHeight: 18, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 2 }}>
                            <Text style={{ color: '#ffffff', fontSize: 8, fontWeight: 'bold', textTransform: 'uppercase' }}>{category.name}</Text>
                          </View>

                          {/* Pointed Arrow Tail */}
                          <Svg width={9} height={18}>
                            <Path d="M 0 0 L 7 7 C 9 9, 9 9, 7 11 L 0 18 Z" fill={catBg} />
                          </Svg>

                          {/* Wind Lines */}
                          <Svg width={14} height={18} viewBox="0 0 12 15">
                            <Path d="M 1 4.5 L 8 4.5 C 10.5 4.5, 10.5 2, 8.5 2" stroke={catBg} strokeWidth={1} fill="none" strokeLinecap="round" />
                            <Path d="M 1 7.5 L 10 7.5" stroke={catBg} strokeWidth={1} fill="none" strokeLinecap="round" />
                            <Path d="M 1 10.5 L 8 10.5 C 10.5 10.5, 10.5 13, 8.5 13" stroke={catBg} strokeWidth={1} fill="none" strokeLinecap="round" />
                          </Svg>

                          {/* Airplane Silhouette (2x size) */}
                          <Svg width={40} height={36} viewBox="0 0 100 100">
                            {/* Tow line attached to tail */}
                            <Line x1={0} y1={50} x2={20} y2={50} stroke={catBg} strokeWidth={5} strokeLinecap="round" />
                            {/* Airplane Silhouette */}
                            <Path
                              d="M 20 50 L 18 36 L 27 38 L 32 46 L 46 45 L 41 12 C 42 7, 52 7, 57 12 L 65 45 C 78 45, 90 47, 94 50 C 90 53, 78 55, 65 55 L 57 88 C 52 93, 42 93, 41 88 L 46 55 L 32 54 L 27 62 L 18 64 Z"
                              fill={catBg}
                            />
                          </Svg>
                        </View>
                      </View>
                    ) : (
                      <View style={styles.tableRow}>
                        <Text style={[styles.catRow, { backgroundColor: catBg }]}>{category.name}</Text>
                      </View>
                    )}
                  {category.products.map((product, pIdx) => {
                    const currentCode = product.product_code !== null && product.product_code !== undefined ? product.product_code : (globalSno + pIdx);
                    const rowBg = getCategoryRowBg(catBg, pIdx, rowBgMode, rowBgIntensity);

                    return (
                      <View key={product.id || pIdx} style={[styles.tableRow, { backgroundColor: rowBg }]}>
                        {showSnoPdf && <Text style={styles.colSno}>{currentCode}</Text>}
                        {showProductPdf && <Text style={{ width: colNameWidth, textAlign: 'left', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, paddingLeft: 4, fontSize: 8, fontWeight: 'bold' }}>{product.name}</Text>}
                        {showTamilPdf && <Text style={{ width: colTamilWidth, textAlign: 'left', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, paddingLeft: 4, fontSize: 8, fontWeight: 'bold' }}>{product.name_ta || ''}</Text>}
                        {showUnitPdf && <Text style={{ width: colPackWidth, textAlign: 'center', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, fontSize: 8 }}>{product.pack_size}</Text>}
                        {showMrpPdf && <Text style={{ width: colMrpWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, fontSize: 8, textDecoration: editForm.strikethrough_mrp !== false ? 'line-through' : 'none' }}>{product.mrp}</Text>}
                        {showOfferPdf && <Text style={{ width: colOfferWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, fontSize: 8, fontWeight: 'bold', color: '#b91c1c' }}>₹{product.selling_price}</Text>}
                        {showReqPdf && <Text style={styles.colReq}>{product.req || '[   ]'}</Text>}
                      </View>
                    );
                  })}
                </React.Fragment>
              );
            })}
          </View>

            {/* Payment Info Section on Last Page */}
            {editForm.show_footer !== false && editForm.footer_position !== 'disabled' && (editForm.footer_position || 'below_table') === 'below_table' && chunkIdx === productPageChunks.length - 1 && (() => {
              const hasQr2 = !!(upiQrUrl2 || editForm.store_gpay_2);
              const showQr = editForm.show_upi_qr !== false;
              const showBank = editForm.show_bank_details !== false;

              return (
                <View style={{ marginTop: 8, gap: 6 }}>
                  {showQr && (
                    <View style={styles.paymentSection}>
                      {/* QR 1 Card */}
                      <View style={hasQr2 || showBank ? styles.paymentLeft : styles.paymentFull}>
                        <Text style={styles.payTitle}>SCAN & PAY VIA UPI</Text>
                        <View style={{ alignItems: 'center', marginVertical: 3 }}>
                          {upiQrUrl ? (
                            <Image src={upiQrUrl} style={{ width: 60, height: 60, objectFit: 'contain' }} />
                          ) : null}
                          {editForm.store_upi_name && (
                            <Text style={{ fontSize: 6.5, fontWeight: 'bold', color: '#0f172a', marginTop: 2 }}>
                              {editForm.store_upi_name}
                            </Text>
                          )}
                          {(editForm.store_gpay || editForm.store_phone) && (
                            <Text style={{ fontSize: 6.5, fontWeight: 'bold', color: '#0f172a', marginTop: 1 }}>
                              UPI: {editForm.store_gpay || editForm.store_phone || ''}
                            </Text>
                          )}
                        </View>
                      </View>

                      {/* QR 2 Card (If present) */}
                      {hasQr2 && (
                        <View style={styles.paymentRight}>
                          <Text style={styles.payTitle}>SCAN & PAY VIA UPI</Text>
                          <View style={{ alignItems: 'center', marginVertical: 3 }}>
                            {upiQrUrl2 ? (
                              <Image src={upiQrUrl2} style={{ width: 60, height: 60, objectFit: 'contain' }} />
                            ) : null}
                            {editForm.store_upi_name_2 && (
                              <Text style={{ fontSize: 6.5, fontWeight: 'bold', color: '#0f172a', marginTop: 2 }}>
                                {editForm.store_upi_name_2}
                              </Text>
                            )}
                            {editForm.store_gpay_2 && (
                              <Text style={{ fontSize: 6.5, fontWeight: 'bold', color: '#0f172a', marginTop: 1 }}>
                                UPI: {editForm.store_gpay_2}
                              </Text>
                            )}
                          </View>
                        </View>
                      )}

                      {/* Bank Details on Right if ONLY 1 QR code */}
                      {!hasQr2 && showBank && (
                        <View style={styles.paymentRight}>
                          <Text style={styles.payTitle}>BANK ACCOUNT INFO</Text>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>A/C Name:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_name || ''}</Text>
                          </View>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>Bank / Branch:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_branch || ''}</Text>
                          </View>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>Account No:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_account_no || ''}</Text>
                          </View>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>IFSC Code:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_ifsc || ''}</Text>
                          </View>
                        </View>
                      )}
                    </View>
                  )}

                  {/* Bank Details BELOW QR cards if HAS QR 2 */}
                  {hasQr2 && showBank && (
                    <View style={styles.paymentFull}>
                      <Text style={styles.payTitle}>BANK ACCOUNT INFO</Text>
                      <View style={{ flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' }}>
                        <View style={{ width: '48%', marginBottom: 2 }}>
                          <Text style={styles.bankLabel}>A/C Name: <Text style={styles.bankValue}>{editForm.bank_name || ''}</Text></Text>
                        </View>
                        <View style={{ width: '48%', marginBottom: 2 }}>
                          <Text style={styles.bankLabel}>Bank / Branch: <Text style={styles.bankValue}>{editForm.bank_branch || ''}</Text></Text>
                        </View>
                        <View style={{ width: '48%' }}>
                          <Text style={styles.bankLabel}>Account No: <Text style={styles.bankValue}>{editForm.bank_account_no || ''}</Text></Text>
                        </View>
                        <View style={{ width: '48%' }}>
                          <Text style={styles.bankLabel}>IFSC Code: <Text style={styles.bankValue}>{editForm.bank_ifsc || ''}</Text></Text>
                        </View>
                      </View>
                    </View>
                  )}

                  {/* Bank Details full width if NO QR code */}
                  {!showQr && showBank && (
                    <View style={styles.paymentFull}>
                      <Text style={styles.payTitle}>BANK ACCOUNT INFO</Text>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>A/C Name:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_name || ''}</Text>
                      </View>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>Bank / Branch:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_branch || ''}</Text>
                      </View>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>Account No:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_account_no || ''}</Text>
                      </View>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>IFSC Code:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_ifsc || ''}</Text>
                      </View>
                    </View>
                  )}
                </View>
              );
            })()}

            {/* Terms & Conditions Sections */}
            {editForm.show_terms !== false && (
              <View style={{ flexDirection: 'column', gap: 4, marginTop: 4 }}>
                {getPDFTermsSections(editForm).map((sec, sIdx) => {
                  if (sec.show === false) return null;
                  return (
                      <View
                        key={sec.id || sIdx}
                        break={sec.move_to_next_page === true}
                        style={{
                          padding: 5,
                          borderWidth: 1,
                          borderColor: sec.border_color || '#fde047',
                          borderRadius: 4,
                          backgroundColor: sec.bg_color || '#fffbeb'
                        }}
                      >
                      <Text style={{
                        fontSize: sec.title_font_size ? sec.title_font_size * 0.6 : 7,
                        fontWeight: 'bold',
                        color: sec.title_color || '#78350f',
                        marginBottom: 3,
                        textTransform: 'uppercase'
                      }}>
                        {sec.title || 'TERMS & CONDITIONS'}
                      </Text>
                      {renderTermsContentPDF(
                        sec.content || '',
                        sec.text_font_size ? sec.text_font_size * 0.6 : 6,
                        sec.text_color || '#1e293b'
                      )}
                    </View>
                  );
                })}
              </View>
            )}

            {/* Footer */}
            <View style={styles.footer}>
              <Text>{editForm.store_name} - Official Price List</Text>
              <Text>Page {chunkIdx + 2} of {productPageChunks.length + 1 + (editForm.footer_position === 'new_page' ? 1 : 0)}</Text>
            </View>
          </Page>
        );
      })}

      {/* Standalone Back Cover / Footer Page */}
      {editForm.footer_position === 'new_page' && (
        <Page size="A4" style={styles.page}>
          <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', padding: 20 }}>
            <Text style={{ fontSize: 20, fontWeight: 'bold', marginBottom: 15, textTransform: 'uppercase' }}>
              {editForm.store_name}
            </Text>
            <Text style={{ fontSize: 11, fontWeight: 'bold', color: '#b45309', marginBottom: 20 }}>
              Payment Information & Terms & Conditions
            </Text>

            {(() => {
              const hasQr2 = !!(upiQrUrl2 || editForm.store_gpay_2);
              const showQr = editForm.show_upi_qr !== false;
              const showBank = editForm.show_bank_details !== false;

              return (
                <View style={{ width: '100%', gap: 10 }}>
                  {showQr && (
                    <View style={styles.paymentSection}>
                      {/* QR 1 Card */}
                      <View style={hasQr2 || showBank ? styles.paymentLeft : styles.paymentFull}>
                        <Text style={styles.payTitle}>SCAN & PAY VIA UPI</Text>
                        <View style={{ alignItems: 'center', marginVertical: 5 }}>
                          {upiQrUrl ? (
                            <Image src={upiQrUrl} style={{ width: 80, height: 80, objectFit: 'contain' }} />
                          ) : null}
                          {editForm.store_upi_name && (
                            <Text style={{ fontSize: 8, fontWeight: 'bold', color: '#0f172a', marginTop: 3 }}>
                              {editForm.store_upi_name}
                            </Text>
                          )}
                          {(editForm.store_gpay || editForm.store_phone) && (
                            <Text style={{ fontSize: 8, fontWeight: 'bold', color: '#0f172a', marginTop: 1 }}>
                              UPI: {editForm.store_gpay || editForm.store_phone || ''}
                            </Text>
                          )}
                        </View>
                      </View>

                      {/* QR 2 Card (If present) */}
                      {hasQr2 && (
                        <View style={styles.paymentRight}>
                          <Text style={styles.payTitle}>SCAN & PAY VIA UPI</Text>
                          <View style={{ alignItems: 'center', marginVertical: 5 }}>
                            {upiQrUrl2 ? (
                              <Image src={upiQrUrl2} style={{ width: 80, height: 80, objectFit: 'contain' }} />
                            ) : null}
                            {editForm.store_upi_name_2 && (
                              <Text style={{ fontSize: 8, fontWeight: 'bold', color: '#0f172a', marginTop: 3 }}>
                                {editForm.store_upi_name_2}
                              </Text>
                            )}
                            {editForm.store_gpay_2 && (
                              <Text style={{ fontSize: 8, fontWeight: 'bold', color: '#0f172a', marginTop: 1 }}>
                                UPI: {editForm.store_gpay_2}
                              </Text>
                            )}
                          </View>
                        </View>
                      )}

                      {/* Bank Details on Right if ONLY 1 QR code */}
                      {!hasQr2 && showBank && (
                        <View style={styles.paymentRight}>
                          <Text style={styles.payTitle}>BANK ACCOUNT INFO</Text>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>A/C Name:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_name || ''}</Text>
                          </View>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>Bank / Branch:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_branch || ''}</Text>
                          </View>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>Account No:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_account_no || ''}</Text>
                          </View>
                          <View style={styles.bankRow}>
                            <Text style={styles.bankLabel}>IFSC Code:</Text>
                            <Text style={styles.bankValue}>{editForm.bank_ifsc || ''}</Text>
                          </View>
                        </View>
                      )}
                    </View>
                  )}

                  {/* Bank Details BELOW QR cards if HAS QR 2 */}
                  {hasQr2 && showBank && (
                    <View style={styles.paymentFull}>
                      <Text style={styles.payTitle}>BANK ACCOUNT INFO</Text>
                      <View style={{ flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' }}>
                        <View style={{ width: '48%', marginBottom: 3 }}>
                          <Text style={styles.bankLabel}>A/C Name: <Text style={styles.bankValue}>{editForm.bank_name || ''}</Text></Text>
                        </View>
                        <View style={{ width: '48%', marginBottom: 3 }}>
                          <Text style={styles.bankLabel}>Bank / Branch: <Text style={styles.bankValue}>{editForm.bank_branch || ''}</Text></Text>
                        </View>
                        <View style={{ width: '48%' }}>
                          <Text style={styles.bankLabel}>Account No: <Text style={styles.bankValue}>{editForm.bank_account_no || ''}</Text></Text>
                        </View>
                        <View style={{ width: '48%' }}>
                          <Text style={styles.bankLabel}>IFSC Code: <Text style={styles.bankValue}>{editForm.bank_ifsc || ''}</Text></Text>
                        </View>
                      </View>
                    </View>
                  )}

                  {/* Bank Details full width if NO QR code */}
                  {!showQr && showBank && (
                    <View style={styles.paymentFull}>
                      <Text style={styles.payTitle}>BANK ACCOUNT INFO</Text>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>A/C Name:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_name || ''}</Text>
                      </View>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>Bank / Branch:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_branch || ''}</Text>
                      </View>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>Account No:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_account_no || ''}</Text>
                      </View>
                      <View style={styles.bankRow}>
                        <Text style={styles.bankLabel}>IFSC Code:</Text>
                        <Text style={styles.bankValue}>{editForm.bank_ifsc || ''}</Text>
                      </View>
                    </View>
                  )}
                </View>
              );
            })()}

            {/* Terms & Conditions Sections */}
            {editForm.show_terms !== false && (
              <View style={{ flexDirection: 'column', gap: 5, marginTop: 6 }}>
                {getPDFTermsSections(editForm).map((sec, sIdx) => {
                  if (sec.show === false) return null;
                  return (
                      <View
                        key={sec.id || sIdx}
                        break={sec.move_to_next_page === true}
                        style={{
                          padding: 6,
                          borderWidth: 1,
                          borderColor: sec.border_color || '#fde047',
                          borderRadius: 4,
                          backgroundColor: sec.bg_color || '#fffbeb'
                        }}
                      >
                      <Text style={{
                        fontSize: sec.title_font_size ? sec.title_font_size * 0.65 : 8,
                        fontWeight: 'bold',
                        color: sec.title_color || '#78350f',
                        marginBottom: 4,
                        textTransform: 'uppercase'
                      }}>
                        {sec.title || 'TERMS & CONDITIONS'}
                      </Text>
                      {renderTermsContentPDF(
                        sec.content || '',
                        sec.text_font_size ? sec.text_font_size * 0.65 : 7,
                        sec.text_color || '#1e293b'
                      )}
                    </View>
                  );
                })}
              </View>
            )}
          </View>

          <View style={styles.footer}>
            <Text>{editForm.store_name} - Official Price List</Text>
            <Text>Page {productPageChunks.length + 2} of {productPageChunks.length + 2}</Text>
          </View>
        </Page>
      )}
    </Document>
  );
};

export async function generateReactPDFBlob(editForm, productPageChunks, showMrp, getImageUrl, colWidths) {
  const doc = (
    <PriceListPDFDocument
      editForm={editForm}
      productPageChunks={productPageChunks}
      showMrp={showMrp}
      getImageUrl={getImageUrl}
      colWidths={colWidths}
    />
  );
  return await pdf(doc).toBlob();
}
