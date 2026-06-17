import React, { useState, useEffect } from 'react';
import { Bot, X } from 'lucide-react';
import { SmartAssistant } from '../SmartAssistant';

export function MobileDashboard({ user, authToken }) {
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const [branches, setBranches] = useState([]);
  const [selectedBranchId, setSelectedBranchId] = useState(user?.branch_id || '');
  const [showAI, setShowAI] = useState(false);

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

  useEffect(() => {
    if (showBranchSelector && !selectedBranchId) return; // Wait until a branch is selected

    setLoading(true);
    fetch(`/api/v1/dashboard/metrics?branch_id=${selectedBranchId}`, {
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    })
      .then(res => {
        if (!res.ok) throw new Error('Gagal memuat data dashboard');
        return res.json();
      })
      .then(data => {
        setMetrics(data);
      })
      .catch(err => {
        setError(err.message);
      })
      .finally(() => {
        setLoading(false);
      });
  }, [authToken, selectedBranchId, user]);

  if (loading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100%' }}>
        <div className="spinner"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div style={{ padding: '1rem', color: '#ef4444', textAlign: 'center' }}>
        <p>{error}</p>
        <p style={{ fontSize: '0.8rem', marginTop: '0.5rem', color: 'var(--text-muted)' }}>Pastikan Anda memiliki hak akses (Supervisor/Manager).</p>
      </div>
    );
  }

  const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

  return (
    <div style={{ padding: '1rem', paddingBottom: '5rem', animation: 'fadeIn 0.3s ease-out', position: 'relative' }}>
      
      {showAI ? (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: '60px', zIndex: 9999, background: '#0f172a', display: 'flex', flexDirection: 'column' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1rem', background: 'var(--bg-card)', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>
            <h3 style={{ margin: 0, fontSize: '1.2rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'white' }}>
              <Bot color="var(--primary)" /> AI Assistant
            </h3>
            <button onClick={() => setShowAI(false)} style={{ background: 'none', border: 'none', color: 'white' }}><X /></button>
          </div>
          <div style={{ flex: 1, padding: '1rem', overflowY: 'auto' }}>
            <SmartAssistant />
          </div>
        </div>
      ) : null}

      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
        <h2 style={{ fontSize: '1.5rem', fontWeight: 'bold', margin: 0 }}>Dashboard Hari Ini</h2>
        <button onClick={() => setShowAI(true)} style={{ background: 'linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%)', border: 'none', color: 'white', padding: '8px 16px', borderRadius: '20px', display: 'flex', alignItems: 'center', gap: '0.5rem', fontWeight: 'bold', boxShadow: '0 4px 12px rgba(139, 92, 246, 0.3)' }}>
          <Bot size={18} /> Tanya AI
        </button>
      </div>
      
      {showBranchSelector && (
        <div style={{ marginBottom: '1rem' }}>
          <label style={{ display: 'block', fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.25rem' }}>Pilih Cabang (Admin Mode)</label>
          <select 
            value={selectedBranchId} 
            onChange={(e) => setSelectedBranchId(e.target.value)}
            className="login-input"
            style={{ width: '100%', cursor: 'pointer', padding: '0.75rem', background: 'var(--bg-card)', color: 'white', border: '1px solid var(--border-light)', borderRadius: '8px' }}
          >
            <option value="" disabled>-- Pilih Cabang --</option>
            {branches.map(b => (
              <option key={b.id} value={b.id}>{b.name}</option>
            ))}
          </select>
        </div>
      )}

      {/* Primary Metrics */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', marginBottom: '1.5rem' }}>
        <div style={{ background: 'linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.05))', border: '1px solid rgba(16, 185, 129, 0.3)', padding: '1rem', borderRadius: '12px' }}>
          <div style={{ fontSize: '0.8rem', color: '#10b981', marginBottom: '0.5rem', fontWeight: 'bold' }}>OMSET HARI INI</div>
          <div style={{ fontSize: '1.3rem', fontWeight: 'bold' }}>{formatCurrency(metrics.summary.todaySales)}</div>
        </div>
        
        <div style={{ background: 'rgba(255, 255, 255, 0.03)', border: '1px solid rgba(255, 255, 255, 0.1)', padding: '1rem', borderRadius: '12px' }}>
          <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>TOTAL TRANSAKSI</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>{metrics.summary.todayTransactions} <span style={{ fontSize: '0.8rem', fontWeight: 'normal', color: 'var(--text-muted)' }}>struk</span></div>
        </div>

        <div style={{ background: 'rgba(255, 255, 255, 0.03)', border: '1px solid rgba(255, 255, 255, 0.1)', padding: '1rem', borderRadius: '12px' }}>
          <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>PROFIT MARGIN</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>{metrics.summary.profitMargin}%</div>
        </div>

        <div style={{ background: metrics.summary.lowStockCount > 0 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(255, 255, 255, 0.03)', border: metrics.summary.lowStockCount > 0 ? '1px solid rgba(239, 68, 68, 0.3)' : '1px solid rgba(255, 255, 255, 0.1)', padding: '1rem', borderRadius: '12px' }}>
          <div style={{ fontSize: '0.8rem', color: metrics.summary.lowStockCount > 0 ? '#ef4444' : 'var(--text-muted)', marginBottom: '0.5rem' }}>STOK MENIPIS</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 'bold', color: metrics.summary.lowStockCount > 0 ? '#ef4444' : 'inherit' }}>{metrics.summary.lowStockCount} <span style={{ fontSize: '0.8rem', fontWeight: 'normal' }}>item</span></div>
        </div>
      </div>

      {/* Weekly Chart (Simple Bar) */}
      <div className="glass-panel" style={{ padding: '1rem', marginBottom: '1.5rem' }}>
        <h3 style={{ fontSize: '1rem', marginBottom: '1rem', color: 'var(--text-muted)' }}>Penjualan 7 Hari Terakhir</h3>
        <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', height: '100px', gap: '4px', marginTop: '1rem' }}>
          {metrics.weeklyChart.map((day, idx) => {
            const maxSales = Math.max(...metrics.weeklyChart.map(d => d.sales), 1);
            const heightPct = (day.sales / maxSales) * 100;
            return (
              <div key={idx} style={{ flex: 1, height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'flex-end', gap: '4px' }}>
                <div style={{ width: '100%', background: 'rgba(16, 185, 129, 0.5)', borderRadius: '4px 4px 0 0', height: `${heightPct}%`, minHeight: '4px', transition: 'height 0.5s ease-out' }}></div>
                <div style={{ fontSize: '0.6rem', color: 'var(--text-muted)' }}>{day.day_name}</div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Top Products */}
      <div className="glass-panel" style={{ padding: '1rem' }}>
        <h3 style={{ fontSize: '1rem', marginBottom: '1rem', color: 'var(--text-muted)' }}>Top 5 Produk (Bulan Ini)</h3>
        {metrics.topProducts.length === 0 ? (
          <div style={{ textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.85rem' }}>Belum ada data penjualan.</div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
            {metrics.topProducts.map((prod, idx) => (
              <div key={idx} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: idx !== metrics.topProducts.length - 1 ? '1px solid rgba(255,255,255,0.05)' : 'none', paddingBottom: '0.5rem' }}>
                <div style={{ fontSize: '0.9rem', flex: 1 }}>{prod.name}</div>
                <div style={{ fontSize: '0.9rem', fontWeight: 'bold', color: '#10b981' }}>{prod.total_sold} terjual</div>
              </div>
            ))}
          </div>
        )}
      </div>

    </div>
  );
}
