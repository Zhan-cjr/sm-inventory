import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { useEcom, Branch } from '../context/EcomContext';
import { useSearchParams } from 'react-router-dom';
import { ScanBarcode, AlertCircle, Loader2, Info, Building2, Tag, ChevronDown, MapPin, X, Keyboard, CreditCard, Package, Maximize, Minimize } from 'lucide-react';
import { ProductImage } from '../components/ProductImage';
import { VirtualKeyboard } from '../components/kiosk/VirtualKeyboard';
import { VirtualNumpad } from '../components/kiosk/VirtualNumpad';

const PriceChecker: React.FC = () => {
  const { selectedBranch, setSelectedBranch } = useEcom();
  const [searchParams, setSearchParams] = useSearchParams();
  const urlBranchId = searchParams.get('branch_id');
  
  const [branches, setBranches] = useState<Branch[]>([]);
  const [activeBranchId, setActiveBranchId] = useState<string | null>(urlBranchId || (selectedBranch ? selectedBranch.id : null));
  const activeBranchName = branches.find((b: Branch) => b.id === activeBranchId)?.name || 'Pilih Cabang';

  const [barcode, setBarcode] = useState('');
  const [loading, setLoading] = useState(false);
  const [product, setProduct] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);
  const [idleTimer, setIdleTimer] = useState<ReturnType<typeof setTimeout> | null>(null);
  const [showBranchModal, setShowBranchModal] = useState(false);
  const [promoProducts, setPromoProducts] = useState<any[]>([]);
  const [currentTime, setCurrentTime] = useState(new Date());
  
  // New Kiosk States
  const [showSearchModal, setShowSearchModal] = useState(false);
  const [showMemberModal, setShowMemberModal] = useState(false);
  const [bgImageIndex, setBgImageIndex] = useState(0);
  const [isFullscreen, setIsFullscreen] = useState(false);

  const inputRef = useRef<HTMLInputElement>(null);

  // Background Slideshow
  useEffect(() => {
    if (promoProducts.length === 0) return;
    const timer = setInterval(() => {
      setBgImageIndex(prev => (prev + 1) % promoProducts.length);
    }, 5000);
    return () => clearInterval(timer);
  }, [promoProducts]);

  // Clock Update
  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  // Sync branch from Context/URL
  useEffect(() => {
    const fetchBranches = async () => {
      try {
        const response = await axios.get('/ecommerce/branches');
        setBranches(response.data);
        
        if (urlBranchId && urlBranchId !== activeBranchId) {
          setActiveBranchId(urlBranchId);
          const b = response.data.find((x: Branch) => x.id === urlBranchId);
          if (b) setSelectedBranch(b);
        } else if (!urlBranchId && selectedBranch) {
          setActiveBranchId(selectedBranch.id);
          setSearchParams({ branch_id: selectedBranch.id });
        }
      } catch (err) {
        console.error('Failed to fetch branches');
      }
    };
    fetchBranches();
  }, [urlBranchId, selectedBranch, setSearchParams]);

  // Fetch Promo Products for Marquee
  useEffect(() => {
    const fetchPromos = async () => {
      try {
        const response = await axios.get('/ecommerce/products', {
          params: activeBranchId ? { branch_id: activeBranchId } : {}
        });
        const promos = response.data.filter((p: any) => p.is_promo);
        setPromoProducts(promos);
      } catch (err) {
        console.error('Failed to fetch promos for kiosk');
      }
    };
    fetchPromos();
  }, [activeBranchId]);

  // Keep focus on input
  useEffect(() => {
    const focusInterval = setInterval(() => {
      if (inputRef.current && document.activeElement !== inputRef.current) {
        inputRef.current.focus();
      }
    }, 1000);
    return () => clearInterval(focusInterval);
  }, []);

  const resetToIdle = () => {
    setProduct(null);
    setError(null);
    setBarcode('');
    if (inputRef.current) inputRef.current.focus();
  };

  const startIdleTimer = () => {
    if (idleTimer) clearTimeout(idleTimer);
    const timer = setTimeout(() => {
      resetToIdle();
    }, 15000); // 15 seconds
    setIdleTimer(timer);
  };

  const playBeep = (success: boolean) => {
    try {
      const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
      const oscillator = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      
      oscillator.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      
      if (success) {
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.1);
      } else {
        oscillator.type = 'sawtooth';
        oscillator.frequency.setValueAtTime(150, audioCtx.currentTime); // Low buzz
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.3);
      }
    } catch (e) {
      console.warn("Audio not supported");
    }
  };

  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(err => {
        console.error(`Error attempting to enable fullscreen: ${err.message}`);
      });
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    }
  };

  useEffect(() => {
    const handleFullscreenChange = () => {
      setIsFullscreen(!!document.fullscreenElement);
    };
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    return () => document.removeEventListener('fullscreenchange', handleFullscreenChange);
  }, []);

  const handleSearchManual = async (keyword: string) => {
    setShowSearchModal(false);
    if (!keyword.trim()) return;
    setBarcode(keyword); // Fallback to simulate barcode change
    
    setLoading(true);
    setError(null);
    setProduct(null);

    try {
      // First attempt to search by name/sku in products endpoint
      const response = await axios.get('/ecommerce/products', {
        params: {
          search: keyword.trim(),
          ...(activeBranchId ? { branch_id: activeBranchId } : {})
        }
      });
      if (response.data && response.data.length > 0) {
        // Just take the first match and run check-price to get full details
        const firstMatch = response.data[0];
        const detailRes = await axios.get('/ecommerce/check-price', {
          params: { barcode: firstMatch.sku || firstMatch.barcode || firstMatch.id, branch_id: activeBranchId }
        });
        setProduct(detailRes.data);
        playBeep(true);
      } else {
        throw new Error('Not found');
      }
      startIdleTimer();
    } catch (err) {
      playBeep(false);
      setError(`Barang dengan nama "${keyword}" tidak ditemukan.`);
      startIdleTimer();
    } finally {
      setLoading(false);
      setBarcode('');
      setTimeout(() => inputRef.current?.focus(), 100);
    }
  };

  const handleScan = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!barcode.trim()) return;

    setLoading(true);
    setError(null);
    setProduct(null);

    try {
      const response = await axios.get('/ecommerce/check-price', {
        params: {
          barcode: barcode.trim(),
          ...(activeBranchId ? { branch_id: activeBranchId } : {})
        }
      });
      setProduct(response.data);
      playBeep(true);
      startIdleTimer();
    } catch (err: any) {
      playBeep(false);
      if (err.response && err.response.status === 404) {
        setError('Produk tidak ditemukan. Silakan pastikan barcode benar atau hubungi kasir.');
      } else {
        setError('Terjadi kesalahan jaringan. Silakan coba lagi.');
      }
      startIdleTimer();
    } finally {
      setLoading(false);
      setBarcode('');
      if (inputRef.current) inputRef.current.focus();
    }
  };

  const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(Number(price));
  };

  return (
    <div className="min-h-screen bg-slate-900 flex flex-col items-center justify-center relative overflow-hidden font-sans select-none">
      
      {/* Background Decor & Slideshow */}
      <div className="absolute inset-0 z-0 opacity-30 pointer-events-none transition-all duration-1000">
        {!product && !error && promoProducts.length > 0 ? (
           <div className="absolute inset-0 animate-in fade-in duration-1000">
              <ProductImage 
                src={promoProducts[bgImageIndex]?.image_url} 
                alt="Promo Background" 
                className="w-full h-full object-cover opacity-20 scale-105" 
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/80 to-transparent"></div>
           </div>
        ) : (
          <>
            <div className="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-brand-blue rounded-full mix-blend-screen filter blur-[100px] animate-pulse"></div>
            <div className="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-brand-green rounded-full mix-blend-screen filter blur-[100px] animate-pulse" style={{ animationDelay: '2s' }}></div>
          </>
        )}
      </div>

      {/* Top Bar for Branch Info & Clock */}
      <div className="absolute top-0 left-0 right-0 p-6 z-20 flex justify-between items-start text-white/50">
        <div className="flex items-center gap-4">
          <button 
            onClick={() => setShowBranchModal(true)}
            className="flex items-center gap-3 bg-white/10 hover:bg-white/20 transition-colors px-4 py-2 rounded-xl backdrop-blur-md border border-white/10 cursor-pointer shadow-lg"
          >
            <Building2 size={24} className="text-white" />
            <span className="text-xl font-bold tracking-wide uppercase text-white">{activeBranchName}</span>
            <ChevronDown size={20} className="text-white/70" />
          </button>
          
          <button 
            onClick={toggleFullscreen}
            className="bg-white/10 hover:bg-white/20 transition-colors p-3 rounded-xl backdrop-blur-md border border-white/10 cursor-pointer shadow-lg text-white"
            title="Toggle Fullscreen"
          >
            {isFullscreen ? <Minimize size={20} /> : <Maximize size={20} />}
          </button>
        </div>
        
        <div className="flex flex-col items-end gap-2">
          <div className="text-sm font-mono tracking-widest uppercase bg-black/30 px-4 py-2 rounded-xl backdrop-blur-sm border border-white/5">
            Toserba Selamat Kiosk
          </div>
          <div className="text-right bg-black/20 px-4 py-2 rounded-xl backdrop-blur-sm border border-white/5">
            <div className="text-2xl font-bold text-white tracking-wider">
              {currentTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
            </div>
            <div className="text-xs text-slate-300 uppercase tracking-widest">
              {currentTime.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
            </div>
          </div>
        </div>
      </div>

      <div className={`z-10 w-full max-w-5xl px-8 flex flex-col items-center transition-all duration-700 ${(!product && !error && promoProducts.length > 0) ? 'mb-40 md:mb-56' : ''}`}>
        
        {!product && !error && !loading && (
          <div className="text-center animate-in fade-in slide-in-from-bottom-8 duration-700 flex flex-col items-center">
            <div className="bg-white/10 p-12 rounded-full mb-12 backdrop-blur-md shadow-2xl border border-white/10 relative">
              <div className="absolute inset-0 bg-brand-blue rounded-full animate-ping opacity-20"></div>
              <ScanBarcode size={120} className="text-white" strokeWidth={1.5} />
            </div>
            <h1 className="text-6xl md:text-7xl font-black text-white mb-6 tracking-tight drop-shadow-xl">
              CEK HARGA
            </h1>
            <p className="text-2xl text-slate-300 font-medium max-w-2xl leading-relaxed drop-shadow-md mb-12">
              Arahkan barcode produk ke alat pemindai (scanner) di bawah layar untuk mengetahui harga dan promo terbaru.
            </p>
            
            <div className="flex items-center gap-6">
              <button 
                onClick={() => setShowSearchModal(true)}
                className="flex items-center gap-3 bg-white/5 border border-white/20 hover:bg-white/10 px-8 py-4 rounded-2xl text-white font-bold tracking-wide transition-colors"
              >
                <Keyboard size={24} />
                CARI MANUAL
              </button>
              <button 
                onClick={() => setShowMemberModal(true)}
                className="flex items-center gap-3 bg-brand-green/20 border border-brand-green/40 hover:bg-brand-green/30 px-8 py-4 rounded-2xl text-white font-bold tracking-wide transition-colors"
              >
                <CreditCard size={24} className="text-brand-green" />
                CEK POIN MEMBER
              </button>
            </div>
          </div>
        )}

        {loading && (
          <div className="flex flex-col items-center animate-in fade-in duration-300">
            <Loader2 size={100} className="text-brand-blue animate-spin mb-8" />
            <h2 className="text-3xl font-bold text-white tracking-widest">MENCARI DATA...</h2>
          </div>
        )}

        {error && (
          <div className="bg-red-500/20 backdrop-blur-xl border-2 border-red-500 p-12 rounded-3xl text-center w-full shadow-[0_0_50px_rgba(239,68,68,0.3)] animate-in zoom-in-95 duration-500">
            <AlertCircle size={100} className="text-red-400 mx-auto mb-8" />
            <h2 className="text-4xl font-bold text-white mb-4">MOHON MAAF</h2>
            <p className="text-2xl text-red-100">{error}</p>
          </div>
        )}

        {product && (
          <div className="bg-white rounded-[2.5rem] w-full overflow-hidden shadow-[0_30px_100px_rgba(0,0,0,0.5)] animate-in zoom-in-95 duration-500 flex flex-col md:flex-row relative">
            {/* Left Image Section */}
            <div className="md:w-5/12 bg-slate-50 p-12 flex items-center justify-center relative border-r border-slate-100">
              {product.is_promo && (
                <div className="absolute top-8 left-8 bg-gradient-to-r from-red-600 to-rose-500 text-white px-6 py-2 rounded-full font-black text-lg shadow-lg flex items-center gap-2 z-10 transform -rotate-2">
                  <Tag size={20} />
                  PROMO
                </div>
              )}
              <ProductImage 
                src={product.image_url} 
                alt={product.name} 
                className="w-full h-auto max-h-[500px] object-contain drop-shadow-2xl transition-transform hover:scale-105 duration-500 rounded-2xl bg-white"
                fallbackIconSize={100}
              />
            </div>

            {/* Right Details Section */}
            <div className="md:w-7/12 p-12 md:p-16 flex flex-col justify-center">
              <div className="text-sm font-bold tracking-widest text-brand-blue uppercase mb-4 flex items-center gap-2">
                <Info size={18} />
                {product.category?.name || 'Kategori Umum'}
              </div>
              
              <h1 className="text-4xl md:text-6xl font-black text-slate-800 mb-8 leading-tight line-clamp-3">
                {product.name}
              </h1>

              <div className="mb-10">
                {product.is_promo && product.original_price ? (
                  <div className="flex flex-col gap-2">
                    <div className="text-3xl text-slate-400 font-bold line-through decoration-red-500/50 decoration-4">
                      {product.formatted_original_price || formatPrice(product.original_price)}
                    </div>
                    <div className="text-6xl md:text-8xl font-black text-brand-red tracking-tighter drop-shadow-sm">
                      {product.formatted_price || formatPrice(product.selling_price)}
                    </div>
                    {product.applied_promo && (
                      <div className="inline-block mt-4 text-rose-600 font-bold text-xl bg-rose-50 px-6 py-3 rounded-2xl border border-rose-100 self-start">
                        {product.applied_promo.name}
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="text-6xl md:text-8xl font-black text-slate-800 tracking-tighter drop-shadow-sm">
                    {product.formatted_price || formatPrice(product.selling_price)}
                  </div>
                )}
              </div>

              {/* Informational Stock Footer */}
              <div className="mt-auto pt-8 border-t border-slate-100 flex items-center justify-between text-slate-500 text-lg font-medium">
                <div className={`inline-flex items-center gap-3 px-6 py-3 rounded-full text-lg font-bold ${product.stock > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                  <Package size={24} />
                  {product.stock > 0 ? `Stok Tersedia: ${product.stock} Unit` : 'Stok Habis'}
                </div>
                <span className="text-slate-400">Barcode: {product.barcode}</span>
              </div>
            </div>
            
            {/* Auto-close Progress Bar */}
            <div className="absolute bottom-0 left-0 right-0 h-2 bg-slate-100">
              <div className="h-full bg-brand-blue animate-[shrink_15s_linear_forwards]"></div>
            </div>
          </div>
        )}

        {/* Cross-Selling / Recommended */}
        {product && !error && !loading && promoProducts.length > 3 && (
          <div className="w-full mt-12 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-300">
            <h3 className="text-white font-bold text-2xl mb-6 tracking-wide drop-shadow-md flex items-center gap-3">
              <Tag className="text-yellow-400" /> Diskon Spesial Hari Ini
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {promoProducts.slice(0, 3).map((rec: any, idx: number) => (
                <div key={idx} className="bg-white/10 backdrop-blur-md rounded-2xl p-6 flex items-center gap-6 border border-white/10 shadow-xl transition-transform hover:scale-105">
                  <ProductImage src={rec.image_url} alt={rec.name} className="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-white object-cover shadow-inner flex-shrink-0" fallbackIconSize={32} />
                  <div className="flex flex-col">
                    <span className="text-white font-bold text-lg md:text-xl line-clamp-1 mb-1">{rec.name}</span>
                    <div className="flex flex-col">
                      {rec.original_price && (
                        <span className="text-slate-400 line-through text-sm font-semibold">{formatPrice(rec.original_price)}</span>
                      )}
                      <span className="text-brand-green font-black text-2xl">{formatPrice(rec.selling_price)}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

      </div>

      {/* Hidden Input Form for Barcode Scanner */}
      <form onSubmit={handleScan} className="opacity-0 absolute top-0 left-0 w-1 h-1 overflow-hidden pointer-events-none">
        <input 
          ref={inputRef}
          type="text" 
          value={barcode}
          onChange={(e) => setBarcode(e.target.value)}
          onBlur={() => {
            // Keep focus if no modal is open
            if (!showBranchModal) {
              setTimeout(() => inputRef.current?.focus(), 100);
            }
          }}
          autoFocus
          className="opacity-0"
        />
        <button type="submit" className="hidden">Scan</button>
      </form>

      {/* Marquee Promo Section */}
      {!product && !error && promoProducts.length > 0 && (
        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent pt-12 pb-6 z-10 overflow-hidden border-t border-white/5">
          <div className="flex whitespace-nowrap animate-[marquee_25s_linear_infinite] hover:[animation-play-state:paused]">
            {promoProducts.map((promoItem, idx) => (
              <div key={`${promoItem.id}-${idx}`} className="flex-shrink-0 w-max inline-flex items-center gap-6 mx-12 bg-white/10 backdrop-blur-md px-10 py-6 rounded-3xl border-2 border-white/10 shadow-2xl">
                <ProductImage src={promoItem.image_url} alt={promoItem.name} className="w-28 h-28 md:w-32 md:h-32 object-cover rounded-2xl bg-white shadow-inner flex-shrink-0" fallbackIconSize={48} />
                <div className="flex flex-col">
                  <span className="text-white font-black text-2xl md:text-3xl mb-2 tracking-wide">{promoItem.name}</span>
                  <div className="flex items-center gap-4">
                    {promoItem.original_price && (
                      <span className="text-slate-400 line-through text-lg md:text-xl font-semibold">{formatPrice(promoItem.original_price)}</span>
                    )}
                    <span className="text-brand-green font-black text-3xl md:text-5xl drop-shadow-sm">{formatPrice(promoItem.selling_price)}</span>
                  </div>
                </div>
              </div>
            ))}
            {/* Duplicate for infinite loop effect */}
            {promoProducts.map((promoItem, idx) => (
              <div key={`dup-${promoItem.id}-${idx}`} className="flex-shrink-0 w-max inline-flex items-center gap-6 mx-12 bg-white/10 backdrop-blur-md px-10 py-6 rounded-3xl border-2 border-white/10 shadow-2xl">
                <ProductImage src={promoItem.image_url} alt={promoItem.name} className="w-28 h-28 md:w-32 md:h-32 object-cover rounded-2xl bg-white shadow-inner flex-shrink-0" fallbackIconSize={48} />
                <div className="flex flex-col">
                  <span className="text-white font-black text-2xl md:text-3xl mb-2 tracking-wide">{promoItem.name}</span>
                  <div className="flex items-center gap-4">
                    {promoItem.original_price && (
                      <span className="text-slate-400 line-through text-lg md:text-xl font-semibold">{formatPrice(promoItem.original_price)}</span>
                    )}
                    <span className="text-brand-green font-black text-3xl md:text-5xl drop-shadow-sm">{formatPrice(promoItem.selling_price)}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Branch Selection Modal */}
      {showBranchModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
          <div className="bg-white max-w-2xl w-full rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-200">
            <div className="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
              <h3 className="font-bold text-slate-800 text-2xl flex items-center gap-3">
                <MapPin className="text-brand-blue" size={28} />
                Pilih Lokasi Cabang Kiosk
              </h3>
              <button 
                onClick={() => {
                  setShowBranchModal(false);
                  setTimeout(() => inputRef.current?.focus(), 100);
                }}
                className="p-2 rounded-xl hover:bg-slate-200 text-slate-500 transition-colors"
              >
                <X size={24} />
              </button>
            </div>
            <div className="p-6 max-h-[60vh] overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-4">
              {branches.map((branch: Branch) => (
                <button
                  key={branch.id}
                  onClick={() => {
                    setSelectedBranch(branch);
                    setActiveBranchId(branch.id);
                    setSearchParams({ branch_id: branch.id });
                    setShowBranchModal(false);
                    setProduct(null);
                    setTimeout(() => inputRef.current?.focus(), 100);
                  }}
                  className={`text-left p-6 rounded-2xl border-2 transition-all ${
                    activeBranchId === branch.id
                      ? 'border-brand-blue bg-brand-blue/5 ring-4 ring-brand-blue/10'
                      : 'border-slate-100 hover:border-brand-blue/30 hover:bg-slate-50'
                  }`}
                >
                  <h4 className="font-bold text-slate-800 text-xl mb-1">{branch.name}</h4>
                  <p className="text-sm text-slate-500 leading-relaxed">{branch.address}</p>
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Virtual Keyboard Modal */}
      {showSearchModal && (
        <VirtualKeyboard 
          onClose={() => {
            setShowSearchModal(false);
            setTimeout(() => inputRef.current?.focus(), 100);
          }}
          onSearch={handleSearchManual}
        />
      )}

      {/* Virtual Numpad Member Modal */}
      {showMemberModal && (
        <VirtualNumpad 
          onClose={() => {
            setShowMemberModal(false);
            setTimeout(() => inputRef.current?.focus(), 100);
          }}
        />
      )}

      <style>{`
        @keyframes shrink {
          from { width: 100%; }
          to { width: 0%; }
        }
        @keyframes marquee {
          0% { transform: translateX(0%); }
          100% { transform: translateX(-50%); }
        }
      `}</style>
    </div>
  );
};

export default PriceChecker;
