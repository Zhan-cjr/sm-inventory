import React from 'react';
import { Trash2 } from 'lucide-react';

export const MultiPaymentModal = ({
  isOpen,
  onClose,
  finalAmount,
  payments,
  setPayments,
  formatCurrency,
  onAddVoucher,
  onAddCash,
  onAddCard,
  onProcessPay
}) => {
  if (!isOpen) return null;

  const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
  const isPaidEnough = totalPaid >= finalAmount;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '600px', width: '90%' }}>
        <h2 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Multi Payment</h2>

        <div style={{ background: 'rgba(36, 42, 122, 0.1)', padding: '1rem', borderRadius: '8px', marginBottom: '1.5rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.1rem', marginBottom: '0.5rem' }}>
            <span>Tagihan:</span>
            <span style={{ fontWeight: 'bold' }}>{formatCurrency(finalAmount)}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.1rem', marginBottom: '0.5rem', color: 'var(--accent)' }}>
            <span>Total Dibayar:</span>
            <span style={{ fontWeight: 'bold' }}>{formatCurrency(totalPaid)}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.25rem', fontWeight: 'bold', color: isPaidEnough ? 'var(--accent)' : 'var(--danger)' }}>
            <span>{isPaidEnough ? 'Kembali:' : 'Sisa:'}</span>
            <span>{formatCurrency(Math.abs(finalAmount - totalPaid))}</span>
          </div>
        </div>

        <div style={{ marginBottom: '1.5rem', maxHeight: '150px', overflowY: 'auto' }}>
          {payments.length === 0 ? (
            <div style={{ textAlign: 'center', color: '#6b7280', padding: '1rem' }}>Belum ada pembayaran ditambahkan</div>
          ) : (
            <table className="modern-table" style={{ width: '100%', fontSize: '0.9rem' }}>
              <tbody>
                {payments.map((p, idx) => (
                  <tr key={idx}>
                    <td>{p.label || p.method}</td>
                    <td style={{ textAlign: 'right', fontWeight: 'bold' }}>{formatCurrency(p.amount)}</td>
                    <td style={{ width: '40px', textAlign: 'center' }}>
                      <button onClick={() => setPayments(payments.filter((_, i) => i !== idx))} style={{ color: 'var(--danger)', background: 'none', border: 'none', cursor: 'pointer' }}><Trash2 size={16} /></button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '1.5rem' }}>
          <button className="btn-secondary" style={{ flex: 1, padding: '0.75rem', fontSize: '0.9rem' }} onClick={onAddVoucher}>+ Voucher</button>
          <button className="btn-secondary" style={{ flex: 1, padding: '0.75rem', fontSize: '0.9rem' }} onClick={onAddCash}>+ Tunai</button>
          <button className="btn-secondary" style={{ flex: 1, padding: '0.75rem', fontSize: '0.9rem' }} onClick={onAddCard}>+ Card</button>
        </div>

        <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onClose}>TUTUP</button>
          <button className="btn-success" style={{ flex: 1 }} disabled={!isPaidEnough} onClick={onProcessPay}>PROSES BAYAR</button>
        </div>
      </div>
    </div>
  );
};
