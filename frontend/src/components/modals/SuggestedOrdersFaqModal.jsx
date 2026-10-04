import React from 'react';
import { HelpCircle, AlertTriangle, X } from 'lucide-react';

export const SuggestedOrdersFaqModal = ({ isOpen, onClose }) => {
  if (!isOpen) return null;

  return (
    <div
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: 'rgba(0, 0, 0, 0.75)',
        zIndex: 10000,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
        backdropFilter: 'blur(4px)',
        WebkitBackdropFilter: 'blur(4px)'
      }}
      onClick={onClose}
    >
      <div
        className="glass-panel"
        style={{
          maxWidth: '560px',
          width: '100%',
          padding: '1.75rem',
          borderRadius: '16px',
          position: 'relative',
          maxHeight: '90vh',
          overflowY: 'auto',
          background: 'var(--bg-card, #1e293b)',
          border: '1px solid var(--border-light, rgba(255,255,255,0.1))',
          boxShadow: '0 20px 40px rgba(0, 0, 0, 0.4)'
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginBottom: '1rem',
            borderBottom: '1px solid var(--border-light, rgba(255,255,255,0.1))',
            paddingBottom: '0.75rem'
          }}
        >
          <h3
            style={{
              fontSize: '1.2rem',
              fontWeight: 700,
              margin: 0,
              color: 'var(--text-main, #f8fafc)',
              display: 'flex',
              alignItems: 'center',
              gap: '0.5rem'
            }}
          >
            <HelpCircle color="#60a5fa" size={22} /> Panduan Order Pintar AI (Smart Restock)
          </h3>
          <button
            onClick={onClose}
            style={{
              background: 'transparent',
              border: 'none',
              color: 'var(--text-muted, #94a3b8)',
              cursor: 'pointer',
              padding: '4px',
              borderRadius: '6px'
            }}
          >
            <X size={20} />
          </button>
        </div>

        <div style={{ color: 'var(--text-muted, #cbd5e1)', lineHeight: '1.6', fontSize: '0.9rem' }}>
          <p style={{ marginBottom: '0.85rem' }}>
            <strong>Saran Order AI</strong> memprediksi kebutuhan kulakan secara otomatis berdasarkan
            perputaran penjualan harian aktual di kasir, waktu pengiriman supplier, dan batas stok aman.
          </p>

          <h4 style={{ color: '#60a5fa', fontWeight: 600, margin: '0.85rem 0 0.35rem 0' }}>
            1. Penjelasan Kolom Data
          </h4>
          <ul style={{ paddingLeft: '1.25rem', marginBottom: '1rem', display: 'flex', flexDirection: 'column', gap: '0.35rem' }}>
            <li><strong>Stok Saat Ini / Gudang:</strong> Sisa stok fisik yang tercatat aktif di cabang saat ini.</li>
            <li><strong>ADS (Average Daily Sales):</strong> Kecepatan rata-rata barang terjual per hari (dihitung dari riwayat transaksi kasir).</li>
            <li><strong>Titik Pesan (ROP):</strong> Batas minimal stok untuk mulai memesan barang kembali agar tidak kehabisan saat menunggu kiriman.</li>
            <li><strong>Target Stok:</strong> Target ketahanan persediaan di toko (default 14–30 hari).</li>
            <li><strong>Saran Pesan:</strong> Estimasi jumlah unit yang direkomendasikan untuk dibeli hari ini.</li>
          </ul>

          <h4 style={{ color: '#60a5fa', fontWeight: 600, margin: '0.85rem 0 0.35rem 0' }}>
            2. Arti Status
          </h4>
          <ul style={{ paddingLeft: '1.25rem', marginBottom: '1rem', display: 'flex', flexDirection: 'column', gap: '0.35rem' }}>
            <li><strong style={{ color: '#ef4444' }}>KRITIS (CRITICAL):</strong> Stok sudah 0, minus, atau di bawah batas aman darurat.</li>
            <li><strong style={{ color: '#f59e0b' }}>REORDER / PERLU ORDER:</strong> Stok sudah menyentuh Titik Pesan (ROP). Segera buat PO.</li>
            <li><strong style={{ color: '#10b981' }}>AMAN (OK):</strong> Stok masih cukup, atau perputaran barang belum memerlukan restock.</li>
          </ul>

          <div
            style={{
              background: 'rgba(59, 130, 246, 0.12)',
              borderLeft: '4px solid #3b82f6',
              padding: '0.85rem',
              borderRadius: '6px',
              color: '#93c5fd',
              fontSize: '0.85rem'
            }}
          >
            <strong>💡 Tips:</strong> Anda dapat mencentang beberapa produk dan klik &quot;Buat PO Terpilih&quot;. Sistem akan otomatis memecah draft PO sesuai Pemasok dan Sub Divisi masing-masing.
          </div>
        </div>

        <button
          onClick={onClose}
          className="btn-secondary"
          style={{
            marginTop: '1.5rem',
            width: '100%',
            padding: '0.75rem',
            borderRadius: '12px',
            background: '#3b82f6',
            color: '#ffffff',
            fontWeight: 700,
            border: 'none',
            cursor: 'pointer'
          }}
        >
          Tutup
        </button>
      </div>
    </div>
  );
};
