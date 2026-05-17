import React, { useState, useEffect } from 'react';
import { 
  ArrowLeft, 
  RefreshCw, 
  TrendingUp, 
  AlertTriangle, 
  CheckCircle,
  Package,
  PlusCircle,
  Search
} from 'lucide-react';

export const SuggestedOrders = ({ user, authToken, onBack }) => {
  const [suggestions, setSuggestions] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [supplierFilter, setSupplierFilter] = useState('');
  const [selectedItems, setSelectedItems] = useState([]);

  const toggleSelect = (productId) => {
    setSelectedItems(prev => 
      prev.includes(productId) 
        ? prev.filter(id => id !== productId) 
        : [...prev, productId]
    );
  };

  const toggleSelectAll = (e) => {
    if (e.target.checked) {
      setSelectedItems(filteredSuggestions.map(s => s.product_id));
    } else {
      setSelectedItems([]);
    }
  };

  const handleBulkCreatePO = () => {
    const itemsToCreate = suggestions
      .filter(s => selectedItems.includes(s.product_id) && s.suggested_qty > 0)
      .map(s => ({
        product_id: s.product_id,
        suggested_qty: s.suggested_qty
      }));

    if (itemsToCreate.length === 0) {
      alert('Pilih produk yang memiliki saran jumlah pesanan terlebih dahulu.');
      return;
    }

    if (!confirm(`Buat satu draft Pesanan Pembelian untuk ${itemsToCreate.length} produk terpilih?`)) return;

    setLoading(true);
    fetch('/api/v1/purchase-orders/create-bulk-from-suggestions', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ items: itemsToCreate })
    })
    .then(res => {
      if (!res.ok) throw new Error('Gagal membuat Pesanan Pembelian Massal');
      return res.json();
    })
    .then(data => {
      alert(data.message);
      setSelectedItems([]);
      // Redirect to the Filament edit page for the newly created PO
      window.location.href = `/admin/purchase-orders/${data.po.id}/edit`;
    })
    .catch(err => {
      alert(err.message);
      setLoading(false);
    });
  };

  const fetchSuggestions = () => {
    setLoading(true);
    fetch('/api/v1/suggested-orders', {
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    })
    .then(res => {
      if (!res.ok) throw new Error('Gagal mengambil data peramalan');
      return res.json();
    })
    .then(res => {
      setSuggestions(res.data);
      setLoading(false);
    })
    .catch(err => {
      setError(err.message);
      setLoading(false);
    });
  };

  const handleCreatePO = (item) => {
    if (!confirm(`Buat draft Pesanan Pembelian untuk ${item.name} sebanyak ${item.suggested_qty} unit?`)) return;

    setLoading(true);
    fetch('/api/v1/purchase-orders/create-from-suggestion', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        product_id: item.product_id,
        suggested_qty: item.suggested_qty
      })
    })
    .then(res => {
      if (!res.ok) throw new Error('Gagal membuat Pesanan Pembelian');
      return res.json();
    })
    .then(data => {
      alert(data.message);
      fetchSuggestions();
    })
    .catch(err => {
      alert(err.message);
      setLoading(false);
    });
  };

  const fetchSuppliers = () => {
    fetch('/api/v1/suppliers', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
    .then(res => res.json())
    .then(data => setSuppliers(data))
    .catch(err => console.error('Gagal mengambil data supplier', err));
  };

  useEffect(() => {
    fetchSuggestions();
    fetchSuppliers();
  }, [authToken]);

  const filteredSuggestions = suggestions.filter(s => {
    const matchesSearch = s.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         s.sku.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesSupplier = supplierFilter === '' || s.supplier_id === supplierFilter;
    return matchesSearch && matchesSupplier;
  });

  const getStatusColor = (status) => {
    switch (status) {
      case 'CRITICAL': return '#ef4444';
      case 'REORDER': return '#f59e0b';
      case 'OK': return '#10b981';
      default: return 'var(--text-muted)';
    }
  };

  if (loading) return (
    <div className="app-container" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
      <div style={{ textAlign: 'center' }}>
        <RefreshCw className="spin" size={48} color="var(--primary)" />
        <p style={{ marginTop: '1rem', color: 'var(--text-muted)' }}>Menganalisis riwayat penjualan...</p>
      </div>
    </div>
  );

  return (
    <div className="app-container">
      <header className="pos-header glassmorphism" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <button onClick={onBack} className="btn-icon-only" style={{ background: 'rgba(255,255,255,0.05)', border: 'none', color: 'white', cursor: 'pointer', padding: '10px', borderRadius: '50%', display: 'flex' }}>
            <ArrowLeft size={20} />
          </button>
          <div>
            <h2 style={{ fontSize: '1.5rem', fontWeight: 600 }}>Saran Pemesanan</h2>
            <p style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>Analisis Peramalan Stok & Forecasting</p>
          </div>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          {selectedItems.length > 0 && (
            <button 
              onClick={handleBulkCreatePO} 
              className="btn-primary" 
              style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', background: '#10b981' }}
            >
              <PlusCircle size={18} /> Buat PO Terpilih ({selectedItems.length})
            </button>
          )}
          <button onClick={fetchSuggestions} className="btn-secondary" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <RefreshCw size={16} /> Refresh
          </button>
        </div>
      </header>

      {/* Summary Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1rem', marginBottom: '2rem' }}>
        <div className="glass-panel" style={{ textAlign: 'center' }}>
          <div style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Perlu Dipesan</div>
          <div style={{ fontSize: '2rem', fontWeight: 700, color: '#f59e0b' }}>
            {suggestions.filter(s => s.suggested_qty > 0).length}
          </div>
        </div>
        <div className="glass-panel" style={{ textAlign: 'center' }}>
          <div style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Stok Kritis</div>
          <div style={{ fontSize: '2rem', fontWeight: 700, color: '#ef4444' }}>
            {suggestions.filter(s => s.status === 'CRITICAL').length}
          </div>
        </div>
        <div className="glass-panel" style={{ textAlign: 'center' }}>
          <div style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>ADS Tertinggi</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 700 }}>
            {suggestions.length > 0 ? Math.max(...suggestions.map(s => s.ads)) : 0} <span style={{fontSize:'0.9rem', fontWeight:400}}>unit/hr</span>
          </div>
        </div>
      </div>

      <div className="glass-panel" style={{ padding: 0, overflow: 'hidden' }}>
        <div style={{ padding: '1rem', borderBottom: '1px solid var(--border-light)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ display: 'flex', gap: '1rem' }}>
            <div style={{ position: 'relative', width: '300px' }}>
              <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
              <input 
                type="text" 
                placeholder="Cari produk atau SKU..." 
                className="modern-barcode-input" 
                style={{ width: '100%', paddingLeft: '40px', fontSize: '0.9rem' }}
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            
            <select
              value={supplierFilter}
              onChange={(e) => setSupplierFilter(e.target.value)}
              style={{ padding: '0 12px', background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)', borderRadius: '8px', color: 'white', fontSize: '0.9rem' }}
            >
              <option value="">Semua Supplier</option>
              {suppliers.map(sup => (
                <option key={sup.id} value={sup.id}>{sup.name}</option>
              ))}
            </select>
          </div>
          <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>
            Menampilkan {filteredSuggestions.length} produk
          </div>
        </div>

        <div style={{ overflowX: 'auto' }}>
          <table className="pos-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ textAlign: 'left', background: 'rgba(255,255,255,0.02)' }}>
                <th style={{ padding: '1rem' }}>
                  <input 
                    type="checkbox" 
                    onChange={toggleSelectAll}
                    checked={filteredSuggestions.length > 0 && selectedItems.length === filteredSuggestions.length}
                  />
                </th>
                <th style={{ padding: '1rem' }}>Produk / SKU</th>
                <th style={{ padding: '1rem' }}>Stok Saat Ini</th>
                <th style={{ padding: '1rem' }}>ADS (Sales/Hr)</th>
                <th style={{ padding: '1rem' }}>Titik Pesan (ROP)</th>
                <th style={{ padding: '1rem' }}>Saran Pesan</th>
                <th style={{ padding: '1rem' }}>Status</th>
                <th style={{ padding: '1rem' }}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {filteredSuggestions.map((item, i) => (
                <tr key={item.product_id} style={{ borderBottom: '1px solid rgba(255,255,255,0.05)', transition: 'background 0.2s' }}>
                  <td style={{ padding: '1rem' }}>
                    <input 
                      type="checkbox" 
                      checked={selectedItems.includes(item.product_id)}
                      onChange={() => toggleSelect(item.product_id)}
                    />
                  </td>
                  <td style={{ padding: '1rem' }}>
                    <div style={{ fontWeight: 600 }}>{item.name}</div>
                    <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{item.sku}</div>
                  </td>
                  <td style={{ padding: '1rem' }}>{item.current_qty} unit</td>
                  <td style={{ padding: '1rem' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                      <TrendingUp size={14} color="var(--primary)" />
                      {item.ads}
                    </div>
                  </td>
                  <td style={{ padding: '1rem' }}>{item.reorder_point} unit</td>
                  <td style={{ padding: '1rem' }}>
                    {item.suggested_qty > 0 ? (
                      <span style={{ fontWeight: 700, color: 'var(--primary)', fontSize: '1.1rem' }}>
                        {item.suggested_qty}
                      </span>
                    ) : '-'}
                  </td>
                  <td style={{ padding: '1rem' }}>
                    <span style={{ 
                      padding: '4px 12px', 
                      borderRadius: '12px', 
                      fontSize: '0.75rem', 
                      fontWeight: 700, 
                      background: `${getStatusColor(item.status)}20`,
                      color: getStatusColor(item.status),
                      border: `1px solid ${getStatusColor(item.status)}40`
                    }}>
                      {item.status}
                    </span>
                  </td>
                  <td style={{ padding: '1rem' }}>
                    {item.suggested_qty > 0 && (
                      <button 
                        onClick={() => handleCreatePO(item)}
                        className="btn-primary-sm" 
                        style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}
                      >
                        <PlusCircle size={14} /> Buat PO
                      </button>
                    )}
                  </td>
                </tr>
              ))}
              {filteredSuggestions.length === 0 && (
                <tr>
                  <td colSpan="8" style={{ padding: '3rem', textAlign: 'center', color: 'var(--text-muted)' }}>
                    Tidak ada data saran pemesanan ditemukan.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
