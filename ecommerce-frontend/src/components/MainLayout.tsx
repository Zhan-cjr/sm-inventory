import { Outlet, useLocation } from 'react-router-dom';
import Navbar from './Navbar';
import Footer from './Footer';
import { CartDrawer } from './CartDrawer';
import { CheckoutModal } from './CheckoutModal';
import { BranchModal } from './BranchModal';
import MemberModal from './MemberModal';
import ProductDetailModal from './ProductDetailModal';
import BottomNav from './BottomNav';
import PpobWidget from './PpobWidget';

export const MainLayout = () => {
  const location = useLocation();
  const isKiosk = location.pathname.includes('/cek-harga');

  if (isKiosk) {
    return (
      <div className="min-h-screen font-sans bg-slate-50">
        <Outlet />
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col font-sans pb-14 md:pb-0 bg-slate-50">
      <Navbar />
      <main className="flex-grow pt-24 md:pt-28">
        <Outlet />
      </main>
      <div className="hidden md:block">
        <Footer />
      </div>
      <BottomNav />
      
      {/* Global UI Elements */}
      <CartDrawer />
      <CheckoutModal />
      <BranchModal />
      <MemberModal />
      <ProductDetailModal />
      <PpobWidget />
    </div>
  );
};
