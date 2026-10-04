import React, { useState, useEffect } from 'react';
import { AlertCircle, RefreshCw, Trash2, X } from 'lucide-react';

export const PpobFailedCorrectionModal = ({
  isOpen,
  onClose,
  failedItem,
  onRetryWithNewNumber,
  onRemovePpobAndContinue,
}) => {
  const [newCustomerNo, setNewCustomerNo] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    if (failedItem?.customer_no) {
      setNewCustomerNo(failedItem.customer_no);
    } else {
      setNewCustomerNo('');
    }
  }, [failedItem]);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!newCustomerNo.trim()) return;
    setIsSubmitting(true);
    try {
      await onRetryWithNewNumber(newCustomerNo.trim());
      onClose();
    } catch (err) {
      console.error(err);
    } finally {
      setIsSubmitting(false);
    }
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
          border: '1px solid #fee2e2'
        }}
      >
        {/* Header */}
        <div style={{ backgroundColor: '#ef4444', color: 'white', padding: '1rem 1.25rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem' }}>
            <AlertCircle size={22} />
            <h3 style={{ margin: 0, fontSize: '1.05rem', fontWeight: 700 }}>Pengisian PPOB Ditolak Provider</h3>
          </div>
          <button 
            type="button" 
            onClick={onClose}
            style={{ background: 'none', border: 'none', color: 'white', cursor: 'pointer', padding: 2 }}
          >
            <X size={20} />
          </button>
        </div>

        {/* Content */}
        <div style={{ padding: '1.25rem', color: '#1f2937' }}>
          <div style={{ background: '#fef2f2', border: '1px solid #fecaca', borderRadius: '8px', padding: '0.75rem 1rem', marginBottom: '1rem', fontSize: '0.88rem' }}>
            <div style={{ fontWeight: 600, color: '#991b1b', marginBottom: '0.25rem' }}>
              {failedItem?.product_name || 'Produk Digital'}
            </div>
            <div style={{ color: '#b91c1c', fontSize: '0.82rem' }}>
              Pesan Operator: <b>{failedItem?.provider_message || 'Nomor tujuan salah atau produk sedang gangguan.'}</b>
            </div>
          </div>

          <form onSubmit={handleSubmit}>
            <label style={{ display: 'block', fontSize: '0.85rem', fontWeight: 600, color: '#374151', marginBottom: '0.35rem' }}>
              Koreksi Nomor Tujuan / No. Meter:
            </label>
            <input
              type="text"
              autoFocus
              className="pos-input"
              value={newCustomerNo}
              onChange={(e) => setNewCustomerNo(e.target.value)}
              placeholder="Contoh: 081234567890"
              style={{
                width: '100%',
                padding: '0.7rem 0.85rem',
                fontSize: '1.05rem',
                fontFamily: 'monospace',
                fontWeight: 700,
                border: '2px solid #3b82f6',
                borderRadius: '8px',
                marginBottom: '1.25rem',
                outline: 'none',
                boxSizing: 'border-box'
              }}
            />

            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
              <button
                type="submit"
                disabled={isSubmitting || !newCustomerNo.trim()}
                style={{
                  width: '100%',
                  padding: '0.75rem',
                  backgroundColor: '#10b981',
                  color: 'white',
                  border: 'none',
                  borderRadius: '8px',
                  fontWeight: 700,
                  fontSize: '0.95rem',
                  cursor: isSubmitting ? 'not-allowed' : 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.5rem',
                  boxShadow: '0 4px 6px -1px rgba(16, 185, 129, 0.3)'
                }}
              >
                <RefreshCw size={16} className={isSubmitting ? 'spin' : ''} />
                <span>{isSubmitting ? 'Memproses Ulang...' : 'Coba Proses Ulang (Nomor Baru)'}</span>
              </button>

              <button
                type="button"
                onClick={() => {
                  onRemovePpobAndContinue();
                  onClose();
                }}
                style={{
                  width: '100%',
                  padding: '0.65rem',
                  backgroundColor: '#f3f4f6',
                  color: '#4b5563',
                  border: '1px solid #d1d5db',
                  borderRadius: '8px',
                  fontWeight: 600,
                  fontSize: '0.85rem',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.4rem'
                }}
              >
                <Trash2 size={15} />
                <span>Hapus Item PPOB & Bayar Barang Fisik Saja</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};
