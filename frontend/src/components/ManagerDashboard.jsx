import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import { LayoutDashboard, TrendingUp, Package, LogOut, LineChart, AlertTriangle, DollarSign, PieChart, ArrowLeft } from 'lucide-react';
import { SuggestedOrders } from './SuggestedOrders';
import { LowStockList } from './LowStockList';

export const ManagerDashboard = ({ user, authToken, onLogout }) => {
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeView, setActiveView] = useState('DASHBOARD');

  useEffect(() => {
    fetch('/api/v1/dashboard/metrics', {
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    })
    .then(res => {
      if (!res.ok) throw new Error('Gagal mengambil data analitik');
      return res.json();
    })
    .then(data => {
      setMetrics(data);
      setLoading(false);
    })
    .catch(err => {
      setError(err.message);
      setLoading(false);
    });
  }, [authToken]);

  if (loading) return <div className="app-container" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}><div className="spinner"></div></div>;
  if (error) return <div className="app-container"><div className="alert-msg slide-down" style={{position:'static', margin:'2rem auto', maxWidth: 400}}>{error}</div></div>;

  if (activeView === 'FORECASTING') {
    return <SuggestedOrders user={user} authToken={authToken} onBack={() => setActiveView('DASHBOARD')} />;
  }

  if (activeView === 'LOW_STOCK') {
    return <LowStockList user={user} authToken={authToken} onBack={() => setActiveView('DASHBOARD')} />;
  }

  return (
    <div className="app-container">
      <header className="pos-header glassmorphism" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <div style={{ background: 'var(--primary)', padding: '8px', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <LayoutDashboard size={24} color="white" />
          </div>
          <h2 style={{ fontSize: '1.5rem', fontWeight: 600 }}>
            Manager Dashboard <span style={{ color: 'var(--text-muted)', fontSize: '1rem', fontWeight: 400 }}>| Cabang {user.branch_id.split('-')[0]}</span>
          </h2>
        </div>
        <div style={{ display: 'flex', gap: '1rem' }}>
           <button onClick={() => setActiveView('FORECASTING')} className="btn-secondary" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', padding: '10px 20px' }}>
             <LineChart size={18} /> Forecasting
           </button>
           <button onClick={onLogout} style={{ background: 'rgba(239, 68, 68, 0.1)', border: '1px solid rgba(239, 68, 68, 0.2)', color: '#ef4444', padding: '10px 20px', borderRadius: '16px', fontSize: '14px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '0.5rem', fontWeight: 500 }}>
             <LogOut size={16} /> Logout
           </button>
        </div>
      </header>

      {/* Primary Metrics */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '1.5rem', marginBottom: '2rem' }}>
        
        {/* Sales Card */}
        <div className="glass-panel" style={{ position: 'relative', overflow: 'hidden' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>
            <span style={{ fontWeight: 500, fontSize: '0.9rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Omzet Hari Ini</span>
            <div style={{ background: 'rgba(16, 185, 129, 0.1)', padding: '6px', borderRadius: '8px' }}>
                <TrendingUp size={18} color="#10b981" />
            </div>
          </div>
          <div style={{ fontSize: '2.2rem', fontWeight: 700, color: 'white', marginBottom: '0.25rem' }}>
            Rp {metrics.summary.todaySales.toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>
            {metrics.summary.todayTransactions} transaksi selesai
          </div>
        </div>

        {/* Profit Card */}
        <div className="glass-panel">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>
            <span style={{ fontWeight: 500, fontSize: '0.9rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Laba Kotor</span>
            <div style={{ background: 'rgba(59, 130, 246, 0.1)', padding: '6px', borderRadius: '8px' }}>
                <DollarSign size={18} color="var(--primary)" />
            </div>
          </div>
          <div style={{ fontSize: '2.2rem', fontWeight: 700, color: 'white', marginBottom: '0.25rem' }}>
            Rp {metrics.summary.grossProfit.toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            Margin <span style={{ color: 'var(--primary)', fontWeight: 600 }}>{metrics.summary.profitMargin}%</span>
          </div>
        </div>

        {/* Stock Alert Card */}
        <div className="glass-panel" style={{ cursor: 'pointer', border: metrics.summary.lowStockCount > 0 ? '1px solid rgba(245, 158, 11, 0.3)' : '1px solid var(--border-light)' }} onClick={() => setActiveView('LOW_STOCK')}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>
            <span style={{ fontWeight: 500, fontSize: '0.9rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Kesehatan Stok</span>
            <div style={{ background: metrics.summary.lowStockCount > 0 ? 'rgba(245, 158, 11, 0.1)' : 'rgba(16, 185, 129, 0.1)', padding: '6px', borderRadius: '8px' }}>
                <AlertTriangle size={18} color={metrics.summary.lowStockCount > 0 ? '#f59e0b' : '#10b981'} />
            </div>
          </div>
          <div style={{ fontSize: '2.2rem', fontWeight: 700, color: metrics.summary.lowStockCount > 0 ? '#f59e0b' : 'white', marginBottom: '0.25rem' }}>
            {metrics.summary.lowStockCount} <span style={{ fontSize: '1rem', fontWeight: 400, color: 'var(--text-muted)' }}>Item Menipis</span>
          </div>
          <div style={{ fontSize: '0.85rem', color: 'var(--primary)', fontWeight: 500 }}>
             Lihat detail &rarr;
          </div>
        </div>

      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1.6fr 1fr', gap: '1.5rem' }}>
        {/* Sales Chart */}
        <div className="glass-panel" style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <h3 style={{ fontSize: '1.1rem', fontWeight: 600, color: 'white' }}>Trend Penjualan (7 Hari Terakhir)</h3>
            <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)', background: 'rgba(255,255,255,0.05)', padding: '4px 12px', borderRadius: '12px' }}>Weekly View</div>
          </div>
          <div style={{ width: '100%', height: 350 }}>
            <ResponsiveContainer>
              <BarChart data={metrics.weeklyChart} margin={{ top: 10, right: 10, left: -10, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" vertical={false} />
                <XAxis dataKey="day_name" stroke="var(--text-muted)" axisLine={false} tickLine={false} style={{ fontSize: '12px' }} />
                <YAxis stroke="var(--text-muted)" axisLine={false} tickLine={false} tickFormatter={value => `Rp${value/1000}k`} style={{ fontSize: '12px' }} />
                <Tooltip 
                  cursor={{fill: 'rgba(255,255,255,0.05)'}} 
                  contentStyle={{ backgroundColor: 'rgba(15, 23, 42, 0.9)', border: '1px solid rgba(255,255,255,0.1)', borderRadius: '12px', backdropFilter: 'blur(8px)' }}
                  itemStyle={{ color: 'var(--primary)' }}
                  formatter={(value) => [`Rp ${value.toLocaleString('id-ID')}`, 'Penjualan']}
                />
                <Bar dataKey="sales" radius={[6, 6, 0, 0]} maxBarSize={45}>
                  {metrics.weeklyChart.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={index === metrics.weeklyChart.length - 1 ? 'var(--primary)' : 'rgba(59, 130, 246, 0.4)'} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Top Products */}
        <div className="glass-panel" style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
            <div style={{ background: 'rgba(236, 72, 153, 0.1)', padding: '6px', borderRadius: '8px' }}>
                <PieChart size={18} color="#ec4899" />
            </div>
            <h3 style={{ fontSize: '1.1rem', fontWeight: 600, color: 'white' }}>Produk Terlaris</h3>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
            {metrics.topProducts.map((p, i) => (
              <div key={i} className="glassmorphism" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1rem', borderRadius: '12px', background: 'rgba(255,255,255,0.02)' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                  <div style={{ width: '32px', height: '32px', borderRadius: '50%', background: 'rgba(255,255,255,0.05)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.85rem', color: 'var(--text-muted)', fontWeight: 600 }}>
                    {i+1}
                  </div>
                  <div>
                    <div style={{ color: 'white', fontWeight: 500, fontSize: '0.95rem' }}>{p.name}</div>
                    <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Bulan ini</div>
                  </div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div style={{ color: 'var(--primary)', fontWeight: 700, fontSize: '1rem' }}>{p.total_sold}</div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>PCS</div>
                </div>
              </div>
            ))}
            {metrics.topProducts.length === 0 && (
              <div style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '3rem 0' }}>
                <Package size={40} style={{ opacity: 0.2, marginBottom: '1rem' }} />
                <div>Belum ada data penjualan.</div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
