import React from 'react';
import { LogIn, User, LogOut } from 'lucide-react';

export const OpenShiftModal = ({
  isOpen,
  user,
  onLogout,
  selectedShiftName,
  setSelectedShiftName,
  startingCash,
  setStartingCash,
  formatThousandSeparator,
  onOpenShift,
  closedShifts = [],
  isProcessing = false
}) => {
  if (!isOpen) return null;

  const isShift1Closed = closedShifts.includes('Shift 1');
  const isShift2Closed = closedShifts.includes('Shift 2');
  const isAllShiftsClosed = isShift1Closed && isShift2Closed;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '420px' }}>
        <div className="modal-header-icon" style={{ background: isAllShiftsClosed ? 'rgba(239, 68, 68, 0.1)' : 'rgba(34, 197, 94, 0.1)', color: isAllShiftsClosed ? '#ef4444' : '#22c55e', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
          <LogIn size={40} />
        </div>
        <h2 style={{ textAlign: 'center' }}>Buka Shift Kasir</h2>
        <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.25rem' }}>Pilih shift dan masukkan modal awal untuk memulai.</p>

        {/* Informasi Kasir Aktif */}
        <div style={{
          background: 'var(--bg-accent, #f8fafc)',
          border: '1px solid var(--border-color, #e2e8f0)',
          borderRadius: '10px',
          padding: '0.85rem 1rem',
          marginBottom: '1.25rem',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          gap: '10px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', minWidth: 0 }}>
            <div style={{
              width: '36px',
              height: '36px',
              borderRadius: '50%',
              background: 'rgba(34, 197, 94, 0.15)',
              color: '#16a34a',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              flexShrink: 0
            }}>
              <User size={20} />
            </div>
            <div style={{ minWidth: 0 }}>
              <div style={{ fontSize: '0.72rem', color: 'var(--text-muted, #64748b)', fontWeight: '600', textTransform: 'uppercase' }}>
                Kasir yang Masuk
              </div>
              <div style={{ fontSize: '0.98rem', fontWeight: 'bold', color: 'var(--text-main, #0f172a)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                {user?.name || user?.username || 'Kasir Aktif'}
              </div>
            </div>
          </div>
          {onLogout && (
            <button
              type="button"
              onClick={onLogout}
              style={{
                background: '#fee2e2',
                color: '#b91c1c',
                border: '1px solid #fca5a5',
                borderRadius: '6px',
                padding: '0.4rem 0.65rem',
                fontSize: '0.75rem',
                fontWeight: 'bold',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '4px',
                flexShrink: 0
              }}
              title="Klik jika Anda kasir yang berbeda"
            >
              <LogOut size={13} /> Ganti Kasir
            </button>
          )}
        </div>

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
            disabled={isAllShiftsClosed || isProcessing}
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
            disabled={isAllShiftsClosed || isProcessing}
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
            opacity: (isAllShiftsClosed || isProcessing) ? 0.5 : 1,
            cursor: (isAllShiftsClosed || isProcessing) ? 'not-allowed' : 'pointer'
          }} 
          onClick={onOpenShift}
          disabled={isAllShiftsClosed || isProcessing}
        >
          {isProcessing ? 'MEMPROSES PEMBUKAAN SHIFT...' : (isAllShiftsClosed ? 'SEMUA SHIFT SELESAI' : 'BUKA SHIFT SEKARANG')}
        </button>
      </div>
    </div>
  );
};
