"use client";

import React, { useState, useEffect } from 'react';
import { X, Receipt, ShoppingBag, Loader2, CreditCard, Printer } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

interface TransactionHistoryModalProps {
  isOpen: boolean;
  onClose: () => void;
  member: any;
}

const getEcomApiUrl = () => {
  if (process.env.NEXT_PUBLIC_API_URL) return process.env.NEXT_PUBLIC_API_URL;
  if (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
    return 'http://localhost:8080/api/v1';
  }
  return '/api/v1';
};

export default function TransactionHistoryModal({ isOpen, onClose, member }: TransactionHistoryModalProps) {
  const [history, setHistory] = useState<any[]>([]);
  const [isLoadingHistory, setIsLoadingHistory] = useState(false);
  const [selectedReceipt, setSelectedReceipt] = useState<any | null>(null);

  const fetchHistory = async () => {
    if (!member) return;
    setIsLoadingHistory(true);
    try {
      const res = await fetch(`${getEcomApiUrl()}/ecommerce/members/history?phone=${member.phone}`, {
        headers: { 'Accept': 'application/json' }
      });
      if (res.ok) {
        const data = await res.json();
        setHistory(data.history || []);
      }
    } catch (err) {
      console.error('Gagal mengambil riwayat belanja', err);
    } finally {
      setIsLoadingHistory(false);
    }
  };

  useEffect(() => {
    if (isOpen && member) {
      fetchHistory();
    }
  }, [isOpen, member]);

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <motion.div 
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm"
      >
        <motion.div 
          initial={{ scale: 0.95, opacity: 0, y: 20 }}
          animate={{ scale: 1, opacity: 1, y: 0 }}
          exit={{ scale: 0.95, opacity: 0, y: 20 }}
          className="bg-white rounded-none sm:rounded-3xl shadow-2xl max-w-lg w-full h-full sm:h-[85vh] flex flex-col overflow-hidden border border-slate-100"
        >
          {/* Header */}
          <div className="relative p-6 border-b border-slate-100 flex justify-between items-center bg-white flex-shrink-0">
            <div>
              <h3 className="text-xl font-extrabold text-slate-800 tracking-tight">
                Riwayat Transaksi
              </h3>
              <p className="text-xs text-slate-500 mt-1">
                Riwayat belanja offline maupun online Anda.
              </p>
            </div>
            <button 
              onClick={onClose}
              className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-all"
            >
              <X size={18} />
            </button>
          </div>

          {/* Content Area */}
          <div className="flex-grow overflow-y-auto p-6 relative bg-slate-50">
            {isLoadingHistory ? (
              <div className="flex flex-col items-center justify-center py-12 gap-3 text-slate-400 text-sm">
                <Loader2 className="animate-spin text-primary" size={28} />
                Mengambil riwayat belanja...
              </div>
            ) : history.length === 0 ? (
              <div className="text-center py-12 text-slate-400 space-y-3">
                <ShoppingBag size={36} className="mx-auto text-slate-300" />
                <p className="text-xs font-semibold">Belum ada riwayat belanja yang tercatat.</p>
                <p className="text-[10px]">Transaksi akan muncul di sini setelah pesanan selesai.</p>
              </div>
            ) : (
              <div className="space-y-3 pr-1 max-h-full overflow-y-auto">
                {history.map((item) => (
                  <div 
                    key={item.id} 
                    className="p-3.5 border border-slate-100 rounded-2xl bg-white hover:bg-slate-50/50 transition-all flex flex-col gap-2.5 shadow-sm"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <div className="flex items-center gap-2">
                          <span className={`px-2 py-0.5 rounded text-[9px] font-bold ${
                            item.type === 'STORE' 
                              ? 'bg-amber-50 text-amber-700 border border-amber-100' 
                              : 'bg-indigo-50 text-indigo-700 border border-indigo-100'
                          }`}>
                            {item.type === 'STORE' ? 'TOKO (POS)' : 'ONLINE'}
                          </span>
                          <span className="text-[10px] font-mono text-slate-400">{item.invoice_number}</span>
                        </div>
                        <span className="text-[10px] text-slate-400 block mt-1">
                          {new Date(item.date).toLocaleDateString('id-ID', {
                            day: '2-digit', month: 'short', year: 'numeric',
                            hour: '2-digit', minute: '2-digit'
                          })}
                        </span>
                        <span className="text-[10px] text-slate-500 font-semibold block mt-0.5">
                          Cabang: <span className="text-slate-700">{item.branch_name}</span>
                        </span>
                      </div>
                      <span className={`text-[10px] font-extrabold px-2 py-0.5 rounded-full ${
                        item.status === 'SUCCESS' || item.status === 'COMPLETED'
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                          : item.status === 'VOID' || item.status === 'CANCELLED'
                          ? 'bg-red-50 text-red-700 border border-red-100'
                          : 'bg-blue-50 text-blue-700 border border-blue-100'
                      }`}>
                        {item.status === 'SUCCESS' || item.status === 'COMPLETED'
                          ? 'Berhasil'
                          : item.status === 'VOID' || item.status === 'CANCELLED'
                          ? 'Batal'
                          : item.status
                        }
                      </span>
                    </div>

                    <div className="flex justify-between items-center pt-2 border-t border-slate-50">
                      <div>
                        <span className="text-[10px] text-slate-400 block leading-none">Total Belanja</span>
                        <span className="text-xs font-mono font-bold text-slate-800 mt-1 block">
                          Rp {item.final_amount.toLocaleString('id-ID')}
                        </span>
                      </div>
                      <div className="flex items-center gap-2">
                        {item.snap_token && ['UNPAID', 'PENDING'].includes(item.payment_status) && (
                          <div className="flex gap-1">
                            <button
                              onClick={() => {
                                // @ts-ignore
                                if (window.snap) {
                                  // @ts-ignore
                                  window.snap.pay(item.snap_token, {
                                    onSuccess: function() { fetchHistory(); },
                                    onPending: function() { fetchHistory(); },
                                    onError: function() { fetchHistory(); },
                                    onClose: function() { fetchHistory(); }
                                  });
                                }
                              }}
                              className="px-2 py-1.5 bg-primary text-white font-bold rounded-lg transition-all text-[10px] flex items-center gap-1 hover:bg-primary/90"
                            >
                              <CreditCard size={12} />
                              Bayar
                            </button>
                            <button
                              onClick={async () => {
                                try {
                                  const res = await fetch(`${getEcomApiUrl()}/ecommerce/orders/${item.id}/refresh-payment`, {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json' }
                                  });
                                  const data = await res.json();
                                  // @ts-ignore
                                  if (window.snap && data.snap_token) {
                                    // @ts-ignore
                                    window.snap.pay(data.snap_token, {
                                      onSuccess: function() { fetchHistory(); },
                                      onPending: function() { fetchHistory(); },
                                      onError: function() { fetchHistory(); },
                                      onClose: function() { fetchHistory(); }
                                    });
                                  }
                                } catch (err) {
                                  alert('Gagal mengganti metode pembayaran.');
                                }
                              }}
                              className="px-2 py-1.5 bg-white text-primary border border-primary font-bold rounded-lg transition-all text-[10px] hover:bg-slate-50"
                              title="Ganti Metode Bayar"
                            >
                              Ganti Metode
                            </button>
                          </div>
                        )}
                        <button
                          onClick={() => setSelectedReceipt(item)}
                          className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-all text-[10px] flex items-center gap-1"
                        >
                          <Receipt size={12} />
                          Lihat Struk
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </motion.div>

        {/* ================= VIRTUAL RECEIPT MODAL OVERLAY ================= */}
        {selectedReceipt && (
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-3 z-[60]">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-white rounded-2xl w-full max-w-sm shadow-2xl flex flex-col overflow-hidden max-h-[90%] border border-slate-100"
            >
              
              {/* Receipt Header Actions */}
              <div className="bg-slate-50 px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                <span className="text-xs font-extrabold text-slate-700 flex items-center gap-1">
                  <Receipt size={14} className="text-primary" />
                  Detail Struk Belanja
                </span>
                <div className="flex gap-2">
                  <button 
                    onClick={() => window.print()}
                    className="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-700 transition-all"
                    title="Cetak"
                  >
                    <Printer size={12} />
                  </button>
                  <button 
                    onClick={() => setSelectedReceipt(null)}
                    className="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-700 transition-all"
                  >
                    <X size={12} />
                  </button>
                </div>
              </div>

              {/* Thermal Receipt Paper Layout */}
              <div className="p-5 overflow-y-auto flex-grow bg-slate-50 print:bg-white">
                <div className="bg-white border border-slate-200/80 rounded-xl p-5 shadow-md flex flex-col font-mono text-[11px] text-slate-800 relative">
                  
                  {/* Jagged border simulation top & bottom */}
                  <div className="absolute top-0 left-0 right-0 h-1 bg-[linear-gradient(45deg,transparent_25%,#f8fafc_25%,#f8fafc_50%,transparent_50%,transparent_75%,#f8fafc_75%)] bg-[size:8px_8px]" />
                  
                  {/* Brand Header */}
                  <div className="text-center border-b border-dashed border-slate-300 pb-3 mb-3">
                    <h4 className="font-extrabold text-sm text-slate-900 tracking-tight">TOSERBA SELAMAT</h4>
                    <p className="text-[10px] text-slate-500 uppercase font-bold tracking-wide mt-0.5">{selectedReceipt.branch_name}</p>
                    <p className="text-[9px] text-slate-400 mt-1">THE MOSLEM FAMILY</p>
                  </div>

                  {/* Receipt Meta */}
                  <div className="space-y-1 pb-3 border-b border-dashed border-slate-200 mb-3 text-slate-600">
                    <div className="flex justify-between">
                      <span>No Nota:</span>
                      <span className="font-bold text-slate-900">{selectedReceipt.invoice_number}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Waktu:</span>
                      <span>{new Date(selectedReceipt.date).toLocaleString('id-ID')}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Kasir:</span>
                      <span>{selectedReceipt.cashier_name || '-'}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Metode:</span>
                      <span>{selectedReceipt.payment_method || '-'}</span>
                    </div>
                    {selectedReceipt.is_voided && (
                      <div className="bg-red-50 text-red-600 font-bold py-1 text-center rounded border border-red-100 text-[10px] mt-1.5">
                        TRANSAKSI DIBATALKAN (VOID)
                      </div>
                    )}
                  </div>

                  {/* Items List */}
                  <div className="space-y-3 pb-3 border-b border-dashed border-slate-200 mb-3">
                    {selectedReceipt.items?.map((item: any, idx: number) => (
                      <div key={idx} className="flex flex-col">
                        <span className="font-semibold text-slate-900">{item.product_name}</span>
                        <div className="flex justify-between text-slate-500 mt-0.5">
                          <span>{item.quantity} x Rp {item.price.toLocaleString('id-ID')}</span>
                          <span className="font-bold text-slate-900">Rp {item.subtotal.toLocaleString('id-ID')}</span>
                        </div>
                      </div>
                    ))}
                  </div>

                  {/* Calculations */}
                  <div className="space-y-1.5 text-xs">
                    <div className="flex justify-between">
                      <span>Subtotal:</span>
                      <span>Rp {selectedReceipt.total_amount.toLocaleString('id-ID')}</span>
                    </div>
                    {selectedReceipt.discount_amount > 0 && (
                      <div className="flex justify-between text-red-600">
                        <span>Diskon:</span>
                        <span>-Rp {selectedReceipt.discount_amount.toLocaleString('id-ID')}</span>
                      </div>
                    )}
                    <div className="flex justify-between font-bold text-sm text-slate-900 pt-1.5 border-t border-slate-100">
                      <span>TOTAL:</span>
                      <span>Rp {selectedReceipt.final_amount.toLocaleString('id-ID')}</span>
                    </div>
                  </div>

                  {/* Thank you Footer */}
                  <div className="text-center pt-5 mt-4 border-t border-dashed border-slate-300 text-slate-400 text-[9px] leading-relaxed">
                    <p className="font-bold">TERIMA KASIH ATAS KUNJUNGAN ANDA</p>
                    <p className="mt-0.5">Barang yang sudah dibeli tidak dapat ditukar</p>
                    <p className="mt-0.5">Semoga Berkah - Insya Allah</p>
                  </div>
                </div>
              </div>

              {/* Close virtual receipt button */}
              <div className="p-4 bg-slate-50 border-t border-slate-100 flex-shrink-0">
                <button
                  onClick={() => setSelectedReceipt(null)}
                  className="w-full py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl transition-all text-xs"
                >
                  Tutup Struk
                </button>
              </div>

            </motion.div>
          </div>
        )}
      </motion.div>
    </AnimatePresence>
  );
}
