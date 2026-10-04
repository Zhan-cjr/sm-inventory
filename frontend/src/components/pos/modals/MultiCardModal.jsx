import React from 'react';

export const MultiCardModal = ({
  isOpen,
  onClose,
  finalAmount,
  payments,
  multiCardInput,
  setMultiCardInput,
  formatCurrency,
  formatThousandSeparator,
  onNext
}) => {
  if (!isOpen) return null;

  const remaining = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);

  const handleNext = () => {
    const val = parseFloat(multiCardInput.replace(/\./g, ''));
    if (!isNaN(val) && val > 0) {
      onNext(val);
    }
  };

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Input Nominal Card</h3>
        <p style={{ textAlign: 'center', fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem' }}>
          Sisa Tagihan: {formatCurrency(remaining)}
        </p>
        <input
          type="text"
          className="modern-barcode-input"
          style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
          placeholder="0"
          value={multiCardInput}
          onChange={(e) => setMultiCardInput(formatThousandSeparator(e.target.value))}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              handleNext();
            } else if (e.key === 'Escape') {
              onClose();
            }
          }}
          autoFocus
          onFocus={(e) => e.target.select()}
        />
        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onClose}>BATAL (Esc)</button>
          <button className="btn-success" style={{ flex: 1 }} onClick={handleNext}>LANJUT (Enter)</button>
        </div>
      </div>
    </div>
  );
};
