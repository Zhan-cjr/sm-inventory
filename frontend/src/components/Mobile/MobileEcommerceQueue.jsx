import React, { useState, useEffect } from 'react';
import { Package, XCircle, CheckCircle, Clock } from 'lucide-react';

export function MobileEcommerceQueue({ authToken, user }) {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [branches, setBranches] = useState([]);
  const [selectedBranchId, setSelectedBranchId] = useState(user?.branch_id || '');

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

  const fetchOrders = () => {
    if (showBranchSelector && !selectedBranchId) return;

    fetch(`/api/v1/ecommerce/pending?branch_id=${selectedBranchId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setOrders(data.data || []);
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });
  };

  useEffect(() => {
    if (showBranchSelector && !selectedBranchId) return;
    
    fetchOrders();
    const interval = setInterval(fetchOrders, 5000);
    return () => clearInterval(interval);
  }, [authToken, selectedBranchId, showBranchSelector]);

  const handleAction = (id, action) => {
    if (action === 'reject' && !window.confirm('Apakah Anda yakin ingin menolak pesanan ini? Stok akan dikembalikan.')) {
        return;
    }
    if (action === 'approve' && !window.confirm('Apakah Anda yakin ingin memproses pesanan ini? Pelanggan akan menerima notifikasi.')) {
        return;
    }
    if (action === 'complete' && !window.confirm('Apakah pesanan ini sudah selesai?')) {
        return;
    }

    fetch(`/api/v1/ecommerce/${id}/process`, {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ action })
    })
      .then(res => res.json())
      .then(() => fetchOrders())
      .catch(err => console.error(err));
  };

  return (
    <div className="mobile-auth-queue">
      <h3 style={{ marginBottom: '1rem', color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '8px' }}>
        <Package size={20} /> E-Commerce Orders
      </h3>

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

      {loading && orders.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '1rem' }}>
          <div className="spin" style={{ width: '20px', height: '20px', border: '2px solid var(--primary)', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
        </div>
      ) : orders.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-muted)', background: 'var(--bg-card)', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
          <Package size={48} style={{ opacity: 0.5, margin: '0 auto 1rem auto' }} />
          <p>Belum ada pesanan E-Commerce baru.</p>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          {orders.map(order => (
            <div key={order.id} style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '12px', border: '1px solid var(--border-light)', boxShadow: '0 4px 6px rgba(0,0,0,0.1)' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.75rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.5rem' }}>
                <span style={{ fontWeight: '700', color: '#3b82f6' }}>Order #{order.id.substring(0, 8).toUpperCase()}</span>
                <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: '4px' }}>
                  <Clock size={12} /> {new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                </span>
              </div>
              
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem', marginBottom: '1rem' }}>
                <div>
                  <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', margin: '0' }}>Pelanggan</p>
                  <p style={{ fontSize: '0.95rem', color: 'white', margin: '0', fontWeight: 'bold' }}>{order.customer_name}</p>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', margin: '0' }}>Pengiriman</p>
                  <p style={{ fontSize: '0.95rem', color: order.delivery_method === 'PICKUP' ? '#10b981' : '#f59e0b', margin: '0', fontWeight: 'bold' }}>
                    {order.delivery_method === 'PICKUP' ? 'Ambil Sendiri' : 'Kirim Alamat'}
                  </p>
                </div>
              </div>

              <div style={{ marginBottom: '1rem', background: 'var(--bg-elevated)', padding: '0.75rem', borderRadius: '8px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                  <span style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Status Bayar:</span>
                  <span style={{ fontSize: '0.85rem', fontWeight: 'bold', color: order.payment_status === 'PAID' ? '#10b981' : '#f59e0b' }}>
                    {order.payment_method} - {order.payment_status}
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Total Amount:</span>
                  <span style={{ fontSize: '1rem', fontWeight: 'bold', color: 'white' }}>
                    Rp {parseFloat(order.total_amount).toLocaleString('id-ID')}
                  </span>
                </div>
              </div>

              <div style={{ marginBottom: '1.25rem' }}>
                <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Daftar Belanja:</p>
                <div style={{ maxHeight: '150px', overflowY: 'auto', paddingRight: '0.5rem' }}>
                  {order.items?.map((item, idx) => (
                    <div key={idx} style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85rem', marginBottom: '0.25rem' }}>
                      <span style={{ color: 'white' }}>{item.quantity}x {item.product?.name || 'Produk'}</span>
                      <span style={{ color: 'var(--text-muted)' }}>Rp {parseFloat(item.subtotal).toLocaleString('id-ID')}</span>
                    </div>
                  ))}
                </div>
              </div>

              <div style={{ display: 'flex', gap: '0.75rem' }}>
                {order.status === 'PENDING' ? (
                  <>
                    <button 
                      onClick={() => handleAction(order.id, 'reject')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: '1px solid #ef4444', background: 'transparent', color: '#ef4444', fontWeight: 'bold', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '6px' }}
                    >
                      <XCircle size={18} /> TOLAK
                    </button>
                    <button 
                      onClick={() => handleAction(order.id, 'approve')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: 'none', background: '#10b981', color: 'white', fontWeight: 'bold', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '6px' }}
                    >
                      <CheckCircle size={18} /> PROSES
                    </button>
                  </>
                ) : (
                  <button 
                    onClick={() => handleAction(order.id, 'complete')}
                    style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: 'none', background: '#3b82f6', color: 'white', fontWeight: 'bold', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '6px' }}
                  >
                    <CheckCircle size={18} /> SELESAI
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
