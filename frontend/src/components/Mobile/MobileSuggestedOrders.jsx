import React, { useState, useEffect } from 'react';
import { 
  PackageSearch, 
  AlertCircle, 
  TrendingDown, 
  TrendingUp, 
  CheckCircle, 
  PlusCircle, 
  ArrowRight,
  RefreshCw,
  Store,
  HelpCircle,
  X,
  CheckCircle2,
  Minus,
  Plus
} from 'lucide-react';

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
          const deviceBranchId = localStorage.getItem('pos_device_branch_id');
          const found = data.find(b => b.id === deviceBranchId);
          setSelectedBranchId(found ? found.id : data[0].id);
        } else {
          const fallbackId = user?.branch_id || localStorage.getItem('pos_device_branch_id');
          setSelectedBranchId(fallbackId);
        }
      }
    } catch (err) {
      console.error('Failed to fetch branches:', err);
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
      
      const needsReorder = (data.data || []).filter(item => item.status === 'REORDER' || item.status === 'CRITICAL');
      needsReorder.sort((a, b) => {
        if (a.status === 'CRITICAL' && b.status !== 'CRITICAL') return -1;
        if (a.status !== 'CRITICAL' && b.status === 'CRITICAL') return 1;
        return 0;
      });
      
      setSuggestions(needsReorder);
      const allIds = new Set(needsReorder.map(item => item.product_id));
      setSelectedItems(allIds);
      
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleToggleSelect = (productId, e) => {
    if (e) e.stopPropagation();
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
      
      setSuggestions(prev => prev.filter(item => !selectedItems.has(item.product_id)));
      setSelectedItems(new Set());
      
    } catch (err) {
      setError(err.message);
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem', animation: 'fadeIn 0.3s ease-out' }}>
      
      {/* Page Header */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
        <div>
          <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600 }}>
            Restock Otomatis ADS
          </div>
          <h2 style={{ fontSize: '1.4rem', fontWeight: 800, margin: 0, color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '8px' }}>
            <TrendingUp size={24} color="#10b981" /> Order Pintar (AI)
          </h2>
        </div>
        <div style={{ display: 'flex', gap: '0.5rem' }}>
          <button 
            onClick={() => setShowFaq(true)} 
            style={{ background: 'rgba(99, 102, 241, 0.15)', color: '#6366f1', border: '1px solid rgba(99, 102, 241, 0.3)', width: 38, height: 38, borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}
          >
            <HelpCircle size={18} />
          </button>
          <button 
            onClick={() => fetchSuggestions()} 
            style={{ background: 'var(--bg-card)', color: 'var(--text-main)', border: '1px solid var(--border-light)', width: 38, height: 38, borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}
          >
            <RefreshCw size={18} />
          </button>
        </div>
      </div>

      {/* Branch Selector */}
      {branches.length > 1 && (
        <div style={{ background: 'var(--bg-card)', border: '1px solid var(--border-light)', padding: '0.75rem 1rem', borderRadius: '16px', display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <Store size={18} color="#10b981" />
          <div style={{ flex: 1 }}>
            <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Cabang Restock</div>
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

      {error && (
        <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', padding: '1rem', borderRadius: '16px', border: '1px solid rgba(239, 68, 68, 0.25)', display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <AlertCircle size={20} />
          <div style={{ fontSize: '0.88rem', fontWeight: 600 }}>{error}</div>
        </div>
      )}

      {successMsg && (
        <div style={{ background: 'rgba(16, 185, 129, 0.1)', color: '#10b981', padding: '1rem', borderRadius: '16px', border: '1px solid rgba(16, 185, 129, 0.25)', display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <CheckCircle2 size={20} />
          <div style={{ fontSize: '0.88rem', fontWeight: 600 }}>{successMsg}</div>
        </div>
      )}

      {loading ? (
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <div className="spin" style={{ width: '32px', height: '32px', border: '3px solid #10b981', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
          <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginTop: '0.75rem' }}>Kalkulasi rekomendasi stok...</div>
        </div>
      ) : suggestions.length === 0 ? (
        <div className="pwa-card" style={{ textAlign: 'center', padding: '2.5rem 1rem' }}>
          <PackageSearch size={48} color="#10b981" style={{ margin: '0 auto 0.75rem' }} />
          <h3 style={{ fontSize: '1.1rem', fontWeight: 700, margin: '0 0 4px', color: 'var(--text-main)' }}>Stok Cabang Aman</h3>
          <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Tidak ada rekomendasi order barang kritis saat ini.</p>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
          
          {/* Select All Toggle Header */}
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)', fontWeight: 600 }}>
              Dipilih {selectedItems.size} dari {suggestions.length} produk
            </span>
            <button 
              onClick={() => {
                if (selectedItems.size === suggestions.length) setSelectedItems(new Set());
                else setSelectedItems(new Set(suggestions.map(i => i.product_id)));
              }}
              style={{ background: 'none', border: 'none', color: '#10b981', fontWeight: 700, fontSize: '0.8rem', cursor: 'pointer' }}
            >
              {selectedItems.size === suggestions.length ? 'Batal Pilih Semua' : 'Pilih Semua'}
            </button>
          </div>

          {suggestions.map((item) => {
            const isSelected = selectedItems.has(item.product_id);
            const currentQtyVal = item.edited_qty !== undefined ? item.edited_qty : item.suggested_qty;

            return (
              <div 
                key={item.product_id} 
                className="pwa-card" 
                style={{ 
                  border: isSelected ? '1px solid #10b981' : '1px solid var(--border-light)',
                  background: isSelected ? 'rgba(16, 185, 129, 0.05)' : 'var(--bg-card)',
                  cursor: 'pointer'
                }}
                onClick={(e) => handleToggleSelect(item.product_id, e)}
              >
                <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'flex-start' }}>
                  {/* Custom Checkbox Pill */}
                  <div 
                    style={{ 
                      width: '22px', 
                      height: '22px', 
                      borderRadius: '50%', 
                      border: isSelected ? 'none' : '2px solid var(--text-muted)',
                      background: isSelected ? '#10b981' : 'transparent',
                      display: 'flex', 
                      alignItems: 'center', 
                      justify: 'center',
                      flexShrink: 0,
                      marginTop: '2px'
                    }}
                  >
                    {isSelected && <CheckCircle2 size={16} color="white" />}
                  </div>
                  
                  <div style={{ flex: 1 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '0.4rem' }}>
                      <div>
                        <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>SKU: {item.sku}</div>
                        <div style={{ fontWeight: 800, fontSize: '0.95rem', color: 'var(--text-main)', lineHeight: 1.2 }}>{item.name}</div>
                      </div>
                      {item.status === 'CRITICAL' ? (
                        <span style={{ background: '#ef4444', color: 'white', fontSize: '0.65rem', padding: '2px 8px', borderRadius: '99px', fontWeight: 800 }}>KRITIS</span>
                      ) : (
                        <span style={{ background: '#f59e0b', color: 'white', fontSize: '0.65rem', padding: '2px 8px', borderRadius: '99px', fontWeight: 800 }}>REORDER</span>
                      )}
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '0.5rem', marginTop: '0.6rem', fontSize: '0.8rem', background: 'rgba(255,255,255,0.04)', padding: '0.65rem', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
                      <div>
                        <div style={{ color: 'var(--text-muted)', fontSize: '0.7rem' }}>Stok Gudang</div>
                        <strong style={{ fontSize: '0.9rem' }}>{item.current_qty}</strong>
                      </div>
                      <div>
                        <div style={{ color: 'var(--text-muted)', fontSize: '0.7rem' }}>ADS Terjual/H</div>
                        <strong style={{ fontSize: '0.9rem' }}>{item.ads}</strong>
                      </div>
                      <div>
                        <div style={{ color: 'var(--text-muted)', fontSize: '0.7rem' }}>Saran Order</div>
                        <strong style={{ color: '#10b981', fontSize: '0.95rem' }}>+{item.suggested_qty}</strong>
                      </div>
                    </div>

                    {/* Quantity Edit Row */}
                    <div 
                      style={{ marginTop: '0.75rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}
                      onClick={(e) => e.stopPropagation()}
                    >
                      <span style={{ color: 'var(--text-muted)', fontSize: '0.82rem', fontWeight: 600 }}>Qty Disetujui PO:</span>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <button 
                          onClick={() => {
                            const val = Math.max((parseFloat(currentQtyVal) || 0) - 1, 0);
                            handleQtyChange(item.product_id, val);
                          }}
                          style={{ width: 32, height: 32, borderRadius: '8px', border: '1px solid var(--border-light)', background: 'var(--bg-card)', color: 'var(--text-main)', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}
                        >
                          <Minus size={14} />
                        </button>
                        <input 
                          type="number" 
                          value={currentQtyVal} 
                          onChange={(e) => handleQtyChange(item.product_id, e.target.value)}
                          min="0"
                          style={{
                            width: '70px',
                            padding: '0.35rem',
                            background: 'rgba(0,0,0,0.2)',
                            color: 'var(--text-main)',
                            border: '1px solid var(--border-light)',
                            borderRadius: '8px',
                            fontWeight: 800,
                            fontSize: '0.9rem',
                            textAlign: 'center',
                            outline: 'none'
                          }}
                        />
                        <button 
                          onClick={() => {
                            const val = (parseFloat(currentQtyVal) || 0) + 1;
                            handleQtyChange(item.product_id, val);
                          }}
                          style={{ width: 32, height: 32, borderRadius: '8px', border: '1px solid var(--border-light)', background: 'var(--bg-card)', color: 'var(--text-main)', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}
                        >
                          <Plus size={14} />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Floating Action Dock for Bulk PO Creation */}
      {selectedItems.size > 0 && (
        <div style={{
          position: 'fixed',
          bottom: '88px',
          left: '50%',
          transform: 'translateX(-50%)',
          width: 'calc(100% - 24px)',
          maxWidth: '460px',
          padding: '0.85rem 1.25rem',
          background: 'var(--bg-card)',
          backdropFilter: 'blur(20px)',
          WebkitBackdropFilter: 'blur(20px)',
          border: '1px solid var(--border-light)',
          borderRadius: '20px',
          display: 'flex',
          justify: 'space-between',
          alignItems: 'center',
          boxShadow: '0 12px 32px rgba(0, 0, 0, 0.3)',
          zIndex: 1000
        }}>
          <div>
            <div style={{ fontSize: '0.72rem', color: 'var(--text-muted)' }}>Draft PO Baru</div>
            <div style={{ fontWeight: 800, fontSize: '0.95rem', color: 'var(--text-main)' }}>{selectedItems.size} Produk</div>
          </div>
          <button 
            onClick={handleCreateBulkPO}
            disabled={processing}
            style={{ 
              display: 'flex', 
              alignItems: 'center', 
              gap: '0.5rem', 
              padding: '0.75rem 1.25rem', 
              borderRadius: '14px', 
              background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)', 
              border: 'none', 
              color: 'white', 
              fontWeight: 800, 
              fontSize: '0.88rem', 
              cursor: 'pointer',
              boxShadow: '0 4px 14px rgba(16, 185, 129, 0.4)'
            }}
          >
            {processing ? (
              <span className="spin" style={{ width: '16px', height: '16px', border: '2px solid white', borderTopColor: 'transparent', borderRadius: '50%' }}></span>
            ) : (
              <>
                Buat PO Gabungan <ArrowRight size={16} />
              </>
            )}
          </button>
        </div>
      )}

      {/* FAQ Guide Modal */}
      {showFaq && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.8)', zIndex: 10000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem' }}>
          <div className="pwa-card" style={{ width: '100%', maxWidth: '440px', padding: '1.5rem', maxHeight: '85vh', overflowY: 'auto' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.5rem' }}>
              <h3 style={{ fontSize: '1.1rem', fontWeight: 800, margin: 0, color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <HelpCircle color="#6366f1" size={20} /> Panduan Order Pintar
              </h3>
              <button onClick={() => setShowFaq(false)} style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}><X size={20} /></button>
            </div>
            <div style={{ color: 'var(--text-muted)', lineHeight: '1.5', fontSize: '0.85rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
              <p>Sistem AI menghitung rekomendasi restock berdasarkan ritme penjualan Average Daily Sales (ADS) 90 hari terakhir.</p>
              <ul style={{ paddingLeft: '1rem', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                <li><strong>Stok Gudang:</strong> Sisa fisik barang di cabang.</li>
                <li><strong>ADS Terjual/H:</strong> Kecepatan penjualan rata-rata per hari.</li>
                <li><strong>Saran Order:</strong> Kuantitas pesanan optimal untuk menjaga persediaan selama 30 hari.</li>
              </ul>
            </div>
            <button onClick={() => setShowFaq(false)} style={{ marginTop: '1.25rem', width: '100%', padding: '0.85rem', borderRadius: '14px', background: '#6366f1', border: 'none', color: 'white', fontWeight: 800, cursor: 'pointer' }}>
              Tutup
            </button>
          </div>
        </div>
      )}

    </div>
  );
}
