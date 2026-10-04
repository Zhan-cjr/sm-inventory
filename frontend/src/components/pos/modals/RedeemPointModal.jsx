import React from 'react';
import { Wallet } from 'lucide-react';

export const RedeemPointModal = ({
  isOpen,
  onClose,
  pointRedemptionEnabled,
  formatCurrency,
  pointRedemptionValue,
  minimumPointsToRedeem,
  selectedCustomer,
  finalAmount,
  payments,
  pointsToRedeemInput,
  setPointsToRedeemInput,
  handleApplyPoints
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '450px' }}>
        <div className="modal-header-icon" style={{ background: 'rgba(59, 130, 246, 0.1)', color: 'var(--primary)', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
          <Wallet size={40} />
        </div>
        <h2 style={{ textAlign: 'center' }}>Penukaran Poin</h2>
        {!pointRedemptionEnabled && (
          <div className="device-auth-error-card" style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.2)', padding: '0.75rem', borderRadius: '8px', marginBottom: '1rem', textAlign: 'center', fontSize: '0.85rem' }}>
            <strong>Penukaran poin saat ini dinonaktifkan oleh Perusahaan.</strong>
          </div>
        )}
        <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>
          1 Poin = {formatCurrency(pointRedemptionValue)}<br />
          Minimal Tukar = {minimumPointsToRedeem} Poin
        </p>

        <div className="shift-summary-mini" style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: '12px', marginBottom: '1.5rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Sisa Poin Member</span>
            <span style={{ fontWeight: 'bold' }}>{selectedCustomer?.points || 0}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between' }}>
            <span style={{ color: 'var(--text-muted)' }}>Sisa Tagihan</span>
            <span style={{ fontWeight: 'bold', color: 'var(--primary)' }}>{formatCurrency(finalAmount - payments.reduce((sum, p) => sum + p.amount, 0))}</span>
          </div>
        </div>

        <div className="form-group" style={{ marginBottom: '1.5rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Jumlah Poin yang Ditukar</label>
          <input
            type="number"
            className="modern-barcode-input"
            style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem', opacity: pointRedemptionEnabled ? 1 : 0.5 }}
            placeholder="0"
            value={pointsToRedeemInput}
            onChange={(e) => setPointsToRedeemInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                onClose();
              }
            }}
            disabled={!pointRedemptionEnabled}
            autoFocus
          />
          {pointsToRedeemInput && !isNaN(parseInt(pointsToRedeemInput, 10)) && (
            <div style={{ textAlign: 'center', marginTop: '0.5rem', color: '#10b981', fontWeight: 'bold' }}>
              Nilai Diskon: {formatCurrency(parseInt(pointsToRedeemInput, 10) * pointRedemptionValue)}
            </div>
          )}
        </div>

        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onClose}>BATAL</button>
          <button
            className="btn-primary"
            style={{ flex: 2, opacity: pointRedemptionEnabled ? 1 : 0.5 }}
            onClick={handleApplyPoints}
            disabled={!pointRedemptionEnabled}
          >
            TERAPKAN POIN
          </button>
        </div>
      </div>
    </div>
  );
};
