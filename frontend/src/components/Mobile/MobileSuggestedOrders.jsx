import React, { useState, useEffect } from 'react';
import { PackageSearch, AlertCircle, TrendingDown, TrendingUp, CheckCircle, PlusCircle, ArrowRight } from 'lucide-react';

export function MobileSuggestedOrders({ user, authToken }) {
  const [suggestions, setSuggestions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [processing, setProcessing] = useState(false);
  const [successMsg, setSuccessMsg] = useState(null);
  
  const [selectedItems, setSelectedItems] = useState(new Set());
  const [showFaq, setShowFaq] = useState(false);
  
  const [branches, setBranches] = useState([]);
  const [selectedBranchId, setSelectedBranchId] = useState('');

  useEffect(() => {
    fetchBranches();
  }, []);

  useEffect(() => {
    if (selectedBranchId) {
      fetchSuggestions(selectedBranchId);
    }
  }, [selectedBranchId]);

  const fetchBranches = async () => {
    try {
      const res = await fetch('/api/v1/branches', {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      if (res.ok) {
        const data = await res.json();
        setBranches(data);
        if (data.length > 0) {
          // Default to the device branch if available and in the list, otherwise the first branch
          const deviceBranchId = localStorage.getItem('pos_device_branch_id');
          const found = data.find(b => b.id === deviceBranchId);
          setSelectedBranchId(found ? found.id : data[0].id);
        } else {
          // If no branches returned but user has one, fallback to user.branch_id
          const fallbackId = user?.branch_id || localStorage.getItem('pos_device_branch_id');
          setSelectedBranchId(fallbackId);
        }
      }
    } catch (err) {
      console.error('Failed to fetch branches:', err);
      // Fallback
      setSelectedBranchId(user?.branch_id || localStorage.getItem('pos_device_branch_id'));
    }
  };

  const fetchSuggestions = async (branchIdToFetch) => {
    setLoading(true);
    setError(null);
    try {
      const branchId = branchIdToFetch || selectedBranchId || user?.branch_id || localStorage.getItem('pos_device_branch_id');
      const url = branchId ? `/api/v1/suggested-orders?branch_id=${branchId}` : '/api/v1/suggested-orders';
      
      const res = await fetch(url, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      if (!res.ok) throw new Error('Gagal memuat rekomendasi order');
      const data = await res.json();
      
      // Filter out only those that need reordering
      const needsReorder = (data.data || []).filter(item => item.status === 'REORDER' || item.status === 'CRITICAL');
      // Sort by status CRITICAL first
      needsReorder.sort((a, b) => {
        if (a.status === 'CRITICAL' && b.status !== 'CRITICAL') return -1;
        if (a.status !== 'CRITICAL' && b.status === 'CRITICAL') return 1;
        return 0;
      });
      
      setSuggestions(needsReorder);
      
      // Select all by default
      const allIds = new Set(needsReorder.map(item => item.product_id));
      setSelectedItems(allIds);
      
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleToggleSelect = (productId) => {
    const newSelected = new Set(selectedItems);
    if (newSelected.has(productId)) {
      newSelected.delete(productId);
    } else {
      newSelected.add(productId);
    }
    setSelectedItems(newSelected);
  };

  const handleQtyChange = (productId, newQty) => {
    setSuggestions(prev => prev.map(item => 
      item.product_id === productId 
        ? { ...item, edited_qty: newQty } 
        : item
    ));
  };

  const handleCreateBulkPO = async () => {
    if (selectedItems.size === 0) return;
    
    setProcessing(true);
    setError(null);
    setSuccessMsg(null);
    
    // Build payload
    const itemsToOrder = suggestions
      .filter(item => selectedItems.has(item.product_id))
      .map(item => ({
        product_id: item.product_id,
        suggested_qty: item.edited_qty !== undefined && item.edited_qty !== '' ? parseFloat(item.edited_qty) || 0 : item.suggested_qty,
        original_qty: item.suggested_qty
      }));
      
    try {
      const branchId = selectedBranchId || user?.branch_id || localStorage.getItem('pos_device_branch_id');
      const payload = { items: itemsToOrder };
      if (branchId) payload.branch_id = branchId;

      const res = await fetch('/api/v1/purchase-orders/create-bulk-from-suggestions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify(payload)
      });
      
      if (!res.ok) {
        const data = await res.json();
        throw new Error(data.message || 'Gagal membuat Draft PO');
      }
      
      const responseData = await res.json();
      const poNumbers = responseData.po_numbers ? responseData.po_numbers.join(', ') : (responseData.po?.po_number || '');
      setSuccessMsg(`${responseData.message} (${poNumbers})`);
      
      // Remove selected items from view
      setSuggestions(prev => prev.filter(item => !selectedItems.has(item.product_id)));
      setSelectedItems(new Set());
      
    } catch (err) {
      setError(err.message);
    } finally {
      setProcessing(false);
    }
  };

  if (loading) {
    return (
      <div className="mobile-page-content" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100%' }}>
        <div className="loading-spinner"></div>
      </div>
    );
  }

  return (
    <div className="mobile-page-content" style={{ paddingBottom: '80px' }}>
      <div className="mobile-page-header" style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <h2 className="mobile-page-title" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <TrendingDown size={20} color="var(--primary)" />
            Order Pintar
          </h2>
          <div style={{ display: 'flex', gap: '0.5rem' }}>
            <button onClick={() => setShowFaq(true)} className="btn-icon-only" style={{ background: 'rgba(59, 130, 246, 0.2)', color: '#60a5fa', border: '1px solid rgba(59, 130, 246, 0.3)', padding: '0.5rem', borderRadius: '8px' }}>
              <AlertCircle size={18} />
            </button>
            <button onClick={() => fetchSuggestions()} className="btn-secondary" style={{ padding: '0.5rem' }}>
              Refresh
            </button>
          </div>
        </div>
        <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>
          Rekomendasi pesanan berdasarkan Average Daily Sales (ADS) 90 hari terakhir.
        </p>

        {branches.length > 1 && (
          <div style={{ marginTop: '0.5rem' }}>
            <select 
              value={selectedBranchId} 
              onChange={(e) => setSelectedBranchId(e.target.value)}
              style={{
                width: '100%',
                padding: '0.75rem',
                borderRadius: '8px',
                background: 'rgba(255,255,255,0.05)',
                color: 'white',
                border: '1px solid rgba(255,255,255,0.1)',
                outline: 'none'
              }}
            >
              <option value="" disabled>-- Pilih Cabang --</option>
              {branches.map(branch => (
                <option key={branch.id} value={branch.id} style={{ color: 'black' }}>
                  {branch.name}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      {error && (
        <div className="alert-error" style={{ margin: '1rem', padding: '1rem', borderRadius: '8px', background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          <AlertCircle size={20} />
          <span>{error}</span>
        </div>
      )}

      {successMsg && (
        <div style={{ margin: '1rem', padding: '1rem', borderRadius: '8px', background: 'rgba(16, 185, 129, 0.1)', color: '#10b981', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          <CheckCircle size={20} />
          <span>{successMsg}</span>
        </div>
      )}

      {suggestions.length === 0 ? (
        <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>
          <PackageSearch size={48} style={{ opacity: 0.5, margin: '0 auto 1rem' }} />
          <p>Semua stok produk aman.</p>
          <p style={{ fontSize: '0.85rem' }}>Tidak ada rekomendasi order saat ini.</p>
        </div>
      ) : (
        <div style={{ padding: '1rem', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
            <span style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>
              {selectedItems.size} dari {suggestions.length} produk dipilih
            </span>
            <button 
              onClick={() => {
                if (selectedItems.size === suggestions.length) setSelectedItems(new Set());
                else setSelectedItems(new Set(suggestions.map(i => i.product_id)));
              }}
              style={{ background: 'none', border: 'none', color: 'var(--primary)', fontWeight: 600, padding: 0 }}
            >
              {selectedItems.size === suggestions.length ? 'Batal Pilih Semua' : 'Pilih Semua'}
            </button>
          </div>

          {suggestions.map((item) => (
            <div 
              key={item.product_id} 
              className="glass-panel" 
              style={{ 
                padding: '1rem', 
                borderRadius: '12px',
                border: selectedItems.has(item.product_id) ? '1px solid var(--primary)' : '1px solid rgba(255,255,255,0.05)',
                display: 'flex',
                gap: '1rem',
                alignItems: 'flex-start',
                transition: 'all 0.2s'
              }}
              onClick={() => handleToggleSelect(item.product_id)}
            >
              <div style={{ paddingTop: '0.2rem' }}>
                <div style={{ 
                  width: '24px', height: '24px', 
                  borderRadius: '50%', 
                  border: selectedItems.has(item.product_id) ? 'none' : '2px solid var(--text-muted)',
                  background: selectedItems.has(item.product_id) ? 'var(--primary)' : 'transparent',
                  display: 'flex', alignItems: 'center', justifyContent: 'center'
                }}>
                  {selectedItems.has(item.product_id) && <CheckCircle size={16} color="white" />}
                </div>
              </div>
              
              <div style={{ flex: 1 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '0.5rem' }}>
                  <div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{item.sku}</div>
                    <div style={{ fontWeight: 600, color: 'white', lineHeight: '1.2' }}>{item.name}</div>
                  </div>
                  {item.status === 'CRITICAL' ? (
                    <span style={{ background: '#ef4444', color: 'white', fontSize: '0.65rem', padding: '2px 6px', borderRadius: '4px', fontWeight: 'bold' }}>KRITIS</span>
                  ) : (
                    <span style={{ background: '#f59e0b', color: 'white', fontSize: '0.65rem', padding: '2px 6px', borderRadius: '4px', fontWeight: 'bold' }}>REORDER</span>
                  )}
                </div>

                  <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem', marginTop: '0.5rem', fontSize: '0.85rem' }}>
                    <div>
                      <span style={{ color: 'var(--text-muted)' }}>Stok Saat Ini:</span> <br />
                      <strong>{item.current_qty}</strong>
                    </div>
                    <div>
                      <span style={{ color: 'var(--text-muted)' }}>Jual/Hari (ADS):</span> <br />
                      <strong>{item.ads}</strong>
                    </div>
                    <div>
                      <span style={{ color: 'var(--text-muted)' }}>Batas Aman:</span> <br />
                      <strong>{item.reorder_point}</strong>
                    </div>
                    <div>
                      <span style={{ color: 'var(--text-muted)' }}>Target Stok:</span> <br />
                      <strong>{item.target_days || 30} hari</strong>
                    </div>
                    <div>
                      <span style={{ color: 'var(--text-muted)' }}>Saran Order:</span> <br />
                      <strong style={{ color: 'var(--primary)', fontSize: '1rem' }}>+{item.suggested_qty}</strong>
                    </div>
                  </div>
                  <div style={{ marginTop: '0.75rem', fontSize: '0.85rem' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <span style={{ color: 'var(--text-muted)', fontWeight: 600 }}>Qty Order:</span>
                      <input 
                        type="number" 
                        value={item.edited_qty !== undefined ? item.edited_qty : item.suggested_qty} 
                        onChange={(e) => handleQtyChange(item.product_id, e.target.value)}
                        min="0"
                        step="0.1"
                        style={{
                          width: '100px',
                          padding: '0.4rem',
                          background: 'white',
                          color: 'black',
                          border: '1px solid #ccc',
                          borderRadius: '6px',
                          fontWeight: 'bold',
                          fontSize: '1rem',
                          textAlign: 'center'
                        }}
                      />
                    </div>
                  </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Floating Action Bar */}
      {selectedItems.size > 0 && (
        <div style={{
          position: 'fixed',
          bottom: '70px', // Above bottom nav
          left: 0, right: 0,
          padding: '1rem',
          background: 'var(--bg-main)',
          borderTop: '1px solid rgba(255,255,255,0.1)',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          boxShadow: '0 -4px 10px rgba(0,0,0,0.2)',
          zIndex: 10
        }}>
          <div>
            <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Total Dipilih</div>
            <div style={{ fontWeight: 'bold', color: 'white' }}>{selectedItems.size} Produk</div>
          </div>
          <button 
            className="btn-primary" 
            onClick={handleCreateBulkPO}
            disabled={processing}
            style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', padding: '0.75rem 1.5rem', borderRadius: '24px' }}
          >
            {processing ? (
              <span className="loading-spinner" style={{ width: '16px', height: '16px', border: '2px solid white', borderTopColor: 'transparent' }}></span>
            ) : (
              <>
                Buat PO <ArrowRight size={18} />
              </>
            )}
          </button>
        </div>
      {showFaq && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.8)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem' }}>
          <div className="glass-panel" style={{ width: '100%', padding: '1.5rem', borderRadius: '16px', position: 'relative', maxHeight: '90vh', overflowY: 'auto' }}>
            <h3 style={{ fontSize: '1.2rem', fontWeight: 600, marginBottom: '1rem', color: 'white', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <AlertCircle color="#60a5fa" /> Cara Membaca Saran AI
            </h3>
            <div style={{ color: 'var(--text-muted)', lineHeight: '1.5', fontSize: '0.9rem' }}>
              <p style={{ marginBottom: '1rem' }}>AI memprediksi kebutuhan restock dengan mempelajari riwayat kecepatan penjualan setiap produk.</p>
              <ul style={{ paddingLeft: '1rem', marginBottom: '1rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                <li><strong>Stok Saat Ini:</strong> Sisa stok aktual di gudang cabang.</li>
                <li><strong>Jual/Hari (ADS):</strong> Rata-rata barang terjual per hari.</li>
                <li><strong>Batas Aman (ROP):</strong> Titik batas terendah. Jika stok di bawah ini, segera order.</li>
                <li><strong>Target Stok:</strong> Lama hari stok ini akan bertahan di gudang (default 30 hari).</li>
                <li><strong>Saran Order:</strong> Jumlah pemesanan ideal untuk memenuhi target hari penjualan ke depan.</li>
              </ul>
              <div style={{ background: 'rgba(59, 130, 246, 0.1)', borderLeft: '4px solid #3b82f6', padding: '0.75rem', borderRadius: '4px', color: '#93c5fd', fontSize: '0.85rem' }}>
                <strong>💡 Tips:</strong> Anda dapat mengubah "Target Stok" per produk melalui dashboard admin.
              </div>
            </div>
            <button onClick={() => setShowFaq(false)} className="btn-secondary" style={{ marginTop: '1.5rem', width: '100%', padding: '0.75rem' }}>
              Tutup
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
