import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  Bot, 
  X, 
  TrendingUp, 
  TrendingDown,
  Receipt, 
  Percent, 
  AlertTriangle, 
  QrCode, 
  ShieldCheck, 
  Sparkles, 
  ChevronRight,
  Store,
  ArrowUpRight,
  Package
} from 'lucide-react';
import { SmartAssistant } from '../SmartAssistant';

export function MobileDashboard({ user, authToken }) {
  const navigate = useNavigate();
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
    if (showBranchSelector && !selectedBranchId) return;

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

  const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);

  if (loading) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center', alignItems: 'center', minHeight: '60vh', gap: '1rem' }}>
        <div className="spin" style={{ width: '36px', height: '36px', border: '3px solid #10b981', borderTopColor: 'transparent', borderRadius: '50%' }}></div>
        <div style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>Memuat ringkasan bisnis...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="pwa-card" style={{ padding: '1.5rem', textAlign: 'center', marginTop: '2rem' }}>
        <AlertTriangle size={40} color="#ef4444" style={{ margin: '0 auto 1rem' }} />
        <h3 style={{ fontSize: '1.1rem', marginBottom: '0.5rem', color: '#ef4444' }}>Gagal Memuat Dashboard</h3>
        <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>{error}</p>
        <p style={{ fontSize: '0.75rem', marginTop: '0.75rem', color: 'var(--text-muted)' }}>Pastikan Anda memiliki izin akses Supervisi/Manager.</p>
      </div>
    );
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem', animation: 'fadeIn 0.3s ease-out' }}>
      
      {/* Slide-up AI Sheet */}
      {showAI && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 9999, background: 'var(--bg-dark)', display: 'flex', flexDirection: 'column' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1rem 1.25rem', background: 'var(--bg-card)', borderBottom: '1px solid var(--border-light)' }}>
            <h3 style={{ margin: 0, fontSize: '1.1rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--text-main)' }}>
              <Bot color="#10b981" size={22} /> AI Assistant SM Inventory
            </h3>
            <button 
              onClick={() => setShowAI(false)} 
              style={{ background: 'rgba(255,255,255,0.1)', border: 'none', color: 'var(--text-main)', width: 34, height: 34, borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}
            >
              <X size={20} />
            </button>
          </div>
          <div style={{ flex: 1, padding: '1rem', overflowY: 'auto' }}>
            <SmartAssistant user={user} authToken={authToken} />
          </div>
        </div>
      )}

      {/* Header Bar & Admin Branch Switcher */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.5px', fontWeight: 600 }}>
            Ringkasan Hari Ini
          </div>
          <h2 style={{ fontSize: '1.4rem', fontWeight: 800, margin: 0, color: 'var(--text-main)' }}>
            Dashboard Kasir
          </h2>
        </div>

        {/* AI Assistant Quick Trigger */}
        <button 
          onClick={() => setShowAI(true)} 
          style={{ 
            background: 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)', 
            border: 'none', 
            color: 'white', 
            padding: '10px 16px', 
            borderRadius: '16px', 
            display: 'flex', 
            alignItems: 'center', 
            gap: '0.5rem', 
            fontWeight: 700, 
            fontSize: '0.85rem',
            boxShadow: '0 4px 16px rgba(99, 102, 241, 0.4)',
            cursor: 'pointer'
          }}
        >
          <Bot size={18} /> Tanya AI
        </button>
      </div>
      
      {/* Branch Selector Dropdown */}
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

      {/* Hero Omset Performance Card */}
      <div 
        style={{ 
          background: 'linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.1) 100%)', 
          border: '1px solid rgba(16, 185, 129, 0.35)', 
          padding: '1.25rem', 
          borderRadius: '24px',
          position: 'relative',
          overflow: 'hidden',
          boxShadow: '0 10px 30px rgba(16, 185, 129, 0.15)'
        }}
      >
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '0.75rem' }}>
          <div>
            <div style={{ fontSize: '0.75rem', color: '#10b981', fontWeight: 800, letterSpacing: '0.5px' }}>
              PENJUALAN (OMSET) HARI INI
            </div>
            <div style={{ fontSize: '1.8rem', fontWeight: 900, color: 'var(--text-main)', marginTop: '4px', letterSpacing: '-0.5px' }}>
              {formatCurrency(metrics?.summary?.todaySales)}
            </div>
          </div>
          <div style={{ background: 'rgba(16, 185, 129, 0.2)', padding: '10px', borderRadius: '16px', color: '#10b981' }}>
            <TrendingUp size={24} />
          </div>
        </div>

        {/* Comparative Sales Breakdown (Versus) */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '0.5rem', marginTop: '0.85rem', paddingTop: '0.85rem', borderTop: '1px solid rgba(16, 185, 129, 0.25)' }}>
          {/* vs Kemarin */}
          <div style={{ background: 'rgba(255,255,255,0.05)', padding: '0.6rem 0.4rem', borderRadius: '12px', textAlign: 'center' }}>
            <div style={{ fontSize: '0.65rem', color: 'var(--text-muted)', fontWeight: 600 }}>vs Kemarin</div>
            <div style={{ fontSize: '0.78rem', fontWeight: 800, marginTop: '2px', color: 'var(--text-main)' }}>
              {formatCurrency(metrics?.summary?.yesterdaySales || 0)}
            </div>
            {(() => {
              const today = metrics?.summary?.todaySales || 0;
              const prev = metrics?.summary?.yesterdaySales || 0;
              const diff = today - prev;
              const isUp = diff >= 0;
              return (
                <div style={{ fontSize: '0.65rem', fontWeight: 700, color: isUp ? '#10b981' : '#ef4444', marginTop: '2px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '2px' }}>
                  {isUp ? <TrendingUp size={10} /> : <TrendingDown size={10} />}
                  {prev > 0 ? `${isUp ? '+' : ''}${((diff / prev) * 100).toFixed(0)}%` : (today > 0 ? '+100%' : '0%')}
                </div>
              );
            })()}
          </div>

          {/* vs Minggu Lalu (Hari Sama) */}
          <div style={{ background: 'rgba(255,255,255,0.05)', padding: '0.6rem 0.4rem', borderRadius: '12px', textAlign: 'center' }}>
            <div style={{ fontSize: '0.65rem', color: 'var(--text-muted)', fontWeight: 600 }}>vs Ming. Lalu</div>
            <div style={{ fontSize: '0.78rem', fontWeight: 800, marginTop: '2px', color: 'var(--text-main)' }}>
              {formatCurrency(metrics?.summary?.sameDayLastWeekSales || 0)}
            </div>
            {(() => {
              const today = metrics?.summary?.todaySales || 0;
              const prev = metrics?.summary?.sameDayLastWeekSales || 0;
              const diff = today - prev;
              const isUp = diff >= 0;
              return (
                <div style={{ fontSize: '0.65rem', fontWeight: 700, color: isUp ? '#10b981' : '#ef4444', marginTop: '2px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '2px' }}>
                  {isUp ? <TrendingUp size={10} /> : <TrendingDown size={10} />}
                  {prev > 0 ? `${isUp ? '+' : ''}${((diff / prev) * 100).toFixed(0)}%` : (today > 0 ? '+100%' : '0%')}
                </div>
              );
            })()}
          </div>

          {/* vs Bulan Lalu (Tgl Sama) */}
          <div style={{ background: 'rgba(255,255,255,0.05)', padding: '0.6rem 0.4rem', borderRadius: '12px', textAlign: 'center' }}>
            <div style={{ fontSize: '0.65rem', color: 'var(--text-muted)', fontWeight: 600 }}>vs Bul. Lalu</div>
            <div style={{ fontSize: '0.78rem', fontWeight: 800, marginTop: '2px', color: 'var(--text-main)' }}>
              {formatCurrency(metrics?.summary?.sameDateLastMonthSales || 0)}
            </div>
            {(() => {
              const today = metrics?.summary?.todaySales || 0;
              const prev = metrics?.summary?.sameDateLastMonthSales || 0;
              const diff = today - prev;
              const isUp = diff >= 0;
              return (
                <div style={{ fontSize: '0.65rem', fontWeight: 700, color: isUp ? '#10b981' : '#ef4444', marginTop: '2px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '2px' }}>
                  {isUp ? <TrendingUp size={10} /> : <TrendingDown size={10} />}
                  {prev > 0 ? `${isUp ? '+' : ''}${((diff / prev) * 100).toFixed(0)}%` : (today > 0 ? '+100%' : '0%')}
                </div>
              );
            })()}
          </div>
        </div>
      </div>

      {/* Metrics 3-Card Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '0.65rem' }}>
        {/* Total Struk */}
        <div className="pwa-card" style={{ padding: '0.9rem', textAlign: 'center' }}>
          <Receipt size={20} color="#6366f1" style={{ margin: '0 auto 4px' }} />
          <div style={{ fontSize: '0.68rem', color: 'var(--text-muted)', fontWeight: 600 }}>TRANSAKSI</div>
          <div style={{ fontSize: '1.2rem', fontWeight: 800, marginTop: '2px' }}>
            {metrics?.summary?.todayTransactions || 0}
          </div>
        </div>

        {/* Margin */}
        <div className="pwa-card" style={{ padding: '0.9rem', textAlign: 'center' }}>
          <Percent size={20} color="#f59e0b" style={{ margin: '0 auto 4px' }} />
          <div style={{ fontSize: '0.68rem', color: 'var(--text-muted)', fontWeight: 600 }}>MARGIN</div>
          <div style={{ fontSize: '1.2rem', fontWeight: 800, marginTop: '2px' }}>
            {metrics?.summary?.profitMargin || 0}%
          </div>
        </div>

        {/* Low Stock Alert Card */}
        <div 
          className="pwa-card" 
          style={{ 
            padding: '0.9rem', 
            textAlign: 'center',
            background: (metrics?.summary?.lowStockCount || 0) > 0 ? 'rgba(239, 68, 68, 0.12)' : 'var(--bg-card)',
            borderColor: (metrics?.summary?.lowStockCount || 0) > 0 ? 'rgba(239, 68, 68, 0.3)' : 'var(--border-light)'
          }}
        >
          <AlertTriangle size={20} color={(metrics?.summary?.lowStockCount || 0) > 0 ? "#ef4444" : "#10b981"} style={{ margin: '0 auto 4px' }} />
          <div style={{ fontSize: '0.68rem', color: (metrics?.summary?.lowStockCount || 0) > 0 ? "#ef4444" : "var(--text-muted)", fontWeight: 600 }}>STOK KRITIS</div>
          <div style={{ fontSize: '1.2rem', fontWeight: 800, marginTop: '2px', color: (metrics?.summary?.lowStockCount || 0) > 0 ? "#ef4444" : "inherit" }}>
            {metrics?.summary?.lowStockCount || 0}
          </div>
        </div>
      </div>

      {/* Quick Action Grid Launcher */}
      <div>
        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', fontWeight: 700, marginBottom: '0.6rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
          Aksi Cepat
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' }}>
          <button 
            onClick={() => navigate('/mobile/scanner')} 
            className="pwa-card"
            style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', border: 'none', cursor: 'pointer', textAlign: 'left' }}
          >
            <div style={{ background: 'rgba(16, 185, 129, 0.15)', padding: '10px', borderRadius: '14px', color: '#10b981' }}>
              <QrCode size={20} />
            </div>
            <div>
              <div style={{ fontWeight: 700, fontSize: '0.88rem', color: 'var(--text-main)' }}>Cek Barang</div>
              <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Scan Barcode & Stok</div>
            </div>
          </button>

          <button 
            onClick={() => navigate('/mobile/auth')} 
            className="pwa-card"
            style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', border: 'none', cursor: 'pointer', textAlign: 'left' }}
          >
            <div style={{ background: 'rgba(99, 102, 241, 0.15)', padding: '10px', borderRadius: '14px', color: '#6366f1' }}>
              <ShieldCheck size={20} />
            </div>
            <div>
              <div style={{ fontWeight: 700, fontSize: '0.88rem', color: 'var(--text-main)' }}>Otorisasi</div>
              <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Approve Supervisi</div>
            </div>
          </button>
        </div>
      </div>

      {/* 7-Day Sales Bar Chart */}
      <div className="pwa-card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
          <h3 style={{ fontSize: '0.95rem', fontWeight: 700, margin: 0, color: 'var(--text-main)' }}>
            Penjualan 7 Hari Terakhir
          </h3>
          <span style={{ fontSize: '0.7rem', color: '#10b981', fontWeight: 600, background: 'rgba(16, 185, 129, 0.1)', padding: '2px 8px', borderRadius: '99px' }}>
            Tren Positif
          </span>
        </div>

        {metrics?.weeklyChart && metrics.weeklyChart.length > 0 ? (
          <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', height: '110px', gap: '6px', paddingTop: '10px' }}>
            {metrics.weeklyChart.map((day, idx) => {
              const maxSales = Math.max(...metrics.weeklyChart.map(d => d.sales), 1);
              const heightPct = Math.max((day.sales / maxSales) * 100, 6);
              const isHighest = day.sales === maxSales && maxSales > 0;
              return (
                <div key={idx} style={{ flex: 1, height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'flex-end', gap: '6px' }}>
                  <div 
                    title={`${day.day_name}: ${formatCurrency(day.sales)}`}
                    style={{ 
                      width: '100%', 
                      background: isHighest ? 'linear-gradient(180deg, #10b981 0%, #059669 100%)' : 'rgba(16, 185, 129, 0.3)', 
                      borderRadius: '6px 6px 0 0', 
                      height: `${heightPct}%`, 
                      transition: 'height 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                      boxShadow: isHighest ? '0 0 12px rgba(16, 185, 129, 0.4)' : 'none'
                    }}
                  ></div>
                  <div style={{ fontSize: '0.65rem', color: isHighest ? '#10b981' : 'var(--text-muted)', fontWeight: isHighest ? 800 : 500 }}>
                    {day.day_name}
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div style={{ textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.85rem', padding: '1rem' }}>
            Belum ada grafik penjualan.
          </div>
        )}
      </div>

      {/* Top 5 Products List */}
      <div className="pwa-card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.85rem' }}>
          <h3 style={{ fontSize: '0.95rem', fontWeight: 700, margin: 0, color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <Package size={18} color="#6366f1" /> Top 5 Produk Terlaris
          </h3>
          <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Bulan Ini</span>
        </div>

        {(!metrics?.topProducts || metrics.topProducts.length === 0) ? (
          <div style={{ textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.85rem', padding: '1rem' }}>
            Belum ada data produk terlaris.
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
            {metrics.topProducts.map((prod, idx) => (
              <div 
                key={idx} 
                style={{ 
                  display: 'flex', 
                  justify: 'space-between', 
                  alignItems: 'center', 
                  borderBottom: idx !== metrics.topProducts.length - 1 ? '1px solid var(--border-light)' : 'none', 
                  paddingBottom: '0.65rem' 
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', flex: 1, minWidth: 0 }}>
                  <div style={{ background: 'rgba(99, 102, 241, 0.12)', color: '#6366f1', width: '24px', height: '24px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.75rem', fontWeight: 800 }}>
                    {idx + 1}
                  </div>
                  <div style={{ fontSize: '0.85rem', fontWeight: 600, color: 'var(--text-main)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                    {prod.name}
                  </div>
                </div>
                <div style={{ fontSize: '0.85rem', fontWeight: 700, color: '#10b981', marginLeft: '0.5rem' }}>
                  {prod.total_sold} <span style={{ fontSize: '0.7rem', fontWeight: 500, color: 'var(--text-muted)' }}>terjual</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

    </div>
  );
}
