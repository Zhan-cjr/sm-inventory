import React, { useState } from 'react';
import { DollarSign, X, CheckCircle, AlertTriangle } from 'lucide-react';

export const PpobRefundModal = ({
  isOpen,
  onClose,
  ppobItem,
  onConfirmRefund,
  isRefunding,
}) => {
  const [refundMethod, setRefundMethod] = useState('CASH');
  const [notes, setNotes] = useState('');

  if (!isOpen || !ppobItem) return null;

  const refundAmount = ppobItem.parentTx?.items?.find(i => 
    i.product?.ppob_sku === ppobItem.buyer_sku_code || i.product_id === ppobItem.product_id
  )?.unit_price || ppobItem.price || 0;

  const handleSubmit = async (e) => {
    e.preventDefault();
    await onConfirmRefund({
      refund_method: refundMethod,
      notes: notes.trim(),
    });
    onClose();
  };

  return (
    <div 
      className="change-modal-overlay"
      style={{ 
        position: 'fixed', 
        inset: 0, 
        backgroundColor: 'rgba(0,0,0,0.65)', 
        display: 'flex', 
        alignItems: 'center', 
        justifyContent: 'center', 
        zIndex: 9999 
      }}
    >
      <div 
        style={{
          backgroundColor: 'white',
          borderRadius: '12px',
          width: '450px',
          maxWidth: '92vw',
          boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.3)',
          overflow: 'hidden',
        }}
      >
        {/* Header */}
        <div style={{ backgroundColor: '#2563eb', color: 'white', padding: '1rem 1.25rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem' }}>
            <DollarSign size={20} />
            <h3 style={{ margin: 0, fontSize: '1.05rem', fontWeight: 700 }}>Proses Refund PPOB ke Konsumen</h3>
          </div>
          <button 
            type="button" 
            onClick={onClose}
            style={{ background: 'none', border: 'none', color: 'white', cursor: 'pointer', padding: 2 }}
          >
            <X size={20} />
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} style={{ padding: '1.25rem', color: '#1f2937' }}>
          <div style={{ background: '#f0f9ff', border: '1px solid #bae6fd', borderRadius: '8px', padding: '0.75rem 1rem', marginBottom: '1.25rem' }}>
            <div style={{ fontSize: '0.82rem', color: '#0369a1', fontWeight: 600 }}>TRANSAKSI GAGAL DARI OPERATOR</div>
            <div style={{ fontSize: '1rem', fontWeight: 700, color: '#0c4a6e', marginTop: '2px' }}>
              {ppobItem.productName || ppobItem.buyer_sku_code}
            </div>
            <div style={{ fontSize: '0.85rem', color: '#334155', marginTop: '4px' }}>
              Tujuan: <b>{ppobItem.customer_no}</b>
            </div>
            <div style={{ fontSize: '0.85rem', color: '#334155' }}>
              No. Struk: <b>#{ppobItem.txReceiptNumber}</b>
            </div>
            <div style={{ marginTop: '0.5rem', fontSize: '1.15rem', fontWeight: 800, color: '#dc2626' }}>
              Nominal Pengembalian: Rp {Number(refundAmount).toLocaleString('id-ID')}
            </div>
          </div>

          <div style={{ marginBottom: '1rem' }}>
            <label style={{ display: 'block', fontSize: '0.85rem', fontWeight: 600, color: '#374151', marginBottom: '0.4rem' }}>
              Metode Pengembalian Dana:
            </label>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem' }}>
              <button
                type="button"
                onClick={() => setRefundMethod('CASH')}
                style={{
                  padding: '0.65rem',
                  borderRadius: '8px',
                  border: refundMethod === 'CASH' ? '2px solid #2563eb' : '1px solid #d1d5db',
                  background: refundMethod === 'CASH' ? '#eff6ff' : '#f9fafb',
                  fontWeight: 700,
                  color: refundMethod === 'CASH' ? '#1d4ed8' : '#4b5563',
                  cursor: 'pointer',
                  fontSize: '0.88rem'
                }}
              >
                💵 Tunai (Laci Kasir)
              </button>
              <button
                type="button"
                onClick={() => setRefundMethod('TRANSFER')}
                style={{
                  padding: '0.65rem',
                  borderRadius: '8px',
                  border: refundMethod === 'TRANSFER' ? '2px solid #2563eb' : '1px solid #d1d5db',
                  background: refundMethod === 'TRANSFER' ? '#eff6ff' : '#f9fafb',
                  fontWeight: 700,
                  color: refundMethod === 'TRANSFER' ? '#1d4ed8' : '#4b5563',
                  cursor: 'pointer',
                  fontSize: '0.88rem'
                }}
              >
                🏦 Transfer / E-Wallet
              </button>
            </div>
            {refundMethod === 'CASH' ? (
              <p style={{ fontSize: '0.75rem', color: '#6b7280', marginTop: '0.35rem' }}>
                *Sistem akan mencatat Pengeluaran Kas (Cash Out) pada shift aktif Anda, sehingga laci kasir saat tutup shift tetap klop.
              </p>
            ) : (
              <p style={{ fontSize: '0.75rem', color: '#6b7280', marginTop: '0.35rem' }}>
                *Dana ditransfer dari rekening toko, laci uang fisik kasir tidak dipotong.
              </p>
            )}
          </div>

          <div style={{ marginBottom: '1.25rem' }}>
            <label style={{ display: 'block', fontSize: '0.85rem', fontWeight: 600, color: '#374151', marginBottom: '0.35rem' }}>
              Catatan Refund (Opsional):
            </label>
            <input
              type="text"
              className="pos-input"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Contoh: Diterima langsung oleh pelanggan / Transfer BCA"
              style={{
                width: '100%',
                padding: '0.6rem 0.75rem',
                fontSize: '0.88rem',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                outline: 'none',
                boxSizing: 'border-box'
              }}
            />
          </div>

          <div style={{ display: 'flex', gap: '0.6rem', justifyContent: 'flex-end' }}>
            <button
              type="button"
              onClick={onClose}
              style={{
                padding: '0.65rem 1rem',
                backgroundColor: '#f3f4f6',
                color: '#4b5563',
                border: '1px solid #d1d5db',
                borderRadius: '8px',
                fontWeight: 600,
                fontSize: '0.85rem',
                cursor: 'pointer'
              }}
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isRefunding}
              style={{
                padding: '0.65rem 1.25rem',
                backgroundColor: '#2563eb',
                color: 'white',
                border: 'none',
                borderRadius: '8px',
                fontWeight: 700,
                fontSize: '0.88rem',
                cursor: isRefunding ? 'not-allowed' : 'pointer'
              }}
            >
              {isRefunding ? 'Memproses Refund...' : 'Konfirmasi Pengembalian'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
