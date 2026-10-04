import React from 'react';

export const DirectCashModal = ({
  isOpen,
  onClose,
  finalAmount,
  payments,
  directCashInput,
  setDirectCashInput,
  formatCurrency,
  formatThousandSeparator,
  onPay
}) => {
  if (!isOpen) return null;

  const remaining = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);

  const handlePay = () => {
    const val = parseFloat(directCashInput.replace(/\./g, ''));
    if (!isNaN(val) && val !== 0) {
      const changeAmt = val - remaining;
      if (changeAmt > 100000) {
        if (!window.confirm(`Peringatan: Kembalian terlalu besar (Rp ${formatThousandSeparator(changeAmt)}). Lanjutkan transaksi?`)) return;
      }
      onPay(val);
    }
  };

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Nominal Tunai</h3>
        <p style={{ textAlign: 'center', fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem' }}>
          Tagihan: {formatCurrency(remaining)}
        </p>
        <input
          type="text"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          value={directCashInput}
          maxLength={11}
          onChange={(e) => {
            setDirectCashInput(formatThousandSeparator(e.target.value));
          }}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              handlePay();
            } else if (e.key === 'Escape') {
              onClose();
            }
          }}
          autoFocus
          onFocus={(e) => e.target.select()}
        />
        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onClose}>BATAL (Esc)</button>
          <button className="btn-success" style={{ flex: 1 }} onClick={handlePay}>BAYAR (Enter)</button>
        </div>
      </div>
    </div>
  );
};
