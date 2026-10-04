import React from 'react';
import { X } from 'lucide-react';

export function MobileDocReviewModal({
  isOpen,
  onClose,
  loading,
  data,
  onReject,
  onApprove
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
        zIndex: 9999,
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'flex-end',
        animation: 'fadeIn 0.2s ease-out'
      }}
      onClick={onClose}
    >
      <div
        className="pwa-card"
        style={{
          width: '100%',
          maxWidth: '500px',
          maxHeight: '85vh',
          overflowY: 'auto',
          borderBottomLeftRadius: 0,
          borderBottomRightRadius: 0,
          padding: '1.5rem',
          animation: 'slideUp 0.3s ease-out'
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginBottom: '1rem',
            borderBottom: '1px solid var(--border-light)',
            paddingBottom: '0.75rem'
          }}
        >
          <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 800, color: 'var(--text-main)' }}>
            Rincian Items Dokumen
          </h3>
          <button
            onClick={onClose}
            style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}
          >
            <X size={20} />
          </button>
        </div>

        {loading ? (
          <div style={{ textAlign: 'center', padding: '2rem' }}>
            <div
              className="spin"
              style={{
                width: '30px',
                height: '30px',
                border: '3px solid #3b82f6',
                borderTopColor: 'transparent',
                borderRadius: '50%',
                margin: '0 auto'
              }}
            />
          </div>
        ) : data ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>
              <div>No: <strong style={{ color: 'var(--text-main)' }}>{data.number}</strong></div>
              <div>Tipe: <strong style={{ color: '#3b82f6' }}>{data.type}</strong></div>
              {data.supplier && <div>Supplier: <strong style={{ color: 'var(--text-main)' }}>{data.supplier}</strong></div>}
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
              {(data.type === 'Otorisasi Penerimaan Qty Gudang'
                ? data.items?.filter(item => item.diff > 0)
                : data.items
              )?.map((item, idx) => (
                <div
                  key={idx}
                  style={{
                    background: 'rgba(255,255,255,0.04)',
                    border: '1px solid var(--border-light)',
                    padding: '0.85rem',
                    borderRadius: '14px'
                  }}
                >
                  <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--text-main)', marginBottom: '4px' }}>
                    {item.product_name}
                  </div>
                  {data.type === 'Otorisasi PO' ? (
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: 'var(--text-muted)' }}>
                      <span>{item.qty} x Rp {parseFloat(item.price).toLocaleString('id-ID')}</span>
                      <strong style={{ color: '#10b981' }}>Rp {parseFloat(item.subtotal).toLocaleString('id-ID')}</strong>
                    </div>
                  ) : (
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: 'var(--text-muted)' }}>
                      <span>Stok: {item.old_qty} &rarr; {item.new_qty}</span>
                      <strong style={{ color: item.diff < 0 ? '#ef4444' : '#10b981' }}>
                        {item.diff > 0 ? '+' : ''}{item.diff}
                      </strong>
                    </div>
                  )}
                </div>
              ))}
            </div>

            <div style={{ display: 'flex', gap: '0.75rem', marginTop: '0.5rem' }}>
              <button
                onClick={() => onReject(data.id)}
                style={{
                  flex: 1,
                  padding: '0.85rem',
                  borderRadius: '14px',
                  border: '1px solid #ef4444',
                  background: 'rgba(239, 68, 68, 0.1)',
                  color: '#ef4444',
                  fontWeight: 800,
                  fontSize: '0.9rem',
                  cursor: 'pointer'
                }}
              >
                Tolak Dokumen
              </button>
              <button
                onClick={() => onApprove(data.id)}
                style={{
                  flex: 1,
                  padding: '0.85rem',
                  borderRadius: '14px',
                  border: 'none',
                  background: '#10b981',
                  color: 'white',
                  fontWeight: 800,
                  fontSize: '0.9rem',
                  cursor: 'pointer'
                }}
              >
                Setujui Dokumen
              </button>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}
