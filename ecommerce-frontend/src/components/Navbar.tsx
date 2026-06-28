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
    notifications,
    unreadNotificationCount,
    markNotificationAsRead,
    markAllNotificationsAsRead,
  } = useEcom();

  const [isScrolled, setIsScrolled] = useState(false);
  const [logoUrl, setLogoUrl] = useState<string | null>(null);
  const [isCartBouncing, setIsCartBouncing] = useState(false);
  const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);

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
      } catch (error) {
        console.error('Error fetching settings:', error);
      }
    };
    fetchSettings();
  }, []);

  const totalCartCount = cart.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <>
      <nav className={`fixed left-0 right-0 z-[100] transition-all duration-300 top-0 ${isScrolled ? 'glass-panel-dark py-2 md:py-3' : 'bg-white/80 backdrop-blur-md py-3.5 md:py-5'}`}>
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
            <div className="flex items-center gap-3">
              {/* Search Bar */}
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                <input 
                  type="text" 
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Cari di Toserba Selamat" 
                  className="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-brand-blue focus:border-brand-blue transition-all"
                />
              </div>

              {/* Action Icons */}
              <div className="flex items-center gap-3 text-slate-600">
                <button onClick={() => alert('Fitur Kotak Masuk akan segera hadir.')} className="hover:text-brand-blue transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </button>
                <button 
                  onClick={() => {
                    if (member) {
                      setIsNotificationsOpen(!isNotificationsOpen);
                    } else {
                      setIsMemberModalOpen(true);
                    }
                  }} 
                  className="relative hover:text-brand-blue transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                  {unreadNotificationCount > 0 && (
                    <span className="absolute -top-1 -right-1 bg-brand-red text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center border border-white">
                      {unreadNotificationCount > 99 ? '99+' : unreadNotificationCount}
                    </span>
                  )}
                </button>
                <button 
                  id="navbar-cart-button-mobile"
                  onClick={() => setIsCartOpen(true)}
                  className={`relative hover:text-brand-blue transition-colors ${isCartBouncing ? 'animate-cart-bounce' : ''}`}
                >
                  <ShoppingCart size={20} />
                  {totalCartCount > 0 && (
                    <span className="absolute -top-1.5 -right-1.5 bg-brand-red text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center border border-white">
                      {totalCartCount}
                    </span>
                  )}
                </button>
              </div>
            </div>
          </div>

        </div>
      </nav>
      {/* Notifications Dropdown/Modal */}
      {isNotificationsOpen && member && (
        <>
          <div 
            className="fixed inset-0 bg-slate-900/20 backdrop-blur-sm z-[105]" 
            onClick={() => setIsNotificationsOpen(false)}
          />
          <div className="fixed top-16 right-4 md:right-24 md:top-20 w-[90vw] max-w-sm bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl z-[110] border border-slate-100 flex flex-col overflow-hidden animate-scale-up">
            <div className="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
              <h3 className="font-extrabold text-slate-800">Notifikasi</h3>
              {unreadNotificationCount > 0 && (
                <button 
                  onClick={markAllNotificationsAsRead}
                  className="text-[11px] font-bold text-brand-blue hover:text-brand-blue/80 transition-colors"
                >
                  Tandai Semua Dibaca
                </button>
              )}
            </div>
            
            <div className="max-h-[60vh] overflow-y-auto">
              {notifications.length === 0 ? (
                <div className="p-8 text-center flex flex-col items-center">
                  <div className="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                  </div>
                  <p className="text-sm font-semibold text-slate-500">Belum ada notifikasi</p>
                  <p className="text-xs text-slate-400 mt-1">Notifikasi pesanan dan promo akan muncul di sini.</p>
                </div>
              ) : (
                <div className="divide-y divide-slate-50">
                  {notifications.map((notif) => (
                    <div 
                      key={notif.id} 
                      onClick={() => !notif.is_read && markNotificationAsRead(notif.id)}
                      className={`p-4 hover:bg-slate-50 transition-colors cursor-pointer flex gap-3 ${!notif.is_read ? 'bg-brand-blue/5' : ''}`}
                    >
                      <div className={`w-2 h-2 rounded-full mt-1.5 flex-shrink-0 ${!notif.is_read ? 'bg-brand-blue' : 'bg-transparent'}`} />
                      <div>
                        <h4 className={`text-sm ${!notif.is_read ? 'font-bold text-slate-800' : 'font-semibold text-slate-600'}`}>
                          {notif.title}
                        </h4>
                        <p className={`text-xs mt-1 leading-relaxed ${!notif.is_read ? 'text-slate-600' : 'text-slate-500'}`}>
                          {notif.body}
                        </p>
                        <span className="text-[10px] text-slate-400 mt-2 block">
                          {new Date(notif.created_at).toLocaleString('id-ID')}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </>
      )}

    </>
  );
};

export default Navbar;
