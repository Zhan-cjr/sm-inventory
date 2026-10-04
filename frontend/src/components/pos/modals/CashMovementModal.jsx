import React from 'react';
import { Banknote, Plus, Minus } from 'lucide-react';

export const CashMovementModal = ({
  isOpen,
  onClose,
  cashMovementType,
  setCashMovementType,
  cashMovementAmount,
  setCashMovementAmount,
  cashMovementDesc,
  setCashMovementDesc,
  onSave,
  isProcessing
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '450px' }}>
        <div className="modal-header-icon" style={{ background: 'rgba(234, 179, 8, 0.1)', color: '#eab308', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
          <Banknote size={40} />
        </div>
        <h2 style={{ textAlign: 'center' }}>Manajemen Kas</h2>
        <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Catat pengeluaran atau penambahan kas laci (Petty Cash).</p>

        <div className="form-group" style={{ marginBottom: '1rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Jenis Transaksi</label>
          <div style={{ display: 'flex', gap: '10px' }}>
            <button
              className={`btn-${cashMovementType === 'CASH_IN' ? 'primary' : 'secondary'}`}
              style={{ flex: 1, padding: '0.75rem' }}
              onClick={() => setCashMovementType('CASH_IN')}
            >
              <Plus size={16} style={{ display: 'inline', marginRight: '5px' }} /> KAS MASUK
            </button>
            <button
              className={`btn-${cashMovementType === 'CASH_OUT' ? 'danger' : 'secondary'}`}
              style={{ flex: 1, padding: '0.75rem' }}
              onClick={() => setCashMovementType('CASH_OUT')}
            >
              <Minus size={16} style={{ display: 'inline', marginRight: '5px' }} /> KAS KELUAR
            </button>
          </div>
        </div>

        <div className="form-group" style={{ marginBottom: '1rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Nominal (Rp)</label>
          <input
            type="number"
            className="modern-barcode-input"
            style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
            placeholder="0"
            value={cashMovementAmount}
            onChange={(e) => setCashMovementAmount(e.target.value)}
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

        <div className="form-group" style={{ marginBottom: '1.5rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Keterangan / Catatan</label>
          <input
            type="text"
            className="modern-barcode-input"
            style={{ width: '100%', padding: '0.75rem' }}
            placeholder="Contoh: Beli air minum galon..."
            value={cashMovementDesc}
            onChange={(e) => setCashMovementDesc(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                onClose();
              }
            }}
          />
        </div>

        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onClose}>BATAL</button>
          <button
            className="btn-primary"
            style={{ flex: 2, background: isProcessing ? '#94a3b8' : undefined }}
            onClick={onSave}
            disabled={isProcessing}
          >
            {isProcessing ? 'MENYIMPAN...' : 'SIMPAN CATATAN KAS'}
          </button>
        </div>
      </div>
    </div>
  );
};
