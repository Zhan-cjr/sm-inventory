import React, { useState, useEffect, useRef } from 'react';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { 
  ClipboardCheck, 
  Search, 
  Camera, 
  Barcode, 
  Plus, 
  Minus, 
  Trash2, 
  CheckCircle2, 
  AlertTriangle, 
  X, 
  Printer, 
  History, 
  Play, 
  Check, 
  Layers, 
  RotateCcw,
  Sparkles,
  Store,
  Calendar
} from 'lucide-react';

export function MobileStockAudit({ user, authToken }) {
  const [activeTab, setActiveTab] = useState('active'); // 'active' or 'history'
  const [loading, setLoading] = useState(true);
  const [activeInspection, setActiveInspection] = useState(null);
  const [historyList, setHistoryList] = useState([]);
  const [racks, setRacks] = useState([]);
  
  // Branch Selector (for Admins or users without branch_id)
  const [branches, setBranches] = useState([]);
  const [selectedBranchId, setSelectedBranchId] = useState(user?.branch_id || '');
  const isAdmin = ['ADMIN', 'SUPER_ADMIN', 'SUPERADMIN'].includes(user?.role?.toUpperCase());
  const showBranchSelector = !user?.branch_id || isAdmin;

  // Modal Start New Audit state
  const [showNewModal, setShowNewModal] = useState(false);
  const [newMode, setNewMode] = useState('FREE_SCAN'); // 'FREE_SCAN' or 'RANDOM_LOTTERY'
  const [targetCount, setTargetCount] = useState(20);
  const [selectedRacks, setSelectedRacks] = useState([]);
  const [highRiskOnly, setHighRiskOnly] = useState(false);
  const [newNotes, setNewNotes] = useState('');
  const [creating, setCreating] = useState(false);

  // Scan / Search state
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [isCameraActive, setIsCameraActive] = useState(false);
  const scannerRef = useRef(null);

  // Selected Product for Counting
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [physicalQty, setPhysicalQty] = useState(0);
  const [itemNotes, setItemNotes] = useState('');
  const [savingItem, setSavingItem] = useState(false);

  // Finish Modal
  const [showFinishModal, setShowFinishModal] = useState(false);
  const [witnessName, setWitnessName] = useState('');
  const [finishNotes, setFinishNotes] = useState('');
  const [finishing, setFinishing] = useState(false);

  const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);

  // Fetch branches
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

  // Load active sessions and history on mount or branch change
  const fetchInspections = async (branchId = selectedBranchId) => {
    setLoading(true);
    try {
      const q = branchId ? `?branch_id=${branchId}` : '';
      const res = await fetch(`/api/v1/stock-audits${q}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      const json = await res.json();
      if (res.ok && json.data) {
        setHistoryList(json.data);
        const current = json.data.find(i => i.status === 'DRAFT');
        if (current) {
          loadInspectionDetail(current.id);
        } else {
          setActiveInspection(null);
        }
      }
    } catch (err) {
      console.error("Gagal load stock audits", err);
    } finally {
      setLoading(false);
    }
  };

  const loadInspectionDetail = async (id) => {
    try {
      const res = await fetch(`/api/v1/stock-audits/${id}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      const json = await res.json();
      if (res.ok && json.data) {
        setActiveInspection(json.data);
      }
    } catch (err) {
      console.error("Gagal load inspection detail", err);
    }
  };

  const fetchRacks = async (branchId = selectedBranchId) => {
    try {
      const q = branchId ? `?branch_id=${branchId}` : '';
      const res = await fetch(`/api/v1/stock-audits/racks${q}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      const json = await res.json();
      if (res.ok && json.data) {
        setRacks(json.data);
      }
    } catch (err) {
      console.error("Gagal load racks", err);
    }
  };

  useEffect(() => {
    if (selectedBranchId || !showBranchSelector) {
      fetchInspections(selectedBranchId);
      fetchRacks(selectedBranchId);
    }
  }, [authToken, selectedBranchId]);

  // Handle Search Product
  useEffect(() => {
    if (!searchQuery || searchQuery.trim().length < 2) {
      setSearchResults([]);
      return;
    }

    const timer = setTimeout(async () => {
      setSearching(true);
      try {
        const q = selectedBranchId ? `&branch_id=${selectedBranchId}` : '';
        const res = await fetch(`/api/v1/stock-audits/search-products?query=${encodeURIComponent(searchQuery.trim())}${q}`, {
          headers: { 'Authorization': `Bearer ${authToken}` }
        });
        const json = await res.json();
        if (res.ok && json.data) {
          setSearchResults(json.data);
        }
      } catch (err) {
        console.error("Gagal search products", err);
      } finally {
        setSearching(false);
      }
    }, 250);

    return () => clearTimeout(timer);
  }, [searchQuery, authToken, selectedBranchId]);

  // Camera Barcode Scanner
  useEffect(() => {
    if (isCameraActive) {
      const timer = setTimeout(() => {
        try {
          const scanner = new Html5QrcodeScanner('audit-camera-reader', {
            fps: 10,
            qrbox: { width: 250, height: 150 },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true,
          });

          scanner.render(
            (decodedText) => {
              if (decodedText) {
                handleBarcodeScanned(decodedText.trim());
                setIsCameraActive(false);
                scanner.clear().catch(e => console.error(e));
              }
            },
            () => {}
          );
          scannerRef.current = scanner;
        } catch (e) {
          console.error("Gagal inisialisasi scanner", e);
        }
      }, 150);

      return () => {
        clearTimeout(timer);
        if (scannerRef.current) {
          scannerRef.current.clear().catch(e => console.error(e));
          scannerRef.current = null;
        }
      };
    }
  }, [isCameraActive]);

  const handleBarcodeScanned = async (barcode) => {
    setSearching(true);
    try {
      const q = selectedBranchId ? `&branch_id=${selectedBranchId}` : '';
      const res = await fetch(`/api/v1/stock-audits/search-products?query=${encodeURIComponent(barcode)}${q}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      const json = await res.json();
      if (res.ok && json.data && json.data.length > 0) {
        openCountModal(json.data[0]);
      } else {
        alert(`Produk dengan barcode/SKU "${barcode}" tidak ditemukan.`);
      }
    } catch (err) {
      console.error("Gagal barcode search", err);
    } finally {
      setSearching(false);
    }
  };

  const openCountModal = (product) => {
    setSelectedProduct(product);
    const existing = activeInspection?.items?.find(i => i.product_id === product.id);
    if (existing) {
      setPhysicalQty(parseFloat(existing.physical_quantity) || 0);
      setItemNotes(existing.clarification_notes || '');
    } else {
      setPhysicalQty(parseFloat(product.stock) || 0);
      setItemNotes('');
    }
    setSearchQuery('');
    setSearchResults([]);
  };

  // Create New Inspection
  const handleCreateInspection = async () => {
    setCreating(true);
    try {
      const effectiveBranchId = selectedBranchId || user?.branch_id || (branches[0]?.id);
      const payload = {
        branch_id: effectiveBranchId,
        inspection_mode: newMode,
        target_count: newMode === 'RANDOM_LOTTERY' ? targetCount : null,
        rack_ids: newMode === 'RANDOM_LOTTERY' && selectedRacks.length > 0 ? selectedRacks : null,
        high_risk_only: highRiskOnly,
        notes: newNotes,
      };

      const res = await fetch('/api/v1/stock-audits', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (res.ok && json.data) {
        setActiveInspection(json.data);
        setShowNewModal(false);
        setActiveTab('active');
        fetchInspections(effectiveBranchId);
      } else {
        alert(json.error || 'Gagal membuat sesi sidak.');
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan saat membuat sesi sidak.');
    } finally {
      setCreating(false);
    }
  };

  // Submit Physical Count
  const handleSaveItemCount = async () => {
    if (!activeInspection || !selectedProduct) return;
    setSavingItem(true);
    try {
      const payload = {
        product_id: selectedProduct.id,
        physical_quantity: physicalQty,
        rack_id: selectedProduct.rack_id || null,
        notes: itemNotes,
      };

      const res = await fetch(`/api/v1/stock-audits/${activeInspection.id}/items`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (res.ok) {
        setSelectedProduct(null);
        loadInspectionDetail(activeInspection.id);
      } else {
        alert(json.error || 'Gagal menyimpan hitungan.');
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan saat mencatat hitungan.');
    } finally {
      setSavingItem(false);
    }
  };

  // Delete Item
  const handleDeleteItem = async (itemId) => {
    if (!window.confirm('Hapus produk ini dari daftar sidak?')) return;
    try {
      const res = await fetch(`/api/v1/stock-audits/${activeInspection.id}/items/${itemId}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      if (res.ok) {
        loadInspectionDetail(activeInspection.id);
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Finish Inspection Action
  const handleFinishInspection = async (action) => {
    if (!activeInspection) return;
    setFinishing(true);
    try {
      const payload = {
        action: action,
        acknowledged_by: witnessName,
        notes: finishNotes,
      };

      const res = await fetch(`/api/v1/stock-audits/${activeInspection.id}/finish`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (res.ok) {
        alert(json.message);
        setShowFinishModal(false);
        setActiveInspection(null);
        fetchInspections(selectedBranchId);
        setActiveTab('history');
      } else {
        alert(json.error || 'Gagal menyelesaikan sidak.');
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan saat menyelesaikan sidak.');
    } finally {
      setFinishing(false);
    }
  };

  // Apply Adjustment from History
  const handleApplyAdjustmentFromHistory = async (id) => {
    if (!window.confirm('Terapkan koreksi stok sekarang? Stok sistem akan langsung disesuaikan dengan hasil sidak.')) return;
    try {
      const res = await fetch(`/api/v1/stock-audits/${id}/adjust`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      const json = await res.json();
      if (res.ok) {
        alert(json.message);
        fetchInspections(selectedBranchId);
      } else {
        alert(json.error || 'Gagal menerapkan penyesuaian stok.');
      }
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem', animation: 'fadeIn 0.3s ease-out' }}>
      
      {/* Top Title Header */}
      <div>
        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600 }}>
          Internal Audit & Kontrol Stok
        </div>
        <h2 style={{ fontSize: '1.4rem', fontWeight: 800, margin: 0, color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          <ClipboardCheck size={26} color="#f59e0b" />
          Sidak Audit Stok
        </h2>
      </div>

      {/* Branch Selector for Admin */}
      {showBranchSelector && (
        <div style={{ 
          background: 'var(--bg-card)', 
          border: '1px solid var(--border-light)', 
          padding: '0.75rem 1rem', 
          borderRadius: '16px', 
          display: 'flex', 
          alignItems: 'center', 
          gap: '0.75rem' 
        }}>
          <Store size={18} color="#f59e0b" />
          <div style={{ flex: 1 }}>
            <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', fontWeight: 600 }}>PILIH CABANG AUDIT</div>
            <select 
              value={selectedBranchId} 
              onChange={(e) => setSelectedBranchId(e.target.value)}
              style={{ 
                width: '100%', 
                cursor: 'pointer', 
                border: 'none', 
                background: 'transparent', 
                color: 'var(--text-main)', 
                fontWeight: 700, 
                fontSize: '0.9rem', 
                outline: 'none',
                marginTop: '2px'
              }}
            >
              <option value="" disabled style={{ background: 'var(--bg-dark)' }}>-- Pilih Cabang --</option>
              {branches.map(b => (
                <option key={b.id} value={b.id} style={{ background: 'var(--bg-dark)', color: 'var(--text-main)' }}>{b.name}</option>
              ))}
            </select>
          </div>
        </div>
      )}

      {/* Segmented Control Tabs */}
      <div className="segmented-control">
        <button
          className={activeTab === 'active' ? 'active' : ''}
          onClick={() => setActiveTab('active')}
        >
          Sidak Aktif
        </button>
        <button
          className={activeTab === 'history' ? 'active' : ''}
          onClick={() => setActiveTab('history')}
        >
          Riwayat ({historyList.length})
        </button>
      </div>

      {/* TAB 1: ACTIVE AUDIT */}
      {activeTab === 'active' && (
        <>
          {!activeInspection ? (
            /* No Active Session Card */
            <div className="pwa-card" style={{ 
              textAlign: 'center', 
              padding: '2.5rem 1.5rem',
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '1.25rem'
            }}>
              <div style={{ width: 68, height: 68, borderRadius: '50%', background: 'rgba(245, 158, 11, 0.15)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <ClipboardCheck size={36} color="#f59e0b" />
              </div>
              <div>
                <h3 style={{ fontSize: '1.25rem', fontWeight: 800, margin: '0 0 0.5rem 0', color: 'var(--text-main)' }}>
                  Tidak Ada Sidak yang Sedang Berjalan
                </h3>
                <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem', maxWidth: '380px', margin: '0 auto', lineHeight: 1.5 }}>
                  Mulai inspeksi mendadak sekarang. Anda bisa memilih mode <b>Scan Bebas (Spot Check)</b> atau <b>Undian Acak Sistem</b>.
                </p>
              </div>

              <button
                onClick={() => setShowNewModal(true)}
                style={{
                  background: 'linear-gradient(135deg, #f59e0b, #d97706)',
                  color: '#000',
                  border: 'none',
                  borderRadius: '12px',
                  padding: '14px 24px',
                  fontSize: '0.95rem',
                  fontWeight: 800,
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '0.5rem',
                  boxShadow: '0 4px 16px rgba(245, 158, 11, 0.35)'
                }}
              >
                <Play size={18} fill="#000" />
                Mulai Sidak Baru Sekarang
              </button>
            </div>
          ) : (
            /* Active Session In Progress */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
              
              {/* Header Box: Session Info & Summary Badges */}
              <div className="pwa-card" style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '0.5rem' }}>
                  <div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', fontWeight: 600 }}>NOMOR SIDAK</div>
                    <div style={{ fontSize: '1.2rem', fontWeight: 800, color: '#f59e0b', fontFamily: 'monospace' }}>
                      {activeInspection.audit_number}
                    </div>
                  </div>

                  <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                    <span style={{ 
                      fontSize: '0.75rem', 
                      fontWeight: 700, 
                      padding: '4px 10px', 
                      borderRadius: '8px', 
                      background: activeInspection.inspection_mode === 'FREE_SCAN' ? 'rgba(59, 130, 246, 0.15)' : 'rgba(245, 158, 11, 0.15)',
                      color: activeInspection.inspection_mode === 'FREE_SCAN' ? '#3b82f6' : '#d97706'
                    }}>
                      {activeInspection.inspection_mode === 'FREE_SCAN' ? '⚡ Scan Bebas' : '🎲 Undian Acak'}
                    </span>

                    <button
                      onClick={() => setShowFinishModal(true)}
                      style={{
                        background: '#10b981',
                        color: '#fff',
                        border: 'none',
                        borderRadius: '8px',
                        padding: '8px 14px',
                        fontSize: '0.8rem',
                        fontWeight: 700,
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px',
                        boxShadow: '0 2px 8px rgba(16, 185, 129, 0.3)'
                      }}
                    >
                      <Check size={16} />
                      Selesaikan
                    </button>
                  </div>
                </div>

                {/* 4 Mini Cards Summary */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))', gap: '0.75rem' }}>
                  <div style={{ background: 'rgba(128,128,128,0.06)', padding: '10px 12px', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
                    <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', textTransform: 'uppercase', fontWeight: 600 }}>Item Dicek</div>
                    <div style={{ fontSize: '1.25rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '2px' }}>
                      {activeInspection.total_items_checked || 0}
                    </div>
                  </div>

                  <div style={{ background: 'rgba(128,128,128,0.06)', padding: '10px 12px', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
                    <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', textTransform: 'uppercase', fontWeight: 600 }}>Item Selisih</div>
                    <div style={{ 
                      fontSize: '1.25rem', 
                      fontWeight: 800, 
                      color: (activeInspection.total_items_discrepancy || 0) > 0 ? '#ef4444' : '#10b981', 
                      marginTop: '2px' 
                    }}>
                      {activeInspection.total_items_discrepancy || 0}
                    </div>
                  </div>

                  <div style={{ background: 'rgba(239, 68, 68, 0.08)', padding: '10px 12px', borderRadius: '12px', border: '1px solid rgba(239, 68, 68, 0.2)' }}>
                    <div style={{ fontSize: '0.7rem', color: '#ef4444', textTransform: 'uppercase', fontWeight: 600 }}>Kerugian (Minus)</div>
                    <div style={{ fontSize: '1.05rem', fontWeight: 800, color: '#ef4444', marginTop: '2px' }}>
                      -{formatCurrency(activeInspection.total_loss_nominal || 0)}
                    </div>
                  </div>

                  <div style={{ background: 'rgba(59, 130, 246, 0.08)', padding: '10px 12px', borderRadius: '12px', border: '1px solid rgba(59, 130, 246, 0.2)' }}>
                    <div style={{ fontSize: '0.7rem', color: '#3b82f6', textTransform: 'uppercase', fontWeight: 600 }}>Lebih (Plus)</div>
                    <div style={{ fontSize: '1.05rem', fontWeight: 800, color: '#3b82f6', marginTop: '2px' }}>
                      +{formatCurrency(activeInspection.total_gain_nominal || 0)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Barcode Search Bar & Camera Trigger */}
              <div className="pwa-card" style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                  <div style={{ position: 'relative', flex: 1 }}>
                    <input
                      type="text"
                      placeholder="Scan Barcode / SKU / Ketik Nama..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' && searchQuery.trim()) {
                          handleBarcodeScanned(searchQuery.trim());
                        }
                      }}
                      style={{
                        width: '100%',
                        padding: '12px 14px 12px 38px',
                        borderRadius: '12px',
                        border: '1px solid var(--border-light)',
                        background: 'rgba(128, 128, 128, 0.06)',
                        color: 'var(--text-main)',
                        fontSize: '0.95rem',
                        outline: 'none',
                        boxSizing: 'border-box'
                      }}
                    />
                    <Search size={18} color="var(--text-muted)" style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)' }} />
                    {searchQuery && (
                      <X 
                        size={16} 
                        color="var(--text-muted)" 
                        onClick={() => { setSearchQuery(''); setSearchResults([]); }}
                        style={{ position: 'absolute', right: 12, top: '50%', transform: 'translateY(-50%)', cursor: 'pointer' }} 
                      />
                    )}
                  </div>

                  {/* Camera Scanner Toggle */}
                  <button
                    onClick={() => setIsCameraActive(prev => !prev)}
                    style={{
                      padding: '12px 14px',
                      borderRadius: '12px',
                      border: 'none',
                      background: isCameraActive ? '#ef4444' : '#6366f1',
                      color: '#fff',
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      boxShadow: '0 2px 8px rgba(99, 102, 241, 0.3)'
                    }}
                    title="Buka Kamera Barcode"
                  >
                    <Camera size={20} />
                  </button>
                </div>

                {/* Camera View Box */}
                {isCameraActive && (
                  <div style={{ border: '2px dashed #6366f1', borderRadius: '14px', padding: '0.75rem', background: 'rgba(0,0,0,0.04)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
                      <span style={{ fontSize: '0.8rem', fontWeight: 700, color: '#6366f1' }}>Arahkan Kamera ke Barcode Produk</span>
                      <X size={18} color="#ef4444" style={{ cursor: 'pointer' }} onClick={() => setIsCameraActive(false)} />
                    </div>
                    <div id="audit-camera-reader" style={{ width: '100%', borderRadius: '10px', overflow: 'hidden' }}></div>
                  </div>
                )}

                {/* Search Results Dropdown */}
                {searchResults.length > 0 && (
                  <div style={{ 
                    maxHeight: '240px', 
                    overflowY: 'auto', 
                    background: 'var(--bg-card)', 
                    borderRadius: '12px', 
                    border: '1px solid var(--border-light)',
                    boxShadow: '0 10px 25px rgba(0,0,0,0.15)',
                    display: 'flex',
                    flexDirection: 'column'
                  }}>
                    {searchResults.map(p => (
                      <div
                        key={p.id}
                        onClick={() => openCountModal(p)}
                        style={{
                          padding: '12px 14px',
                          borderBottom: '1px solid var(--border-light)',
                          cursor: 'pointer',
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          gap: '0.5rem'
                        }}
                      >
                        <div>
                          <div style={{ fontWeight: 700, fontSize: '0.875rem', color: 'var(--text-main)' }}>{p.name}</div>
                          <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', fontFamily: 'monospace', marginTop: '2px' }}>
                            {p.barcode || p.sku} &bull; Rak: {p.racks}
                          </div>
                        </div>
                        <div style={{ textAlign: 'right' }}>
                          <div style={{ fontSize: '0.85rem', fontWeight: 800, color: '#38bdf8' }}>
                            Sistem: {p.stock} {p.unit}
                          </div>
                          <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                            HPP: {formatCurrency(p.cost_price)}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* List of Audited Items in this Session */}
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div style={{ fontSize: '0.9rem', fontWeight: 800, color: 'var(--text-main)' }}>
                    Daftar Produk Diperiksa ({activeInspection.items?.length || 0})
                  </div>
                </div>

                {(!activeInspection.items || activeInspection.items.length === 0) ? (
                  <div className="pwa-card" style={{ 
                    padding: '2.5rem 1.5rem', 
                    textAlign: 'center', 
                    color: 'var(--text-muted)',
                    fontSize: '0.875rem',
                    border: '1px dashed var(--border-light)'
                  }}>
                    Belum ada barang yang dihitung. Scan barcode produk di rak untuk mulai mencatat.
                  </div>
                ) : (
                  activeInspection.items.map((it, idx) => {
                    const isMinus = parseFloat(it.discrepancy_quantity) < 0;
                    const isPlus = parseFloat(it.discrepancy_quantity) > 0;

                    return (
                      <div
                        key={it.id}
                        className="pwa-card"
                        style={{
                          padding: '12px 14px',
                          display: 'flex',
                          flexDirection: 'column',
                          gap: '0.5rem',
                          borderLeft: `4px solid ${isMinus ? '#ef4444' : isPlus ? '#3b82f6' : '#10b981'}`
                        }}
                      >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '0.5rem' }}>
                          <div style={{ flex: 1 }}>
                            <div style={{ fontWeight: 800, fontSize: '0.9rem', color: 'var(--text-main)' }}>
                              {idx + 1}. {it.product?.name || 'Produk'}
                            </div>
                            <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', fontFamily: 'monospace', marginTop: '2px' }}>
                              {it.product?.barcode || it.product?.sku} {it.rack ? `• Rak: ${it.rack.rack_name}` : ''}
                            </div>
                          </div>

                          <button
                            onClick={() => handleDeleteItem(it.id)}
                            style={{ background: 'transparent', border: 'none', color: '#ef4444', cursor: 'pointer', padding: '4px' }}
                            title="Hapus"
                          >
                            <Trash2 size={16} />
                          </button>
                        </div>

                        {/* Counts Comparison Row */}
                        <div style={{ 
                          display: 'flex', 
                          justifyContent: 'space-between', 
                          alignItems: 'center', 
                          background: 'rgba(128,128,128,0.06)', 
                          padding: '8px 12px', 
                          borderRadius: '8px' 
                        }}>
                          <div>
                            <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Sistem: </span>
                            <span style={{ fontSize: '0.85rem', fontWeight: 700, color: 'var(--text-main)' }}>{it.system_quantity}</span>
                            {parseFloat(it.delta_sales_quantity) !== 0 && (
                              <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginLeft: '4px' }}>
                                ({parseFloat(it.delta_sales_quantity) > 0 ? '+' : ''}{it.delta_sales_quantity} kasir)
                              </span>
                            )}
                          </div>

                          <div>
                            <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Fisik: </span>
                            <span style={{ fontSize: '0.95rem', fontWeight: 800, color: '#f59e0b' }}>{it.physical_quantity}</span>
                          </div>

                          <div style={{ textAlign: 'right' }}>
                            <span style={{ 
                              fontSize: '0.8rem', 
                              fontWeight: 800, 
                              padding: '2px 8px', 
                              borderRadius: '6px',
                              background: isMinus ? 'rgba(239, 68, 68, 0.15)' : isPlus ? 'rgba(59, 130, 246, 0.15)' : 'rgba(16, 185, 129, 0.15)',
                              color: isMinus ? '#ef4444' : isPlus ? '#3b82f6' : '#10b981'
                            }}>
                              {isMinus ? `${it.discrepancy_quantity} pcs` : isPlus ? `+${it.discrepancy_quantity} pcs` : 'Cocok'}
                            </span>
                          </div>
                        </div>

                        {/* Nominal Difference & Clarification */}
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '0.75rem' }}>
                          <span style={{ color: 'var(--text-muted)' }}>
                            HPP: {formatCurrency(it.unit_cost)}
                          </span>
                          <span style={{ fontWeight: 800, color: isMinus ? '#ef4444' : isPlus ? '#3b82f6' : '#10b981' }}>
                            Selisih: {isMinus ? `-${formatCurrency(Math.abs(it.discrepancy_cost))}` : isPlus ? `+${formatCurrency(it.discrepancy_cost)}` : 'Rp 0'}
                          </span>
                        </div>

                        {it.clarification_notes && (
                          <div style={{ fontSize: '0.75rem', color: 'var(--text-main)', background: 'rgba(128,128,128,0.08)', padding: '6px 10px', borderRadius: '6px' }}>
                            💬 {it.clarification_notes}
                          </div>
                        )}
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          )}
        </>
      )}

      {/* TAB 2: HISTORY */}
      {activeTab === 'history' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
          {historyList.length === 0 ? (
            <div className="pwa-card" style={{ 
              padding: '2.5rem 1.5rem', 
              textAlign: 'center', 
              color: 'var(--text-muted)' 
            }}>
              Belum ada riwayat sidak audit stok di cabang ini.
            </div>
          ) : (
            historyList.map(h => (
              <div
                key={h.id}
                className="pwa-card"
                style={{
                  padding: '14px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '0.75rem'
                }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '0.5rem' }}>
                  <div>
                    <div style={{ fontSize: '1rem', fontWeight: 800, color: 'var(--text-main)', fontFamily: 'monospace' }}>
                      {h.audit_number}
                    </div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                      Tgl: {h.inspection_date} &bull; Auditor: {h.auditor?.name || '-'}
                    </div>
                  </div>

                  <div>
                    {h.status === 'DRAFT' && (
                      <span style={{ fontSize: '0.75rem', fontWeight: 700, padding: '3px 8px', borderRadius: '6px', background: 'rgba(245, 158, 11, 0.15)', color: '#d97706' }}>
                        Sedang Berjalan
                      </span>
                    )}
                    {h.status === 'INVESTIGATION_PENDING' && (
                      <span style={{ fontSize: '0.75rem', fontWeight: 700, padding: '3px 8px', borderRadius: '6px', background: 'rgba(56, 189, 248, 0.15)', color: '#0284c7' }}>
                        Menunggu Investigasi
                      </span>
                    )}
                    {h.status === 'ADJUSTED' && (
                      <span style={{ fontSize: '0.75rem', fontWeight: 700, padding: '3px 8px', borderRadius: '6px', background: 'rgba(16, 185, 129, 0.15)', color: '#10b981' }}>
                        Stok Disesuaikan
                      </span>
                    )}
                    {h.status === 'CLOSED' && (
                      <span style={{ fontSize: '0.75rem', fontWeight: 700, padding: '3px 8px', borderRadius: '6px', background: 'rgba(148, 163, 184, 0.15)', color: '#64748b' }}>
                        Ditutup
                      </span>
                    )}
                  </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.8rem', color: 'var(--text-muted)' }}>
                  <div>
                    Item: <strong style={{ color: 'var(--text-main)' }}>{h.total_items_checked}</strong>
                  </div>
                  <div>
                    Selisih: <strong style={{ color: h.total_items_discrepancy > 0 ? '#ef4444' : '#10b981' }}>{h.total_items_discrepancy} item</strong>
                  </div>
                  <div>
                    Minus: <strong style={{ color: '#ef4444' }}>-{formatCurrency(h.total_loss_nominal)}</strong>
                  </div>
                </div>

                {/* Action Buttons in History Card */}
                <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end', paddingTop: '0.5rem', borderTop: '1px solid var(--border-light)' }}>
                  <a
                    href={`/stock-audits/${h.id}/print-bap`}
                    target="_blank"
                    rel="noreferrer"
                    style={{
                      background: 'rgba(128,128,128,0.1)',
                      color: 'var(--text-main)',
                      padding: '8px 14px',
                      borderRadius: '8px',
                      fontSize: '0.75rem',
                      fontWeight: 700,
                      textDecoration: 'none',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px'
                    }}
                  >
                    <Printer size={14} /> Cetak BAP
                  </a>

                  {h.status === 'INVESTIGATION_PENDING' && h.total_items_discrepancy > 0 && (
                    <button
                      onClick={() => handleApplyAdjustmentFromHistory(h.id)}
                      style={{
                        background: '#10b981',
                        color: '#fff',
                        border: 'none',
                        padding: '8px 14px',
                        borderRadius: '8px',
                        fontSize: '0.75rem',
                        fontWeight: 700,
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px'
                      }}
                    >
                      <Check size={14} /> Terapkan Koreksi
                    </button>
                  )}
                </div>
              </div>
            ))
          )}
        </div>
      )}

      {/* MODAL 1: START NEW AUDIT */}
      {showNewModal && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(6px)', zIndex: 99999, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem' }}>
          <div className="pwa-card" style={{ width: '100%', maxWidth: '440px', padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1.25rem', boxShadow: '0 20px 40px rgba(0,0,0,0.3)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <h3 style={{ margin: 0, fontSize: '1.2rem', fontWeight: 800, color: 'var(--text-main)' }}>
                Mulai Sesi Sidak Baru
              </h3>
              <X size={20} color="var(--text-muted)" style={{ cursor: 'pointer' }} onClick={() => setShowNewModal(false)} />
            </div>

            {/* Mode Selector */}
            <div>
              <div style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)', marginBottom: '0.5rem' }}>
                PILIH METODE PEMERIKSAAN
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem' }}>
                <div
                  onClick={() => setNewMode('FREE_SCAN')}
                  style={{
                    border: `2px solid ${newMode === 'FREE_SCAN' ? '#f59e0b' : 'var(--border-light)'}`,
                    borderRadius: '12px',
                    padding: '14px 10px',
                    cursor: 'pointer',
                    background: newMode === 'FREE_SCAN' ? 'rgba(245, 158, 11, 0.12)' : 'rgba(128,128,128,0.04)',
                    textAlign: 'center',
                    transition: 'all 0.2s'
                  }}
                >
                  <Barcode size={24} color={newMode === 'FREE_SCAN' ? '#f59e0b' : 'var(--text-muted)'} style={{ margin: '0 auto 6px' }} />
                  <div style={{ fontWeight: 800, fontSize: '0.875rem', color: newMode === 'FREE_SCAN' ? '#f59e0b' : 'var(--text-main)' }}>
                    Scan Bebas
                  </div>
                  <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                    Keliling rak & bebas scan barang
                  </div>
                </div>

                <div
                  onClick={() => setNewMode('RANDOM_LOTTERY')}
                  style={{
                    border: `2px solid ${newMode === 'RANDOM_LOTTERY' ? '#f59e0b' : 'var(--border-light)'}`,
                    borderRadius: '12px',
                    padding: '14px 10px',
                    cursor: 'pointer',
                    background: newMode === 'RANDOM_LOTTERY' ? 'rgba(245, 158, 11, 0.12)' : 'rgba(128,128,128,0.04)',
                    textAlign: 'center',
                    transition: 'all 0.2s'
                  }}
                >
                  <Sparkles size={24} color={newMode === 'RANDOM_LOTTERY' ? '#f59e0b' : 'var(--text-muted)'} style={{ margin: '0 auto 6px' }} />
                  <div style={{ fontWeight: 800, fontSize: '0.875rem', color: newMode === 'RANDOM_LOTTERY' ? '#f59e0b' : 'var(--text-main)' }}>
                    Undian Acak
                  </div>
                  <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                    Sistem acak N barang otomatis
                  </div>
                </div>
              </div>
            </div>

            {/* Random Lottery Options */}
            {newMode === 'RANDOM_LOTTERY' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', background: 'rgba(128,128,128,0.06)', padding: '12px', borderRadius: '12px' }}>
                <div>
                  <label style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)' }}>Jumlah Item Diacak</label>
                  <div style={{ display: 'flex', gap: '0.5rem', marginTop: '6px' }}>
                    {[10, 20, 30, 50].map(cnt => (
                      <button
                        key={cnt}
                        type="button"
                        onClick={() => setTargetCount(cnt)}
                        style={{
                          flex: 1,
                          padding: '8px',
                          borderRadius: '8px',
                          border: `1px solid ${targetCount === cnt ? '#f59e0b' : 'var(--border-light)'}`,
                          background: targetCount === cnt ? '#f59e0b' : 'rgba(128,128,128,0.04)',
                          color: targetCount === cnt ? '#000' : 'var(--text-main)',
                          fontWeight: 700,
                          cursor: 'pointer'
                        }}
                      >
                        {cnt} Item
                      </button>
                    ))}
                  </div>
                </div>

                {racks.length > 0 && (
                  <div>
                    <label style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)' }}>Batasi ke Rak Tertentu (Opsional)</label>
                    <select
                      onChange={(e) => {
                        const val = e.target.value;
                        if (!val) {
                          setSelectedRacks([]);
                        } else {
                          setSelectedRacks([val]);
                        }
                      }}
                      style={{
                        width: '100%',
                        padding: '10px 12px',
                        borderRadius: '10px',
                        border: '1px solid var(--border-light)',
                        background: 'var(--bg-card)',
                        color: 'var(--text-main)',
                        marginTop: '4px',
                        fontSize: '0.85rem'
                      }}
                    >
                      <option value="">-- Seluruh Rak di Toko --</option>
                      {racks.map(r => (
                        <option key={r.id} value={r.id}>{r.rack_name} ({r.rack_code})</option>
                      ))}
                    </select>
                  </div>
                )}

                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer' }} onClick={() => setHighRiskOnly(p => !p)}>
                  <input type="checkbox" checked={highRiskOnly} onChange={() => {}} />
                  <span style={{ fontSize: '0.8rem', color: 'var(--text-main)', fontWeight: 600 }}>Prioritaskan Produk Bernilai Tinggi (High Risk / Kategori A)</span>
                </div>
              </div>
            )}

            <div>
              <label style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)' }}>Catatan Sidak (Opsional)</label>
              <input
                type="text"
                placeholder="Contoh: Sidak shift pagi / audit rutin"
                value={newNotes}
                onChange={(e) => setNewNotes(e.target.value)}
                style={{
                  width: '100%',
                  padding: '10px 12px',
                  borderRadius: '10px',
                  border: '1px solid var(--border-light)',
                  background: 'rgba(128,128,128,0.06)',
                  color: 'var(--text-main)',
                  marginTop: '4px',
                  fontSize: '0.85rem',
                  boxSizing: 'border-box'
                }}
              />
            </div>

            <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end', marginTop: '0.5rem' }}>
              <button
                type="button"
                onClick={() => setShowNewModal(false)}
                style={{ padding: '10px 18px', borderRadius: '10px', border: '1px solid var(--border-light)', background: 'transparent', color: 'var(--text-muted)', fontWeight: 700, cursor: 'pointer' }}
              >
                Batal
              </button>
              <button
                type="button"
                disabled={creating}
                onClick={handleCreateInspection}
                style={{ padding: '10px 22px', borderRadius: '10px', border: 'none', background: '#f59e0b', color: '#000', fontWeight: 800, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px' }}
              >
                {creating ? 'Memproses...' : 'Mulai Sekarang'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 2: INPUT PHYSICAL COUNT */}
      {selectedProduct && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.65)', backdropFilter: 'blur(6px)', zIndex: 99999, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem' }}>
          <div className="pwa-card" style={{ width: '100%', maxWidth: '420px', padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1rem', boxShadow: '0 20px 40px rgba(0,0,0,0.3)' }}>
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
              <div>
                <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', fontFamily: 'monospace' }}>
                  {selectedProduct.barcode || selectedProduct.sku}
                </div>
                <h3 style={{ margin: '2px 0 0', fontSize: '1.15rem', fontWeight: 800, color: 'var(--text-main)' }}>
                  {selectedProduct.name}
                </h3>
              </div>
              <X size={20} color="var(--text-muted)" style={{ cursor: 'pointer' }} onClick={() => setSelectedProduct(null)} />
            </div>

            {/* System Info Box */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem', background: 'rgba(128,128,128,0.06)', padding: '12px', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
              <div>
                <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', fontWeight: 600 }}>STOK SISTEM</div>
                <div style={{ fontSize: '1.15rem', fontWeight: 800, color: '#38bdf8', marginTop: '2px' }}>
                  {selectedProduct.stock} {selectedProduct.unit}
                </div>
              </div>
              <div>
                <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', fontWeight: 600 }}>HPP SATUAN</div>
                <div style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginTop: '2px' }}>
                  {formatCurrency(selectedProduct.cost_price)}
                </div>
              </div>
            </div>

            {/* Physical Qty Input Controls */}
            <div>
              <label style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)' }}>JUMLAH FISIK DI RAK & GUDANG</label>
              
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginTop: '6px' }}>
                <button
                  type="button"
                  onClick={() => setPhysicalQty(prev => Math.max(0, prev - 1))}
                  style={{ width: 48, height: 48, borderRadius: '12px', border: '1px solid var(--border-light)', background: 'rgba(128,128,128,0.08)', color: 'var(--text-main)', fontSize: '1.25rem', fontWeight: 800, cursor: 'pointer' }}
                >
                  -1
                </button>

                <input
                  type="number"
                  step="any"
                  value={physicalQty}
                  onChange={(e) => setPhysicalQty(parseFloat(e.target.value) || 0)}
                  style={{
                    flex: 1,
                    height: 48,
                    borderRadius: '12px',
                    border: '2px solid #f59e0b',
                    background: 'rgba(128,128,128,0.06)',
                    color: 'var(--text-main)',
                    fontSize: '1.4rem',
                    fontWeight: 800,
                    textAlign: 'center',
                    outline: 'none'
                  }}
                />

                <button
                  type="button"
                  onClick={() => setPhysicalQty(prev => prev + 1)}
                  style={{ width: 48, height: 48, borderRadius: '12px', border: '1px solid var(--border-light)', background: 'rgba(128,128,128,0.08)', color: 'var(--text-main)', fontSize: '1.25rem', fontWeight: 800, cursor: 'pointer' }}
                >
                  +1
                </button>
              </div>

              {/* Quick Adder Buttons */}
              <div style={{ display: 'flex', gap: '0.5rem', marginTop: '8px' }}>
                {[0, 5, 10, 20].map(add => (
                  <button
                    key={add}
                    type="button"
                    onClick={() => {
                      if (add === 0) setPhysicalQty(0);
                      else setPhysicalQty(prev => prev + add);
                    }}
                    style={{ flex: 1, padding: '6px', borderRadius: '8px', border: '1px solid var(--border-light)', background: 'rgba(128,128,128,0.04)', color: 'var(--text-muted)', fontSize: '0.75rem', fontWeight: 700, cursor: 'pointer' }}
                  >
                    {add === 0 ? 'Set 0' : `+${add}`}
                  </button>
                ))}
              </div>
            </div>

            {/* Difference Preview */}
            <div style={{ 
              padding: '12px', 
              borderRadius: '12px', 
              background: (physicalQty - selectedProduct.stock) < 0 ? 'rgba(239, 68, 68, 0.1)' : (physicalQty - selectedProduct.stock) > 0 ? 'rgba(59, 130, 246, 0.1)' : 'rgba(16, 185, 129, 0.1)',
              border: `1px solid ${(physicalQty - selectedProduct.stock) < 0 ? '#ef4444' : (physicalQty - selectedProduct.stock) > 0 ? '#3b82f6' : '#10b981'}`,
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            }}>
              <div>
                <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Kalkulasi Selisih:</div>
                <div style={{ 
                  fontSize: '1.05rem', 
                  fontWeight: 800, 
                  color: (physicalQty - selectedProduct.stock) < 0 ? '#ef4444' : (physicalQty - selectedProduct.stock) > 0 ? '#3b82f6' : '#10b981' 
                }}>
                  {(physicalQty - selectedProduct.stock) < 0 ? `${physicalQty - selectedProduct.stock} pcs` : (physicalQty - selectedProduct.stock) > 0 ? `+${physicalQty - selectedProduct.stock} pcs` : 'Sesuai (Cocok)'}
                </div>
              </div>

              <div style={{ textAlign: 'right' }}>
                <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Nominal:</div>
                <div style={{ 
                  fontSize: '1rem', 
                  fontWeight: 800, 
                  color: (physicalQty - selectedProduct.stock) < 0 ? '#ef4444' : (physicalQty - selectedProduct.stock) > 0 ? '#3b82f6' : '#10b981' 
                }}>
                  {formatCurrency((physicalQty - selectedProduct.stock) * selectedProduct.cost_price)}
                </div>
              </div>
            </div>

            {/* Notes / Clarification input */}
            <div>
              <label style={{ fontSize: '0.75rem', fontWeight: 700, color: 'var(--text-muted)' }}>Keterangan / Alasan Toko (Opsional)</label>
              <input
                type="text"
                placeholder="Misal: Barang retur belum diinput, rusak di rak"
                value={itemNotes}
                onChange={(e) => setItemNotes(e.target.value)}
                style={{
                  width: '100%',
                  padding: '10px 12px',
                  borderRadius: '10px',
                  border: '1px solid var(--border-light)',
                  background: 'rgba(128,128,128,0.06)',
                  color: 'var(--text-main)',
                  marginTop: '4px',
                  fontSize: '0.85rem',
                  boxSizing: 'border-box'
                }}
              />
            </div>

            {/* Buttons */}
            <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end', marginTop: '0.5rem' }}>
              <button
                type="button"
                onClick={() => setSelectedProduct(null)}
                style={{ padding: '10px 16px', borderRadius: '10px', border: '1px solid var(--border-light)', background: 'transparent', color: 'var(--text-muted)', fontWeight: 700, cursor: 'pointer' }}
              >
                Batal
              </button>
              <button
                type="button"
                disabled={savingItem}
                onClick={handleSaveItemCount}
                style={{ padding: '10px 22px', borderRadius: '10px', border: 'none', background: '#f59e0b', color: '#000', fontWeight: 800, cursor: 'pointer' }}
              >
                {savingItem ? 'Menyimpan...' : 'Simpan Hitungan'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 3: FINISH INSPECTION */}
      {showFinishModal && activeInspection && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.65)', backdropFilter: 'blur(6px)', zIndex: 99999, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem' }}>
          <div className="pwa-card" style={{ width: '100%', maxWidth: '440px', padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1.25rem', boxShadow: '0 20px 40px rgba(0,0,0,0.3)' }}>
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <h3 style={{ margin: 0, fontSize: '1.2rem', fontWeight: 800, color: 'var(--text-main)' }}>
                Selesaikan Sesi Sidak
              </h3>
              <X size={20} color="var(--text-muted)" style={{ cursor: 'pointer' }} onClick={() => setShowFinishModal(false)} />
            </div>

            {/* Summary Highlights */}
            <div style={{ background: 'rgba(128,128,128,0.06)', padding: '14px', borderRadius: '12px', border: '1px solid var(--border-light)', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85rem' }}>
                <span style={{ color: 'var(--text-muted)' }}>Total Item Diperiksa:</span>
                <strong style={{ color: 'var(--text-main)' }}>{activeInspection.total_items_checked} Produk</strong>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85rem' }}>
                <span style={{ color: 'var(--text-muted)' }}>Item Mengalami Selisih:</span>
                <strong style={{ color: activeInspection.total_items_discrepancy > 0 ? '#ef4444' : '#10b981' }}>
                  {activeInspection.total_items_discrepancy} Produk
                </strong>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.9rem', borderTop: '1px solid var(--border-light)', paddingTop: '8px' }}>
                <span style={{ color: '#ef4444', fontWeight: 600 }}>Total Kerugian (Minus):</span>
                <strong style={{ color: '#ef4444' }}>-{formatCurrency(activeInspection.total_loss_nominal)}</strong>
              </div>
            </div>

            {/* Witness & Notes inputs */}
            <div>
              <label style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)' }}>Saksi Pemeriksaan (Kepala Toko / SPV)</label>
              <input
                type="text"
                placeholder="Nama saksi lapangan"
                value={witnessName}
                onChange={(e) => setWitnessName(e.target.value)}
                style={{
                  width: '100%',
                  padding: '10px 12px',
                  borderRadius: '10px',
                  border: '1px solid var(--border-light)',
                  background: 'rgba(128,128,128,0.06)',
                  color: 'var(--text-main)',
                  marginTop: '4px',
                  fontSize: '0.85rem',
                  boxSizing: 'border-box'
                }}
              />
            </div>

            <div>
              <label style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--text-muted)' }}>Catatan Temuan Akhir</label>
              <input
                type="text"
                placeholder="Catatan untuk laporan BAP"
                value={finishNotes}
                onChange={(e) => setFinishNotes(e.target.value)}
                style={{
                  width: '100%',
                  padding: '10px 12px',
                  borderRadius: '10px',
                  border: '1px solid var(--border-light)',
                  background: 'rgba(128,128,128,0.06)',
                  color: 'var(--text-main)',
                  marginTop: '4px',
                  fontSize: '0.85rem',
                  boxSizing: 'border-box'
                }}
              />
            </div>

            {/* 2 RETAIL STANDARD DECISION BUTTONS */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', marginTop: '0.5rem' }}>
              
              {/* Option 1: Save Investigation Pending */}
              <button
                type="button"
                disabled={finishing}
                onClick={() => handleFinishInspection('INVESTIGATION')}
                style={{
                  padding: '14px',
                  borderRadius: '12px',
                  border: '1px solid #38bdf8',
                  background: 'rgba(56, 189, 248, 0.1)',
                  color: '#0284c7',
                  cursor: 'pointer',
                  textAlign: 'left'
                }}
              >
                <div style={{ fontWeight: 800, fontSize: '0.9rem' }}>📋 Simpan Temuan Saja (Investigasi)</div>
                <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                  Stok sistem <b>BELUM diubah</b>. Memberikan waktu klarifikasi dan pencarian fisik di toko.
                </div>
              </button>

              {/* Option 2: Adjust Now */}
              <button
                type="button"
                disabled={finishing}
                onClick={() => handleFinishInspection('ADJUST')}
                style={{
                  padding: '14px',
                  borderRadius: '12px',
                  border: 'none',
                  background: 'linear-gradient(135deg, #10b981, #059669)',
                  color: '#fff',
                  cursor: 'pointer',
                  textAlign: 'left',
                  boxShadow: '0 4px 14px rgba(16, 185, 129, 0.3)'
                }}
              >
                <div style={{ fontWeight: 800, fontSize: '0.9rem' }}>⚡ Simpan & Terapkan Koreksi Stok</div>
                <div style={{ fontSize: '0.75rem', color: 'rgba(255,255,255,0.85)', marginTop: '2px' }}>
                  Stok sistem <b>LANGSUNG disesuaikan</b> detik ini juga dengan membuat dokumen koreksi stok resmi.
                </div>
              </button>
            </div>

            <div style={{ textAlign: 'center' }}>
              <button
                type="button"
                onClick={() => setShowFinishModal(false)}
                style={{ background: 'transparent', border: 'none', color: 'var(--text-muted)', fontSize: '0.8rem', fontWeight: 600, cursor: 'pointer' }}
              >
                Kembali ke Pemeriksaan
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
