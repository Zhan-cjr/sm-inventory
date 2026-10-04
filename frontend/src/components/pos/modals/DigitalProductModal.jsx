import React from 'react';

export const DigitalProductModal = ({
  isOpen,
  onClose,
  pendingDigitalProduct,
  customerNoInput,
  setCustomerNoInput,
  onSubmit
}) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]" style={{ position: 'fixed', top: 0, left: 0, width: '100vw', height: '100vh', backgroundColor: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
      <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm" style={{ backgroundColor: 'white', padding: '20px', borderRadius: '8px', width: '400px' }}>
        <h3 className="text-lg font-bold mb-4" style={{ marginBottom: '15px', color: 'black' }}>Input Data Tujuan</h3>
        <p className="text-sm text-gray-600 mb-4" style={{ marginBottom: '15px', color: 'black' }}>
          Masukkan Nomor HP / ID Pelanggan untuk produk digital <b>{pendingDigitalProduct?.product?.name}</b>
        </p>
        <form onSubmit={onSubmit}>
          <input
            type="text"
            autoFocus
            className="w-full border p-2 rounded mb-4 focus:ring-2 focus:ring-blue-500"
            style={{ width: '100%', padding: '10px', marginBottom: '15px', border: '1px solid #ccc', borderRadius: '4px', color: 'black' }}
            placeholder="Misal: 081234567890"
            value={customerNoInput}
            onChange={(e) => setCustomerNoInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                onClose();
              }
            }}
          />
          <div className="flex justify-end gap-2" style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
            <button
              type="button"
              style={{ padding: '8px 16px', backgroundColor: '#e5e7eb', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
              onClick={onClose}
            >
              Batal
            </button>
            <button
              type="submit"
              style={{ padding: '8px 16px', backgroundColor: '#2563eb', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
            >
              Lanjut
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
