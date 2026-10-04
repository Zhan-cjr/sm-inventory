import React from 'react';

export function MobileWeeklySalesChart({ weeklyChart, formatCurrency }) {
  const hasData = weeklyChart && weeklyChart.length > 0;
  const maxSales = hasData ? Math.max(...weeklyChart.map(d => d.sales || 0), 1) : 1;

  return (
    <div className="pwa-card">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
        <h3 style={{ fontSize: '0.95rem', fontWeight: 700, margin: 0, color: 'var(--text-main)' }}>
          Penjualan 7 Hari Terakhir
        </h3>
        <span style={{ fontSize: '0.7rem', color: '#10b981', fontWeight: 600, background: 'rgba(16, 185, 129, 0.1)', padding: '2px 8px', borderRadius: '99px' }}>
          Tren Positif
        </span>
      </div>

      {hasData ? (
        <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', height: '110px', gap: '6px', paddingTop: '10px' }}>
          {weeklyChart.map((day, idx) => {
            const heightPct = Math.max(((day.sales || 0) / maxSales) * 100, 6);
            const isHighest = (day.sales || 0) === maxSales && maxSales > 0;

            return (
              <div
                key={idx}
                style={{
                  flex: 1,
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  justifyContent: 'flex-end',
                  gap: '6px'
                }}
              >
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
                />
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
  );
}
