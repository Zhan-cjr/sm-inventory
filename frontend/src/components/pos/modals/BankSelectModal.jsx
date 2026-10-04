import React from 'react';
import { CreditCard } from 'lucide-react';

export const BankSelectModal = ({
  isOpen,
  onClose,
  banks,
  formatCurrency,
  onSelectBank
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content bank-select-card fade-in">
        <CreditCard size={48} className="text-primary" />
        <h2>Pilih Bank / Mesin EDC / QRIS</h2>
        <div className="bank-grid-large">
          {banks.map(bank => {
            const minReq = parseFloat(bank.min_transaction_amount) || (bank.type === 'QRIS' ? 20000 : 50000);
            return (
              <button key={bank.id} className="bank-item-btn" onClick={() => onSelectBank(bank)}>
                <span className="bank-name">{bank.name}</span>
                <div style={{ display: 'flex', gap: '6px', alignItems: 'center', marginTop: '4px', flexWrap: 'wrap', justifyContent: 'center' }}>
                  <span className={`bank-type-badge ${bank.type === 'QRIS' ? 'qris' : (bank.type === 'TRANSFER' ? 'transfer' : 'edc')}`}>
                    {bank.type || 'EDC'}
                  </span>
                  <span className="bank-min-badge">
                    Min. {formatCurrency(minReq)}
                  </span>
                </div>
              </button>
            );
          })}
        </div>
        <button className="btn-secondary" onClick={onClose}>BATAL (ESC)</button>
      </div>
    </div>
  );
};

export const MultiBankSelectModal = ({
  isOpen,
  onClose,
  banks,
  pendingCardAmount,
  formatCurrency,
  onSelectBank
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content bank-select-card fade-in">
        <CreditCard size={48} className="text-primary" />
        <h2>Pilih Bank / Mesin EDC / QRIS (Multi Payment)</h2>
        <p style={{ fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem', textAlign: 'center' }}>
          Porsi Nominal yang Digesek: <strong style={{ color: '#2563eb' }}>{formatCurrency(pendingCardAmount)}</strong>
        </p>
        <div className="bank-grid-large">
          {banks.map(bank => {
            const minReq = parseFloat(bank.min_transaction_amount) || (bank.type === 'QRIS' ? 20000 : 50000);
            const isBelowMin = pendingCardAmount < minReq;
            return (
              <button key={bank.id} 
                      className="bank-item-btn" 
                      style={isBelowMin ? { opacity: 0.7, borderColor: 'rgba(239, 68, 68, 0.4)' } : {}}
                      onClick={() => onSelectBank(bank, minReq, isBelowMin)}>
                <span className="bank-name">{bank.name}</span>
                <div style={{ display: 'flex', gap: '6px', alignItems: 'center', marginTop: '4px', flexWrap: 'wrap', justifyContent: 'center' }}>
                  <span className={`bank-type-badge ${bank.type === 'QRIS' ? 'qris' : (bank.type === 'TRANSFER' ? 'transfer' : 'edc')}`}>
                    {bank.type || 'EDC'}
                  </span>
                  <span className="bank-min-badge" style={isBelowMin ? { color: '#ef4444', borderColor: 'rgba(239,68,68,0.4)', background: 'rgba(239,68,68,0.1)' } : {}}>
                    Min. {formatCurrency(minReq)}
                  </span>
                </div>
              </button>
            );
          })}
        </div>
        <button className="btn-secondary" onClick={onClose}>BATAL (ESC)</button>
      </div>
    </div>
  );
};
