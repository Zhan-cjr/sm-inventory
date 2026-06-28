import { useState, useEffect } from 'react';
import { ClipboardList, Loader2, ShoppingBag, CreditCard, RefreshCw, CheckCircle, Receipt, X, Printer, Truck, MapPin, Copy } from 'lucide-react';
import { useEcom } from '../context/EcomContext';
import axios from 'axios';

const Pesanan = () => {
  const { member, setIsMemberModalOpen } = useEcom();
  const [history, setHistory] = useState<any[]>([]);
  const [isLoadingHistory, setIsLoadingHistory] = useState(false);
  const [selectedReceipt, setSelectedReceipt] = useState<any | null>(null);
  
  const [trackingData, setTrackingData] = useState<any>(null);
  const [isTrackingModalOpen, setIsTrackingModalOpen] = useState(false);
  const [isLoadingTracking, setIsLoadingTracking] = useState(false);

  const handleTrackOrder = async (orderId: string) => {
    setIsTrackingModalOpen(true);
    setIsLoadingTracking(true);
    setTrackingData(null);
    try {
      const res = await axios.get(`/ecommerce/orders/${orderId}/tracking`);
      setTrackingData(res.data.tracking);
    } catch (err: any) {
      console.error(err);
      setTrackingData({ error: err.response?.data?.message || 'Gagal melacak pesanan.' });
    } finally {
      setIsLoadingTracking(false);
    }
  };

  const fetchHistory = async () => {
    if (!member) return;
    setIsLoadingHistory(true);
    try {
      const response = await axios.get('/ecommerce/members/history', {
        params: { phone: member.phone }
      });
      setHistory(response.data.history || []);
    } catch (err: any) {
      console.error(err);
    } finally {
      setIsLoadingHistory(false);
    }
  };

  useEffect(() => {
    if (member) {
      fetchHistory();
    }
  }, [member]);

  return (
    <div className="flex flex-col min-h-[70vh] bg-slate-50 overflow-x-hidden pt-6">
      <div className="max-w-7xl mx-auto px-4 w-full">
        <h1 className="text-xl font-bold text-slate-800 mb-6">Daftar Pesanan</h1>
        
        {!member ? (
          <div className="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100">
            <ClipboardList size={48} className="mx-auto text-slate-300 mb-4" />
            <h3 className="text-lg font-semibold text-slate-800">Anda belum masuk</h3>
            <p className="text-sm text-slate-500 mt-2 mb-6">Masuk sekarang untuk melihat pesanan Anda.</p>
            <button 
              onClick={() => setIsMemberModalOpen(true)}
              className="bg-brand-blue text-white px-6 py-2.5 rounded-xl font-bold hover:bg-brand-blue/90 transition-all"
            >
              Masuk / Daftar
            </button>
          </div>
        ) : (
          <div className="space-y-4">
            {isLoadingHistory ? (
              <div className="flex flex-col items-center justify-center py-12 gap-3 text-slate-400 text-sm">
                <Loader2 className="animate-spin text-brand-blue" size={28} />
                Mengambil riwayat belanja...
              </div>
            ) : history.length === 0 ? (
              <div className="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100">
                <ShoppingBag size={48} className="mx-auto text-slate-300 mb-4" />
                <h3 className="text-lg font-semibold text-slate-800">Belum Ada Pesanan</h3>
                <p className="text-sm text-slate-500 mt-2">Anda belum memiliki riwayat pesanan saat ini.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {history.map((item) => (
                  <div 
                    key={item.id} 
                    className="p-5 border border-slate-200 rounded-2xl bg-white hover:border-brand-blue/30 hover:shadow-md transition-all flex flex-col gap-4"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <div className="flex items-center gap-2 mb-1">
                          <span className={`px-2.5 py-1 rounded-md text-[10px] font-bold ${
                            item.type === 'STORE' 
                              ? 'bg-amber-50 text-amber-700 border border-amber-100' 
                              : item.delivery_method === 'DIGITAL'
                              ? 'bg-brand-red/10 text-brand-red border border-brand-red/20'
                              : 'bg-indigo-50 text-indigo-700 border border-indigo-100'
                          }`}>
                            {item.type === 'STORE' 
                              ? 'TOKO (POS)' 
                              : item.delivery_method === 'DIGITAL'
                              ? 'PPOB / DIGITAL'
                              : 'ONLINE'}
                          </span>
                          <span className="text-[11px] font-mono font-medium text-slate-500">{item.invoice_number}</span>
                        </div>
                        <span className="text-xs text-slate-500 block">
                          {new Date(item.date).toLocaleDateString('id-ID', {
                            day: '2-digit', month: 'short', year: 'numeric',
                            hour: '2-digit', minute: '2-digit'
                          })}
                        </span>
                        <span className="text-xs text-slate-600 font-semibold block mt-1">
                          Cabang: <span className="text-slate-800">{item.branch_name}</span>
                        </span>
                      </div>
                      <span className={`text-xs font-extrabold px-3 py-1 rounded-full ${
                        item.status === 'SUCCESS' || item.status === 'COMPLETED'
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                          : item.status === 'VOID'
                          ? 'bg-red-50 text-red-700 border border-red-100'
                          : 'bg-blue-50 text-blue-700 border border-blue-100'
                      }`}>
                        {item.status === 'SUCCESS' || item.status === 'COMPLETED'
                          ? 'Berhasil'
                          : item.status === 'VOID'
                          ? 'Batal (Void)'
                          : item.status
                        }
                      </span>
                    </div>

                    <div className="flex justify-between items-center pt-3 border-t border-slate-100">
                      <div>
                        <span className="text-xs text-slate-500 block leading-none mb-1">Total Belanja</span>
                        <span className="text-sm font-mono font-black text-slate-800 block">
                          Rp {item.final_amount.toLocaleString('id-ID')}
                        </span>
                      </div>
                      <div className="flex flex-col items-end gap-2">
                        {item.snap_token && ['UNPAID', 'PENDING'].includes(item.payment_status) && (
                          <div className="flex flex-wrap justify-end gap-1.5">
                            <button
                              onClick={() => {
                                if (window.snap) {
                                  window.snap.pay(item.snap_token, {
                                    onSuccess: function() { fetchHistory(); },
                                    onPending: function() { fetchHistory(); },
                                    onError: function() { fetchHistory(); },
                                    onClose: function() { fetchHistory(); }
                                  });
                                }
                              }}
                              className="px-3 py-1.5 bg-brand-blue text-white font-bold rounded-lg transition-all text-[11px] flex items-center gap-1.5 hover:bg-brand-blue/90 shadow-sm"
                            >
                              <CreditCard size={14} />
                              Bayar Sekarang
                            </button>
                            <button
                              onClick={async () => {
                                try {
                                  const res = await axios.post(`/ecommerce/orders/${item.id}/refresh-payment`);
                                  if (window.snap) {
                                    window.snap.pay(res.data.snap_token, {
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
                              className="px-3 py-1.5 bg-white text-brand-blue border border-brand-blue font-bold rounded-lg transition-all text-[11px] hover:bg-slate-50"
                            >
                              Ganti Metode
                            </button>
                            <button
                              onClick={async () => {
                                try {
                                  const res = await axios.post(`/ecommerce/orders/${item.id}/check-status`);
                                  if (res.data.payment_status === 'PAID') {
                                    alert('Status berhasil disinkronkan menjadi PAID!');
                                  } else {
                                    alert(`Status saat ini: ${res.data.payment_status}`);
                                  }
                                  fetchHistory();
                                } catch (err) {
                                  alert('Gagal menyinkronkan status.');
                                }
                              }}
                              className="px-3 py-1.5 bg-white text-emerald-600 border border-emerald-500 font-bold rounded-lg transition-all text-[11px] hover:bg-emerald-50"
                            >
                              <RefreshCw size={12} className="inline mr-1" />
                              Cek Status
                            </button>
                          </div>
                        )}
                        <div className="flex gap-2 items-center">
                          {item.payment_status === 'PAID' && (
                            <span className="px-2 py-1 bg-emerald-50 text-emerald-600 border border-emerald-200 font-bold rounded-md text-[10px] flex items-center gap-1">
                              <CheckCircle size={10} />
                              Lunas
                            </span>
                          )}
                          <button
                            onClick={() => setSelectedReceipt(item)}
                            className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-all text-xs flex items-center gap-1.5 border border-slate-200"
                          >
                            <Receipt size={14} />
                            Lihat Struk
                          </button>
                          {item.type === 'ONLINE' && item.delivery_method === 'DELIVERY' && item.status !== 'PENDING' && item.status !== 'CANCELLED' && (
                            <button
                              onClick={() => handleTrackOrder(item.id)}
                              className="px-3 py-1.5 bg-brand-blue hover:bg-brand-blue/90 text-white font-bold rounded-lg transition-all text-xs flex items-center gap-1.5 shadow-sm"
                            >
                              <Truck size={14} />
                              Lacak
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* ================= VIRTUAL RECEIPT MODAL OVERLAY ================= */}
        {selectedReceipt && (
          <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-[70] animate-fade-in">
            <div className="bg-white rounded-2xl w-full max-w-sm shadow-2xl flex flex-col overflow-hidden max-h-[90vh] border border-slate-100 animate-scale-up">
              
              {/* Receipt Header Actions */}
              <div className="bg-slate-50 px-5 py-4 border-b border-slate-200 flex justify-between items-center">
                <span className="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                  <Receipt size={18} className="text-brand-blue" />
                  Detail Struk Belanja
                </span>
                <div className="flex gap-2">
                  <button 
                    onClick={() => window.print()}
                    className="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-700 transition-all shadow-sm"
                    title="Cetak"
                  >
                    <Printer size={14} />
                  </button>
                  <button 
                    onClick={() => setSelectedReceipt(null)}
                    className="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-700 transition-all shadow-sm"
                  >
                    <X size={14} />
                  </button>
                </div>
              </div>

              {/* Thermal Receipt Paper Layout */}
              <div className="p-6 overflow-y-auto flex-grow bg-slate-100 print:bg-white relative">
                <div className="bg-white border-x border-slate-200 shadow-md flex flex-col font-mono text-[11px] text-slate-800 relative min-h-full">
                  
                  <div className="absolute top-0 left-0 right-0 h-1.5 bg-[linear-gradient(45deg,transparent_25%,#f1f5f9_25%,#f1f5f9_50%,transparent_50%,transparent_75%,#f1f5f9_75%)] bg-[size:10px_10px]" />
                  
                  <div className="p-6">
                    {/* Brand Header */}
                    <div className="text-center border-b-2 border-dashed border-slate-300 pb-4 mb-4">
                      <h4 className="font-extrabold text-base text-slate-900 tracking-tight">TOSERBA SELAMAT</h4>
                      <p className="text-[11px] text-slate-500 uppercase font-bold tracking-wide mt-1">{selectedReceipt.branch_name}</p>
                      <p className="text-[9px] text-slate-400 mt-1">THE MOSLEM FAMILY</p>
                    </div>

                    {/* Receipt Meta */}
                    <div className="space-y-1.5 pb-4 border-b-2 border-dashed border-slate-300 mb-4 text-slate-600">
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
                        <span>{selectedReceipt.cashier_name}</span>
                      </div>
                      <div className="flex justify-between">
                        <span>Metode:</span>
                        <span>{selectedReceipt.payment_method}</span>
                      </div>
                      
                      {/* PPOB Specific Info */}
                      {selectedReceipt.ppob_transaction && (
                        <>
                          <div className="flex justify-between mt-2 pt-2 border-t border-dashed border-slate-200">
                            <span>No Tujuan:</span>
                            <span className="font-bold text-slate-900">{selectedReceipt.ppob_transaction.customer_no}</span>
                          </div>
                          <div className="flex justify-between">
                            <span>Status:</span>
                            <span className={`font-bold ${
                              selectedReceipt.ppob_transaction.status === 'Sukses' ? 'text-green-600' :
                              selectedReceipt.ppob_transaction.status === 'Gagal' ? 'text-red-600' : 'text-amber-600'
                            }`}>
                              {selectedReceipt.ppob_transaction.status}
                            </span>
                          </div>
                          {selectedReceipt.ppob_transaction.sn && (
                            <div className="flex flex-col mt-1">
                              <span>SN / Token:</span>
                              <span className="font-bold text-slate-900 text-[10px] break-all">
                                {selectedReceipt.ppob_transaction.sn}
                              </span>
                            </div>
                          )}
                        </>
                      )}

                      {selectedReceipt.is_voided && (
                        <div className="bg-red-50 text-red-600 font-bold py-1.5 text-center rounded border border-red-100 text-[11px] mt-2">
                          TRANSAKSI DIBATALKAN (VOID)
                        </div>
                      )}
                    </div>

                    {/* Items List */}
                    <div className="space-y-3 pb-4 border-b-2 border-dashed border-slate-300 mb-4">
                      {selectedReceipt.items?.map((item: any, idx: number) => (
                        <div key={idx} className="flex flex-col">
                          <span className="font-semibold text-slate-900 text-xs">{item.product_name}</span>
                          <div className="flex justify-between text-slate-500 mt-1">
                            <span>{item.quantity} x Rp {item.price.toLocaleString('id-ID')}</span>
                            <span className="font-bold text-slate-900">Rp {item.subtotal.toLocaleString('id-ID')}</span>
                          </div>
                        </div>
                      ))}
                    </div>

                    {/* Calculations */}
                    <div className="space-y-2 text-xs">
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
                      <div className="flex justify-between font-bold text-sm text-slate-900 pt-2 border-t-2 border-slate-200 mt-1">
                        <span>TOTAL:</span>
                        <span>Rp {selectedReceipt.final_amount.toLocaleString('id-ID')}</span>
                      </div>
                    </div>

                    {/* Thank you Footer */}
                    <div className="text-center pt-6 mt-6 border-t-2 border-dashed border-slate-300 text-slate-400 text-[10px] leading-relaxed">
                      <p className="font-bold text-slate-500">TERIMA KASIH ATAS KUNJUNGAN ANDA</p>
                      <p className="mt-1">Barang yang sudah dibeli tidak dapat ditukar</p>
                      <p className="mt-0.5">Semoga Berkah - Insya Allah</p>
                    </div>
                  </div>

                  <div className="absolute bottom-0 left-0 right-0 h-1.5 bg-[linear-gradient(45deg,transparent_25%,#f1f5f9_25%,#f1f5f9_50%,transparent_50%,transparent_75%,#f1f5f9_75%)] bg-[size:10px_10px]" />
                </div>
              </div>

              {/* Close virtual receipt button */}
              <div className="p-4 bg-white border-t border-slate-100 flex-shrink-0">
                <button
                  onClick={() => setSelectedReceipt(null)}
                  className="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-sm border border-slate-200"
                >
                  Tutup Struk
                </button>
              </div>

            </div>
          </div>
        )}
      </div>
      
        {/* ================= TRACKING MODAL ================= */}
        {isTrackingModalOpen && (
          <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-[70] animate-fade-in">
            <div className="bg-white rounded-2xl w-full max-w-md shadow-2xl flex flex-col overflow-hidden max-h-[90vh] border border-slate-100 animate-scale-up">
              
              {/* Tracking Header */}
              <div className="bg-slate-50 px-5 py-4 border-b border-slate-200 flex justify-between items-center">
                <span className="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                  <Truck size={18} className="text-brand-blue" />
                  Lacak Pengiriman
                </span>
                <button 
                  onClick={() => setIsTrackingModalOpen(false)}
                  className="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-700 transition-all shadow-sm"
                >
                  <X size={14} />
                </button>
              </div>

              {/* Tracking Content */}
              <div className="p-5 overflow-y-auto flex-grow bg-white">
                {isLoadingTracking ? (
                  <div className="flex flex-col items-center justify-center py-12 gap-3 text-slate-400">
                    <Loader2 className="animate-spin text-brand-blue" size={32} />
                    <span className="text-sm">Menarik data pelacakan...</span>
                  </div>
                ) : trackingData?.error ? (
                  <div className="flex flex-col items-center justify-center py-10 gap-3 text-center">
                    <MapPin size={40} className="text-slate-300" />
                    <p className="text-sm font-semibold text-slate-700">{trackingData.error}</p>
                    <p className="text-xs text-slate-500">Coba cek kembali nanti atau hubungi Admin.</p>
                  </div>
                ) : trackingData ? (
                  <div className="flex flex-col gap-6">
                    
                    {/* AWB & Courier Info */}
                    <div className="bg-slate-50 border border-slate-100 p-4 rounded-xl flex items-center justify-between">
                      <div>
                        <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Kurir & Resi</p>
                        <div className="flex items-center gap-2">
                          <span className="font-extrabold text-brand-blue uppercase">{trackingData.courier?.company || 'KURIR'}</span>
                          <span className="text-sm font-mono font-bold text-slate-800">{trackingData.courier?.waybill_id || '-'}</span>
                        </div>
                      </div>
                      {trackingData.courier?.waybill_id && (
                        <button 
                          onClick={() => {
                            navigator.clipboard.writeText(trackingData.courier.waybill_id);
                            alert('Nomor Resi disalin!');
                          }}
                          className="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 transition-all text-slate-600"
                        >
                          <Copy size={16} />
                        </button>
                      )}
                    </div>
                    
                    {/* Timeline Status */}
                    <div className="bg-white px-2">
                      <p className="text-xs font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Riwayat Perjalanan</p>
                      <div className="relative border-l-2 border-slate-200 ml-3 space-y-6 pb-4">
                        {trackingData.history && trackingData.history.length > 0 ? (
                          trackingData.history.map((hist: any, idx: number) => (
                            <div key={idx} className="relative pl-6">
                              <span className={`absolute -left-[9px] top-1 h-4 w-4 rounded-full border-2 border-white ${idx === 0 ? 'bg-brand-blue ring-4 ring-brand-blue/20' : 'bg-slate-300'}`} />
                              <div className="flex flex-col">
                                <span className={`text-sm font-bold ${idx === 0 ? 'text-slate-800' : 'text-slate-600'}`}>{hist.note}</span>
                                <span className="text-[11px] font-medium text-slate-400 mt-0.5">
                                  {new Date(hist.updated_at).toLocaleString('id-ID', {
                                    day: '2-digit', month: 'short', year: 'numeric',
                                    hour: '2-digit', minute: '2-digit'
                                  })}
                                </span>
                              </div>
                            </div>
                          ))
                        ) : (
                          <p className="text-sm text-slate-500 pl-4">Belum ada riwayat perjalanan.</p>
                        )}
                      </div>
                    </div>
                    
                  </div>
                ) : null}
              </div>

            </div>
          </div>
        )}
    </div>
  );
};

export default Pesanan;
