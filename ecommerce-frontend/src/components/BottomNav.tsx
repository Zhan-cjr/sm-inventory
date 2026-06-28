import { Home, Grid, ClipboardList, User, Tag } from 'lucide-react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useEcom } from '../context/EcomContext';

const BottomNav = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { 
    setSelectedCategory, 
    setIsMemberModalOpen,
    setIsCartOpen,
    setIsPpobModalOpen,
    setIsBranchModalOpen,
    setIsCheckoutModalOpen,
    setIsProductModalOpen
  } = useEcom();

  const navItems = [
    { path: '/', icon: <Home size={24} />, label: 'Home' },
    { path: '#promo', icon: <Tag size={24} />, label: 'Promo', isAction: true },
    { path: '#kategori', icon: <Grid size={24} />, label: 'Kategori', isAction: true },
    { path: '/pesanan', icon: <ClipboardList size={24} />, label: 'Pesanan' },
    { path: '#akun', icon: <User size={24} />, label: 'Akun', isAction: true },
  ];

  const handleNavClick = (item: any) => {
    // Tutup modal lain
    if (setIsCartOpen) setIsCartOpen(false);
    if (setIsPpobModalOpen) setIsPpobModalOpen(false);
    if (setIsBranchModalOpen) setIsBranchModalOpen(false);
    if (setIsCheckoutModalOpen) setIsCheckoutModalOpen(false);
    if (setIsProductModalOpen) setIsProductModalOpen(false);
    
    if (item.path !== '#akun' && setIsMemberModalOpen) {
      setIsMemberModalOpen(false);
    }

    if (item.isAction) {
      if (item.path === '#kategori') {
        if (location.pathname !== '/') {
          navigate('/');
          setTimeout(() => {
            document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth' });
          }, 100);
        } else {
          document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth' });
        }
      } else if (item.path === '#promo') {
        setSelectedCategory('promo');
        if (location.pathname !== '/') {
          navigate('/');
          setTimeout(() => {
            document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth' });
          }, 100);
        } else {
          document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth' });
        }
      } else if (item.path === '#akun') {
        setIsMemberModalOpen(true);
      }
    } else {
      navigate(item.path);
    }
  };

  return (
    <div className="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-[100] px-1 pb-safe">
      <div className="flex justify-around items-center h-14">
        {navItems.map((item) => {
          const isActive = location.pathname === item.path || (item.isAction && location.hash === item.path);
          return (
            <button
              key={item.path}
              onClick={() => handleNavClick(item)}
              className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${
                isActive ? 'text-brand-blue font-semibold' : 'text-slate-500 hover:text-brand-blue'
              }`}
            >
              <div className={`${isActive ? 'scale-110 transition-transform' : ''}`}>
                {item.icon}
              </div>
              <span className="text-[9px]">{item.label}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
};

export default BottomNav;
