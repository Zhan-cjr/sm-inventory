import React from 'react';
import { Package } from 'lucide-react';

export function MobileTopProductsCard({ topProducts }) {
  const hasProducts = topProducts && topProducts.length > 0;

  return (
    <div className="pwa-card">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.85rem' }}>
        <h3 style={{ fontSize: '0.95rem', fontWeight: 700, margin: 0, color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          <Package size={18} color="#6366f1" /> Top 5 Produk Terlaris
        </h3>
        <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Bulan Ini</span>
      </div>

      {!hasProducts ? (
        <div style={{ textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.85rem', padding: '1rem' }}>
          Belum ada data produk terlaris.
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
          {topProducts.map((prod, idx) => (
            <div 
              key={idx} 
              style={{ 
                display: 'flex', 
                justifyContent: 'space-between', 
                alignItems: 'center', 
                borderBottom: idx !== topProducts.length - 1 ? '1px solid var(--border-light)' : 'none', 
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
  );
}
