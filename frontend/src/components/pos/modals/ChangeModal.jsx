import React from 'react';
import { CheckCircle } from 'lucide-react';

export const ChangeModal = ({
  changeModalInfo,
  onClose,
  onPrintReceipt,
  formatCurrency,
  autoPrint
}) => {
  if (!changeModalInfo) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in">
        <CheckCircle size={64} className="text-online" />
        <h2>Transaksi Berhasil!</h2>
        <div className="change-amount-display">
          <label>KEMBALIAN</label>
          <div className="amount">{formatCurrency(changeModalInfo.amount)}</div>
        </div>
        <div style={{ display: 'flex', gap: '1rem', marginTop: '2rem' }}>
          <button className="btn-secondary" onClick={onClose}>TUTUP (ESC)</button>
          <button className="btn-primary" onClick={onPrintReceipt}>
            {autoPrint ? 'CETAK (ENTER)' : 'PREVIEW (ENTER)'}
          </button>
        </div>
      </div>
    </div>
  );
};
