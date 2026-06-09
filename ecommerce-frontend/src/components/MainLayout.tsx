import { Outlet, useLocation } from 'react-router-dom';
import Navbar from './Navbar';
import Footer from './Footer';
import { CartDrawer } from './CartDrawer';
import { CheckoutModal } from './CheckoutModal';
import { BranchModal } from './BranchModal';
import MemberModal from './MemberModal';
import ProductDetailModal from './ProductDetailModal';

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
    <div className="min-h-screen flex flex-col font-sans">
      <Navbar />
      <main className="flex-grow pt-36 md:pt-32">
        <Outlet />
      </main>
      <Footer />
      
      {/* Global UI Elements */}
      <CartDrawer />
      <CheckoutModal />
      <BranchModal />
      <MemberModal />
      <ProductDetailModal />
    </div>
  );
};
