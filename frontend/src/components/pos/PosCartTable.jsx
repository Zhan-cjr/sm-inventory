import React from 'react';
import { ShoppingCart, Search, Trash2 } from 'lucide-react';

const PosCartTable = ({
  barcodeInput,
  items,
  inputValue,
  handleInputChange,
  highlightedIndex,
  setHighlightedIndex,
  searchResults,
  addItemToTransaction,
  handleClearInput,
  handleBarcodeScan,
  formatCurrency,
  lastScannedProductId,
  requestAuthorization,
  removeItem,
  subtotal,
  totalDiscount,
  manualTotalDiscount,
  selectedCustomer,
  pointRedemptionEnabled,
  minimumPointsToRedeem,
  setPointsToRedeemInput,
  setIsRedeemPointModalOpen,
  finalAmount,
  isSubtotalMode,
  totalPaid,
  changeAmount
}) => {
  return (
    <main
      className="pos-cart-container"
      style={{
        position: 'relative',
        zIndex: 10,
        display: 'flex',
        flexDirection: 'column',
        height: '100%',
        minHeight: 0,
        overflow: 'hidden'
      }}
    >
      <div
        className="cart-header"
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '0.75rem',
          flexShrink: 0
        }}
      >
        <h3 style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', margin: 0 }}>
          <ShoppingCart size={20} /> Keranjang Belanja
        </h3>
        <span className="item-count">
          {items.length} Items | {items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0)} Qty
        </span>
      </div>

      {/* TOP BARCODE SCANNER INPUT AREA */}
      <div
        className="barcode-input-wrapper-top"
        style={{ marginBottom: '1rem', position: 'relative', zIndex: 9999, flexShrink: 0 }}
      >
        <div
          className="input-icon"
          style={{
            position: 'absolute',
            left: '1rem',
            top: '50%',
            transform: 'translateY(-50%)',
            color: 'var(--primary)',
            zIndex: 5
          }}
        >
          <Search size={22} />
        </div>
        <input
          ref={barcodeInput}
          type="text"
          className="modern-barcode-input"
          style={{
            width: '100%',
            paddingLeft: '3.2rem',
            paddingRight: '1rem',
            height: '3.2rem',
            fontSize: '1.1rem',
            fontWeight: '600',
            borderRadius: '12px',
            border: '2px solid var(--primary)',
            outline: 'none',
            background: 'var(--bg-card)',
            color: 'var(--text-main)'
          }}
          value={inputValue}
          placeholder="⚡ Scan Barcode / Cari Nama Produk / SKU... [Tekan ENTER]"
          onChange={(e) => handleInputChange(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'ArrowDown') {
              e.preventDefault();
              if (highlightedIndex < searchResults.length - 1) {
                const newIndex = highlightedIndex + 1;
                setHighlightedIndex(newIndex);
                setTimeout(() => {
                  const elItems = document.querySelectorAll('.search-item');
                  if (elItems[newIndex]) elItems[newIndex].scrollIntoView({ block: 'nearest' });
                }, 0);
              }
            } else if (e.key === 'ArrowUp') {
              e.preventDefault();
              if (highlightedIndex > 0) {
                const newIndex = highlightedIndex - 1;
                setHighlightedIndex(newIndex);
                setTimeout(() => {
                  const elItems = document.querySelectorAll('.search-item');
                  if (elItems[newIndex]) elItems[newIndex].scrollIntoView({ block: 'nearest' });
                }, 0);
              }
            } else if (e.key === 'Enter') {
              if (highlightedIndex >= 0 && highlightedIndex < searchResults.length) {
                addItemToTransaction(searchResults[highlightedIndex]);
                handleClearInput();
              } else if (searchResults.length === 1) {
                addItemToTransaction(searchResults[0]);
                handleClearInput();
              } else {
                handleBarcodeScan(e.target.value);
              }
            }
            if (e.key === 'Escape') handleClearInput();
          }}
          autoFocus
        />
        {searchResults.length > 0 && (
          <div
            className="search-results-floating fade-in"
            style={{
              top: '100%',
              left: 0,
              right: 0,
              maxHeight: '360px',
              overflowY: 'auto',
              zIndex: 99999,
              position: 'absolute',
              background: '#ffffff',
              border: '2px solid #059669',
              borderRadius: '12px',
              marginTop: '6px'
            }}
          >
            {searchResults.map((p, index) => (
              <div
                key={p.id}
                className={`search-item ${index === highlightedIndex ? 'highlighted' : ''}`}
                style={{
                  padding: '12px 16px',
                  cursor: 'pointer',
                  borderBottom: '1px solid #e2e8f0',
                  backgroundColor: index === highlightedIndex ? '#dbeafe' : '#ffffff',
                  borderLeft: index === highlightedIndex ? '5px solid #059669' : '5px solid transparent',
                  transition: 'all 0.1s ease-in-out',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center'
                }}
                onClick={() => {
                  addItemToTransaction(p);
                  handleClearInput();
                }}
              >
                <div style={{ display: 'flex', flexDirection: 'column' }}>
                  <span className="sku" style={{ fontWeight: '700', color: '#0f172a', fontSize: '0.95rem' }}>
                    {p.sku}
                  </span>
                  <span className="name" style={{ fontSize: '0.85rem', color: '#334155' }}>
                    {p.name}
                  </span>
                </div>
                <span className="price" style={{ fontWeight: '700', color: '#059669', fontSize: '1rem' }}>
                  {formatCurrency(p.selling_price)}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="cart-table-wrapper" style={{ flex: 1, minHeight: 0, overflowY: 'auto' }}>
        <table className="modern-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Kode / Nama Barang</th>
              <th>Harga</th>
              <th>Qty</th>
              <th>Total</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan="6" className="empty-state">
                  <div className="empty-content">
                    <Search size={48} />
                    <p>Belum ada produk. Silakan scan barcode atau ketik SKU pada kolom di atas.</p>
                  </div>
                </td>
              </tr>
            ) : (
              items.map((item, index) => (
                <tr
                  key={item.productId}
                  className={`cart-row animate-slide-in ${item.productId === lastScannedProductId ? 'highlight-row' : ''}`}
                >
                  <td>{index + 1}</td>
                  <td>
                    <div className="prod-cell">
                      <span className="sku">{item.sku}</span>
                      <span className="name">{item.name}</span>
                      {item.manualDiscount > 0 && (
                        <span className="item-discount-tag">
                          Manual Disc: -{formatCurrency(item.manualDiscount)}
                        </span>
                      )}
                    </div>
                  </td>
                  <td>{formatCurrency(item.unitPrice)}</td>
                  <td style={{ textAlign: 'center', verticalAlign: 'middle' }}>
                    <span style={{ fontWeight: 'bold', fontSize: '1.1rem' }}>{item.quantity}</span>
                  </td>
                  <td className="subtotal-cell">
                    {formatCurrency(
                      item.quantity * item.unitPrice - item.quantity * (item.manualDiscount || 0)
                    )}
                  </td>
                  <td>
                    <button
                      className="btn-remove-item"
                      onClick={() => requestAuthorization('VOID', () => removeItem(item.productId))}
                    >
                      <Trash2 size={16} />
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* BOTTOM SUMMARY BAR */}
      <div
        className="bottom-summary-bar fade-in"
        style={{
          flexShrink: 0,
          display: 'flex',
          flexDirection: 'column',
          gap: '0.5rem',
          marginTop: '1rem',
          background: 'rgba(5, 150, 105, 0.08)',
          padding: '1rem 1.25rem',
          borderRadius: '14px',
          border: '1.5px solid rgba(5, 150, 105, 0.3)'
        }}
      >
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            borderBottom: '1px dashed var(--border-light)',
            paddingBottom: '0.5rem'
          }}
        >
          <div style={{ display: 'flex', gap: '1.5rem', fontSize: '0.95rem', color: 'var(--text-main)' }}>
            <div>
              <span style={{ color: 'var(--text-muted)' }}>Subtotal: </span>
              <span style={{ fontWeight: '700' }}>{formatCurrency(subtotal)}</span>
            </div>
            {totalDiscount + manualTotalDiscount > 0 && (
              <div>
                <span style={{ color: 'var(--text-muted)' }}>Diskon: </span>
                <span style={{ fontWeight: '700', color: 'var(--danger)' }}>
                  -{formatCurrency(totalDiscount + manualTotalDiscount)}
                </span>
              </div>
            )}
          </div>

          {selectedCustomer && (
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.85rem' }}>
              <span
                style={{
                  background: 'var(--primary)',
                  color: 'white',
                  padding: '2px 6px',
                  borderRadius: '4px',
                  fontWeight: 'bold'
                }}
              >
                {selectedCustomer.member_tier}
              </span>
              <span style={{ fontWeight: '600', color: 'var(--text-main)' }}>{selectedCustomer.name}</span>
              <span style={{ color: 'var(--text-muted)' }}>(Pts: {selectedCustomer.points})</span>
              {pointRedemptionEnabled && selectedCustomer.points >= minimumPointsToRedeem && (
                <button
                  type="button"
                  onClick={() => {
                    setPointsToRedeemInput('');
                    setIsRedeemPointModalOpen(true);
                  }}
                  style={{
                    marginLeft: '0.25rem',
                    padding: '3px 10px',
                    fontSize: '0.75rem',
                    borderRadius: '6px',
                    background: 'var(--primary)',
                    color: 'white',
                    border: 'none',
                    cursor: 'pointer',
                    fontWeight: 'bold',
                    transition: 'opacity 0.2s'
                  }}
                  onMouseOver={(e) => (e.currentTarget.style.opacity = '0.85')}
                  onMouseOut={(e) => (e.currentTarget.style.opacity = '1')}
                >
                  Tukar Poin
                </button>
              )}
            </div>
          )}
        </div>

        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            paddingTop: '0.25rem'
          }}
        >
          <div
            className="grand-total-box"
            style={{ border: 'none', padding: 0, marginTop: 0, background: 'transparent' }}
          >
            <label
              style={{
                fontSize: '0.8rem',
                letterSpacing: '1px',
                color: 'var(--text-muted)',
                marginBottom: '2px',
                display: 'block',
                fontWeight: '700'
              }}
            >
              GRAND TOTAL
            </label>
            <div
              className="total-amount"
              style={{ fontSize: '2.6rem', lineHeight: '1', fontWeight: '900', color: '#10b981' }}
            >
              {formatCurrency(finalAmount)}
            </div>
          </div>

          {isSubtotalMode && (
            <div style={{ display: 'flex', gap: '2rem', alignItems: 'center' }}>
              <div style={{ textAlign: 'right' }}>
                <label
                  style={{
                    fontSize: '0.75rem',
                    color: 'var(--text-muted)',
                    display: 'block',
                    marginBottom: '2px',
                    fontWeight: '600'
                  }}
                >
                  DITERIMA
                </label>
                <div
                  style={{
                    fontSize: '1.3rem',
                    fontWeight: '700',
                    color: 'var(--text-main)',
                    lineHeight: '1'
                  }}
                >
                  {formatCurrency(totalPaid || 0)}
                </div>
              </div>
              <div style={{ textAlign: 'right' }}>
                <label
                  style={{
                    fontSize: '0.75rem',
                    color: 'var(--text-muted)',
                    display: 'block',
                    marginBottom: '2px',
                    fontWeight: '600'
                  }}
                >
                  KEMBALI
                </label>
                <div
                  className={changeAmount >= 0 ? 'text-online' : 'text-danger'}
                  style={{ fontSize: '1.6rem', fontWeight: '800', lineHeight: '1' }}
                >
                  {formatCurrency(changeAmount)}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </main>
  );
};

export default PosCartTable;
