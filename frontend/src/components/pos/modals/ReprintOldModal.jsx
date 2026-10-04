import React from 'react';

export const ReprintOldModal = ({
  isOpen,
  oldReceiptInput,
  setOldReceiptInput,
  onSubmit,
  onCancel
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
          Reprint Nota Lama
        </h3>
        <p style={{ textAlign: 'center', color: '#6b7280', marginBottom: '1rem', fontSize: '0.875rem' }}>
          Masukkan nomor struk (Misal: SMI-ABCD12)
        </p>
        <input
          type="text"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.25rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          placeholder="SMI-..."
          value={oldReceiptInput}
          onChange={(e) => setOldReceiptInput(e.target.value)}
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
