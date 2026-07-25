import React, { useState, useEffect } from 'react';
import { 
  ShieldCheck, 
  FileText, 
  Check, 
  X, 
  Eye, 
  Clock, 
  User, 
  Store, 
  AlertCircle,
  Package,
  Building,
  CheckCircle2,
  XCircle,
  HelpCircle
} from 'lucide-react';

export function MobileAuthQueue({ authToken, user }) {
  const [activeTab, setActiveTab] = useState('pos'); // 'pos' or 'docs'
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [branches, setBranches] = useState([]);
  const [selectedBranchId, setSelectedBranchId] = useState(user?.branch_id || '');

  const isAdmin = ['ADMIN', 'SUPER_ADMIN'].includes(user?.role?.toUpperCase());
  const showBranchSelector = !user?.branch_id || isAdmin;

  const hasPosAuth = user?.pos_authorizations && user.pos_authorizations.length > 0;
  const hasDocAuth = user?.custom_authorizations?.includes('APPROVE_PO') || user?.custom_authorizations?.includes('APPROVE_STOCK_ADJUSTMENT') || user?.custom_authorizations?.includes('APPROVE_GR_OVERQUANTITY');

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

  const [docRequests, setDocRequests] = useState([]);
  const [loadingDocs, setLoadingDocs] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [modalData, setModalData] = useState(null);
  const [modalLoading, setModalLoading] = useState(false);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectId, setRejectId] = useState(null);
  const [rejectReason, setRejectReason] = useState('');

  const fetchRequests = () => {
    if (showBranchSelector && !selectedBranchId) return;

    // Fetch POS Authorizations
    fetch(`/api/v1/authorizations/pending?branch_id=${selectedBranchId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setRequests(data.data || []);
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });

    // Fetch Document Approvals
    fetch(`/api/v1/document-approvals/pending?branch_id=${selectedBranchId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setDocRequests(data.data || []);
        setLoadingDocs(false);
      })
      .catch(err => {
        console.error(err);
        setLoadingDocs(false);
      });
  };

  useEffect(() => {
    if (showBranchSelector && !selectedBranchId) return;
    
    fetchRequests();
    const interval = setInterval(fetchRequests, 3000);
    return () => clearInterval(interval);
  }, [authToken, selectedBranchId, showBranchSelector]);

  const handleAction = (id, type) => {
    fetch(`/api/v1/authorizations/${id}/${type}`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(() => fetchRequests())
      .catch(err => console.error(err));
  };

  const handleRejectClick = (id) => {
    setRejectId(id);
    setRejectReason('');
    setShowRejectModal(true);
  };

  const handleDocAction = (id, action, reason = '') => {
    let payload = {};
    if (action === 'reject') {
      if (reason.trim() === '') {
        alert("Alasan penolakan wajib diisi.");
        return;
      }
      payload = { notes: reason };
    }

    fetch(`/api/v1/document-approvals/${id}/${action}`, {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })
      .then(res => res.json())
      .then(() => {
        setShowModal(false);
        fetchRequests();
      })
      .catch(err => console.error(err));
  };

  const handleReview = (id) => {
    setModalLoading(true);
    setShowModal(true);
    setModalData(null);
    fetch(`/api/v1/document-approvals/${id}/details`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setModalData({ ...data, id });
        setModalLoading(false);
      })
      .catch(err => {
        console.error(err);
        setModalLoading(false);
      });
  };

  const canApprovePO = user?.custom_authorizations?.includes('APPROVE_PO');
  const canApproveStock = user?.custom_authorizations?.includes('APPROVE_STOCK_ADJUSTMENT');
  const canApproveGR = user?.custom_authorizations?.includes('APPROVE_GR_OVERQUANTITY');

  const poRequests = docRequests.filter(req => req.type === 'Otorisasi PO');
  const stockRequests = docRequests.filter(req => req.type === 'Otorisasi Koreksi Stok');
  const grRequests = docRequests.filter(req => req.type === 'Otorisasi Penerimaan Qty Gudang');

  const totalPendingPos = requests.length;
  const totalPendingDocs = docRequests.length;

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem', animation: 'fadeIn 0.3s ease-out' }}>
      
      {/* Title Header */}
      <div>
        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600 }}>
          Persetujuan Supervisor
        </div>
        <h2 style={{ fontSize: '1.4rem', fontWeight: 800, margin: 0, color: 'var(--text-main)' }}>
          Antrean Otorisasi
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

      {/* Segmented Control Tab Switcher */}
      <div className="segmented-control">
        <button 
          className={activeTab === 'pos' ? 'active' : ''} 
          onClick={() => setActiveTab('pos')}
          style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px' }}
        >
          <ShieldCheck size={16} /> Supervisi POS
          {totalPendingPos > 0 && (
            <span style={{ background: '#ef4444', color: 'white', fontSize: '0.65rem', fontWeight: 800, padding: '1px 6px', borderRadius: '99px' }}>
              {totalPendingPos}
            </span>
          )}
        </button>
        <button 
          className={activeTab === 'docs' ? 'active' : ''} 
          onClick={() => setActiveTab('docs')}
          style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px' }}
        >
          <FileText size={16} /> Dokumen & PO
          {totalPendingDocs > 0 && (
            <span style={{ background: '#3b82f6', color: 'white', fontSize: '0.65rem', fontWeight: 800, padding: '1px 6px', borderRadius: '99px' }}>
              {totalPendingDocs}
            </span>
          )}
        </button>
      </div>

      {/* TAB 1: POS Supervisor Requests */}
      {activeTab === 'pos' && (
        <div>
          {loading && requests.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '2rem' }}>
              <div className="spin" style={{ width: '32px', height: '32px', border: '3px solid #10b981', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
              <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginTop: '0.75rem' }}>Memeriksa permintaan otorisasi POS...</div>
            </div>
          ) : requests.length === 0 ? (
            <div className="pwa-card" style={{ textAlign: 'center', padding: '2rem' }}>
              <CheckCircle2 size={40} color="#10b981" style={{ margin: '0 auto 0.75rem' }} />
              <h3 style={{ fontSize: '1.1rem', fontWeight: 700, margin: '0 0 4px', color: 'var(--text-main)' }}>Tidak Ada Antrean POS</h3>
              <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Semua transaksi POS kasir berjalan normal tanpa pending otorisasi.</p>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
              {requests.map(req => (
                <div key={req.id} className="pwa-card" style={{ borderLeft: '4px solid #f59e0b' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.65rem' }}>
                    <span style={{ fontWeight: 800, color: '#f59e0b', fontSize: '0.95rem', background: 'rgba(245, 158, 11, 0.12)', padding: '4px 10px', borderRadius: '8px' }}>
                      {req.action}
                    </span>
                    <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <Clock size={14} /> {new Date(req.created_at).toLocaleTimeString('id-ID')}
                    </span>
                  </div>

                  <div style={{ display: 'flex', flexDirection: 'column', gap: '4px', marginBottom: '1rem', fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <User size={15} /> Diminta oleh: <strong style={{ color: 'var(--text-main)' }}>{req.cashier?.name || 'Kasir'}</strong>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <Building size={15} /> Cabang: <strong style={{ color: 'var(--text-main)' }}>{req.branch?.name || 'Cabang POS'}</strong>
                    </div>
                  </div>

                  <div style={{ display: 'flex', gap: '0.75rem' }}>
                    <button 
                      onClick={() => handleAction(req.id, 'reject')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '14px', border: '1px solid rgba(239, 68, 68, 0.3)', background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', fontWeight: 800, fontSize: '0.88rem', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px' }}
                    >
                      <X size={16} /> TOLAK
                    </button>
                    <button 
                      onClick={() => handleAction(req.id, 'approve')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '14px', border: 'none', background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)', color: 'white', fontWeight: 800, fontSize: '0.88rem', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', boxShadow: '0 4px 14px rgba(16, 185, 129, 0.3)' }}
                    >
                      <Check size={16} /> SETUJUI
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* TAB 2: Document Approvals (PO, Stock, GR) */}
      {activeTab === 'docs' && (
        <div>
          {loadingDocs && docRequests.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '2rem' }}>
              <div className="spin" style={{ width: '32px', height: '32px', border: '3px solid #3b82f6', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
              <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginTop: '0.75rem' }}>Memuat persetujuan dokumen...</div>
            </div>
          ) : docRequests.length === 0 ? (
            <div className="pwa-card" style={{ textAlign: 'center', padding: '2rem' }}>
              <CheckCircle2 size={40} color="#3b82f6" style={{ margin: '0 auto 0.75rem' }} />
              <h3 style={{ fontSize: '1.1rem', fontWeight: 700, margin: '0 0 4px', color: 'var(--text-main)' }}>Tidak Ada Dokumen Pending</h3>
              <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Semua dokumen Purchase Order & Koreksi Stok telah diproses.</p>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
              {docRequests.map(req => (
                <div key={req.id} className="pwa-card" style={{ borderLeft: '4px solid #3b82f6' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.65rem' }}>
                    <span style={{ fontWeight: 800, color: '#3b82f6', fontSize: '0.9rem', background: 'rgba(59, 130, 246, 0.12)', padding: '4px 10px', borderRadius: '8px' }}>
                      {req.type} ({req.number})
                    </span>
                    <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <Clock size={14} /> {new Date(req.created_at).toLocaleTimeString('id-ID')}
                    </span>
                  </div>

                  <div style={{ display: 'flex', flexDirection: 'column', gap: '4px', marginBottom: '0.85rem', fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                    <div>Total Nominal / Qty: <strong style={{ color: 'var(--text-main)', fontSize: '0.95rem' }}>{req.type === 'Otorisasi Penerimaan Qty Gudang' ? req.total + ' item' : 'Rp ' + parseFloat(req.total).toLocaleString('id-ID')}</strong></div>
                    <div>Dibuat oleh: <strong style={{ color: 'var(--text-main)' }}>{req.created_by}</strong></div>
                    {req.supplier_name && <div>Supplier: <strong style={{ color: 'var(--text-main)' }}>{req.supplier_name}</strong></div>}
                    {req.notes && <div style={{ color: '#f59e0b', fontStyle: 'italic', marginTop: '2px' }}>Catatan: "{req.notes}"</div>}
                  </div>

                  <div style={{ display: 'flex', gap: '0.5rem' }}>
                    <button 
                      onClick={() => handleRejectClick(req.id)}
                      style={{ flex: 1, padding: '0.65rem', borderRadius: '12px', border: '1px solid rgba(239, 68, 68, 0.3)', background: 'transparent', color: '#ef4444', fontWeight: 700, fontSize: '0.8rem', cursor: 'pointer' }}
                    >
                      Tolak
                    </button>
                    <button 
                      onClick={() => handleReview(req.id)}
                      style={{ flex: 1, padding: '0.65rem', borderRadius: '12px', border: '1px solid #3b82f6', background: 'rgba(59, 130, 246, 0.1)', color: '#3b82f6', fontWeight: 700, fontSize: '0.8rem', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '4px' }}
                    >
                      <Eye size={15} /> Rincian
                    </button>
                    <button 
                      onClick={() => handleDocAction(req.id, 'approve')}
                      style={{ flex: 1, padding: '0.65rem', borderRadius: '12px', border: 'none', background: '#10b981', color: 'white', fontWeight: 800, fontSize: '0.8rem', cursor: 'pointer' }}
                    >
                      Setujui
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Review Details Sheet Modal */}
      {showModal && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.75)', zIndex: 9999, display: 'flex', justifyContent: 'center', alignItems: 'flex-end', animation: 'fadeIn 0.2s ease-out' }}>
          <div className="pwa-card" style={{ width: '100%', maxWidth: '500px', maxHeight: '85vh', overflowY: 'auto', borderBottomLeftRadius: 0, borderBottomRightRadius: 0, padding: '1.5rem', animation: 'slideUp 0.3s ease-out' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.75rem' }}>
              <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 800, color: 'var(--text-main)' }}>Rincian Items Dokumen</h3>
              <button onClick={() => setShowModal(false)} style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}><X size={20} /></button>
            </div>
            
            {modalLoading ? (
              <div style={{ textAlign: 'center', padding: '2rem' }}>
                <div className="spin" style={{ width: '30px', height: '30px', border: '3px solid #3b82f6', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
              </div>
            ) : modalData ? (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                  <div>No: <strong style={{ color: 'var(--text-main)' }}>{modalData.number}</strong></div>
                  <div>Tipe: <strong style={{ color: '#3b82f6' }}>{modalData.type}</strong></div>
                  {modalData.supplier && <div>Supplier: <strong style={{ color: 'var(--text-main)' }}>{modalData.supplier}</strong></div>}
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
                  {(modalData.type === 'Otorisasi Penerimaan Qty Gudang' 
                    ? modalData.items?.filter(item => item.diff > 0) 
                    : modalData.items
                  )?.map((item, idx) => (
                    <div key={idx} style={{ background: 'rgba(255,255,255,0.04)', border: '1px solid var(--border-light)', padding: '0.85rem', borderRadius: '14px' }}>
                      <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--text-main)', marginBottom: '4px' }}>{item.product_name}</div>
                      {modalData.type === 'Otorisasi PO' ? (
                        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: 'var(--text-muted)' }}>
                          <span>{item.qty} x Rp {parseFloat(item.price).toLocaleString('id-ID')}</span>
                          <strong style={{ color: '#10b981' }}>Rp {parseFloat(item.subtotal).toLocaleString('id-ID')}</strong>
                        </div>
                      ) : (
                        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: 'var(--text-muted)' }}>
                          <span>Stok: {item.old_qty} &rarr; {item.new_qty}</span>
                          <strong style={{ color: item.diff < 0 ? '#ef4444' : '#10b981' }}>{item.diff > 0 ? '+' : ''}{item.diff}</strong>
                        </div>
                      )}
                    </div>
                  ))}
                </div>

                <div style={{ display: 'flex', gap: '0.75rem', marginTop: '0.5rem' }}>
                  <button 
                    onClick={() => { setShowModal(false); handleRejectClick(modalData.id); }}
                    style={{ flex: 1, padding: '0.85rem', borderRadius: '14px', border: '1px solid #ef4444', background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', fontWeight: 800, fontSize: '0.9rem', cursor: 'pointer' }}
                  >
                    Tolak Dokumen
                  </button>
                  <button 
                    onClick={() => handleDocAction(modalData.id, 'approve')}
                    style={{ flex: 1, padding: '0.85rem', borderRadius: '14px', border: 'none', background: '#10b981', color: 'white', fontWeight: 800, fontSize: '0.9rem', cursor: 'pointer' }}
                  >
                    Setujui Dokumen
                  </button>
                </div>
              </div>
            ) : null}
          </div>
        </div>
      )}

      {/* Reject Reason Modal */}
      {showRejectModal && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.75)', zIndex: 10000, display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '1rem' }}>
          <div className="pwa-card" style={{ width: '100%', maxWidth: '400px' }}>
            <h3 style={{ margin: '0 0 1rem 0', fontSize: '1.1rem', fontWeight: 800, color: 'var(--text-main)' }}>Input Alasan Penolakan</h3>
            <textarea
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              placeholder="Masukkan catatan alasan penolakan untuk supervisor/staf..."
              style={{ width: '100%', padding: '0.75rem', borderRadius: '14px', background: 'rgba(0,0,0,0.2)', color: 'var(--text-main)', border: '1px solid var(--border-light)', minHeight: '100px', marginBottom: '1rem', resize: 'vertical', fontSize: '0.85rem', outline: 'none' }}
            />
            <div style={{ display: 'flex', gap: '0.75rem' }}>
              <button 
                onClick={() => setShowRejectModal(false)}
                style={{ flex: 1, padding: '0.75rem', borderRadius: '12px', border: '1px solid var(--border-light)', background: 'transparent', color: 'var(--text-muted)', fontWeight: 700, cursor: 'pointer' }}
              >
                Batal
              </button>
              <button 
                onClick={() => {
                  handleDocAction(rejectId, 'reject', rejectReason);
                  setShowRejectModal(false);
                }}
                style={{ flex: 1, padding: '0.75rem', borderRadius: '12px', border: 'none', background: '#ef4444', color: 'white', fontWeight: 800, cursor: 'pointer' }}
              >
                Konfirmasi Tolak
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
