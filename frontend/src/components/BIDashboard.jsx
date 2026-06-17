import React, { useState, useEffect } from 'react';
import { ResponsiveContainer, Heatmap, Tooltip as RechartsTooltip, CartesianGrid } from 'recharts';
import { ArrowLeft, BrainCircuit, Activity, Link2 } from 'lucide-react';
import { SmartAssistant } from './SmartAssistant';

// Mock data for heatmap: 7 days, 12 hours (8am - 8pm)
const generateHeatmapData = () => {
  const days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
  const data = [];
  days.forEach((day, dIdx) => {
    for (let h = 8; h <= 19; h++) {
      data.push({
        day,
        hour: `${h}:00`,
        dayIndex: dIdx,
        hourIndex: h,
        value: Math.floor(Math.random() * 100) + (dIdx >= 5 ? 50 : 0) // Weekend is busier
      });
    }
  });
  return data;
};

// Mock apriori rules
const mockRules = [
  { item1: 'Popok Bayi Merries', item2: 'Susu Formula Bebelac', confidence: '85%' },
  { item1: 'Roti Tawar Sariroti', item2: 'Selai Kacang Skippy', confidence: '78%' },
  { item1: 'Kopi Kapal Api', item2: 'Gula Pasir Gulaku', confidence: '92%' },
  { item1: 'Mie Instan Indomie', item2: 'Telur Ayam 1kg', confidence: '75%' }
];

export const BIDashboard = ({ user, authToken, onBack }) => {
  const [heatmapData, setHeatmapData] = useState([]);

  useEffect(() => {
    // In real implementation, fetch from Python Service or Laravel backend
    setHeatmapData(generateHeatmapData());
  }, []);

  return (
    <div className="app-container">
      <header className="pos-header glassmorphism" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
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

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 350px', gap: '1.5rem', alignItems: 'stretch' }}>
        
        {/* Main BI Content */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          
          {/* Apriori Recommendations */}
          <div className="glass-panel" style={{ flex: 'none' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.5rem' }}>
              <div style={{ background: 'rgba(236, 72, 153, 0.1)', padding: '6px', borderRadius: '8px' }}>
                <Link2 size={20} color="#ec4899" />
              </div>
              <h3 style={{ fontSize: '1.1rem', fontWeight: 600, color: 'white' }}>Customer Behavior: Pola Pembelian Bersamaan</h3>
            </div>
            
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1rem' }}>
              {mockRules.map((rule, idx) => (
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
                 {heatmapData.map((d, i) => {
                   // Calculate color intensity based on value
                   const intensity = d.value / 150; 
                   return (
                     <div key={i} title={`${d.day} ${d.hour} - ${d.value} trx`} style={{
                       background: `rgba(59, 130, 246, ${intensity})`,
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
        <div style={{ height: '100%' }}>
          <SmartAssistant />
        </div>

      </div>
    </div>
  );
};
