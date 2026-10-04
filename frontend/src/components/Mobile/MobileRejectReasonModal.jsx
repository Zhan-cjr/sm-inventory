import React from 'react';

export function MobileRejectReasonModal({
  isOpen,
  onClose,
  rejectReason,
  setRejectReason,
  onConfirm
}) {
  if (!isOpen) return null;

  return (
    <div
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: 'rgba(0,0,0,0.75)',
        zIndex: 10000,
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        padding: '1rem'
      }}
      onClick={onClose}
    >
      <div
        className="pwa-card"
        style={{ width: '100%', maxWidth: '400px' }}
        onClick={(e) => e.stopPropagation()}
      >
        <h3 style={{ margin: '0 0 1rem 0', fontSize: '1.1rem', fontWeight: 800, color: 'var(--text-main)' }}>
          Input Alasan Penolakan
        </h3>
        <textarea
          value={rejectReason}
          onChange={(e) => setRejectReason(e.target.value)}
          placeholder="Masukkan catatan alasan penolakan untuk supervisor/staf..."
          style={{
            width: '100%',
            padding: '0.75rem',
            borderRadius: '14px',
            background: 'rgba(0,0,0,0.2)',
            color: 'var(--text-main)',
            border: '1px solid var(--border-light)',
            minHeight: '100px',
            marginBottom: '1rem',
            resize: 'vertical',
            fontSize: '0.85rem',
            outline: 'none'
          }}
        />
        <div style={{ display: 'flex', gap: '0.75rem' }}>
          <button
            onClick={onClose}
            style={{
              flex: 1,
              padding: '0.75rem',
              borderRadius: '12px',
              border: '1px solid var(--border-light)',
              background: 'transparent',
              color: 'var(--text-muted)',
              fontWeight: 700,
              cursor: 'pointer'
            }}
          >
            Batal
          </button>
          <button
            onClick={onConfirm}
            style={{
              flex: 1,
              padding: '0.75rem',
              borderRadius: '12px',
              border: 'none',
              background: '#ef4444',
              color: 'white',
              fontWeight: 800,
              cursor: 'pointer'
            }}
          >
            Konfirmasi Tolak
          </button>
        </div>
      </div>
    </div>
  );
}
