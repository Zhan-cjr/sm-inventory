import { useState, useEffect } from 'react';
import { Smartphone, Zap, Wifi, Loader2 } from 'lucide-react';
import axios from 'axios';
import { useEcom } from '../context/EcomContext';

const PpobWidget = () => {
  const { member, setIsMemberModalOpen, selectedBranch } = useEcom();
  const [activeTab, setActiveTab] = useState<'PULSA' | 'DATA' | 'PLN'>('PULSA');
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
          params: { type: activeTab, prefix: phoneNumber.substring(0, 4) }
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
  }, [phoneNumber, activeTab]);

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
      
      // Redirect to Midtrans payment URL
      const snapToken = response.data.order.snap_token;
      if (snapToken && (window as any).snap) {
        (window as any).snap.pay(snapToken, {
          onSuccess: function (_result: any) {
            alert('Pembayaran sukses! Top up Anda sedang diproses.');
            setPhoneNumber('');
          },
          onPending: function (_result: any) {
            alert('Menunggu pembayaran Anda!');
          },
          onError: function (_result: any) {
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

  return (
    <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
      <div className="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden relative group">
        <div className="absolute inset-0 bg-gradient-to-r from-brand-blue/5 via-brand-green/5 to-brand-red/5 opacity-50 pointer-events-none group-hover:opacity-100 transition-opacity duration-1000" />
        
        <div className="p-4 sm:p-6 lg:p-8 relative z-10">
          <div className="flex flex-col md:flex-row gap-6 md:gap-12">
            
            {/* Left Column: Title and Tabs */}
            <div className="md:w-1/3">
              <h3 className="text-xl font-extrabold text-slate-800 mb-2">Top Up & Tagihan</h3>
              <p className="text-sm text-slate-500 mb-6">Bayar tagihan dan beli pulsa jadi lebih mudah, cepat, dan aman.</p>
              
              <div className="flex flex-row md:flex-col gap-2">
                <button
                  onClick={() => { setActiveTab('PULSA'); setPhoneNumber(''); }}
                  className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all ${
                    activeTab === 'PULSA' 
                      ? 'bg-brand-blue text-white shadow-md shadow-brand-blue/30 scale-[1.02]' 
                      : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                  }`}
                >
                  <Smartphone size={20} />
                  Pulsa
                </button>
                <button
                  onClick={() => { setActiveTab('DATA'); setPhoneNumber(''); }}
                  className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all ${
                    activeTab === 'DATA' 
                      ? 'bg-brand-blue text-white shadow-md shadow-brand-blue/30 scale-[1.02]' 
                      : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                  }`}
                >
                  <Wifi size={20} />
                  Paket Data
                </button>
                <button
                  onClick={() => { setActiveTab('PLN'); setPhoneNumber(''); }}
                  className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all ${
                    activeTab === 'PLN' 
                      ? 'bg-brand-blue text-white shadow-md shadow-brand-blue/30 scale-[1.02]' 
                      : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                  }`}
                >
                  <Zap size={20} />
                  Token PLN
                </button>
              </div>
            </div>

            {/* Right Column: Input and Product Grid */}
            <div className="md:w-2/3 border-t md:border-t-0 md:border-l border-slate-100 pt-6 md:pt-0 md:pl-12">
              <div className="mb-6 relative">
                <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                  {activeTab === 'PLN' ? 'Nomor Meter / ID Pelanggan' : 'Nomor HP'}
                </label>
                <div className="relative">
                  <input 
                    type="tel"
                    value={phoneNumber}
                    onChange={(e) => setPhoneNumber(e.target.value.replace(/[^0-9]/g, ''))}
                    placeholder={activeTab === 'PLN' ? 'Contoh: 12345678901' : 'Contoh: 08123456789'}
                    className="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-xl font-bold text-slate-800 tracking-wider shadow-inner"
                  />
                  {phoneNumber.length >= 4 && activeTab !== 'PLN' && (
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-brand-red/10 flex items-center justify-center border border-brand-red/20">
                      <Smartphone size={16} className="text-brand-red" />
                    </div>
                  )}
                </div>
              </div>

              {/* Product Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                {loading ? (
                   <div className="col-span-full py-12 flex justify-center">
                     <Loader2 className="animate-spin text-brand-blue" size={32} />
                   </div>
                ) : phoneNumber.length >= 4 ? (
                  products.length > 0 ? (
                    products.map((product) => (
                      <div 
                        key={product.id} 
                        className="border border-slate-200 rounded-xl p-4 hover:border-brand-blue hover:bg-brand-blue/5 hover:shadow-md cursor-pointer transition-all group flex flex-col justify-between"
                      >
                        <h4 className="font-bold text-slate-800 group-hover:text-brand-blue transition-colors text-sm">{product.name}</h4>
                        <div className="mt-3 flex justify-between items-end">
                          <span className="text-base font-bold text-brand-red">Rp {parseFloat(product.selling_price).toLocaleString('id-ID')}</span>
                          <button 
                            onClick={() => handleBuy(product)}
                            disabled={purchasing === product.id}
                            className="text-xs font-bold bg-brand-blue/10 text-brand-blue px-4 py-2 rounded-lg group-hover:bg-brand-blue group-hover:text-white transition-all disabled:opacity-50 flex items-center gap-2"
                          >
                            {purchasing === product.id ? <Loader2 size={14} className="animate-spin" /> : 'Beli'}
                          </button>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="col-span-full py-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                      <p className="text-slate-500 text-sm font-semibold">Produk tidak tersedia untuk nomor ini.</p>
                    </div>
                  )
                ) : (
                  <div className="col-span-full py-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <Zap size={32} className="mx-auto text-slate-300 mb-2" />
                    <p className="text-slate-500 text-sm font-medium">
                      Silakan masukkan {activeTab === 'PLN' ? 'Nomor Meter' : 'Nomor HP'} Anda untuk melihat produk.
                    </p>
                  </div>
                )}
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  );
};

export default PpobWidget;
