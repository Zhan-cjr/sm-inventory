import { useState, useEffect } from 'react';
import { ShoppingCart, Search, MapPin, User, Bell } from 'lucide-react';
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
      <nav className={`fixed left-0 right-0 z-[100] transition-all duration-300 top-0 ${isScrolled ? 'glass-nav py-2.5 shadow-sm' : 'bg-white/95 backdrop-blur-md py-3 md:py-4 border-b border-slate-100'}`}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          
          {/* Desktop Navbar */}
          <div className="hidden md:flex justify-between items-center gap-6">
            
            {/* Logo Section */}
            <div className="flex-shrink-0 flex items-center gap-2">
              <Link to="/" className="flex items-center gap-3 group">
                {logoUrl ? (
                  <img src={logoUrl} alt="Logo" className="h-11 w-auto rounded-xl object-contain" />
                ) : (
                  <>
                    <div className="w-10 h-10 rounded-xl gradient-bg-emerald text-white flex items-center justify-center font-black text-xl shadow-md group-hover:scale-105 transition-transform badge-glow-emerald">
                      S
                    </div>
                    <div className="hidden sm:block">
                      <span className="text-xl font-black text-slate-800 tracking-tight">toserba <span className="gradient-text-emerald">Selamat</span></span>
                      <p className="text-[0.65rem] font-bold text-amber-500 tracking-wider uppercase mt-[-4px]">Hypermarket & Grocery</p>
                    </div>
                  </>
                )}
              </Link>
            </div>

            {/* Desktop Branch Selector & Search Bar */}
            <div className="flex flex-1 max-w-3xl items-center gap-3">
              <button 
                onClick={() => setIsBranchModalOpen(true)}
                className="flex items-center gap-2 text-xs font-semibold text-emerald-800 bg-emerald-50/90 border border-emerald-200/80 hover:bg-emerald-100 transition-all px-3.5 py-2.5 rounded-xl whitespace-nowrap max-w-[210px] shadow-xs cursor-pointer"
              >
                <MapPin size={15} className="text-emerald-600 animate-pulse" />
                <span className="truncate">{selectedBranch ? selectedBranch.name : 'Pilih Cabang Toko'}</span>
              </button>

              <div className="relative flex-1">
                <input 
                  type="text" 
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Cari sembako, makanan, produk kesegaran..." 
                  className="w-full pl-4 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all text-sm text-slate-800 font-medium placeholder:text-slate-400 shadow-inner"
                />
                <button className="absolute right-1.5 top-1/2 -translate-y-1/2 gradient-bg-emerald text-white p-1.5 rounded-lg hover:opacity-95 transition-opacity cursor-pointer">
                  <Search size={16} />
                </button>
              </div>
            </div>

            {/* Desktop Action Icons */}
            <div className="flex items-center gap-5">
              
              {/* Member Profile */}
              <button 
                onClick={() => setIsMemberModalOpen(true)}
                className="flex items-center gap-2.5 text-slate-700 hover:text-emerald-700 transition-colors font-semibold text-sm bg-slate-50 hover:bg-slate-100 p-1.5 pr-3 rounded-xl border border-slate-200/60 cursor-pointer"
              >
                <div className="w-8 h-8 rounded-lg gradient-bg-emerald text-white flex items-center justify-center font-bold shadow-xs">
                  <User size={16} />
                </div>
                <div className="text-left leading-tight">
                  <div className="text-xs font-bold text-slate-800">{member ? member.name.split(' ')[0] : 'Masuk Akun'}</div>
                  <div className="text-[10px] text-amber-600 font-semibold">{member ? `${member.points} Poin Reward` : 'Member Diskon'}</div>
                </div>
              </button>

              {/* Cart Button */}
              <button 
                id="navbar-cart-button-desktop"
                onClick={() => setIsCartOpen(true)}
                className={`relative flex items-center gap-2 text-white gradient-bg-emerald hover:opacity-95 transition-all px-4 py-2.5 rounded-xl font-bold text-sm shadow-md cursor-pointer ${isCartBouncing ? 'animate-cart-bounce' : ''}`}
              >
                <ShoppingCart size={18} />
                <span className="hidden lg:inline">Keranjang</span>
                {totalCartCount > 0 && (
                  <span className="bg-amber-400 text-slate-950 text-xs font-black px-2 py-0.5 rounded-full shadow-xs">
                    {totalCartCount}
                  </span>
                )}
              </button>
            </div>
          </div>

          {/* Mobile Navbar */}
          <div className="md:hidden flex flex-col gap-2.5">
            <div className="flex items-center justify-between gap-2">
              <Link to="/" className="flex items-center gap-2">
                {logoUrl ? (
                  <img src={logoUrl} alt="Logo" className="h-9 w-auto rounded-xl object-contain" />
                ) : (
                  <>
                    <div className="w-8 h-8 rounded-lg gradient-bg-emerald text-white flex items-center justify-center font-black text-base shadow-xs">
                      S
                    </div>
                    <span className="text-base font-black text-slate-800">toserba <span className="gradient-text-emerald">Selamat</span></span>
                  </>
                )}
              </Link>

              <button 
                onClick={() => setIsBranchModalOpen(true)}
                className="flex items-center gap-1.5 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-1.5 rounded-lg truncate max-w-[140px]"
              >
                <MapPin size={13} className="text-emerald-600 flex-shrink-0" />
                <span className="truncate">{selectedBranch ? selectedBranch.name : 'Cabang'}</span>
              </button>
            </div>

            <div className="flex items-center gap-2">
              {/* Mobile Search Bar */}
              <div className="relative flex-1">
                <input 
                  type="text" 
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Cari produk di Selamat..." 
                  className="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500 text-slate-800"
                />
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={14} />
              </div>

              {/* Mobile Icons */}
              <div className="flex items-center gap-2">
                <button 
                  onClick={() => {
                    if (member) {
                      setIsNotificationsOpen(!isNotificationsOpen);
                    } else {
                      setIsMemberModalOpen(true);
                    }
                  }} 
                  className="relative p-2 bg-slate-100 rounded-xl text-slate-700 hover:text-emerald-700 transition-colors"
                >
                  <Bell size={18} />
                  {unreadNotificationCount > 0 && (
                    <span className="absolute -top-1 -right-1 bg-rose-600 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center border border-white">
                      {unreadNotificationCount > 99 ? '99+' : unreadNotificationCount}
                    </span>
                  )}
                </button>

                <button 
                  id="navbar-cart-button-mobile"
                  onClick={() => setIsCartOpen(true)}
                  className={`relative p-2 gradient-bg-emerald text-white rounded-xl shadow-xs ${isCartBouncing ? 'animate-cart-bounce' : ''}`}
                >
                  <ShoppingCart size={18} />
                  {totalCartCount > 0 && (
                    <span className="absolute -top-1.5 -right-1.5 bg-amber-400 text-slate-950 text-[10px] font-extrabold w-4 h-4 rounded-full flex items-center justify-center border border-white">
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
            className="fixed inset-0 bg-slate-900/20 backdrop-blur-xs z-[105]" 
            onClick={() => setIsNotificationsOpen(false)}
          />
          <div className="fixed top-16 right-4 md:right-24 md:top-20 w-[90vw] max-w-sm bg-white rounded-2xl shadow-2xl z-[110] border border-slate-100 flex flex-col overflow-hidden animate-scale-up">
            <div className="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
              <h3 className="font-extrabold text-slate-800">Notifikasi</h3>
              {unreadNotificationCount > 0 && (
                <button 
                  onClick={markAllNotificationsAsRead}
                  className="text-xs font-bold text-emerald-600 hover:text-emerald-700 transition-colors"
                >
                  Tandai Dibaca
                </button>
              )}
            </div>
            
            <div className="max-h-[60vh] overflow-y-auto">
              {notifications.length === 0 ? (
                <div className="p-8 text-center flex flex-col items-center">
                  <div className="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 mb-3">
                    <Bell size={24} />
                  </div>
                  <p className="text-sm font-semibold text-slate-500">Belum ada notifikasi</p>
                  <p className="text-xs text-slate-400 mt-1">Notifikasi pesanan dan promo akan muncul di sini.</p>
                </div>
              ) : (
                <div className="divide-y divide-slate-100">
                  {notifications.map((notif) => (
                    <div 
                      key={notif.id} 
                      onClick={() => !notif.is_read && markNotificationAsRead(notif.id)}
                      className={`p-4 hover:bg-slate-50 transition-colors cursor-pointer flex gap-3 ${!notif.is_read ? 'bg-emerald-50/40' : ''}`}
                    >
                      <div className={`w-2 h-2 rounded-full mt-1.5 flex-shrink-0 ${!notif.is_read ? 'bg-emerald-600' : 'bg-transparent'}`} />
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
