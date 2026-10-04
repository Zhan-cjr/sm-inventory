import React from 'react';
import { LogIn } from 'lucide-react';

export const OpenShiftModal = ({
  isOpen,
  selectedShiftName,
  setSelectedShiftName,
  startingCash,
  setStartingCash,
  formatThousandSeparator,
  onOpenShift
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <div className="modal-header-icon" style={{ background: 'rgba(34, 197, 94, 0.1)', color: '#22c55e', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
          <LogIn size={40} />
        </div>
        <h2 style={{ textAlign: 'center' }}>Buka Shift Kasir</h2>
        <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Pilih shift dan masukkan modal awal untuk memulai.</p>

        <div className="form-group" style={{ marginBottom: '1rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Nama Shift</label>
          <select
            className="modern-barcode-input"
            style={{ width: '100%', padding: '0.75rem' }}
            value={selectedShiftName}
            onChange={(e) => setSelectedShiftName(e.target.value)}
          >
            <option value="Shift 1">Shift 1</option>
            <option value="Shift 2">Shift 2</option>
            <option value="Shift 3">Shift 3</option>
            <option value="Shift Umum">Shift Umum</option>
          </select>
        </div>

        <div className="form-group" style={{ marginBottom: '1.5rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Modal Awal (Cash)</label>
          <input
            type="text"
            className="modern-barcode-input"
            style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
            placeholder="0"
            value={startingCash ? formatThousandSeparator(startingCash) : ''}
            onChange={(e) => setStartingCash(e.target.value.replace(/[^0-9]/g, ''))}
          />
        </div>

        <button className="btn-primary" style={{ width: '100%', padding: '1rem', fontSize: '1.1rem' }} onClick={onOpenShift}>
          BUKA SHIFT SEKARANG
        </button>
      </div>
    </div>
  );
};
