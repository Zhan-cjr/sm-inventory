import React from 'react';
import { Plus, X } from 'lucide-react';

export const PwpUpsellBanner = ({
  pwpUpsellPrompt,
  onAccept,
  onDismiss
}) => {
  if (!pwpUpsellPrompt) return null;

  return (
    <div 
      className="pwp-upsell-banner fade-in"
      style={{
        position: 'fixed',
        top: '70px',
        left: '50%',
        transform: 'translateX(-50%)',
        zIndex: 9998,
        width: '94%',
        maxWidth: '740px',
        background: pwpUpsellPrompt.type === 'BUNDLING'
          ? 'linear-gradient(135deg, #064e3b 0%, #065f46 100%)'
          : 'linear-gradient(135deg, #1e1b4b 0%, #312e81 100%)',
        color: '#ffffff',
        border: pwpUpsellPrompt.type === 'BUNDLING' ? '2px solid #34d399' : '2px solid #fbbf24',
        borderRadius: '14px',
        padding: '12px 18px',
        boxShadow: '0 14px 40px rgba(0, 0, 0, 0.5), 0 0 20px ' + (pwpUpsellPrompt.type === 'BUNDLING' ? 'rgba(52, 211, 153, 0.35)' : 'rgba(251, 191, 36, 0.35)'),
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '16px',
        animation: 'slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1)'
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
        <div style={{ 
          background: pwpUpsellPrompt.type === 'BUNDLING' ? '#34d399' : '#fbbf24', 
          color: '#0f172a', 
          borderRadius: '50%', 
          width: '44px', 
          height: '44px', 
          display: 'flex', 
          alignItems: 'center', 
          justifyContent: 'center', 
          fontSize: '1.4rem', 
          flexShrink: 0,
          boxShadow: '0 4px 12px rgba(0, 0, 0, 0.3)'
        }}>
          {pwpUpsellPrompt.type === 'BUNDLING' ? '📦' : '🎁'}
        </div>
        <div>
          <div style={{ fontSize: '0.75rem', textTransform: 'uppercase', letterSpacing: '0.08em', color: pwpUpsellPrompt.type === 'BUNDLING' ? '#a7f3d0' : '#fbbf24', fontWeight: '800' }}>
            {pwpUpsellPrompt.type === 'BUNDLING' ? 'Promo Paket Bundling (Hemat Bersama)' : 'Promo Subsidi Silang (Tebus Murah)'}
          </div>
          <div style={{ fontSize: '0.95rem', fontWeight: '600', lineHeight: 1.35 }}>
            {pwpUpsellPrompt.type === 'BUNDLING' ? (
              <>
                Tawarkan ke konsumen: Tambah <strong>{pwpUpsellPrompt.rewardProduct.name}</strong> untuk Diskon Paket <span style={{ color: '#4ade80', fontSize: '1.15rem', fontWeight: '800' }}>Rp {Math.round(pwpUpsellPrompt.bundleDiscount).toLocaleString('id-ID')}</span>!
              </>
            ) : (
              <>
                Tawarkan ke konsumen: Tebus <strong>{pwpUpsellPrompt.rewardProduct.name}</strong> hanya <span style={{ color: '#4ade80', fontSize: '1.15rem', fontWeight: '800' }}>Rp {Math.round(pwpUpsellPrompt.rewardFinalPrice).toLocaleString('id-ID')}</span>
                {pwpUpsellPrompt.hematText && (
                  <span style={{ marginLeft: '6px', background: 'rgba(239, 68, 68, 0.3)', color: '#fca5a5', padding: '2px 8px', borderRadius: '4px', fontSize: '0.75rem', fontWeight: '700' }}>
                    {pwpUpsellPrompt.hematText}
                  </span>
                )}
              </>
            )}
          </div>
          <div style={{ fontSize: '0.75rem', color: '#cbd5e1', marginTop: '2px' }}>
            {pwpUpsellPrompt.type === 'BUNDLING' 
              ? `(Paket: ${pwpUpsellPrompt.promoName} — Beli ${pwpUpsellPrompt.triggerName} + ${pwpUpsellPrompt.rewardProduct.name})`
              : `(Harga Normal: Rp ${Math.round(pwpUpsellPrompt.rewardNormalPrice).toLocaleString('id-ID')} — Syarat: Beli ${pwpUpsellPrompt.triggerName})`
            }
          </div>
        </div>
      </div>

      <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexShrink: 0 }}>
        <button
          type="button"
          onClick={() => onAccept(pwpUpsellPrompt)}
          style={{
            background: '#10b981',
            color: '#ffffff',
            border: 'none',
            padding: '9px 16px',
            borderRadius: '8px',
            fontWeight: '700',
            fontSize: '0.85rem',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            boxShadow: '0 4px 12px rgba(16, 185, 129, 0.35)',
            transition: 'all 0.2s ease'
          }}
        >
          <Plus size={16} />
          {pwpUpsellPrompt.type === 'BUNDLING' ? '+ Tambahkan Paket' : '+ Tambahkan (Tebus)'}
        </button>
        <button
          type="button"
          onClick={onDismiss}
          style={{
            background: 'rgba(255, 255, 255, 0.12)',
            color: '#e2e8f0',
            border: '1px solid rgba(255, 255, 255, 0.2)',
            padding: '9px 12px',
            borderRadius: '8px',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            transition: 'all 0.2s ease'
          }}
          title="Tolak / Tutup (Esc)"
        >
          <X size={18} />
        </button>
      </div>
    </div>
  );
};
