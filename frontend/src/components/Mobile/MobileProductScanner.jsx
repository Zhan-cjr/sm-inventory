import React, { useState, useEffect, useRef } from 'react';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { 
  Camera, 
  Search, 
  Barcode, 
  MapPin, 
  Layers, 
  Tag, 
  X, 
  RotateCcw, 
  CheckCircle2, 
  AlertCircle,
  Store,
  ArrowRight,
  History
} from 'lucide-react';

export function MobileProductScanner({ user, authToken }) {
  const [scannedProduct, setScannedProduct] = useState(null);
  const [isScanning, setIsScanning] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const scannerRef = useRef(null);
  const [productsCache, setProductsCache] = useState([]);
  const [recentScans, setRecentScans] = useState([]);
  const [manualCode, setManualCode] = useState('');
  
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
        { fps: 15, qrbox: { width: 260, height: 140 } },
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
    if (!barcode) return;
    setError(null);
    setLoading(true);

    const term = barcode.trim().toLowerCase();
    // Cari di local cache dulu (dukung barcode utama, SKU, multi barcode, dan nama)
    const found = productsCache.find(p => {
      const bc = (p.barcode || '').toLowerCase();
      const sku = (p.sku || '').toLowerCase();
      const name = (p.name || '').toLowerCase();
      const addBc = Array.isArray(p.metadata?.additional_barcodes)
        ? p.metadata.additional_barcodes.map(b => String(b).toLowerCase())
        : [];
      return bc === term || sku === term || addBc.includes(term) || name.includes(term);
    });
    
    if (found) {
      setScannedProduct(found);
      // Tambah ke riwayat scan (max 5)
      setRecentScans(prev => {
        const filtered = prev.filter(item => item.id !== found.id);
        return [found, ...filtered].slice(0, 5);
      });
    } else {
      setError(`Produk "${barcode}" tidak ditemukan di database cabang ini.`);
      setScannedProduct(null);
    }
    setLoading(false);
  };

  const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem', animation: 'fadeIn 0.3s ease-out' }}>
      
      {/* Title */}
      <div>
        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600 }}>
          Pemeriksaan Produk
        </div>
        <h2 style={{ fontSize: '1.4rem', fontWeight: 800, margin: 0, color: 'var(--text-main)' }}>
          Cek Harga & Stok
        </h2>
      </div>

      {/* Branch Selector */}
      {showBranchSelector && (
        <div style={{ background: 'var(--bg-card)', border: '1px solid var(--border-light)', padding: '0.75rem 1rem', borderRadius: '16px', display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <Store size={18} color="#10b981" />
          <div style={{ flex: 1 }}>
            <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Cabang Aktif (Admin)</div>
            <select 
              value={selectedBranchId} 
              onChange={(e) => setSelectedBranchId(e.target.value)}
              style={{ width: '100%', cursor: 'pointer', border: 'none', background: 'transparent', color: 'var(--text-main)', fontWeight: 700, fontSize: '0.9rem', outline: 'none' }}
            >
              <option value="" disabled style={{ background: 'var(--bg-dark)' }}>-- Pilih Cabang --</option>
              {branches.map(b => (
                <option key={b.id} value={b.id} style={{ background: 'var(--bg-dark)' }}>{b.name}</option>
              ))}
            </select>
          </div>
        </div>
      )}

      {/* Main Scanner Launcher / Viewfinder */}
      {!isScanning ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          {/* Big Tap to Scan Camera Button */}
          <button 
            onClick={() => setIsScanning(true)}
            style={{ 
              width: '100%', 
              padding: '1.75rem 1rem', 
              borderRadius: '24px', 
              background: 'linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.08) 100%)',
              border: '2px dashed rgba(16, 185, 129, 0.4)',
              color: 'var(--text-main)',
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '0.75rem',
              cursor: 'pointer',
              boxShadow: '0 8px 24px rgba(16, 185, 129, 0.1)',
              transition: 'all 0.2s ease'
            }}
          >
            <div style={{ background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)', width: '56px', height: '56px', borderRadius: '20px', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'white', boxShadow: '0 6px 18px rgba(16, 185, 129, 0.4)' }}>
              <Camera size={28} />
            </div>
            <div>
              <div style={{ fontSize: '1.1rem', fontWeight: 800 }}>TAP UNTUK SCAN BARCODE KAMERA</div>
              <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', marginTop: '2px' }}>Gunakan kamera HP untuk memindai otomatis</div>
            </div>
          </button>
          
          {/* Manual Input Search Bar */}
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', background: 'var(--bg-card)', padding: '6px 6px 6px 14px', borderRadius: '16px', border: '1px solid var(--border-light)' }}>
            <Search size={18} color="var(--text-muted)" />
            <input 
              type="text" 
              value={manualCode}
              onChange={(e) => setManualCode(e.target.value)}
              style={{ flex: 1, padding: '0.65rem 0', background: 'transparent', border: 'none', color: 'var(--text-main)', outline: 'none', fontSize: '0.9rem', fontWeight: 600 }}
              placeholder="Atau ketik Barcode / SKU / Nama Produk..."
              onKeyDown={(e) => {
                if (e.key === 'Enter' && manualCode.trim() !== '') {
                  handleLookup(manualCode);
                }
              }}
            />
            {manualCode && (
              <button 
                onClick={() => setManualCode('')}
                style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer', padding: '4px' }}
              >
                <X size={16} />
              </button>
            )}
            <button 
              onClick={() => handleLookup(manualCode)}
              style={{ padding: '0.65rem 1.25rem', borderRadius: '12px', background: '#10b981', border: 'none', color: 'white', fontWeight: 700, fontSize: '0.85rem', cursor: 'pointer' }}
            >
              Cari
            </button>
          </div>
        </div>
      ) : (
        /* Active Scanner Reticle View */
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <div className="scanner-reticle-box">
            <div className="scanner-laser-line"></div>
            <div id="reader" style={{ width: '100%', minHeight: '260px' }}></div>
          </div>

          <button 
            onClick={() => setIsScanning(false)}
            style={{ width: '100%', padding: '0.9rem', borderRadius: '16px', background: 'rgba(239, 68, 68, 0.15)', border: '1px solid rgba(239, 68, 68, 0.3)', color: '#ef4444', fontWeight: 700, fontSize: '0.9rem', cursor: 'pointer' }}
          >
            Batal Pemindaian
          </button>
        </div>
      )}

      {/* Loading Spinner */}
      {loading && (
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <div className="spin" style={{ width: '32px', height: '32px', border: '3px solid #10b981', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
          <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginTop: '0.75rem' }}>Mencari data produk...</div>
        </div>
      )}

      {/* Error Message */}
      {error && !loading && (
        <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', padding: '1rem 1.25rem', borderRadius: '16px', border: '1px solid rgba(239, 68, 68, 0.25)', display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <AlertCircle size={22} style={{ flexShrink: 0 }} />
          <div style={{ fontSize: '0.88rem', fontWeight: 600 }}>{error}</div>
        </div>
      )}

      {/* Scanned Product Card Sheet */}
      {scannedProduct && !loading && (
        <div className="pwa-card" style={{ animation: 'slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1)', border: '1px solid rgba(16, 185, 129, 0.3)' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '0.75rem' }}>
            <span style={{ fontSize: '0.7rem', fontWeight: 800, color: '#10b981', background: 'rgba(16, 185, 129, 0.12)', padding: '4px 10px', borderRadius: '99px', letterSpacing: '0.5px' }}>
              PRODUK DITEMUKAN
            </span>
            <button 
              onClick={() => setScannedProduct(null)} 
              style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer', padding: '2px' }}
            >
              <X size={18} />
            </button>
          </div>

          <h3 style={{ fontSize: '1.25rem', fontWeight: 800, margin: '0 0 1rem', color: 'var(--text-main)', lineHeight: 1.3 }}>
            {scannedProduct.name}
          </h3>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
            {/* Barcode & SKU */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingBottom: '0.5rem', borderBottom: '1px solid var(--border-light)' }}>
              <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <Barcode size={16} /> Barcode / SKU
              </span>
              <span style={{ fontWeight: 700, fontSize: '0.9rem' }}>{scannedProduct.barcode || scannedProduct.sku}</span>
            </div>

            {/* Selling Price */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingBottom: '0.5rem', borderBottom: '1px solid var(--border-light)' }}>
              <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <Tag size={16} color="#10b981" /> Harga Jual Utama
              </span>
              <span style={{ fontWeight: 900, fontSize: '1.2rem', color: '#10b981' }}>{formatCurrency(scannedProduct.harga_jual_1)}</span>
            </div>

            {/* Tier Price 2 if exists */}
            {scannedProduct.harga_jual_2 > 0 && (
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingBottom: '0.5rem', borderBottom: '1px solid var(--border-light)' }}>
                <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>Harga Grosir (Tier 2)</span>
                <span style={{ fontWeight: 700, fontSize: '0.95rem' }}>{formatCurrency(scannedProduct.harga_jual_2)}</span>
              </div>
            )}

            {/* Stock Quantity Status */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingBottom: '0.5rem', borderBottom: '1px solid var(--border-light)' }}>
              <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <Layers size={16} /> Sisa Stok Sistem
              </span>
              <div style={{ textAlign: 'right' }}>
                <span style={{ 
                  fontWeight: 900, 
                  fontSize: '1.1rem', 
                  color: (scannedProduct.quantity_on_hand !== undefined ? scannedProduct.quantity_on_hand : scannedProduct.stock_quantity) <= 0 
                    ? '#ef4444' 
                    : ((scannedProduct.quantity_on_hand !== undefined ? scannedProduct.quantity_on_hand : scannedProduct.stock_quantity) < 5 ? '#f59e0b' : '#10b981')
                }}>
                  {scannedProduct.quantity_on_hand !== undefined ? scannedProduct.quantity_on_hand : scannedProduct.stock_quantity}
                </span>
                <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginLeft: '4px' }}>
                  {scannedProduct.unit_of_measure || 'Pcs'}
                </span>
              </div>
            </div>

            {/* Rack Location */}
            {scannedProduct.rack_code && (
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <MapPin size={16} color="#6366f1" /> Lokasi Rak
                </span>
                <span style={{ fontWeight: 700, background: 'rgba(99, 102, 241, 0.15)', color: '#6366f1', padding: '2px 10px', borderRadius: '8px', fontSize: '0.85rem' }}>
                  {scannedProduct.rack_code}
                </span>
              </div>
            )}
          </div>

          <button 
            onClick={() => setScannedProduct(null)}
            style={{ width: '100%', marginTop: '1.25rem', padding: '0.85rem', borderRadius: '14px', background: 'rgba(255,255,255,0.08)', border: '1px solid var(--border-light)', color: 'var(--text-main)', fontWeight: 700, fontSize: '0.9rem', cursor: 'pointer' }}
          >
            Selesai / Scan Selanjutnya
          </button>
        </div>
      )}

      {/* Session Recent Scans Pill History */}
      {recentScans.length > 0 && (
        <div className="pwa-card">
          <div style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)', marginBottom: '0.65rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <History size={16} /> Riwayat Scan Sesi Ini
          </div>
          <div style={{ display: 'flex', gap: '0.5rem', overflowX: 'auto', paddingBottom: '4px' }}>
            {recentScans.map((prod, idx) => (
              <button
                key={idx}
                onClick={() => setScannedProduct(prod)}
                style={{
                  background: scannedProduct?.id === prod.id ? 'rgba(16, 185, 129, 0.2)' : 'rgba(255,255,255,0.05)',
                  border: scannedProduct?.id === prod.id ? '1px solid #10b981' : '1px solid var(--border-light)',
                  padding: '6px 12px',
                  borderRadius: '12px',
                  whiteSpace: 'nowrap',
                  cursor: 'pointer',
                  color: 'var(--text-main)',
                  fontSize: '0.78rem',
                  fontWeight: 600,
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px'
                }}
              >
                <span>{prod.name}</span>
                <span style={{ color: '#10b981', fontWeight: 700 }}>{formatCurrency(prod.harga_jual_1)}</span>
              </button>
            ))}
          </div>
        </div>
      )}

    </div>
  );
}
