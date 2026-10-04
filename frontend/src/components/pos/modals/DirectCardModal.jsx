import React from 'react';

export const DirectCardModal = ({
  isOpen,
  onClose,
  selectedBank,
  finalAmount,
  payments,
  directCardInput,
  setDirectCardInput,
  formatCurrency,
  formatThousandSeparator,
  onPay
}) => {
  if (!isOpen) return null;

  const remaining = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
  const minRequired = parseFloat(selectedBank?.min_transaction_amount) || (selectedBank?.type === 'QRIS' ? 20000 : 50000);

  const handlePay = () => {
    const val = parseFloat(directCardInput.replace(/\./g, ''));
    if (!isNaN(val) && val !== 0) {
      if (val < minRequired) {
        onPay(null, minRequired, val);
        return;
      }
      onPay(val);
    }
  };

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '0.5rem' }}>Nominal Pembayaran {selectedBank?.name}</h3>
        <div style={{ textAlign: 'center', marginBottom: '1rem' }}>
          <span style={{ 
            fontSize: '0.75rem', 
            fontWeight: '700', 
            color: '#f59e0b', 
            background: 'rgba(245,158,11,0.12)', 
            border: '1px solid rgba(245,158,11,0.3)',
            padding: '3px 10px', 
            borderRadius: '6px' 
          }}>
            Minimal Transaksi: {formatCurrency(minRequired)}
          </span>
        </div>
        <p style={{ textAlign: 'center', fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem' }}>
          Sisa Tagihan: {formatCurrency(remaining)}
        </p>
        <input
          type="text"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          value={directCardInput}
          onChange={(e) => {
            setDirectCardInput(formatThousandSeparator(e.target.value));
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
