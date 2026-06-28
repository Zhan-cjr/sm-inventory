import { useState, useEffect } from 'react';
import { Smartphone, Zap, Wifi, Loader2, X, ChevronRight, History } from 'lucide-react';
import axios from 'axios';
import { useEcom } from '../context/EcomContext';
import { useNavigate } from 'react-router-dom';

const PpobWidget = () => {
  const { member, setIsMemberModalOpen, selectedBranch, isPpobModalOpen, setIsPpobModalOpen, activePpobTab, setActivePpobTab } = useEcom();
  const navigate = useNavigate();
  const [phoneNumber, setPhoneNumber] = useState('');
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [purchasing, setPurchasing] = useState<number | null>(null);

  useEffect(() => {
    const fetchPpobProducts = async () => {
      if (phoneNumber.length < 4) {
        setProducts([]);
        return;
      }
      
      setLoading(true);
      try {
        const response = await axios.get('/ecommerce/ppob/products', {
          params: { type: activePpobTab, prefix: phoneNumber.substring(0, 4) }
        });
        setProducts(response.data);
      } catch (error) {
        console.error('Failed to fetch PPOB products', error);
      } finally {
        setLoading(false);
      }
    };

    const timeoutId = setTimeout(() => {
      fetchPpobProducts();
    }, 500);

    return () => clearTimeout(timeoutId);
  }, [phoneNumber, activePpobTab]);

  const handleBuy = async (product: any) => {
    if (!member) {
      setIsMemberModalOpen(true);
      return;
    }

    if (!selectedBranch) {
      alert("Memuat data cabang, silakan tunggu sebentar.");
      return;
    }

    if (!phoneNumber || phoneNumber.length < 10) {
      alert("Masukkan nomor tujuan yang valid.");
      return;
    }

    setPurchasing(product.id);
    try {
      const response = await axios.post('/ecommerce/ppob/orders', {
        customer_name: member.name,
        customer_phone: member.phone,
        target_number: phoneNumber,
        product_id: product.id,
        branch_id: selectedBranch.id
      });
      
      const snapToken = response.data.order.snap_token;
      if (snapToken && (window as any).snap) {
        (window as any).snap.pay(snapToken, {
          onSuccess: function () {
            alert('Pembayaran sukses! Pesanan Anda sedang diproses.');
            setPhoneNumber('');
            setIsPpobModalOpen(false);
            navigate('/pesanan');
          },
          onPending: function () {
            alert('Menunggu pembayaran Anda!');
            setIsPpobModalOpen(false);
            navigate('/pesanan');
          },
          onError: function () {
            alert('Pembayaran gagal!');
          },
          onClose: function () {
            alert('Anda menutup popup tanpa menyelesaikan pembayaran');
          }
        });
      } else {
        alert("Sistem pembayaran belum siap.");
      }
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal membuat pesanan');
    } finally {
      setPurchasing(null);
    }
  };

  if (!isPpobModalOpen) return null;

  return (
    <div className="fixed inset-0 z-[60] flex items-end sm:items-center justify-center bg-slate-900/60 backdrop-blur-sm sm:p-4">
      <div className="bg-white w-full sm:max-w-xl sm:rounded-3xl rounded-t-3xl shadow-2xl flex flex-col h-[90vh] sm:h-[80vh] overflow-hidden animate-slide-up sm:animate-scale-up">
        
        {/* Header */}
        <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-white relative z-10">
          <div className="flex flex-col">
            <h3 className="font-extrabold text-lg text-slate-800">Top Up & Tagihan</h3>
            <span className="text-xs text-slate-500 font-medium">Beli pulsa, paket data, dan token PLN.</span>
          </div>
          <button 
            onClick={() => setIsPpobModalOpen(false)}
            className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-all"
          >
            <X size={18} />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto bg-slate-50/50">
          <div className="p-5">
            {/* Tabs */}
            <div className="flex bg-slate-100 p-1.5 rounded-2xl mb-6">
              <button
                onClick={() => { setActivePpobTab('PULSA'); setPhoneNumber(''); }}
                className={`flex-1 py-2.5 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 ${
                  activePpobTab === 'PULSA' 
                    ? 'bg-white text-brand-blue shadow-sm' 
                    : 'text-slate-500 hover:text-slate-800'
                }`}
              >
                <Smartphone size={16} />
                Pulsa
              </button>
              <button
                onClick={() => { setActivePpobTab('DATA'); setPhoneNumber(''); }}
                className={`flex-1 py-2.5 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 ${
                  activePpobTab === 'DATA' 
                    ? 'bg-white text-brand-blue shadow-sm' 
                    : 'text-slate-500 hover:text-slate-800'
                }`}
              >
                <Wifi size={16} />
                Paket Data
              </button>
              <button
                onClick={() => { setActivePpobTab('PLN'); setPhoneNumber(''); }}
                className={`flex-1 py-2.5 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 ${
                  activePpobTab === 'PLN' 
                    ? 'bg-white text-brand-blue shadow-sm' 
                    : 'text-slate-500 hover:text-slate-800'
                }`}
              >
                <Zap size={16} />
                Token PLN
              </button>
            </div>

            {/* Input */}
            <div className="mb-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
              <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                {activePpobTab === 'PLN' ? 'Nomor Meter / ID Pelanggan' : 'Nomor Handphone'}
              </label>
              <div className="relative">
                <input 
                  type="tel"
                  value={phoneNumber}
                  onChange={(e) => setPhoneNumber(e.target.value.replace(/[^0-9]/g, ''))}
                  placeholder={activePpobTab === 'PLN' ? 'Contoh: 12345678901' : 'Contoh: 08123456789'}
                  className="w-full pl-4 pr-12 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-lg font-bold text-slate-800 tracking-wider shadow-inner"
                />
                {phoneNumber.length >= 4 && activePpobTab !== 'PLN' && (
                  <div className="absolute right-4 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-brand-red/10 flex items-center justify-center border border-brand-red/20">
                    <Smartphone size={14} className="text-brand-red" />
                  </div>
                )}
              </div>
            </div>

            {/* Product Grid */}
            <div className="space-y-4">
              <h4 className="text-sm font-bold text-slate-800">Pilih Nominal</h4>
              
              <div className="grid grid-cols-2 gap-3 pb-8">
                {loading ? (
                   <div className="col-span-2 py-12 flex justify-center">
                     <Loader2 className="animate-spin text-brand-blue" size={32} />
                   </div>
                ) : phoneNumber.length >= 4 ? (
                  products.length > 0 ? (
                    products.map((product) => (
                      <button 
                        key={product.id} 
                        onClick={() => handleBuy(product)}
                        disabled={purchasing === product.id}
                        className="text-left border border-slate-200 rounded-xl p-4 hover:border-brand-blue hover:bg-brand-blue/5 hover:shadow-md transition-all group flex flex-col justify-between bg-white relative overflow-hidden"
                      >
                        {purchasing === product.id && (
                          <div className="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-10">
                            <Loader2 className="animate-spin text-brand-blue" size={24} />
                          </div>
                        )}
                        <h4 className="font-bold text-slate-800 group-hover:text-brand-blue transition-colors text-sm">{product.name}</h4>
                        <div className="mt-3">
                          <span className="text-[10px] text-slate-500 block mb-0.5">Harga</span>
                          <span className="text-sm font-black text-brand-red">Rp {parseFloat(product.selling_price).toLocaleString('id-ID')}</span>
                        </div>
                      </button>
                    ))
                  ) : (
                    <div className="col-span-2 py-8 text-center bg-white rounded-xl border border-dashed border-slate-200">
                      <p className="text-slate-500 text-sm font-semibold">Produk tidak tersedia untuk nomor ini.</p>
                    </div>
                  )
                ) : (
                  <div className="col-span-2 py-8 text-center bg-white rounded-xl border border-dashed border-slate-200">
                    <div className="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-3">
                      {activePpobTab === 'PLN' ? <Zap size={24} className="text-amber-400" /> : <Smartphone size={24} className="text-brand-blue" />}
                    </div>
                    <p className="text-slate-500 text-xs font-medium max-w-[200px] mx-auto">
                      Silakan masukkan {activePpobTab === 'PLN' ? 'Nomor Meter' : 'Nomor HP'} Anda untuk melihat pilihan nominal.
                    </p>
                  </div>
                )}
              </div>
            </div>
            
          </div>
        </div>

        {/* Footer */}
        <div className="p-4 border-t border-slate-100 bg-white flex items-center justify-between">
           <button 
             onClick={() => {
               setIsPpobModalOpen(false);
               navigate('/pesanan');
             }}
             className="flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-brand-blue transition-colors"
           >
             <History size={16} />
             Riwayat Transaksi
           </button>
           <div className="flex items-center gap-1 text-[10px] text-slate-400 font-medium">
             Aman & Terpercaya <ChevronRight size={12} />
           </div>
        </div>

      </div>
    </div>
  );
};

export default PpobWidget;
