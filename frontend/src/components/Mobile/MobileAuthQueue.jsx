import React, { useState, useEffect } from 'react';

export function MobileAuthQueue({ authToken, user }) {
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

  return (
    <div className="mobile-auth-queue">
      <h3 style={{ marginBottom: '1rem', color: 'var(--text-main)' }}>Persetujuan & Otorisasi</h3>

      {showBranchSelector && (
        <div style={{ marginBottom: '1.5rem' }}>
          <label style={{ display: 'block', fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.25rem' }}>Pilih Cabang (Admin Mode)</label>
          <select 
            value={selectedBranchId} 
            onChange={(e) => setSelectedBranchId(e.target.value)}
            style={{ width: '100%', padding: '0.75rem', borderRadius: '8px', background: 'var(--bg-elevated)', color: 'white', border: '1px solid var(--border-light)' }}
          >
            <option value="">-- Pilih Cabang --</option>
            {branches.map(b => (
              <option key={b.id} value={b.id}>{b.name}</option>
            ))}
          </select>
        </div>
      )}

      {hasPosAuth && (
        <>
          <h4 style={{ color: 'var(--text-main)', marginTop: '1.5rem', marginBottom: '0.5rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.5rem' }}>Otorisasi POS Kasir</h4>
          {loading && requests.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '1rem' }}>
              <div className="spin" style={{ width: '20px', height: '20px', border: '2px solid var(--primary)', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
            </div>
          ) : requests.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '1.5rem', color: 'var(--text-muted)' }}>
              <p>Belum ada permintaan otorisasi POS.</p>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
              {requests.map(req => (
                <div key={req.id} style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                    <span style={{ fontWeight: '700', color: '#f59e0b' }}>{req.action}</span>
                    <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                      {new Date(req.created_at).toLocaleTimeString('id-ID')}
                    </span>
                  </div>
                  <p style={{ fontSize: '0.9rem', marginBottom: '0.25rem', color: 'var(--text-muted)' }}>
                    Diminta oleh: <strong style={{ color: 'white' }}>{req.cashier?.name}</strong>
                  </p>
                  <p style={{ fontSize: '0.9rem', marginBottom: '1rem', color: 'var(--text-muted)' }}>
                    Cabang: <strong style={{ color: 'white' }}>{req.branch?.name || 'Pusat'}</strong>
                  </p>
                  <div style={{ display: 'flex', gap: '0.75rem' }}>
                    <button 
                      onClick={() => handleAction(req.id, 'reject')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: 'none', background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', fontWeight: 'bold' }}
                    >
                      TOLAK
                    </button>
                    <button 
                      onClick={() => handleAction(req.id, 'approve')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: 'none', background: 'rgba(16, 185, 129, 0.1)', color: '#10b981', fontWeight: 'bold' }}
                    >
                      SETUJUI
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      )}

      {hasDocAuth && (
        <>
          <h4 style={{ color: 'var(--text-main)', marginTop: '2rem', marginBottom: '0.5rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.5rem' }}>Persetujuan Dokumen (PO & Koreksi Stok)</h4>
          {loadingDocs && docRequests.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '1rem' }}>
              <div className="spin" style={{ width: '20px', height: '20px', border: '2px solid var(--primary)', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
            </div>
          ) : docRequests.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '1.5rem', color: 'var(--text-muted)' }}>
              <p>Belum ada dokumen yang perlu disetujui.</p>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
              {docRequests.map(req => (
                <div key={req.id} style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                    <span style={{ fontWeight: '700', color: '#3b82f6' }}>{req.type} ({req.number})</span>
                    <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                      {new Date(req.created_at).toLocaleTimeString('id-ID')}
                    </span>
                  </div>
                  <p style={{ fontSize: '0.9rem', marginBottom: '0.25rem', color: 'var(--text-muted)' }}>
                    Total: <strong style={{ color: 'white' }}>Rp {parseFloat(req.total).toLocaleString('id-ID')}</strong>
                  </p>
                  <p style={{ fontSize: '0.9rem', marginBottom: '0.25rem', color: 'var(--text-muted)' }}>
                    Dibuat oleh: <strong style={{ color: 'white' }}>{req.created_by}</strong>
                  </p>
                  {req.supplier_name && (
                    <p style={{ fontSize: '0.9rem', marginBottom: '0.25rem', color: 'var(--text-muted)' }}>
                      Supplier: <strong style={{ color: 'white' }}>{req.supplier_name}</strong>
                    </p>
                  )}
                  <p style={{ fontSize: '0.9rem', marginBottom: '0.25rem', color: 'var(--text-muted)' }}>
                    Cabang: <strong style={{ color: 'white' }}>{req.branch_name || 'Pusat'}</strong>
                  </p>
                  {req.notes && (
                    <p style={{ fontSize: '0.85rem', color: '#f59e0b', fontStyle: 'italic', marginBottom: '1rem' }}>
                      {req.notes}
                    </p>
                  )}
                  <div style={{ display: 'flex', gap: '0.75rem', marginTop: '1rem' }}>
                    <button 
                      onClick={() => handleRejectClick(req.id)}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: '1px solid #ef4444', background: 'transparent', color: '#ef4444', fontWeight: 'bold' }}
                    >
                      TOLAK
                    </button>
                    <button 
                      onClick={() => handleReview(req.id)}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: '1px solid #3b82f6', background: 'transparent', color: '#3b82f6', fontWeight: 'bold' }}
                    >
                      REVIEW
                    </button>
                    <button 
                      onClick={() => handleDocAction(req.id, 'approve')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: 'none', background: '#10b981', color: 'white', fontWeight: 'bold' }}
                    >
                      SETUJUI
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      )}

      {showModal && (
        <div style={{
          position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
          background: 'rgba(0,0,0,0.7)', zIndex: 9999,
          display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '1rem'
        }}>
          <div style={{
            background: 'var(--bg-card)', padding: '1.5rem', borderRadius: '12px',
            width: '100%', maxWidth: '500px', maxHeight: '90vh', overflowY: 'auto',
            border: '1px solid var(--border-light)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
              <h3 style={{ margin: 0, color: 'var(--text-main)' }}>Detail Dokumen</h3>
              <button onClick={() => setShowModal(false)} style={{ background: 'transparent', border: 'none', color: 'var(--text-muted)', fontSize: '1.5rem' }}>&times;</button>
            </div>
            
            {modalLoading ? (
              <div style={{ textAlign: 'center', padding: '2rem' }}>
                <div className="spin" style={{ width: '30px', height: '30px', border: '3px solid var(--primary)', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
              </div>
            ) : modalData ? (
              <div>
                <p style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Tipe: <strong style={{ color: 'white' }}>{modalData.type}</strong></p>
                <p style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>No: <strong style={{ color: 'white' }}>{modalData.number}</strong></p>
                {modalData.supplier && <p style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Supplier: <strong style={{ color: 'white' }}>{modalData.supplier}</strong></p>}
                {modalData.reason && <p style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Alasan: <strong style={{ color: 'white' }}>{modalData.reason}</strong></p>}
                
                <h4 style={{ marginTop: '1.5rem', marginBottom: '0.75rem', color: 'var(--text-main)', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.5rem' }}>Rincian Barang</h4>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                  {modalData.items?.map((item, idx) => (
                    <div key={idx} style={{ background: 'var(--bg-elevated)', padding: '0.75rem', borderRadius: '8px' }}>
                      <p style={{ margin: '0 0 0.25rem 0', fontWeight: 'bold', color: 'white' }}>{item.product_name}</p>
                      {modalData.type === 'Otorisasi PO' ? (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.35rem' }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                            <span>{item.qty} x Rp {parseFloat(item.price).toLocaleString('id-ID')}</span>
                            <strong style={{ color: '#10b981' }}>Rp {parseFloat(item.subtotal).toLocaleString('id-ID')}</strong>
                          </div>
                          <div style={{ fontSize: '0.8rem', color: '#f59e0b', background: 'rgba(245, 158, 11, 0.1)', padding: '0.35rem 0.5rem', borderRadius: '4px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <span>Terjual (30 Hari): <strong style={{color: 'white'}}>{item.avg_sales_per_month || 0}</strong></span>
                            <span>Sisa Stok: <strong style={{color: 'white'}}>{item.current_stock || 0}</strong></span>
                          </div>
                        </div>
                      ) : (
                        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                          <span>{modalData.type === 'Otorisasi Penerimaan Qty Gudang' ? 'Qty: ' + item.old_qty + ' \u2192 ' + item.new_qty : 'Stok: ' + item.old_qty + ' \u2192 ' + item.new_qty}</span>
                          <strong style={{ color: item.diff < 0 ? '#ef4444' : '#10b981' }}>
                            {item.diff > 0 ? '+' : ''}{item.diff}
                          </strong>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
                
                <div style={{ display: 'flex', gap: '0.75rem', marginTop: '2rem' }}>
                  <button 
                    onClick={() => {
                        setShowModal(false);
                        handleRejectClick(modalData.id);
                    }}
                    style={{ flex: 1, padding: '1rem', borderRadius: '8px', border: 'none', background: '#ef4444', color: 'white', fontWeight: 'bold' }}
                  >
                    TOLAK DOKUMEN
                  </button>
                  <button 
                    onClick={() => handleDocAction(modalData.id, 'approve')}
                    style={{ flex: 1, padding: '1rem', borderRadius: '8px', border: 'none', background: '#10b981', color: 'white', fontWeight: 'bold' }}
                  >
                    SETUJUI DOKUMEN
                  </button>
                </div>
              </div>
            ) : null}
          </div>
        </div>
      )}

      {showRejectModal && (
        <div style={{
          position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
          background: 'rgba(0,0,0,0.7)', zIndex: 10000,
          display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '1rem'
        }}>
          <div style={{
            background: 'var(--bg-card)', padding: '1.5rem', borderRadius: '12px',
            width: '100%', maxWidth: '400px',
            border: '1px solid var(--border-light)'
          }}>
            <h3 style={{ margin: '0 0 1rem 0', color: 'var(--text-main)' }}>Alasan Penolakan</h3>
            <textarea
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              placeholder="Masukkan alasan penolakan..."
              style={{
                width: '100%', padding: '0.75rem', borderRadius: '8px', 
                background: 'var(--bg-elevated)', color: 'white', 
                border: '1px solid var(--border-light)', minHeight: '100px',
                marginBottom: '1rem', resize: 'vertical'
              }}
            />
            <div style={{ display: 'flex', gap: '0.75rem' }}>
              <button 
                onClick={() => setShowRejectModal(false)}
                style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: '1px solid var(--border-light)', background: 'transparent', color: 'var(--text-muted)', fontWeight: 'bold' }}
              >
                BATAL
              </button>
              <button 
                onClick={() => {
                  handleDocAction(rejectId, 'reject', rejectReason);
                  setShowRejectModal(false);
                }}
                style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: 'none', background: '#ef4444', color: 'white', fontWeight: 'bold' }}
              >
                KONFIRMASI TOLAK
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
