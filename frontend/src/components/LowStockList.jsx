import React, { useState, useEffect } from 'react';
import { ArrowLeft, AlertCircle, Package, RefreshCw, Search } from 'lucide-react';

export const LowStockList = ({ authToken, onBack }) => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');

  const fetchData = () => {
    setLoading(true);
    fetch('/api/v1/dashboard/low-stock', {
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    })
    .then(res => {
      if (!res.ok) throw new Error('Gagal mengambil data stok');
      return res.json();
    })
    .then(data => {
      setProducts(data);
      setLoading(false);
    })
    .catch(err => {
      setError(err.message);
      setLoading(false);
    });
  };

  useEffect(() => {
    fetchData();
  }, [authToken]);

  const filteredProducts = products.filter(p => 
    p.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="app-container">
      <header className="pos-header glassmorphism" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <button onClick={onBack} style={{ background: 'rgba(255,255,255,0.05)', border: 'none', color: 'white', padding: '8px', borderRadius: '12px', cursor: 'pointer', display: 'flex', alignItems: 'center' }}>
            <ArrowLeft size={20} />
          </button>
          <h2 style={{ fontSize: '1.5rem', fontWeight: 600 }}>Daftar Stok Menipis</h2>
        </div>
        <div style={{ display: 'flex', gap: '1rem' }}>
           <button onClick={fetchData} className="btn-secondary" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
             <RefreshCw size={18} className={loading ? 'spin' : ''} /> Refresh
           </button>
        </div>
      </header>

      <div className="glass-panel" style={{ marginBottom: '1.5rem', padding: '1rem' }}>
        <div style={{ position: 'relative' }}>
          <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
          <input 
            type="text" 
            placeholder="Cari produk..." 
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={{ 
              width: '100%', 
              background: 'rgba(255,255,255,0.05)', 
              border: '1px solid var(--border-light)', 
              borderRadius: '12px', 
              padding: '12px 12px 12px 40px', 
              color: 'white',
              fontSize: '1rem'
            }}
          />
        </div>
      </div>

      {loading ? (
        <div style={{ display: 'flex', justifyContent: 'center', padding: '5rem' }}><div className="spinner"></div></div>
      ) : error ? (
        <div className="alert-msg">{error}</div>
      ) : (
        <div className="glass-panel" style={{ padding: 0, overflow: 'hidden' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid var(--border-light)', background: 'rgba(255,255,255,0.02)' }}>
                <th style={{ padding: '1.25rem', color: 'var(--text-muted)', fontWeight: 600, fontSize: '0.85rem', textTransform: 'uppercase' }}>Nama Produk</th>
                <th style={{ padding: '1.25rem', color: 'var(--text-muted)', fontWeight: 600, fontSize: '0.85rem', textTransform: 'uppercase' }}>Stok Saat Ini</th>
                <th style={{ padding: '1.25rem', color: 'var(--text-muted)', fontWeight: 600, fontSize: '0.85rem', textTransform: 'uppercase' }}>Min. Stok</th>
                <th style={{ padding: '1.25rem', color: 'var(--text-muted)', fontWeight: 600, fontSize: '0.85rem', textTransform: 'uppercase' }}>Status</th>
              </tr>
            </thead>
            <tbody>
              {filteredProducts.map((p, i) => (
                <tr key={i} style={{ borderBottom: '1px solid rgba(255,255,255,0.05)', transition: 'background 0.2s' }}>
                  <td style={{ padding: '1.25rem' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                      <Package size={18} color="var(--primary)" />
                      <span style={{ fontWeight: 500 }}>{p.name}</span>
                    </div>
                  </td>
                  <td style={{ padding: '1.25rem' }}>
                    <span style={{ color: p.quantity_on_hand <= 0 ? '#ef4444' : '#f59e0b', fontWeight: 700, fontSize: '1.1rem' }}>
                        {p.quantity_on_hand}
                    </span>
                    <span style={{ marginLeft: '4px', fontSize: '0.85rem', color: 'var(--text-muted)' }}>{p.unit}</span>
                  </td>
                  <td style={{ padding: '1.25rem', color: 'var(--text-muted)' }}>{p.min_qty} {p.unit}</td>
                  <td style={{ padding: '1.25rem' }}>
                    <div style={{ 
                        display: 'inline-flex', 
                        alignItems: 'center', 
                        gap: '0.4rem', 
                        padding: '4px 12px', 
                        borderRadius: '20px', 
                        fontSize: '0.75rem', 
                        fontWeight: 600,
                        background: p.quantity_on_hand <= 0 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)',
                        color: p.quantity_on_hand <= 0 ? '#ef4444' : '#f59e0b',
                        border: p.quantity_on_hand <= 0 ? '1px solid rgba(239, 68, 68, 0.2)' : '1px solid rgba(245, 158, 11, 0.2)'
                    }}>
                      <AlertCircle size={14} />
                      {p.quantity_on_hand <= 0 ? 'HABIS' : 'KRITIS'}
                    </div>
                  </td>
                </tr>
              ))}
              {filteredProducts.length === 0 && (
                <tr>
                  <td colSpan="4" style={{ padding: '4rem', textAlign: 'center', color: 'var(--text-muted)' }}>
                    <div style={{ opacity: 0.3, marginBottom: '1rem' }}><Package size={48} /></div>
                    Tidak ada produk dengan stok menipis.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};
