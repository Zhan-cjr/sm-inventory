import React from 'react';
import { History, Trash2 } from 'lucide-react';

export const RecallModal = ({
  isOpen,
  onClose,
  heldTransactions,
  setHeldTransactions,
  onRecallTransaction,
  formatCurrency,
  safeSetItem
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content bank-select-card fade-in" style={{ maxWidth: '600px' }}>
        <History size={48} className="text-primary" />
        <h2>Daftar Transaksi Ditunda (HOLD)</h2>
        <div className="held-list-grid" style={{ maxHeight: '400px', overflowY: 'auto', width: '100%' }}>
          {heldTransactions.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-muted)' }}>
              <p>Tidak ada transaksi yang ditunda.</p>
            </div>
          ) : (
            heldTransactions.map(tx => (
              <div key={tx.id} className="held-item-card" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1rem', background: 'rgba(255,255,255,0.05)', borderRadius: '12px', marginBottom: '0.75rem' }}>
                <div className="held-info" style={{ textAlign: 'left' }}>
                  <div style={{ fontWeight: '700' }}>#{String(tx.id).substring(0, 8)}</div>
                  <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{tx.itemCount} Items | {formatCurrency(tx.finalAmount || tx.total)}</div>
                  <small style={{ color: 'var(--text-muted)' }}>{new Date(tx.timestamp || tx.time).toLocaleTimeString('id-ID')}</small>
                </div>
                <div className="held-actions" style={{ display: 'flex', gap: '0.5rem' }}>
                  <button className="btn-primary-sm" onClick={() => onRecallTransaction(tx)}>PANGGIL</button>
                  <button className="btn-danger-sm" onClick={() => {
                    const newHeld = heldTransactions.filter(h => h.id !== tx.id);
                    setHeldTransactions(newHeld);
                    safeSetItem('pos_held_transactions', JSON.stringify(newHeld));
                  }}><Trash2 size={14} /></button>
                </div>
              </div>
            ))
          )}
        </div>
        <button className="btn-secondary" style={{ marginTop: '1rem', width: '100%' }} onClick={onClose}>TUTUP (ESC)</button>
      </div>
    </div>
  );
};
