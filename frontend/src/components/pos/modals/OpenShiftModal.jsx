import React from 'react';
import { LogIn } from 'lucide-react';

export const OpenShiftModal = ({
  isOpen,
  selectedShiftName,
  setSelectedShiftName,
  startingCash,
  setStartingCash,
  formatThousandSeparator,
  onOpenShift,
  closedShifts = []
}) => {
  if (!isOpen) return null;

  const isShift1Closed = closedShifts.includes('Shift 1');
  const isShift2Closed = closedShifts.includes('Shift 2');
  const isAllShiftsClosed = isShift1Closed && isShift2Closed;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <div className="modal-header-icon" style={{ background: isAllShiftsClosed ? 'rgba(239, 68, 68, 0.1)' : 'rgba(34, 197, 94, 0.1)', color: isAllShiftsClosed ? '#ef4444' : '#22c55e', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
          <LogIn size={40} />
        </div>
        <h2 style={{ textAlign: 'center' }}>Buka Shift Kasir</h2>
        <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Pilih shift dan masukkan modal awal untuk memulai.</p>

        {isAllShiftsClosed && (
          <div style={{ background: '#fef2f2', border: '1px solid #f87171', color: '#991b1b', padding: '0.85rem', borderRadius: '8px', marginBottom: '1.25rem', fontSize: '0.88rem', lineHeight: '1.4' }}>
            <strong>Semua Shift Ditutup:</strong> Shift 1 dan Shift 2 pada kassa ini sudah selesai ditutup untuk hari ini. Tidak ada shift lain yang dapat dibuka kembali.
          </div>
        )}

        <div className="form-group" style={{ marginBottom: '1rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Nama Shift</label>
          <select
            className="modern-barcode-input"
            style={{ width: '100%', padding: '0.75rem' }}
            value={selectedShiftName}
            disabled={isAllShiftsClosed}
            onChange={(e) => setSelectedShiftName(e.target.value)}
          >
            <option value="Shift 1" disabled={isShift1Closed}>
              Shift 1 {isShift1Closed ? '(Sudah Ditutup)' : ''}
            </option>
            <option value="Shift 2" disabled={isShift2Closed}>
              Shift 2 {isShift2Closed ? '(Sudah Ditutup)' : ''}
            </option>
          </select>
        </div>

        <div className="form-group" style={{ marginBottom: '1.5rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Modal Awal (Cash)</label>
          <input
            type="text"
            className="modern-barcode-input"
            style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
            placeholder="0"
            disabled={isAllShiftsClosed}
            value={startingCash ? formatThousandSeparator(startingCash) : ''}
            onChange={(e) => setStartingCash(e.target.value.replace(/[^0-9]/g, ''))}
          />
        </div>

        <button 
          className="btn-primary" 
          style={{ 
            width: '100%', 
            padding: '1rem', 
            fontSize: '1.1rem',
            opacity: isAllShiftsClosed ? 0.5 : 1,
            cursor: isAllShiftsClosed ? 'not-allowed' : 'pointer'
          }} 
          onClick={onOpenShift}
          disabled={isAllShiftsClosed}
        >
          {isAllShiftsClosed ? 'SEMUA SHIFT SELESAI' : 'BUKA SHIFT SEKARANG'}
        </button>
      </div>
    </div>
  );
};
