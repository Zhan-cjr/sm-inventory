import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { LayoutDashboard, TrendingUp, Package, LogOut } from 'lucide-react';

export const ManagerDashboard = ({ user, authToken, onLogout }) => {
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

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

  return (
    <div className="app-container">
      <header className="pos-header glassmorphism" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <LayoutDashboard size={28} color="var(--primary)" />
          <h2 style={{ fontSize: '1.5rem', fontWeight: 600 }}>
            Manager Dashboard <span style={{ color: 'var(--text-muted)', fontSize: '1rem', fontWeight: 400 }}>| Cabang {user.branch_id.split('-')[0]}</span>
          </h2>
        </div>
        <button onClick={onLogout} style={{ background: 'transparent', border: '1px solid rgba(255,255,255,0.2)', color: 'white', padding: '8px 16px', borderRadius: '16px', fontSize: '14px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          <LogOut size={16} /> Logout ({user.name})
        </button>
      </header>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1.5rem', marginBottom: '2rem' }}>
        <div className="glass-panel" style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', color: 'var(--text-muted)' }}>
            <span style={{ fontWeight: 500 }}>Total Penjualan (Hari Ini)</span>
            <TrendingUp size={20} color="#10b981" />
          </div>
          <div style={{ fontSize: '2.5rem', fontWeight: 700, color: 'white' }}>
            Rp {metrics.summary.todaySales.toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>
            Dari {metrics.summary.todayTransactions} transaksi selesai
          </div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '1.5rem' }}>
        <div className="glass-panel" style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <h3 style={{ fontSize: '1.2rem', fontWeight: 500, color: 'var(--text-muted)' }}>Grafik Transaksi (7 Hari Terakhir)</h3>
          <div style={{ width: '100%', height: 300 }}>
            <ResponsiveContainer>
              <BarChart data={metrics.weeklyChart.reverse()} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.1)" vertical={false} />
                <XAxis dataKey="day_name" stroke="var(--text-muted)" axisLine={false} tickLine={false} />
                <YAxis stroke="var(--text-muted)" axisLine={false} tickLine={false} tickFormatter={value => `Rp${value/1000}k`} />
                <Tooltip 
                  cursor={{fill: 'rgba(255,255,255,0.05)'}} 
                  contentStyle={{ backgroundColor: 'var(--bg-dark)', border: '1px solid var(--border-light)', borderRadius: '8px' }}
                  formatter={(value) => [`Rp ${value.toLocaleString('id-ID')}`, 'Penjualan']}
                />
                <Bar dataKey="sales" fill="var(--primary)" radius={[4, 4, 0, 0]} maxBarSize={40} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="glass-panel" style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--text-muted)' }}>
            <Package size={20} />
            <h3 style={{ fontSize: '1.2rem', fontWeight: 500 }}>Produk Terlaris (Bulan Ini)</h3>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '0.5rem' }}>
            {metrics.topProducts.map((p, i) => (
              <div key={i} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '0.5rem 0', borderBottom: i < 4 ? '1px solid rgba(255,255,255,0.05)' : 'none' }}>
                <div>
                  <div style={{ color: 'white', fontWeight: 500 }}>{p.name}</div>
                  <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Peringkat #{i+1}</div>
                </div>
                <div style={{ background: 'rgba(59, 130, 246, 0.1)', color: 'var(--primary)', padding: '0.25rem 0.75rem', borderRadius: '12px', fontSize: '0.9rem', fontWeight: 600 }}>
                  {p.total_sold} terjual
                </div>
              </div>
            ))}
            {metrics.topProducts.length === 0 && (
              <div style={{ textAlign: 'center', color: 'var(--text-muted)', marginTop: '2rem' }}>Belum ada data penjualan.</div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
