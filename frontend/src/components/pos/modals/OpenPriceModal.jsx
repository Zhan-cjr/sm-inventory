import React from 'react';

export const OpenPriceModal = ({
  isOpen,
  openPriceTargetItem,
  newOpenPrice,
  setNewOpenPrice,
  formatCurrency,
  onSubmit,
  onCancel
}) => {
  if (!isOpen || !openPriceTargetItem) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Open Price</h3>
        <div style={{ textAlign: 'center', marginBottom: '1rem', fontWeight: 'bold' }}>{openPriceTargetItem.name}</div>
        <div style={{ textAlign: 'center', marginBottom: '1rem', fontSize: '0.85rem' }}>
          Harga Asli: {formatCurrency(openPriceTargetItem.originalUnitPrice || openPriceTargetItem.unitPrice)}
        </div>
        <input
          type="text"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          placeholder="Harga Baru"
          value={newOpenPrice}
          onChange={(e) => setNewOpenPrice(e.target.value.replace(/[^0-9]/g, ''))}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              onSubmit();
            } else if (e.key === 'Escape') {
              onCancel();
            }
          }}
          autoFocus
        />
        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onCancel}>BATAL (Esc)</button>
        </div>
      </div>
    </div>
  );
};
