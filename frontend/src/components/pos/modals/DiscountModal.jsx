import React from 'react';

export const DiscountModal = ({
  discountModal,
  discountInputVal,
  setDiscountInputVal,
  formatThousandSeparator,
  onApply,
  onCancel
}) => {
  if (!discountModal) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
          Masukkan Nilai Diskon {discountModal.target === 'TOTAL' ? 'Total' : 'Item'} ({discountModal.type === 'PERCENT' ? 'Persen %' : 'Nominal Rp'})
        </h3>
        <input
          type="text"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          placeholder="0"
          value={discountInputVal}
          onChange={(e) => {
            if (discountModal?.type === 'RUPIAH') {
              setDiscountInputVal(formatThousandSeparator(e.target.value));
            } else {
              setDiscountInputVal(e.target.value.replace(/[^0-9.]/g, ''));
            }
          }}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              onApply();
            } else if (e.key === 'Escape') {
              onCancel();
            }
          }}
          autoFocus
        />
        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onCancel}>BATAL (Esc)</button>
          <button className="btn-success" style={{ flex: 1 }} onClick={onApply}>OK (Enter)</button>
        </div>
      </div>
    </div>
  );
};
