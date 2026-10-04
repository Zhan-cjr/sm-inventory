import React from 'react';

export const QtyModal = ({
  isOpen,
  nextItemQty,
  setNextItemQty,
  onConfirm,
  onCancel
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
          Masukkan Qty Barang
        </h3>
        <input
          type="number"
          step="any"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          placeholder="1"
          value={nextItemQty}
          onChange={(e) => setNextItemQty(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              onConfirm();
            } else if (e.key === 'Escape') {
              onCancel();
            }
          }}
          autoFocus
          onFocus={(e) => e.target.select()}
        />
        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onCancel}>BATAL (Esc)</button>
          <button className="btn-success" style={{ flex: 1 }} onClick={onConfirm}>OK (Enter)</button>
        </div>
      </div>
    </div>
  );
};
