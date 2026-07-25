import React, { useState, useEffect } from 'react';
import { 
  ShoppingBag, 
  PackageCheck, 
  Clock, 
  User, 
  CreditCard, 
  Truck, 
  Store, 
  CheckCircle2, 
  XCircle, 
  AlertCircle 
} from 'lucide-react';

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
    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem', animation: 'fadeIn 0.3s ease-out' }}>
      
      {/* Title */}
      <div>
        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600 }}>
          Pesanan Online
        </div>
        <h2 style={{ fontSize: '1.4rem', fontWeight: 800, margin: 0, color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '8px' }}>
          <ShoppingBag size={24} color="#6366f1" /> E-Commerce Orders
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

      {loading && orders.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <div className="spin" style={{ width: '32px', height: '32px', border: '3px solid #6366f1', borderTopColor: 'transparent', borderRadius: '50%', margin: '0 auto' }}></div>
          <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginTop: '0.75rem' }}>Memeriksa pesanan e-commerce...</div>
        </div>
      ) : orders.length === 0 ? (
        <div className="pwa-card" style={{ textAlign: 'center', padding: '2.5rem 1rem' }}>
          <PackageCheck size={48} color="#10b981" style={{ margin: '0 auto 0.75rem' }} />
          <h3 style={{ fontSize: '1.1rem', fontWeight: 700, margin: '0 0 4px', color: 'var(--text-main)' }}>Semua Pesanan Selesai</h3>
          <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Belum ada pesanan online baru dari aplikasi pelanggan.</p>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
          {orders.map(order => (
            <div key={order.id} className="pwa-card" style={{ borderLeft: '4px solid #6366f1' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.55rem' }}>
                <span style={{ fontWeight: 800, color: '#6366f1', fontSize: '0.95rem' }}>
                  Order #{order.id.substring(0, 8).toUpperCase()}
                </span>
                <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: '4px' }}>
                  <Clock size={13} /> {new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                </span>
              </div>
              
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem', marginBottom: '0.85rem' }}>
                <div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Pelanggan</div>
                  <div style={{ fontSize: '0.9rem', color: 'var(--text-main)', fontWeight: 700 }}>{order.customer_name}</div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Metode Ambil</div>
                  <div style={{ fontSize: '0.88rem', color: order.delivery_method === 'PICKUP' ? '#10b981' : '#f59e0b', fontWeight: 700, display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '4px' }}>
                    <Truck size={14} /> {order.delivery_method === 'PICKUP' ? 'Pick-Up Store' : 'Delivery'}
                  </div>
                </div>
              </div>

              <div style={{ marginBottom: '0.85rem', background: 'rgba(255,255,255,0.04)', padding: '0.75rem', borderRadius: '12px', border: '1px solid var(--border-light)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                  <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Pembayaran:</span>
                  <span style={{ fontSize: '0.8rem', fontWeight: 700, color: order.payment_status === 'PAID' ? '#10b981' : '#f59e0b' }}>
                    {order.payment_method} &bull; {order.payment_status}
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Total Belanja:</span>
                  <span style={{ fontSize: '1.05rem', fontWeight: 900, color: '#10b981' }}>
                    Rp {parseFloat(order.total_amount).toLocaleString('id-ID')}
                  </span>
                </div>
              </div>

              <div style={{ marginBottom: '1rem' }}>
                <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', marginBottom: '0.4rem', fontWeight: 600 }}>Daftar Belanja Item:</div>
                <div style={{ maxHeight: '140px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '4px' }}>
                  {order.items?.map((item, idx) => (
                    <div key={idx} style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem' }}>
                      <span style={{ color: 'var(--text-main)', fontWeight: 600 }}>{item.quantity}x {item.product?.name || 'Produk'}</span>
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
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '14px', border: '1px solid rgba(239, 68, 68, 0.3)', background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', fontWeight: 800, fontSize: '0.85rem', cursor: 'pointer', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '6px' }}
                    >
                      <XCircle size={16} /> TOLAK
                    </button>
                    <button 
                      onClick={() => handleAction(order.id, 'approve')}
                      style={{ flex: 1, padding: '0.75rem', borderRadius: '14px', border: 'none', background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)', color: 'white', fontWeight: 800, fontSize: '0.85rem', cursor: 'pointer', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '6px' }}
                    >
                      <CheckCircle2 size={16} /> PROSES
                    </button>
                  </>
                ) : (
                  <button 
                    onClick={() => handleAction(order.id, 'complete')}
                    style={{ flex: 1, padding: '0.75rem', borderRadius: '14px', border: 'none', background: '#3b82f6', color: 'white', fontWeight: 800, fontSize: '0.85rem', cursor: 'pointer', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '6px' }}
                  >
                    <CheckCircle2 size={16} /> SELESAI
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
