import React from 'react';
import { Document, Page, Text, View, Image, StyleSheet, pdf } from '@react-pdf/renderer';

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
  },
  invocationSymbol: {
    fontSize: 14,
    color: '#ffffff',
    fontWeight: 'bold',
    marginBottom: 2,
  },
  invocationText: {
    fontSize: 9,
    color: '#ffffff',
    fontWeight: 'bold',
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
    fontSize: 8,
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

export const PriceListPDFDocument = ({ editForm, productPageChunks, showMrp, getImageUrl }) => {
  const resolveUrl = (path) => {
    if (!path) return null;
    const url = getImageUrl(path);
    if (!url) return null;
    if (url.startsWith('http') || url.startsWith('data:')) return url;
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    return origin + (url.startsWith('/') ? url : '/' + url);
  };

  const coverBgUrl = resolveUrl(editForm.store_cover_bg || '/images/cover_bg_1.jpg');
  const deityUrl = resolveUrl(editForm.store_deity_image);
  const logoUrl = resolveUrl(editForm.store_logo);
  const upiQrUrl = resolveUrl(editForm.store_upi_qr);

  const showSnoPdf = editForm.show_col_sno !== false;
  const showProductPdf = editForm.show_col_product !== false;
  const showUnitPdf = editForm.show_col_unit !== false;
  const showMrpPdf = showMrp && editForm.show_col_mrp !== false;
  const showOfferPdf = editForm.show_col_offer !== false;
  const showReqPdf = editForm.show_col_req !== false;

  const colSnoWidth = showSnoPdf ? '7%' : '0%';
  const colReqWidth = showReqPdf ? '7%' : '0%';
  const colPackWidth = showUnitPdf ? '16%' : '0%';
  const colMrpWidth = showMrpPdf ? '14%' : '0%';
  const colOfferWidth = showOfferPdf ? '16%' : '0%';

  let fixedPct = (showSnoPdf ? 7 : 0) + (showReqPdf ? 7 : 0) + (showUnitPdf ? 16 : 0) + (showMrpPdf ? 14 : 0) + (showOfferPdf ? 16 : 0);
  const colNameWidth = showProductPdf ? `${Math.max(20, 100 - fixedPct)}%` : '0%';

  return (
    <Document title={`${editForm.store_name || 'PriceList'}_Catalogue`}>
      {/* Cover Page */}
      <Page size="A4" style={styles.coverPage}>
        {coverBgUrl && <Image src={coverBgUrl} style={styles.coverBg} />}
        <View style={styles.coverOverlay}>
          {/* Top Invocation */}
          <View style={styles.coverTop}>
            {editForm.store_invocation_symbol ? (
              <Text style={styles.invocationSymbol}>{editForm.store_invocation_symbol}</Text>
            ) : null}
            {editForm.store_invocation ? (
              <Text style={styles.invocationText}>{editForm.store_invocation}</Text>
            ) : null}
          </View>

          {/* Center Brand */}
          <View style={styles.coverCenter}>
            <Text style={styles.storeName}>{editForm.store_name || 'MASS CRACKERS'}</Text>
            {editForm.store_tagline ? (
              <Text style={styles.storeTagline}>"{editForm.store_tagline}"</Text>
            ) : null}
            <Text style={styles.priceListBadge}>PRICE LIST - {editForm.store_year || '2026'}</Text>
          </View>

          {/* Center Deity Motif Image */}
          {deityUrl ? (
            <View style={styles.deityImageContainer}>
              <Image src={deityUrl} style={styles.deityImg} />
            </View>
          ) : (
            <View style={{ flex: 1 }} />
          )}

          {/* Bottom Order Banner */}
          <View style={{ width: '100%' }}>
            <View style={styles.coverBanner}>
              <View style={styles.coverBannerLeft}>
                {logoUrl ? (
                  <Image src={logoUrl} style={styles.coverLogo} />
                ) : (
                  <Text style={{ fontSize: 12, fontWeight: 'bold', color: '#991b1b' }}>{editForm.store_name}</Text>
                )}
              </View>
              <View style={styles.coverBannerCenter}>
                {editForm.store_email ? (
                  <Text style={styles.contactItem}>🌐 {editForm.store_email}</Text>
                ) : null}
                <Text style={styles.contactItem}>
                  📞 {[editForm.store_phone, editForm.store_phone_2].filter(Boolean).join(', ')}
                </Text>
                {editForm.store_gpay ? (
                  <Text style={styles.contactItem}>💳 GPay: {editForm.store_gpay}</Text>
                ) : null}
              </View>
              <View style={styles.coverBannerRight}>
                <Text style={styles.megaSaleText}>MEGA SALE</Text>
                <Text style={styles.discountVal}>{editForm.discount_percent || 50}%</Text>
                <Text style={styles.discountBadge}>DISCOUNT</Text>
              </View>
            </View>
            {editForm.store_address ? (
              <Text style={styles.addressRow}>📍 {editForm.store_address}</Text>
            ) : null}
          </View>
        </View>
      </Page>

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

        let globalSno = 1;
        for (let c = 0; c < chunkIdx; c++) {
          const prevChunk = productPageChunks[c];
          if (Array.isArray(prevChunk)) {
            globalSno += prevChunk.length;
          }
        }

        return (
          <Page key={chunkIdx} size="A4" style={styles.page}>
            {/* Header */}
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

            {/* Table */}
            <View style={styles.table}>
              {/* Header Row */}
              <View style={[styles.tableRow, styles.tableHeaderRow]}>
                {showSnoPdf && <Text style={[styles.colSno, styles.thText]}>{editForm.header_sno || 'S.No'}</Text>}
                {showProductPdf && <Text style={[{ width: colNameWidth, textAlign: 'left', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, paddingLeft: 4 }, styles.thText]}>{editForm.header_product || 'Product Name'}</Text>}
                {showUnitPdf && <Text style={[{ width: colPackWidth, textAlign: 'center', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2 }, styles.thText]}>{editForm.header_unit || 'Unit'}</Text>}
                {showMrpPdf && <Text style={[{ width: colMrpWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2 }, styles.thText]}>{editForm.header_mrp || 'Rate (₹)'}</Text>}
                {showOfferPdf && <Text style={[{ width: colOfferWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2 }, styles.thText]}>{editForm.header_offer || `${editForm.discount_percent || 50}% Rate`}</Text>}
                {showReqPdf && <Text style={[styles.colReq, styles.thText]}>{editForm.header_req || 'Req'}</Text>}
              </View>

              {/* Category Rows & Products */}
              {chunkCategories.map((category) => (
                <React.Fragment key={category.id}>
                  <View style={styles.tableRow}>
                    <Text style={styles.catRow}>{category.name}</Text>
                  </View>
                  {category.products.map((product, pIdx) => {
                    const currentCode = product.product_code !== null && product.product_code !== undefined ? product.product_code : (globalSno + pIdx);

                    return (
                      <View key={product.id || pIdx} style={[styles.tableRow, { backgroundColor: pIdx % 2 === 1 ? '#f8fafc' : '#ffffff' }]}>
                        {showSnoPdf && <Text style={styles.colSno}>{currentCode}</Text>}
                        {showProductPdf && <Text style={{ width: colNameWidth, textAlign: 'left', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, paddingLeft: 4, fontSize: 8, fontWeight: 'bold' }}>{product.name}</Text>}
                        {showUnitPdf && <Text style={{ width: colPackWidth, textAlign: 'center', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, fontSize: 8 }}>{product.pack_size}</Text>}
                        {showMrpPdf && <Text style={{ width: colMrpWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, fontSize: 8 }}>{product.mrp}</Text>}
                        {showOfferPdf && <Text style={{ width: colOfferWidth, textAlign: 'right', borderRightWidth: 1, borderRightColor: '#cbd5e1', padding: 2, fontSize: 8, fontWeight: 'bold', color: '#b91c1c' }}>₹{product.selling_price}</Text>}
                        {showReqPdf && <Text style={styles.colReq}>{product.req || '[   ]'}</Text>}
                      </View>
                    );
                  })}
                </React.Fragment>
              ))}
            </View>

            {/* Payment Info Section on Last Page */}
            {(editForm.footer_position || 'below_table') === 'below_table' && chunkIdx === productPageChunks.length - 1 && (
              <View style={styles.paymentSection}>
                {editForm.show_upi_qr !== false && (
                  <View style={editForm.show_bank_details !== false ? styles.paymentLeft : styles.paymentFull}>
                    <Text style={styles.payTitle}>SCAN & PAY VIA UPI</Text>
                    {upiQrUrl ? (
                      <Image src={upiQrUrl} style={styles.qrImg} />
                    ) : null}
                    <Text style={{ fontSize: 7, fontWeight: 'bold', color: '#0f172a' }}>
                      GPay / PhonePe / Paytm: {editForm.store_gpay || editForm.store_phone || ''}
                    </Text>
                  </View>
                )}

                {editForm.show_bank_details !== false && (
                  <View style={editForm.show_upi_qr !== false ? styles.paymentRight : styles.paymentFull}>
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

            <View style={styles.paymentSection}>
              {editForm.show_upi_qr !== false && (
                <View style={editForm.show_bank_details !== false ? styles.paymentLeft : styles.paymentFull}>
                  <Text style={styles.payTitle}>SCAN & PAY VIA UPI</Text>
                  {upiQrUrl ? (
                    <Image src={upiQrUrl} style={styles.qrImg} />
                  ) : null}
                  <Text style={{ fontSize: 8, fontWeight: 'bold', color: '#0f172a', marginTop: 5 }}>
                    GPay / PhonePe / Paytm: {editForm.store_gpay || editForm.store_phone || ''}
                  </Text>
                </View>
              )}

              {editForm.show_bank_details !== false && (
                <View style={editForm.show_upi_qr !== false ? styles.paymentRight : styles.paymentFull}>
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

export async function generateReactPDFBlob(editForm, productPageChunks, showMrp, getImageUrl) {
  const doc = (
    <PriceListPDFDocument
      editForm={editForm}
      productPageChunks={productPageChunks}
      showMrp={showMrp}
      getImageUrl={getImageUrl}
    />
  );
  return await pdf(doc).toBlob();
}
