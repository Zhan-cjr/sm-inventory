import React from 'react';

export const VoucherModal = ({
  isOpen,
  voucherInput,
  setVoucherInput,
  onProcessVoucher,
  onCancel
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Validasi Voucher</h3>
        <input
          type="text"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.25rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          placeholder="Masukkan Kode Voucher"
          value={voucherInput}
          onChange={(e) => setVoucherInput(e.target.value.toUpperCase())}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              onProcessVoucher();
            } else if (e.key === 'Escape') {
              onCancel();
            }
          }}
          autoFocus
        />
        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onCancel}>BATAL (Esc)</button>
          <button className="btn-success" style={{ flex: 1 }} onClick={onProcessVoucher}>PROSES (Enter)</button>
        </div>
      </div>
    </div>
  );
};
