import React from 'react';
import { LogOut } from 'lucide-react';

export const CloseShiftModal = ({
  isOpen,
  onClose,
  activeShift,
  actualCash,
  setActualCash,
  formatThousandSeparator,
  formatCurrency,
  onCloseShift,
  isProcessing
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '450px' }}>
        <div className="modal-header-icon" style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
          <LogOut size={40} />
        </div>
        <h2 style={{ textAlign: 'center' }}>Tutup Kasir / End Shift</h2>

        <div className="shift-summary-mini" style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: '12px', marginBottom: '1.5rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Mulai Shift</span>
            <span>{activeShift?.start_time ? new Date(activeShift.start_time).toLocaleTimeString('id-ID') : '-'}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Modal Awal</span>
            <span>{formatCurrency(activeShift?.starting_cash || 0)}</span>
          </div>
        </div>

        <div className="form-group" style={{ marginBottom: '1.5rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Uang Fisik di Laci (Cash)</label>
          <input
            type="text"
            className="modern-barcode-input"
            style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
            placeholder="0"
            value={actualCash ? formatThousandSeparator(actualCash) : ''}
            onChange={(e) => setActualCash(e.target.value.replace(/[^0-9]/g, ''))}
            onKeyDown={(e) => {
              if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                onClose();
              }
            }}
            autoFocus
          />
        </div>

        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onClose}>BATAL</button>
          <button
            className="btn-danger"
            style={{ flex: 2, background: isProcessing ? '#94a3b8' : '#ef4444' }}
            onClick={onCloseShift}
            disabled={isProcessing}
          >
            {isProcessing ? 'MEMPROSES...' : 'TUTUP KASIR (BLIND CLOSE)'}
          </button>
        </div>
      </div>
    </div>
  );
};
