import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { useEcom, Branch } from '../context/EcomContext';
import { useSearchParams } from 'react-router-dom';
import { 
  ScanBarcode, 
  AlertCircle, 
  Info, 
  Building2, 
  Tag, 
  ChevronDown, 
  MapPin, 
  X, 
  Keyboard, 
  CreditCard, 
  Package, 
  Maximize, 
  Minimize,
  Sparkles,
  Sun,
  Moon
} from 'lucide-react';
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
  
  // Kiosk & Theme States
  const [theme, setTheme] = useState<'dark' | 'light'>(() => {
    return (localStorage.getItem('kiosk_theme') as 'dark' | 'light') || 'dark';
  });
  const [showSearchModal, setShowSearchModal] = useState(false);
  const [showMemberModal, setShowMemberModal] = useState(false);
  const [bgImageIndex, setBgImageIndex] = useState(0);
  const [isFullscreen, setIsFullscreen] = useState(false);

  const inputRef = useRef<HTMLInputElement>(null);

  const toggleTheme = () => {
    const newTheme = theme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
    localStorage.setItem('kiosk_theme', newTheme);
  };

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

  // Keep focus on hidden barcode scanner input
  useEffect(() => {
    const focusInterval = setInterval(() => {
      if (inputRef.current && document.activeElement !== inputRef.current && !showBranchModal && !showSearchModal && !showMemberModal) {
        inputRef.current.focus();
      }
    }, 1000);
    return () => clearInterval(focusInterval);
  }, [showBranchModal, showSearchModal, showMemberModal]);

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
    }, 15000);
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
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.1);
      } else {
        oscillator.type = 'sawtooth';
        oscillator.frequency.setValueAtTime(150, audioCtx.currentTime);
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
    setBarcode(keyword);
    
    setLoading(true);
    setError(null);
    setProduct(null);

    try {
      const response = await axios.get('/ecommerce/products', {
        params: {
          search: keyword.trim(),
          ...(activeBranchId ? { branch_id: activeBranchId } : {})
        }
      });
      if (response.data && response.data.length > 0) {
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

  const isDark = theme === 'dark';

  return (
    <div className={`min-h-screen ${isDark ? 'bg-slate-950 text-slate-100' : 'bg-slate-100 text-slate-900'} flex flex-col items-center justify-center relative overflow-hidden font-sans select-none transition-colors duration-300`}>
      
      {/* Ambient Lighting & Background Effects */}
      <div className="absolute inset-0 z-0 opacity-40 pointer-events-none transition-all duration-1000">
        {!product && !error && promoProducts.length > 0 ? (
           <div className="absolute inset-0 animate-in fade-in duration-1000">
              <ProductImage 
                src={promoProducts[bgImageIndex]?.image_url} 
                alt="Promo Background" 
                className="w-full h-full object-cover opacity-15 scale-105 filter blur-sm" 
              />
              <div className={`absolute inset-0 bg-gradient-to-t ${isDark ? 'from-slate-950 via-slate-950/80' : 'from-slate-100 via-slate-100/80'} to-transparent`}></div>
           </div>
        ) : (
          <>
            <div className={`absolute top-[-10%] left-[-10%] w-[55%] h-[55%] ${isDark ? 'bg-emerald-600/30' : 'bg-emerald-400/20'} rounded-full mix-blend-screen filter blur-[120px] animate-pulse`}></div>
            <div className={`absolute bottom-[-10%] right-[-10%] w-[55%] h-[55%] ${isDark ? 'bg-indigo-600/30' : 'bg-indigo-400/20'} rounded-full mix-blend-screen filter blur-[120px] animate-pulse`} style={{ animationDelay: '2s' }}></div>
          </>
        )}
      </div>

      {/* Header Bar */}
      <header className="absolute top-0 left-0 right-0 p-6 z-20 flex justify-between items-center">
        <div className="flex items-center gap-4">
          <button 
            onClick={() => setShowBranchModal(true)}
            className={`flex items-center gap-3 ${isDark ? 'bg-white/10 hover:bg-white/20 border-white/15 text-white' : 'bg-slate-900/10 hover:bg-slate-900/15 border-slate-300 text-slate-900'} transition-all px-5 py-3 rounded-2xl backdrop-blur-xl border cursor-pointer shadow-xl active:scale-95`}
          >
            <Building2 size={22} className="text-emerald-500" />
            <div className="flex flex-col text-left">
              <span className={`text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'} font-medium uppercase tracking-wider`}>Lokasi Kiosk</span>
              <span className="text-lg font-black tracking-wide uppercase leading-tight">{activeBranchName}</span>
            </div>
            <ChevronDown size={18} className="opacity-70 ml-1" />
          </button>

          {/* Theme Toggle Button */}
          <button 
            onClick={toggleTheme}
            className={`flex items-center gap-2 ${isDark ? 'bg-white/10 hover:bg-white/20 border-white/15 text-amber-400' : 'bg-slate-900/10 hover:bg-slate-900/15 border-slate-300 text-amber-600'} transition-all p-3.5 rounded-2xl backdrop-blur-xl border cursor-pointer shadow-xl active:scale-95`}
            title={isDark ? "Mode Terang" : "Mode Gelap"}
            aria-label="Toggle Theme"
          >
            {isDark ? <Sun size={20} /> : <Moon size={20} />}
          </button>

          {/* Fullscreen Button */}
          <button 
            onClick={toggleFullscreen}
            className={`${isDark ? 'bg-white/10 hover:bg-white/20 border-white/15 text-white' : 'bg-slate-900/10 hover:bg-slate-900/15 border-slate-300 text-slate-900'} transition-all p-3.5 rounded-2xl backdrop-blur-xl border cursor-pointer shadow-xl active:scale-95`}
            title="Toggle Fullscreen"
          >
            {isFullscreen ? <Minimize size={20} /> : <Maximize size={20} />}
          </button>
        </div>

        {/* Live Clock & Status */}
        <div className="flex items-center gap-4">
          <div className="hidden md:flex items-center gap-2 bg-emerald-500/15 border border-emerald-500/30 px-4 py-2 rounded-xl text-emerald-500 font-bold text-xs tracking-wider">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            KIOSK ONLINE
          </div>
          
          <div className={`flex flex-col items-end ${isDark ? 'bg-white/5 border-white/10 text-white' : 'bg-slate-900/5 border-slate-200 text-slate-900'} border px-5 py-2.5 rounded-2xl backdrop-blur-xl shadow-lg`}>
            <div className="text-2xl font-black tracking-wider font-mono">
              {currentTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
            </div>
            <div className={`text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'} uppercase tracking-widest font-semibold`}>
              {currentTime.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
            </div>
          </div>
        </div>
      </header>

      {/* Main Kiosk Viewport */}
      <div className={`z-10 w-full max-w-5xl px-6 flex flex-col items-center transition-all duration-700 ${(!product && !error && promoProducts.length > 0) ? 'mb-40 md:mb-52' : ''}`}>
        
        {/* IDLE SCANNER HUB */}
        {!product && !error && !loading && (
          <div className="text-center animate-in fade-in slide-in-from-bottom-8 duration-700 flex flex-col items-center">
            
            {/* Viewfinder Reticle Frame */}
            <div className="relative mb-10 group cursor-pointer" onClick={() => inputRef.current?.focus()}>
              <div className="absolute -inset-4 bg-gradient-to-r from-emerald-500 to-indigo-500 rounded-[3rem] blur-xl opacity-30 group-hover:opacity-50 transition duration-500"></div>
              
              <div className={`relative ${isDark ? 'bg-slate-900/90 border-emerald-500/50 shadow-[0_0_60px_rgba(16,185,129,0.25)]' : 'bg-white/95 border-emerald-500/60 shadow-[0_10px_40px_rgba(16,185,129,0.2)]'} border-2 p-12 md:p-14 rounded-[2.5rem] backdrop-blur-2xl flex flex-col items-center overflow-hidden transition-colors duration-300`}>
                {/* Pulsing Laser Bar */}
                <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent shadow-[0_0_15px_#10b981] animate-[scanLaser_2.5s_ease-in-out_infinite]"></div>
                
                <ScanBarcode size={110} className="text-emerald-500 transition-transform group-hover:scale-110 duration-300" strokeWidth={1.5} />
              </div>
            </div>

            <h1 className={`text-5xl md:text-7xl font-black ${isDark ? 'text-white' : 'text-slate-900'} mb-5 tracking-tight drop-shadow-2xl`}>
              CEK HARGA & PROMO
            </h1>
            <p className={`text-xl md:text-2xl ${isDark ? 'text-slate-300' : 'text-slate-600'} font-medium max-w-2xl leading-relaxed drop-shadow-md mb-10`}>
              Dekatkan barcode produk ke alat pemindai (scanner) di bawah layar untuk melihat harga dan diskon terbaru.
            </p>
            
            {/* Action Triggers */}
            <div className="flex items-center gap-5 flex-wrap justify-center">
              <button 
                onClick={() => setShowSearchModal(true)}
                className={`flex items-center gap-3 ${isDark ? 'bg-white/10 hover:bg-white/20 border-white/20 text-white' : 'bg-slate-900/10 hover:bg-slate-900/15 border-slate-300 text-slate-900'} border active:scale-95 px-7 py-4 rounded-2xl font-extrabold tracking-wide transition-all shadow-xl backdrop-blur-md`}
              >
                <Keyboard size={22} className="text-indigo-500" />
                CARI MANUAL (KEYBOARD)
              </button>
              <button 
                onClick={() => setShowMemberModal(true)}
                className={`flex items-center gap-3 ${isDark ? 'bg-emerald-500/20 hover:bg-emerald-500/30 border-emerald-500/40 text-white' : 'bg-emerald-500/15 hover:bg-emerald-500/25 border-emerald-500/30 text-emerald-950'} border active:scale-95 px-7 py-4 rounded-2xl font-extrabold tracking-wide transition-all shadow-xl backdrop-blur-md`}
              >
                <CreditCard size={22} className="text-emerald-500" />
                CEK POIN MEMBER
              </button>
            </div>
          </div>
        )}

        {/* LOADING STATE */}
        {loading && (
          <div className="flex flex-col items-center animate-in fade-in duration-300">
            <div className="relative mb-6">
              <div className="w-24 h-24 rounded-full border-4 border-emerald-500/20 border-t-emerald-500 animate-spin"></div>
              <div className="absolute inset-0 flex items-center justify-center">
                <Sparkles size={32} className="text-emerald-500 animate-pulse" />
              </div>
            </div>
            <h2 className={`text-3xl font-black ${isDark ? 'text-white' : 'text-slate-900'} tracking-widest`}>MEMINDAI DATA PRODUK...</h2>
          </div>
        )}

        {/* ERROR STATE */}
        {error && (
          <div className={`${isDark ? 'bg-rose-950/80 border-rose-500/50 text-rose-200' : 'bg-rose-50 border-rose-300 text-rose-800'} backdrop-blur-2xl border-2 p-10 rounded-[2.5rem] text-center w-full shadow-2xl animate-in zoom-in-95 duration-500`}>
            <AlertCircle size={80} className="text-rose-500 mx-auto mb-6" />
            <h2 className={`text-3xl md:text-4xl font-black ${isDark ? 'text-white' : 'text-slate-900'} mb-3`}>BARANG TIDAK DITEMUKAN</h2>
            <p className="text-xl max-w-xl mx-auto">{error}</p>
          </div>
        )}

        {/* SCANNED PRODUCT CARD RESULT */}
        {product && (
          <div className={`${isDark ? 'bg-slate-900/90 border-white/15 text-white shadow-[0_30px_100px_rgba(0,0,0,0.6)]' : 'bg-white border-slate-200 text-slate-900 shadow-[0_20px_70px_rgba(0,0,0,0.15)]'} border rounded-[2.5rem] w-full overflow-hidden backdrop-blur-2xl animate-in zoom-in-95 duration-500 flex flex-col md:flex-row relative transition-colors duration-300`}>
            
            {/* Left Product Image Section */}
            <div className={`md:w-5/12 ${isDark ? 'bg-slate-950/50 border-slate-800' : 'bg-slate-50 border-slate-100'} p-10 flex items-center justify-center relative border-r`}>
              {product.is_promo && (
                <div className="absolute top-6 left-6 bg-gradient-to-r from-rose-600 to-red-500 text-white px-5 py-2 rounded-full font-black text-base shadow-xl flex items-center gap-2 z-10 transform -rotate-2">
                  <Tag size={18} />
                  PROMO HEBOH
                </div>
              )}
              <ProductImage 
                src={product.image_url} 
                alt={product.name} 
                className="w-full h-auto max-h-[440px] object-contain drop-shadow-2xl transition-transform hover:scale-105 duration-500 rounded-2xl"
                fallbackIconSize={100}
              />
            </div>

            {/* Right Details Section */}
            <div className="md:w-7/12 p-10 md:p-14 flex flex-col justify-between">
              <div>
                <div className="text-xs font-black tracking-widest text-emerald-500 uppercase mb-3 flex items-center gap-2">
                  <Info size={16} />
                  {product.ecommerce_category || product.category?.name || 'Kategori Produk'}
                </div>
                
                <h1 className={`text-3xl md:text-5xl font-black ${isDark ? 'text-white' : 'text-slate-900'} mb-6 leading-tight line-clamp-3`}>
                  {product.name}
                </h1>

                <div className="mb-8">
                  {product.is_promo && product.original_price ? (
                    <div className="flex flex-col gap-1">
                      <div className={`text-2xl md:text-3xl ${isDark ? 'text-slate-400' : 'text-slate-400'} font-bold line-through decoration-rose-500/60 decoration-4`}>
                        {product.formatted_original_price || formatPrice(product.original_price)}
                      </div>
                      <div className="text-5xl md:text-7xl font-black text-emerald-500 tracking-tight drop-shadow-lg">
                        {product.formatted_price || formatPrice(product.selling_price)}
                      </div>
                      {product.applied_promo && (
                        <div className={`inline-block mt-3 ${isDark ? 'text-rose-300 bg-rose-950/60 border-rose-800/60' : 'text-rose-700 bg-rose-50 border-rose-200'} font-extrabold text-lg px-5 py-2 rounded-xl border self-start`}>
                          🏷️ {product.applied_promo.name}
                        </div>
                      )}
                    </div>
                  ) : (
                    <div className="text-5xl md:text-7xl font-black text-emerald-500 tracking-tight drop-shadow-lg">
                      {product.formatted_price || formatPrice(product.selling_price)}
                    </div>
                  )}
                </div>
              </div>

              {/* Informational Stock Footer */}
              <div className={`pt-6 border-t ${isDark ? 'border-white/10 text-slate-400' : 'border-slate-100 text-slate-600'} flex items-center justify-between text-base font-medium`}>
                <div className={`inline-flex items-center gap-2.5 px-5 py-2.5 rounded-full text-base font-extrabold ${product.stock > 0 ? (isDark ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-emerald-100 text-emerald-800 border border-emerald-200') : (isDark ? 'bg-rose-500/20 text-rose-400 border border-rose-500/40' : 'bg-rose-100 text-rose-800 border border-rose-200')}`}>
                  <Package size={20} />
                  {product.stock > 0 ? `Stok Tersedia: ${product.stock} Unit` : 'Stok Habis'}
                </div>
                <span className="font-mono text-sm opacity-80">Barcode: {product.barcode}</span>
              </div>
            </div>
            
            {/* Auto-close Progress Bar */}
            <div className={`absolute bottom-0 left-0 right-0 h-1.5 ${isDark ? 'bg-slate-800' : 'bg-slate-200'}`}>
              <div className="h-full bg-emerald-500 animate-[shrink_15s_linear_forwards]"></div>
            </div>
          </div>
        )}

        {/* CROSS-SELLING PROMO RECOMMENDATIONS */}
        {product && !error && !loading && promoProducts.length > 3 && (
          <div className="w-full mt-10 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-300">
            <h3 className={`${isDark ? 'text-white' : 'text-slate-900'} font-extrabold text-xl mb-4 tracking-wide drop-shadow-md flex items-center gap-2`}>
              <Tag className="text-amber-500" size={20} /> Diskon Spesial Hari Ini
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
              {promoProducts.slice(0, 3).map((rec: any, idx: number) => (
                <div key={idx} className={`${isDark ? 'bg-slate-900/80 border-white/10 text-white' : 'bg-white border-slate-200 text-slate-900'} backdrop-blur-xl rounded-2xl p-4 flex items-center gap-4 border shadow-xl transition-all hover:scale-105`}>
                  <ProductImage src={rec.image_url} alt={rec.name} className="w-20 h-20 rounded-xl bg-white object-cover shadow-inner flex-shrink-0" fallbackIconSize={32} />
                  <div className="flex flex-col min-w-0">
                    <span className="font-bold text-base line-clamp-1 mb-1">{rec.name}</span>
                    <div className="flex flex-col">
                      {rec.original_price && (
                        <span className="text-slate-400 line-through text-xs font-semibold">{formatPrice(rec.original_price)}</span>
                      )}
                      <span className="text-emerald-500 font-black text-xl">{formatPrice(rec.selling_price)}</span>
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
            if (!showBranchModal && !showSearchModal && !showMemberModal) {
              setTimeout(() => inputRef.current?.focus(), 100);
            }
          }}
          autoFocus
          className="opacity-0"
        />
        <button type="submit" className="hidden">Scan</button>
      </form>

      {/* MARQUEE PROMO BANNER AT BOTTOM */}
      {!product && !error && promoProducts.length > 0 && (
        <div className={`absolute bottom-0 left-0 right-0 bg-gradient-to-t ${isDark ? 'from-slate-950 via-slate-950/90' : 'from-slate-100 via-slate-100/90'} to-transparent pt-10 pb-5 z-10 overflow-hidden border-t ${isDark ? 'border-white/10' : 'border-slate-300'}`}>
          <div className="flex whitespace-nowrap animate-[marquee_25s_linear_infinite] hover:[animation-play-state:paused]">
            {promoProducts.map((promoItem, idx) => (
              <div key={`${promoItem.id}-${idx}`} className={`flex-shrink-0 w-max inline-flex items-center gap-5 mx-8 ${isDark ? 'bg-slate-900/90 border-white/15 text-white' : 'bg-white/95 border-slate-200 text-slate-900 shadow-xl'} backdrop-blur-2xl px-8 py-5 rounded-3xl border shadow-2xl`}>
                <ProductImage src={promoItem.image_url} alt={promoItem.name} className="w-24 h-24 md:w-28 md:h-28 object-cover rounded-2xl bg-white shadow-inner flex-shrink-0" fallbackIconSize={44} />
                <div className="flex flex-col">
                  <span className="font-extrabold text-xl md:text-2xl mb-1 tracking-wide">{promoItem.name}</span>
                  <div className="flex items-center gap-3">
                    {promoItem.original_price && (
                      <span className="text-slate-400 line-through text-base md:text-lg font-semibold">{formatPrice(promoItem.original_price)}</span>
                    )}
                    <span className="text-emerald-500 font-black text-2xl md:text-4xl drop-shadow-sm">{formatPrice(promoItem.selling_price)}</span>
                  </div>
                </div>
              </div>
            ))}
            {/* Duplicate for infinite marquee loop */}
            {promoProducts.map((promoItem, idx) => (
              <div key={`dup-${promoItem.id}-${idx}`} className={`flex-shrink-0 w-max inline-flex items-center gap-5 mx-8 ${isDark ? 'bg-slate-900/90 border-white/15 text-white' : 'bg-white/95 border-slate-200 text-slate-900 shadow-xl'} backdrop-blur-2xl px-8 py-5 rounded-3xl border shadow-2xl`}>
                <ProductImage src={promoItem.image_url} alt={promoItem.name} className="w-24 h-24 md:w-28 md:h-28 object-cover rounded-2xl bg-white shadow-inner flex-shrink-0" fallbackIconSize={44} />
                <div className="flex flex-col">
                  <span className="font-extrabold text-xl md:text-2xl mb-1 tracking-wide">{promoItem.name}</span>
                  <div className="flex items-center gap-3">
                    {promoItem.original_price && (
                      <span className="text-slate-400 line-through text-base md:text-lg font-semibold">{formatPrice(promoItem.original_price)}</span>
                    )}
                    <span className="text-emerald-500 font-black text-2xl md:text-4xl drop-shadow-sm">{formatPrice(promoItem.selling_price)}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Branch Selection Modal */}
      {showBranchModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4">
          <div className={`${isDark ? 'bg-slate-900 border-white/15 text-white' : 'bg-white border-slate-200 text-slate-900'} border max-w-2xl w-full rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-200`}>
            <div className={`p-6 border-b ${isDark ? 'border-white/10 bg-slate-900/50' : 'border-slate-100 bg-slate-50'} flex justify-between items-center`}>
              <h3 className="font-extrabold text-2xl flex items-center gap-3">
                <MapPin className="text-emerald-500" size={28} />
                Pilih Lokasi Cabang Kiosk
              </h3>
              <button 
                onClick={() => {
                  setShowBranchModal(false);
                  setTimeout(() => inputRef.current?.focus(), 100);
                }}
                className={`p-2 rounded-xl ${isDark ? 'hover:bg-white/10 text-slate-400' : 'hover:bg-slate-200 text-slate-500'} transition-colors`}
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
                  className={`text-left p-5 rounded-2xl border-2 transition-all ${
                    activeBranchId === branch.id
                      ? 'border-emerald-500 bg-emerald-500/10 ring-4 ring-emerald-500/20'
                      : (isDark ? 'border-white/10 hover:border-emerald-500/40 hover:bg-white/5' : 'border-slate-200 hover:border-emerald-500/40 hover:bg-slate-50')
                  }`}
                >
                  <h4 className="font-bold text-xl mb-1">{branch.name}</h4>
                  <p className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'} leading-relaxed`}>{branch.address}</p>
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
        @keyframes scanLaser {
          0% { top: 5%; opacity: 0; }
          15% { opacity: 1; }
          85% { opacity: 1; }
          100% { top: 95%; opacity: 0; }
        }
      `}</style>
    </div>
  );
};

export default PriceChecker;
