import React, { useState, useEffect } from 'react';
import { ArrowLeft, BrainCircuit, Activity, Link2, Store } from 'lucide-react';
import { SmartAssistant } from './SmartAssistant';

export const BIDashboard = ({ user, authToken, onBack }) => {
  const [heatmapData, setHeatmapData] = useState([]);
  const [aprioriRules, setAprioriRules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedCell, setSelectedCell] = useState(null);
  const [branches, setBranches] = useState([]);
  const [selectedBranch, setSelectedBranch] = useState('');

  const isAdmin = ['ADMIN', 'SUPER_ADMIN'].includes(user?.role?.toUpperCase());
  const showBranchSelector = !user?.branch_id || isAdmin;

  useEffect(() => {
    // Fetch branches if admin
    if (showBranchSelector) {
      fetch('/api/v1/branches', { headers: { 'Authorization': `Bearer ${authToken}` } })
        .then(res => res.json())
        .then(data => {
          if (Array.isArray(data)) setBranches(data);
        })
        .catch(err => console.error('Error fetching branches:', err));
    }
  }, [authToken, showBranchSelector]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const urlParams = new URLSearchParams();
        if (selectedBranch) {
          urlParams.append('branch_id', selectedBranch);
        }

        // Fetch Heatmap
        const heatmapRes = await fetch('/api/v1/bi/heatmap?' + urlParams.toString(), {
          headers: { 
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
          }
        });
        if (heatmapRes.ok) {
          const hData = await heatmapRes.json();
          setHeatmapData(hData);
        } else {
          setHeatmapData([]); // Fallback
        }

        // Fetch Apriori
        const aprioriRes = await fetch('/api/v1/bi/apriori?' + urlParams.toString(), {
          headers: { 
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
          }
        });
        if (aprioriRes.ok) {
          const aData = await aprioriRes.json();
          setAprioriRules(aData);
        } else {
          setAprioriRules([]); // Fallback
        }
      } catch (error) {
        console.error('Error fetching BI data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [authToken, selectedBranch]);

  return (
    <div style={{ height: '100dvh', width: '100vw', overflowY: 'auto', overflowX: 'hidden', WebkitOverflowScrolling: 'touch', padding: '1rem', paddingTop: 'max(1rem, env(safe-area-inset-top, 20px))', paddingBottom: '100px', backgroundColor: 'var(--bg-dark)', color: 'var(--text-main)' }}>
      {/* Header */}
      <header className="pwa-card" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem', padding: '0.85rem 1.25rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <button 
            onClick={onBack} 
            style={{ padding: '8px 12px', borderRadius: '12px', background: 'rgba(255,255,255,0.08)', border: '1px solid var(--border-light)', color: 'var(--text-main)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
          >
            <ArrowLeft size={20} />
          </button>
          <div style={{ background: 'linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%)', padding: '8px', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, color: 'white' }}>
            <BrainCircuit size={24} />
          </div>
          <h2 style={{ fontSize: 'clamp(1.1rem, 4vw, 1.4rem)', fontWeight: 800, lineHeight: 1.2, margin: 0, color: 'var(--text-main)' }}>
            AI & Business Intelligence
          </h2>
        </div>
      </header>

      {/* Branch Switcher */}
      {showBranchSelector && branches.length > 0 && (
        <div style={{ background: 'var(--bg-card)', border: '1px solid var(--border-light)', padding: '0.75rem 1rem', borderRadius: '16px', display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.25rem' }}>
          <Store size={18} color="#8b5cf6" />
          <div style={{ flex: 1 }}>
            <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Cabang Analisis</div>
            <select 
              value={selectedBranch} 
              onChange={(e) => setSelectedBranch(e.target.value)}
              style={{ 
                width: '100%', 
                cursor: 'pointer', 
                border: 'none', 
                background: 'transparent', 
                color: 'var(--text-main)', 
                fontWeight: 700, 
                fontSize: '0.9rem', 
                outline: 'none'
              }}
            >
              <option value="" style={{ background: 'var(--bg-dark)', color: 'var(--text-main)' }}>Semua Cabang (Gabungan)</option>
              {branches.map(b => (
                <option key={b.id} value={b.id} style={{ background: 'var(--bg-dark)', color: 'var(--text-main)' }}>
                  {b.code} - {b.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      )}

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '1.25rem', alignItems: 'stretch' }}>
        
        {/* Main BI Content */}
        <div style={{ flex: '1 1 500px', display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
          
          {/* Apriori Recommendations */}
          <div className="pwa-card" style={{ flex: 'none' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.25rem' }}>
              <div style={{ background: 'rgba(236, 72, 153, 0.15)', padding: '8px', borderRadius: '12px' }}>
                <Link2 size={20} color="#ec4899" />
              </div>
              <h3 style={{ fontSize: '1.1rem', fontWeight: 800, color: 'var(--text-main)', margin: 0 }}>Customer Behavior: Pola Pembelian Bersamaan</h3>
            </div>
            
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '0.85rem' }}>
              {loading ? (
                <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>Memuat data AI...</div>
              ) : aprioriRules.length === 0 ? (
                <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>Belum cukup data transaksi untuk menemukan pola.</div>
              ) : aprioriRules.map((rule, idx) => (
                <div key={idx} style={{ background: 'rgba(255,255,255,0.04)', border: '1px dashed var(--border-light)', padding: '1rem', borderRadius: '14px', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>Membeli</span>
                    <span style={{ fontSize: '0.82rem', color: '#10b981', fontWeight: 700 }}>{rule.confidence} korelasi</span>
                  </div>
                  <div style={{ color: 'var(--text-main)', fontWeight: 700, fontSize: '0.95rem' }}>{rule.item1}</div>
                  <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)' }}>Cenderung juga membeli:</div>
                  <div style={{ color: '#38bdf8', fontWeight: 800, fontSize: '0.95rem' }}>{rule.item2}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Activity Heatmap */}
          <div className="pwa-card" style={{ flex: 1, minHeight: '300px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.25rem' }}>
              <div style={{ background: 'rgba(245, 158, 11, 0.15)', padding: '8px', borderRadius: '12px' }}>
                <Activity size={20} color="#f59e0b" />
              </div>
              <h3 style={{ fontSize: '1.1rem', fontWeight: 800, color: 'var(--text-main)', margin: 0 }}>Heatmap Aktivitas Transaksi</h3>
            </div>
            <div style={{ height: 'auto', background: 'rgba(255,255,255,0.03)', borderRadius: '14px', display: 'flex', flexDirection: 'column', border: '1px solid var(--border-light)', padding: '1rem' }}>
               
               {/* Label Jam (X-Axis) */}
               <div style={{ display: 'grid', gridTemplateColumns: '40px repeat(12, 1fr)', gap: '4px', marginBottom: '8px' }}>
                  <div></div> {/* Spacer */}
                  {[8,9,10,11,12,13,14,15,16,17,18,19].map(h => (
                    <div key={h} style={{ fontSize: '0.65rem', color: 'var(--text-muted)', textAlign: 'center', alignSelf: 'end', fontWeight: 600 }}>
                      {h}
                    </div>
                  ))}
               </div>

               {/* Grid Utama (Y-Axis + Heatmap) */}
               <div style={{ display: 'grid', gridTemplateColumns: '40px repeat(12, 1fr)', gridTemplateRows: 'repeat(7, 1fr)', gap: '4px', width: '100%' }}>
                 {loading ? (
                   <div style={{ gridColumn: '1 / -1', padding: '2rem', display: 'flex', justifyContent: 'center', alignItems: 'center', color: 'var(--text-muted)' }}>Memuat Heatmap...</div>
                 ) : (
                   ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].map((dayName, dIdx) => (
                     <React.Fragment key={`row-${dIdx}`}>
                       {/* Y-Axis Label */}
                       <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', justifyContent: 'flex-end', paddingRight: '8px', fontWeight: 600 }}>
                         {dayName}
                       </div>
                       
                       {/* Sel-sel Heatmap */}
                       {[8,9,10,11,12,13,14,15,16,17,18,19].map(hIdx => {
                         const cellData = heatmapData.find(d => d.dayIndex === dIdx && d.hourIndex === hIdx) || { value: 0, day: dayName, hour: `${hIdx}:00` };
                         const maxValue = Math.max(...heatmapData.map(h => h.value), 1);
                         const intensity = cellData.value / maxValue;
                         const bgOpacity = intensity === 0 ? 0.05 : 0.2 + (intensity * 0.8);
                         
                         return (
                           <div 
                             key={`cell-${dIdx}-${hIdx}`} 
                             onClick={() => setSelectedCell(cellData)}
                             style={{
                               background: `rgba(59, 130, 246, ${bgOpacity})`,
                               borderRadius: '4px',
                               width: '100%',
                               height: '24px',
                               cursor: 'pointer',
                               border: selectedCell && selectedCell.dayIndex === dIdx && selectedCell.hourIndex === hIdx ? '1px solid var(--text-main)' : 'none'
                             }}
                           ></div>
                         );
                       })}
                     </React.Fragment>
                   ))
                 )}
               </div>

               {/* Info Sel Terpilih */}
               <div style={{ marginTop: '1rem', padding: '0.85rem', background: 'rgba(59, 130, 246, 0.1)', borderRadius: '12px', border: '1px solid rgba(59, 130, 246, 0.25)', minHeight: '56px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                 {selectedCell ? (
                   <div style={{ textAlign: 'center' }}>
                     <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Hari {selectedCell.day}, Jam {selectedCell.hour}</div>
                     <div style={{ fontSize: '1.1rem', fontWeight: 800, color: 'var(--text-main)' }}>{selectedCell.value} Transaksi</div>
                   </div>
                 ) : (
                   <div style={{ fontSize: '0.82rem', color: 'var(--text-muted)' }}>Klik kotak di atas untuk melihat detail transaksi.</div>
                 )}
               </div>

               {/* Keterangan Warna */}
               <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '0.85rem' }}>
                  <span style={{ fontSize: '0.72rem', color: 'var(--text-muted)', fontWeight: 600 }}>Sepi</span>
                  <div style={{ flex: 1, height: '6px', margin: '0 1rem', borderRadius: '4px', background: 'linear-gradient(to right, rgba(59,130,246,0.1), rgba(59,130,246,1))' }}></div>
                  <span style={{ fontSize: '0.72rem', color: 'var(--text-muted)', fontWeight: 600 }}>Ramai</span>
               </div>
            </div>
          </div>
        </div>
        
        {/* AI Assistant Sidebar */}
        <div style={{ flex: '1 1 350px', maxWidth: '100%', minHeight: '480px' }}>
          <SmartAssistant user={user} authToken={authToken} />
        </div>

      </div>
    </div>
  );
};
