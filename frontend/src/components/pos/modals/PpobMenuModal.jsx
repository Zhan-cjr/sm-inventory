import React, { useState, useMemo } from 'react';
import { 
  X, 
  Search, 
  RotateCcw, 
  Printer, 
  Zap, 
  CheckCircle, 
  Clock, 
  AlertCircle, 
  Copy, 
  Check 
} from 'lucide-react';

export const PpobMenuModal = ({
  isOpen,
  onClose,
  ppobSearchQuery,
  setPpobSearchQuery,
  fetchPpobTransactions,
  isFetchingPpobTransactions,
  ppobTransactions = [],
  handleCheckPpobStatus,
  handleReprintPpob,
  openRefundModal
}) => {
  const [statusFilter, setStatusFilter] = useState('ALL');
  const [copiedSnId, setCopiedSnId] = useState(null);

  // Flatten all ppob transactions with parent transaction details
  const allPpobList = useMemo(() => {
    if (!Array.isArray(ppobTransactions)) return [];
    return ppobTransactions.flatMap(tx => 
      (tx.ppob_transactions || []).map(p => {
        const matchedItem = tx.items?.find(i => 
          i.product?.ppob_sku === p.buyer_sku_code || 
          i.product_id === p.product_id
        );
        return {
          ...p,
          txId: tx.id,
          txCreatedAt: tx.created_at,
          txReceiptNumber: tx.receipt_number || tx.receiptNumber,
          txCashier: tx.cashier?.name,
          parentTx: tx,
          productName: matchedItem?.product?.name || p.product_name || p.buyer_sku_code
        };
      })
    );
  }, [ppobTransactions]);

  // Statistics
  const stats = useMemo(() => {
    const total = allPpobList.length;
    const sukses = allPpobList.filter(p => p.status === 'Sukses').length;
    const pending = allPpobList.filter(p => p.status === 'Pending').length;
    const gagal = allPpobList.filter(p => p.status === 'Gagal').length;
    const butuhRefund = allPpobList.filter(p => p.status === 'Gagal' && p.refund_status !== 'REFUNDED').length;
    return { total, sukses, pending, gagal, butuhRefund };
  }, [allPpobList]);

  // Filtered List
  const filteredList = useMemo(() => {
    return allPpobList.filter(item => {
      // 1. Status Filter
      if (statusFilter === 'BUTUH_REFUND') {
        if (item.status !== 'Gagal' || item.refund_status === 'REFUNDED') return false;
      } else if (statusFilter !== 'ALL' && item.status !== statusFilter) {
        return false;
      }
      // 2. Search Query Filter
      if (!ppobSearchQuery || !ppobSearchQuery.trim()) return true;
      const q = ppobSearchQuery.trim().toLowerCase();
      return (
        (item.customer_no && item.customer_no.toLowerCase().includes(q)) ||
        (item.sn && item.sn.toLowerCase().includes(q)) ||
        (item.productName && item.productName.toLowerCase().includes(q)) ||
        (item.buyer_sku_code && item.buyer_sku_code.toLowerCase().includes(q)) ||
        (item.txReceiptNumber && String(item.txReceiptNumber).toLowerCase().includes(q))
      );
    });
  }, [allPpobList, statusFilter, ppobSearchQuery]);

  const handleCopySn = (sn, id) => {
    if (!sn) return;
    navigator.clipboard.writeText(sn);
    setCopiedSnId(id);
    setTimeout(() => {
      setCopiedSnId(null);
    }, 2000);
  };

  if (!isOpen) return null;

  return (
    <div 
      className="change-modal-overlay" 
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
      style={{ zIndex: 3000 }}
    >
      <div className="ppob-modal-card">
        {/* MODAL HEADER */}
        <div style={{
          padding: '1.25rem 1.75rem',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          borderBottom: '1px solid var(--border-light)',
          background: 'var(--bg-card)'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
            <div style={{
              width: '46px',
              height: '46px',
              borderRadius: '14px',
              background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              boxShadow: '0 8px 16px -4px rgba(37, 99, 235, 0.4)',
              flexShrink: 0
            }}>
              <Zap size={24} />
            </div>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                <h2 style={{ margin: 0, fontSize: '1.35rem', fontWeight: 700, color: 'var(--text-main)', letterSpacing: '-0.02em' }}>
                  Menu PPOB Hari Ini
                </h2>
                <span style={{
                  fontSize: '0.75rem',
                  padding: '0.2rem 0.65rem',
                  borderRadius: '9999px',
                  fontWeight: 700,
                  background: 'rgba(59, 130, 246, 0.12)',
                  color: '#3b82f6',
                  border: '1px solid rgba(59, 130, 246, 0.25)'
                }}>
                  {stats.total} Transaksi
                </span>
              </div>
              <p style={{ margin: '0.25rem 0 0 0', fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                Pantau riwayat pulsa, paket data, token PLN, e-wallet, dan status transaksi produk digital
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="btn-secondary"
            title="Tutup (ESC)"
            style={{
              width: '38px',
              height: '38px',
              borderRadius: '50%',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              padding: 0,
              cursor: 'pointer',
              border: '1px solid var(--border-light)',
              color: 'var(--text-muted)'
            }}
          >
            <X size={18} />
          </button>
        </div>

        {/* TOOLBAR: SEARCH & STATUS FILTER */}
        <div style={{
          padding: '1rem 1.75rem',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          flexWrap: 'wrap',
          gap: '1rem',
          borderBottom: '1px solid var(--border-light)',
          background: 'var(--bg-hover)'
        }}>
          {/* Search Box */}
          <div style={{ position: 'relative', width: '320px', maxWidth: '100%' }}>
            <Search 
              size={18} 
              style={{
                position: 'absolute',
                left: '0.85rem',
                top: '50%',
                transform: 'translateY(-50%)',
                color: 'var(--text-muted)',
                pointerEvents: 'none'
              }} 
            />
            <input
              type="text"
              className="ppob-search-input"
              placeholder="Cari No Tujuan, SN, atau Produk..."
              value={ppobSearchQuery}
              onChange={(e) => setPpobSearchQuery(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Escape') {
                  e.preventDefault();
                  e.stopPropagation();
                  onClose();
                }
              }}
              autoFocus
            />
            {ppobSearchQuery && (
              <button
                type="button"
                onClick={() => setPpobSearchQuery('')}
                style={{
                  position: 'absolute',
                  right: '0.75rem',
                  top: '50%',
                  transform: 'translateY(-50%)',
                  background: 'none',
                  border: 'none',
                  color: 'var(--text-muted)',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  padding: 2
                }}
              >
                <X size={14} />
              </button>
            )}
          </div>

          {/* Status Filters */}
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem', flexWrap: 'wrap' }}>
            <button
              type="button"
              className={`ppob-filter-tab ${statusFilter === 'ALL' ? 'active' : ''}`}
              onClick={() => setStatusFilter('ALL')}
            >
              <span>Semua</span>
              <span style={{ fontSize: '0.75rem', opacity: 0.8 }}>({stats.total})</span>
            </button>

            <button
              type="button"
              className={`ppob-filter-tab ${statusFilter === 'Sukses' ? 'active' : ''}`}
              onClick={() => setStatusFilter('Sukses')}
              style={{
                color: statusFilter === 'Sukses' ? '#10b981' : undefined
              }}
            >
              <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#10b981' }} />
              <span>Sukses</span>
              <span style={{ fontSize: '0.75rem', opacity: 0.8 }}>({stats.sukses})</span>
            </button>

            <button
              type="button"
              className={`ppob-filter-tab ${statusFilter === 'Pending' ? 'active' : ''}`}
              onClick={() => setStatusFilter('Pending')}
              style={{
                color: statusFilter === 'Pending' ? '#f59e0b' : undefined
              }}
            >
              <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#f59e0b' }} />
              <span>Pending</span>
              <span style={{ 
                fontSize: '0.75rem', 
                fontWeight: 700, 
                color: stats.pending > 0 ? '#f59e0b' : undefined 
              }}>
                ({stats.pending})
              </span>
            </button>

            <button
              type="button"
              className={`ppob-filter-tab ${statusFilter === 'Gagal' ? 'active' : ''}`}
              onClick={() => setStatusFilter('Gagal')}
              style={{
                color: statusFilter === 'Gagal' ? '#ef4444' : undefined
              }}
            >
              <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#ef4444' }} />
              <span>Gagal</span>
              <span style={{ fontSize: '0.75rem', opacity: 0.8 }}>({stats.gagal})</span>
            </button>

            <button
              type="button"
              className={`ppob-filter-tab ${statusFilter === 'BUTUH_REFUND' ? 'active' : ''}`}
              onClick={() => setStatusFilter('BUTUH_REFUND')}
              style={{
                color: statusFilter === 'BUTUH_REFUND' ? '#dc2626' : undefined,
                fontWeight: stats.butuhRefund > 0 ? 800 : undefined,
                backgroundColor: stats.butuhRefund > 0 && statusFilter !== 'BUTUH_REFUND' ? '#fef2f2' : undefined,
                border: stats.butuhRefund > 0 ? '1px solid #f87171' : undefined
              }}
            >
              <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#dc2626' }} />
              <span>Perlu Refund</span>
              <span style={{ fontSize: '0.75rem', fontWeight: 700, color: '#dc2626' }}>({stats.butuhRefund})</span>
            </button>
          </div>

          {/* Refresh Button */}
          <button
            type="button"
            className="btn-secondary"
            onClick={fetchPpobTransactions}
            disabled={isFetchingPpobTransactions}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '0.5rem',
              padding: '0.55rem 1rem',
              fontSize: '0.85rem',
              borderRadius: '10px'
            }}
          >
            <RotateCcw 
              size={15} 
              className={isFetchingPpobTransactions ? 'spin' : ''} 
            />
            <span>{isFetchingPpobTransactions ? 'Memuat...' : 'Segarkan'}</span>
          </button>
        </div>

        {/* TABLE CONTENT AREA */}
        <div style={{ flex: 1, overflowY: 'auto', overflowX: 'auto', background: 'var(--bg-card)' }}>
          {isFetchingPpobTransactions && allPpobList.length === 0 ? (
            <div style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              padding: '5rem 2rem',
              gap: '1rem',
              color: 'var(--text-muted)'
            }}>
              <RotateCcw size={36} className="spin" style={{ color: '#3b82f6' }} />
              <div style={{ fontSize: '0.95rem', fontWeight: 500 }}>Memuat daftar transaksi PPOB hari ini...</div>
            </div>
          ) : filteredList.length === 0 ? (
            <div style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              padding: '5rem 2rem',
              textAlign: 'center',
              gap: '1rem'
            }}>
              <div style={{
                width: '64px',
                height: '64px',
                borderRadius: '50%',
                background: 'var(--bg-hover)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'var(--text-muted)'
              }}>
                <Search size={32} style={{ opacity: 0.4 }} />
              </div>
              <div>
                <h4 style={{ margin: '0 0 0.4rem 0', fontSize: '1.1rem', color: 'var(--text-main)', fontWeight: 600 }}>
                  {allPpobList.length === 0 
                    ? 'Belum ada transaksi PPOB hari ini' 
                    : 'Tidak ada transaksi yang cocok'}
                </h4>
                <p style={{ margin: 0, fontSize: '0.875rem', color: 'var(--text-muted)', maxWidth: '400px' }}>
                  {allPpobList.length === 0
                    ? 'Transaksi produk digital (pulsa, PLN, dll) yang diproses kasir hari ini akan muncul di sini.'
                    : `Hasil pencarian untuk "${ppobSearchQuery}" dengan filter status ${statusFilter} tidak ditemukan.`}
                </p>
              </div>
              {(ppobSearchQuery || statusFilter !== 'ALL') && (
                <button
                  type="button"
                  className="btn-secondary"
                  onClick={() => {
                    setPpobSearchQuery('');
                    setStatusFilter('ALL');
                  }}
                  style={{ marginTop: '0.5rem', fontSize: '0.85rem', padding: '0.45rem 1rem' }}
                >
                  Reset Filter & Pencarian
                </button>
              )}
            </div>
          ) : (
            <table style={{ width: '100%', minWidth: '920px', borderCollapse: 'collapse', textAlign: 'left' }}>
              <thead style={{
                position: 'sticky',
                top: 0,
                zIndex: 10,
                background: 'var(--bg-hover)',
                borderBottom: '1px solid var(--border-light)'
              }}>
                <tr>
                  <th style={{ width: '13%', padding: '0.85rem 1.25rem', fontSize: '0.75rem', fontWeight: 700, color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Waktu / Nota
                  </th>
                  <th style={{ width: '15%', padding: '0.85rem 1.25rem', fontSize: '0.75rem', fontWeight: 700, color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Produk Digital
                  </th>
                  <th style={{ width: '18%', padding: '0.85rem 1.25rem', fontSize: '0.75rem', fontWeight: 700, color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    No. Tujuan / ID Pelanggan
                  </th>
                  <th style={{ width: '12%', padding: '0.85rem 1.25rem', fontSize: '0.75rem', fontWeight: 700, color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.05em', textAlign: 'center' }}>
                    Status
                  </th>
                  <th style={{ width: '27%', padding: '0.85rem 1.25rem', fontSize: '0.75rem', fontWeight: 700, color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    SN / No. Token
                  </th>
                  <th style={{ width: '15%', padding: '0.85rem 1.25rem', fontSize: '0.75rem', fontWeight: 700, color: 'var(--text-muted)', textTransform: 'uppercase', letterSpacing: '0.05em', textAlign: 'center' }}>
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody>
                {filteredList.map((ppob) => {
                  const isSukses = ppob.status === 'Sukses';
                  const isPending = ppob.status === 'Pending';
                  const isGagal = ppob.status === 'Gagal';
                  const isCopied = copiedSnId === ppob.id;

                  return (
                    <tr 
                      key={ppob.id}
                      style={{
                        borderBottom: '1px solid var(--border-light)',
                        transition: 'background-color 0.15s ease'
                      }}
                      onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'var(--bg-hover)'}
                      onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
                    >
                      {/* Waktu & Struk */}
                      <td style={{ padding: '0.9rem 1.25rem', verticalAlign: 'middle' }}>
                        <div style={{ fontWeight: 600, color: 'var(--text-main)', fontSize: '0.9rem' }}>
                          {new Date(ppob.txCreatedAt).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
                        </div>
                        <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '2px', fontFamily: 'monospace' }}>
                          #{ppob.txReceiptNumber ? String(ppob.txReceiptNumber).substring(0, 14) : `ID:${ppob.txId}`}
                        </div>
                      </td>

                      {/* Produk */}
                      <td style={{ padding: '0.9rem 1.25rem', verticalAlign: 'middle' }}>
                        <div style={{ fontWeight: 600, color: 'var(--text-main)', fontSize: '0.92rem' }}>
                          {ppob.productName}
                        </div>
                        <div style={{ display: 'inline-block', fontSize: '0.72rem', color: 'var(--text-muted)', background: 'var(--bg-hover)', padding: '1px 6px', borderRadius: '4px', marginTop: '3px', border: '1px solid var(--border-light)' }}>
                          SKU: {ppob.buyer_sku_code}
                        </div>
                      </td>

                      {/* No Tujuan */}
                      <td style={{ padding: '0.9rem 1.25rem', verticalAlign: 'middle' }}>
                        <div style={{ 
                          fontFamily: 'monospace', 
                          fontWeight: 700, 
                          fontSize: '1rem', 
                          letterSpacing: '0.04em',
                          color: 'var(--text-main)' 
                        }}>
                          {ppob.customer_no}
                        </div>
                        {ppob.message && (
                          <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '2px', maxWidth: '220px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }} title={ppob.message}>
                            {ppob.message}
                          </div>
                        )}
                      </td>

                      {/* Status */}
                      <td style={{ padding: '0.9rem 1.25rem', verticalAlign: 'middle', textAlign: 'center' }}>
                        <div style={{
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '0.35rem',
                          padding: '0.35rem 0.8rem',
                          borderRadius: '9999px',
                          fontSize: '0.8rem',
                          fontWeight: 700,
                          background: isSukses 
                            ? 'rgba(16, 185, 129, 0.12)' 
                            : (isPending ? 'rgba(245, 158, 11, 0.12)' : 'rgba(239, 68, 68, 0.12)'),
                          color: isSukses 
                            ? '#10b981' 
                            : (isPending ? '#f59e0b' : '#ef4444'),
                          border: `1px solid ${isSukses ? 'rgba(16, 185, 129, 0.25)' : (isPending ? 'rgba(245, 158, 11, 0.25)' : 'rgba(239, 68, 68, 0.25)')}`
                        }}>
                          {isSukses && <CheckCircle size={14} />}
                          {isPending && <Clock size={14} className="spin" style={{ animationDuration: '3s' }} />}
                          {isGagal && <AlertCircle size={14} />}
                          <span>{ppob.status}</span>
                        </div>
                      </td>

                      {/* SN / No. Token */}
                      <td style={{ padding: '0.9rem 1.25rem', verticalAlign: 'middle', maxWidth: '300px' }}>
                        {ppob.sn ? (
                          <div style={{ 
                            display: 'inline-flex', 
                            alignItems: 'center', 
                            gap: '0.5rem', 
                            background: 'var(--bg-hover)', 
                            padding: '0.4rem 0.65rem', 
                            borderRadius: '8px', 
                            border: '1px solid var(--border-light)',
                            maxWidth: '100%',
                            boxSizing: 'border-box'
                          }}>
                            <span style={{ 
                              fontFamily: 'monospace', 
                              fontWeight: 600, 
                              fontSize: '0.82rem', 
                              color: 'var(--text-main)', 
                              letterSpacing: '0.02em',
                              wordBreak: 'break-all',
                              overflowWrap: 'anywhere',
                              lineHeight: 1.35
                            }}>
                              {ppob.sn}
                            </span>
                            <button
                              type="button"
                              onClick={() => handleCopySn(ppob.sn, ppob.id)}
                              title="Salin SN / Token"
                              style={{
                                background: 'none',
                                border: 'none',
                                cursor: 'pointer',
                                padding: 2,
                                display: 'flex',
                                alignItems: 'center',
                                flexShrink: 0,
                                color: isCopied ? '#10b981' : 'var(--text-muted)'
                              }}
                            >
                              {isCopied ? <Check size={14} /> : <Copy size={14} />}
                            </button>
                          </div>
                        ) : (
                          <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>-</span>
                        )}
                      </td>

                      {/* Aksi */}
                      <td style={{ padding: '0.9rem 1.25rem', verticalAlign: 'middle', textAlign: 'center', whiteSpace: 'nowrap' }}>
                        <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'center', alignItems: 'center', whiteSpace: 'nowrap' }}>
                          {isGagal && (
                            ppob.refund_status === 'REFUNDED' ? (
                              <span style={{ 
                                display: 'inline-flex', 
                                alignItems: 'center', 
                                gap: '3px',
                                padding: '4px 8px', 
                                borderRadius: '6px', 
                                fontSize: '0.75rem', 
                                fontWeight: 700, 
                                background: '#ecfdf5', 
                                color: '#059669', 
                                border: '1px solid #a7f3d0' 
                              }}>
                                ✓ Direfund ({ppob.refund_method || 'CASH'})
                              </span>
                            ) : (
                              <button 
                                type="button"
                                style={{ 
                                  padding: '0.45rem 0.8rem', 
                                  fontSize: '0.8rem', 
                                  borderRadius: '8px',
                                  display: 'flex',
                                  alignItems: 'center',
                                  gap: '0.35rem',
                                  backgroundColor: '#ef4444',
                                  color: 'white',
                                  border: 'none',
                                  fontWeight: 700,
                                  cursor: 'pointer',
                                  boxShadow: '0 2px 4px rgba(239, 68, 68, 0.25)'
                                }} 
                                onClick={() => openRefundModal && openRefundModal(ppob)}
                                title="Klik untuk memproses pengembalian dana ke konsumen"
                              >
                                💵 Refund Konsumen
                              </button>
                            )
                          )}
                          {isPending && (
                            <button 
                              type="button"
                              className="btn-secondary" 
                              style={{ 
                                padding: '0.45rem 0.8rem', 
                                fontSize: '0.8rem', 
                                borderRadius: '8px',
                                display: 'flex',
                                alignItems: 'center',
                                gap: '0.35rem',
                                color: '#f59e0b',
                                borderColor: 'rgba(245, 158, 11, 0.4)'
                              }} 
                              onClick={() => handleCheckPpobStatus(ppob.id)}
                              title="Cek update status ke server provider"
                            >
                              <RotateCcw size={14} /> Cek Status
                            </button>
                          )}
                          <button 
                            type="button"
                            className="btn-primary" 
                            style={{ 
                              padding: '0.45rem 0.85rem', 
                              fontSize: '0.8rem', 
                              borderRadius: '8px',
                              display: 'flex',
                              alignItems: 'center',
                              gap: '0.35rem'
                            }} 
                            onClick={() => { 
                              onClose(); 
                              handleReprintPpob(ppob.parentTx); 
                            }}
                            title="Cetak ulang struk transaksi ini"
                          >
                            <Printer size={14} /> Cetak Struk
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>

        {/* MODAL FOOTER */}
        <div style={{
          padding: '0.85rem 1.75rem',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          borderTop: '1px solid var(--border-light)',
          background: 'var(--bg-card)'
        }}>
          <div style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>
            Menampilkan <strong style={{ color: 'var(--text-main)' }}>{filteredList.length}</strong> dari {stats.total} transaksi PPOB hari ini
          </div>
          <button
            type="button"
            className="btn-secondary"
            onClick={onClose}
            style={{
              padding: '0.55rem 1.25rem',
              fontSize: '0.875rem',
              fontWeight: 600,
              borderRadius: '8px'
            }}
          >
            TUTUP (ESC)
          </button>
        </div>
      </div>
    </div>
  );
};
