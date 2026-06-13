import { useState, useEffect } from 'react';
import { ShoppingCart, Search, MapPin, User } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useEcom } from '../context/EcomContext';
import axios from 'axios';
import { getImageUrl } from '../utils/api';

const Navbar = () => {
  const {
    cart,
    selectedBranch,
    searchQuery,
    setSearchQuery,
    setIsCartOpen,
    setIsBranchModalOpen,
    setIsMemberModalOpen,
    member,
  } = useEcom();

  const [isScrolled, setIsScrolled] = useState(false);
  const [logoUrl, setLogoUrl] = useState<string | null>(null);
  const [announcement, setAnnouncement] = useState<string | null>(null);
  const [isCartBouncing, setIsCartBouncing] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  useEffect(() => {
    const handleCartItemAdded = () => {
      setIsCartBouncing(true);
      setTimeout(() => setIsCartBouncing(false), 600);
    };
    window.addEventListener('cart-item-added', handleCartItemAdded);
    return () => window.removeEventListener('cart-item-added', handleCartItemAdded);
  }, []);

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await axios.get('/ecommerce/settings');
        if (response.data.logo_url) {
          setLogoUrl(getImageUrl(response.data.logo_url));
        }
        if (response.data.ecommerce_announcement) {
          setAnnouncement(response.data.ecommerce_announcement);
        }
      } catch (error) {
        console.error('Error fetching settings:', error);
      }
    };
    fetchSettings();
  }, []);

  const totalCartCount = cart.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <>
      {announcement && (
        <div className="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-brand-blue to-indigo-900 text-white text-xs py-1.5 px-4 shadow-sm flex items-center gap-2 overflow-hidden select-none">
          <span className="inline-block animate-pulse bg-gradient-to-r from-brand-blue to-indigo-900 z-10 pr-2 flex-shrink-0">📢</span>
          <div className="w-full overflow-hidden relative">
            <span className="animate-marquee font-semibold cursor-pointer">
              {announcement}
            </span>
          </div>
        </div>
      )}
      <nav className={`fixed left-0 right-0 z-50 transition-all duration-300 ${announcement ? 'top-8' : 'top-0'} ${isScrolled ? 'glass-panel-dark py-2 md:py-3' : 'bg-white/80 backdrop-blur-md py-3.5 md:py-5'}`}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          
          {/* Desktop Navbar */}
          <div className="hidden md:flex justify-between items-center">
            {/* Logo Section */}
            <div className="flex-shrink-0 flex items-center gap-2">
              <Link to="/" className="flex items-center gap-2">
                {logoUrl ? (
                  <img src={logoUrl} alt="Logo" className="h-12 w-auto rounded-xl object-contain" />
                ) : (
                  <>
                    <div className="w-10 h-10 rounded-xl bg-brand-red text-white flex items-center justify-center font-bold text-2xl shadow-sm">
                      S
                    </div>
                    <div className="hidden sm:block">
                      <span className="text-xl font-bold text-brand-blue tracking-tight">toserba <span className="text-2xl">Selamat</span></span>
                      <p className="text-[0.6rem] font-semibold text-brand-green tracking-widest uppercase mt-[-4px]">The Moslem Family</p>
                    </div>
                  </>
                )}
              </Link>
            </div>

            {/* Desktop Search & Location */}
            <div className="flex flex-1 max-w-2xl mx-8 items-center gap-4">
              <button 
                onClick={() => setIsBranchModalOpen(true)}
                className="flex items-center gap-2 text-sm text-slate-600 hover:text-brand-blue transition-colors bg-slate-100 px-3 py-2 rounded-lg whitespace-nowrap max-w-[200px] truncate"
              >
                <MapPin size={16} className="text-brand-red" />
                <span className="truncate">{selectedBranch ? selectedBranch.name : 'Cari Cabang Terdekat'}</span>
              </button>
              <div className="relative flex-1">
                <input 
                  type="text" 
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Cari produk halal..." 
                  className="w-full pl-4 pr-10 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all shadow-inner"
                />
                <Search className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
              </div>
            </div>

            {/* Desktop Icons */}
            <div className="flex items-center gap-6">
              <button 
                id="navbar-cart-button-desktop"
                onClick={() => setIsCartOpen(true)}
                className={`relative text-slate-600 hover:text-brand-blue transition-colors group ${isCartBouncing ? 'animate-cart-bounce' : ''}`}
              >
                <ShoppingCart size={24} />
                {totalCartCount > 0 && (
                  <span className="absolute -top-1.5 -right-1.5 bg-brand-red text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    {totalCartCount}
                  </span>
                )}
              </button>
              <button 
                onClick={() => setIsMemberModalOpen(true)}
                className="flex items-center gap-2 text-slate-600 hover:text-brand-blue transition-colors font-medium text-sm"
              >
                <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                  <User size={18} />
                </div>
                <span>{member ? `Hai, ${member.name.split(' ')[0]} (${member.points} Poin)` : 'Masuk / Daftar'}</span>
              </button>
            </div>
          </div>

          {/* Mobile Navbar */}
          <div className="md:hidden flex flex-col gap-2.5">
            {/* Top Row */}
            <div className="flex justify-between items-center">
              {/* Logo */}
              <Link to="/" className="flex items-center gap-1.5">
                {logoUrl ? (
                  <img src={logoUrl} alt="Logo" className="h-9 w-auto rounded-lg object-contain" />
                ) : (
                  <div className="flex items-center gap-1.5">
                    <div className="w-8 h-8 rounded-lg bg-brand-red text-white flex items-center justify-center font-bold text-lg shadow-sm">
                      S
                    </div>
                    <span className="text-sm font-bold text-brand-blue tracking-tight">Toserba <span className="text-brand-red">Selamat</span></span>
                  </div>
                )}
              </Link>

              {/* Action Buttons */}
              <div className="flex items-center gap-3">
                {/* Branch Selection pill */}
                <button 
                  onClick={() => setIsBranchModalOpen(true)}
                  className="flex items-center gap-1 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors px-2.5 py-1.5 rounded-full max-w-[120px] truncate"
                >
                  <MapPin size={11} className="text-brand-red flex-shrink-0" />
                  <span className="truncate">{selectedBranch ? selectedBranch.name : 'Pilih Cabang'}</span>
                </button>

                {/* Cart Icon */}
                <button 
                  id="navbar-cart-button-mobile"
                  onClick={() => setIsCartOpen(true)}
                  className={`relative text-slate-600 p-1 hover:text-brand-blue transition-colors ${isCartBouncing ? 'animate-cart-bounce' : ''}`}
                >
                  <ShoppingCart size={22} />
                  {totalCartCount > 0 && (
                    <span className="absolute -top-1 -right-1 bg-brand-red text-white text-[8px] font-bold w-4 h-4 rounded-full flex-items-center justify-center">
                      {totalCartCount}
                    </span>
                  )}
                </button>

                {/* User / Member Icon */}
                <button 
                  onClick={() => setIsMemberModalOpen(true)}
                  className="text-slate-600 p-1 hover:text-brand-blue transition-colors"
                >
                  <User size={22} />
                </button>
              </div>
            </div>

            {/* Bottom Search Row */}
            <div className="relative w-full">
              <input 
                type="text" 
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Cari produk halal..." 
                className="w-full pl-3 pr-9 py-2 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:bg-white transition-all shadow-inner"
              />
              <Search className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" size={14} />
            </div>
          </div>

        </div>
      </nav>
    </>
  );
};

export default Navbar;
