import React, { useState, useEffect } from 'react';
import { ResponsiveContainer, Tooltip as RechartsTooltip, CartesianGrid } from 'recharts';
import { ArrowLeft, BrainCircuit, Activity, Link2 } from 'lucide-react';
import { SmartAssistant } from './SmartAssistant';

// Removed mock data

export const BIDashboard = ({ user, authToken, onBack }) => {
  const [heatmapData, setHeatmapData] = useState([]);
  const [aprioriRules, setAprioriRules] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        // Fetch Heatmap
        const heatmapRes = await fetch('/api/v1/bi/heatmap', {
          headers: { 'Authorization': `Bearer ${authToken}` }
        });
        if (heatmapRes.ok) {
          const hData = await heatmapRes.json();
          setHeatmapData(hData);
        }

        // Fetch Apriori
        const aprioriRes = await fetch('/api/v1/bi/apriori', {
          headers: { 'Authorization': `Bearer ${authToken}` }
        });
        if (aprioriRes.ok) {
          const aData = await aprioriRes.json();
          setAprioriRules(aData);
        }
      } catch (error) {
        console.error('Error fetching BI data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [authToken]);

  return (
    <div style={{ height: '100vh', width: '100vw', overflowY: 'auto', overflowX: 'hidden', WebkitOverflowScrolling: 'touch', padding: '1.5rem', paddingBottom: '100px', backgroundColor: 'var(--bg-main)' }}>
      <header className="pos-header glassmorphism" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem', flexShrink: 0 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <button onClick={onBack} className="btn-secondary" style={{ padding: '8px', borderRadius: '12px' }}>
            <ArrowLeft size={20} />
          </button>
          <div style={{ background: 'linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%)', padding: '8px', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <BrainCircuit size={24} color="white" />
          </div>
          <h2 style={{ fontSize: '1.5rem', fontWeight: 600 }}>
            AI & Business Intelligence
          </h2>
        </div>
      </header>

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '1.5rem', alignItems: 'stretch' }}>
        
        {/* Main BI Content */}
        <div style={{ flex: '1 1 500px', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          
          {/* Apriori Recommendations */}
          <div className="glass-panel" style={{ flex: 'none' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.5rem' }}>
              <div style={{ background: 'rgba(236, 72, 153, 0.1)', padding: '6px', borderRadius: '8px' }}>
                <Link2 size={20} color="#ec4899" />
              </div>
              <h3 style={{ fontSize: '1.1rem', fontWeight: 600, color: 'white' }}>Customer Behavior: Pola Pembelian Bersamaan</h3>
            </div>
            
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1rem' }}>
              {loading ? (
                <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>Memuat data AI...</div>
              ) : aprioriRules.length === 0 ? (
                <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>Belum cukup data transaksi untuk menemukan pola.</div>
              ) : aprioriRules.map((rule, idx) => (
                <div key={idx} style={{ background: 'rgba(255,255,255,0.02)', border: '1px dashed rgba(255,255,255,0.1)', padding: '1rem', borderRadius: '12px', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Membeli</span>
                    <span style={{ fontSize: '0.85rem', color: '#10b981', fontWeight: 600 }}>{rule.confidence} korelasi</span>
                  </div>
                  <div style={{ color: 'white', fontWeight: 500 }}>{rule.item1}</div>
                  <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Cenderung juga membeli:</div>
                  <div style={{ color: 'var(--primary)', fontWeight: 600 }}>{rule.item2}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Activity Heatmap Mock */}
          <div className="glass-panel" style={{ flex: 1, minHeight: '300px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.5rem' }}>
              <div style={{ background: 'rgba(245, 158, 11, 0.1)', padding: '6px', borderRadius: '8px' }}>
                <Activity size={20} color="#f59e0b" />
              </div>
              <h3 style={{ fontSize: '1.1rem', fontWeight: 600, color: 'white' }}>Heatmap Aktivitas Transaksi</h3>
            </div>
            <div style={{ height: 'calc(100% - 60px)', background: 'rgba(255,255,255,0.02)', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center', border: '1px solid rgba(255,255,255,0.05)' }}>
               {/* 
                  Since Recharts Heatmap is complex and often requires a plugin or custom implementation, 
                  we mock the visual of a heatmap here for demonstration of the dashboard layout.
               */}
               <div style={{ display: 'grid', gridTemplateColumns: 'repeat(12, 1fr)', gridTemplateRows: 'repeat(7, 1fr)', gap: '4px', width: '100%', padding: '1rem', height: '100%' }}>
                 {loading ? (
                   <div style={{ gridColumn: '1 / -1', gridRow: '1 / -1', display: 'flex', justifyContent: 'center', alignItems: 'center', color: 'var(--text-muted)' }}>Memuat Heatmap...</div>
                 ) : heatmapData.map((d, i) => {
                   // Calculate color intensity based on value. Max value is approx dynamically estimated
                   const maxValue = Math.max(...heatmapData.map(h => h.value), 1);
                   const intensity = d.value / maxValue;
                   
                   // Increase base visibility slightly so empty blocks are visible
                   const bgOpacity = intensity === 0 ? 0.05 : 0.2 + (intensity * 0.8);
                   
                   return (
                     <div key={i} title={`${d.day} ${d.hour} - ${d.value} trx`} style={{
                       background: `rgba(59, 130, 246, ${bgOpacity})`,
                       borderRadius: '4px',
                       width: '100%',
                       height: '100%',
                       minHeight: '20px'
                     }}></div>
                   );
                 })}
               </div>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', padding: '0 1rem', marginTop: '0.5rem', color: 'var(--text-muted)', fontSize: '0.8rem' }}>
              <span>8:00</span>
              <span>19:00</span>
            </div>
          </div>
        </div>
        
        {/* AI Assistant Sidebar */}
        <div style={{ flex: '1 1 350px', maxWidth: '100%', minHeight: '500px' }}>
          <SmartAssistant user={user} authToken={authToken} />
        </div>

      </div>
    </div>
  );
};
