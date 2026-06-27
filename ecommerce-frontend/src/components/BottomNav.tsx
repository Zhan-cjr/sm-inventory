import { Home, Tag, ClipboardList, User } from 'lucide-react';
import { useLocation, useNavigate } from 'react-router-dom';

const BottomNav = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const navItems = [
    { path: '/', icon: <Home size={24} />, label: 'Home' },
    { path: '/promo', icon: <Tag size={24} />, label: 'Promo' },
    { path: '/pesanan', icon: <ClipboardList size={24} />, label: 'Pesanan' },
    { path: '/akun', icon: <User size={24} />, label: 'Akun' },
  ];

  return (
    <div className="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50 px-2 pb-safe">
      <div className="flex justify-around items-center h-14">
        {navItems.map((item) => {
          const isActive = location.pathname === item.path;
          return (
            <button
              key={item.path}
              onClick={() => navigate(item.path)}
              className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${
                isActive ? 'text-brand-blue font-semibold' : 'text-slate-500 hover:text-brand-blue'
              }`}
            >
              <div className={`${isActive ? 'scale-110 transition-transform' : ''}`}>
                {item.icon}
              </div>
              <span className="text-[10px]">{item.label}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
};

export default BottomNav;
