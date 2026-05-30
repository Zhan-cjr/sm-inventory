import React, { useState, useEffect, useRef } from 'react';
import { Html5QrcodeScanner } from 'html5-qrcode';

export function MobileProductScanner({ user, authToken }) {
  const [scannedProduct, setScannedProduct] = useState(null);
  const [isScanning, setIsScanning] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const scannerRef = useRef(null);
  const [productsCache, setProductsCache] = useState([]);
  
  const [branches, setBranches] = useState([]);
  const [selectedBranchId, setSelectedBranchId] = useState(user?.branch_id || '');

  // Fetch branches if user has no default branch or is ADMIN
  const isAdmin = ['ADMIN', 'SUPER_ADMIN'].includes(user?.role?.toUpperCase());
  const showBranchSelector = !user?.branch_id || isAdmin;

  useEffect(() => {
    if (showBranchSelector) {
      fetch('/api/v1/branches', { headers: { 'Authorization': `Bearer ${authToken}` } })
        .then(res => res.json())
        .then(data => {
          setBranches(data);
          if (data.length > 0 && !selectedBranchId) {
            setSelectedBranchId(data[0].id);
          }
        })
        .catch(err => console.error(err));
    }
  }, [user, authToken, selectedBranchId, showBranchSelector]);

  // Load all products to cache for fast lookup
  useEffect(() => {
    if (showBranchSelector && !selectedBranchId) return;

    fetch(`/api/v1/products?branch_id=${selectedBranchId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => setProductsCache(data))
      .catch(err => console.error("Gagal load products cache", err));
  }, [authToken, selectedBranchId, user]);

  useEffect(() => {
    if (isScanning) {
      scannerRef.current = new Html5QrcodeScanner(
        "reader",
        { fps: 10, qrbox: { width: 250, height: 100 } },
        false
      );

      scannerRef.current.render((decodedText) => {
        scannerRef.current.clear().catch(e => console.error(e));
        setIsScanning(false);
        handleLookup(decodedText);
      }, (errorMessage) => {
        // ignore scan errors
      });
    }

    return () => {
      if (scannerRef.current) {
        scannerRef.current.clear().catch(e => console.error(e));
        scannerRef.current = null;
      }
    };
  }, [isScanning]);

  const handleLookup = (barcode) => {
    setError(null);
    setLoading(true);

    // Cari di local cache dulu agar instan
    const found = productsCache.find(p => p.barcode === barcode || p.sku === barcode);
    
    if (found) {
      setScannedProduct(found);
      setLoading(false);
    } else {
      // Jika tidak ada di cache cabang ini, mungkin bisa panggil API tambahan
      // Tapi untuk sekarang kita tampilkan error
      setError(`Produk dengan barcode ${barcode} tidak ditemukan.`);
      setScannedProduct(null);
      setLoading(false);
    }
  };

  const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

  return (
    <div style={{ padding: '1rem', paddingBottom: '6rem', animation: 'fadeIn 0.3s ease-out' }}>
      
      {showBranchSelector && (
        <div style={{ marginBottom: '1rem' }}>
          <label style={{ display: 'block', fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.25rem' }}>Pilih Cabang (Admin Mode)</label>
          <select 
            value={selectedBranchId} 
            onChange={(e) => setSelectedBranchId(e.target.value)}
            className="login-input"
            style={{ width: '100%', cursor: 'pointer', padding: '0.75rem', background: 'var(--bg-card)', color: 'white', border: '1px solid var(--border-light)', borderRadius: '8px' }}
          >
            <option value="" disabled>-- Pilih Cabang --</option>
            {branches.map(b => (
              <option key={b.id} value={b.id}>{b.name}</option>
            ))}
          </select>
        </div>
      )}

      <h2 style={{ marginBottom: '0.5rem', fontSize: '1.5rem', fontWeight: 'bold' }}>Cek Harga & Stok</h2>
      <p style={{ color: 'var(--text-muted)', marginBottom: '1.5rem', fontSize: '0.85rem' }}>Scan barcode untuk melihat detail barang.</p>

      {!isScanning && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginBottom: '2rem' }}>
          <button 
            className="btn-primary" 
            style={{ width: '100%', height: '100px', fontSize: '1.1rem', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: '0.5rem' }}
            onClick={() => setIsScanning(true)}
          >
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M4 4h4v4H4z"></path>
              <path d="M4 16h4v4H4z"></path>
              <path d="M16 4h4v4h-4z"></path>
              <path d="M16 16h4v4h-4z"></path>
              <line x1="12" y1="4" x2="12" y2="20"></line>
              <line x1="4" y1="12" x2="20" y2="12"></line>
            </svg>
            TAP UNTUK SCAN KAMERA
          </button>
          
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', background: 'var(--bg-card)', padding: '0.5rem', borderRadius: '8px', border: '1px solid var(--border-light)' }}>
            <input 
              type="text" 
              className="modern-barcode-input" 
              style={{ flex: 1, padding: '0.75rem', background: 'transparent' }}
              placeholder="Atau ketik Barcode manual..."
              onKeyDown={(e) => {
                if (e.key === 'Enter' && e.target.value.trim() !== '') {
                  handleLookup(e.target.value.trim());
                  e.target.value = '';
                }
              }}
            />
            <button 
              className="btn-secondary" 
              style={{ padding: '0.75rem 1rem' }}
              onClick={(e) => {
                const input = e.currentTarget.previousElementSibling;
                if (input.value.trim() !== '') {
                  handleLookup(input.value.trim());
                  input.value = '';
                }
              }}
            >
              CARI
            </button>
          </div>
        </div>
      )}

      {isScanning && (
        <div style={{ marginBottom: '1rem' }}>
          <div id="reader" style={{ width: '100%', borderRadius: '12px', overflow: 'hidden', border: '2px solid #10b981' }}></div>
          <button 
            className="btn-secondary" 
            style={{ width: '100%', marginTop: '1rem', padding: '1rem' }}
            onClick={() => setIsScanning(false)}
          >
            Batal Scan
          </button>
        </div>
      )}

      {loading && <div style={{ textAlign: 'center', padding: '2rem' }}><div className="spinner"></div></div>}

      {error && (
        <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', padding: '1rem', borderRadius: '8px', border: '1px solid rgba(239, 68, 68, 0.2)' }}>
          {error}
        </div>
      )}

      {scannedProduct && !loading && (
        <div className="glass-panel slide-up" style={{ padding: '1.5rem' }}>
          <div style={{ fontSize: '0.8rem', color: '#10b981', fontWeight: 'bold', marginBottom: '0.25rem' }}>HASIL PENCARIAN</div>
          <h3 style={{ fontSize: '1.2rem', marginBottom: '1rem', lineHeight: '1.3' }}>{scannedProduct.name}</h3>
          
          <div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '0.75rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '0.5rem', borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
              <span style={{ color: 'var(--text-muted)' }}>Barcode/SKU</span>
              <span style={{ fontWeight: 'bold' }}>{scannedProduct.barcode || scannedProduct.sku}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '0.5rem', borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
              <span style={{ color: 'var(--text-muted)' }}>Harga Jual Dasar</span>
              <span style={{ fontWeight: 'bold', color: '#10b981' }}>{formatCurrency(scannedProduct.harga_jual_1)}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '0.5rem', borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
              <span style={{ color: 'var(--text-muted)' }}>Sisa Stok (Sistem)</span>
              <span style={{ fontWeight: 'bold', fontSize: '1.1rem', color: scannedProduct.stock_quantity <= 0 ? '#ef4444' : 'inherit' }}>
                {scannedProduct.stock_quantity} <span style={{ fontSize: '0.8rem', fontWeight: 'normal' }}>{scannedProduct.unit_of_measure}</span>
              </span>
            </div>
            {scannedProduct.rack_code && (
              <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '0.5rem', borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
                <span style={{ color: 'var(--text-muted)' }}>Lokasi Rak</span>
                <span style={{ fontWeight: 'bold' }}>{scannedProduct.rack_code}</span>
              </div>
            )}
          </div>
          
          <button 
            className="btn-secondary" 
            style={{ width: '100%', marginTop: '1.5rem', padding: '0.75rem' }}
            onClick={() => setScannedProduct(null)}
          >
            Tutup
          </button>
        </div>
      )}
    </div>
  );
}
