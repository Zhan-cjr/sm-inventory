
import DiscountedProductsCarousel from '../components/DiscountedProductsCarousel';
import CategoryIcons from '../components/CategoryIcons';
import ProductGrid from '../components/ProductGrid';
import { useEcom } from '../context/EcomContext';
import { ChevronRight } from 'lucide-react';

const Home = () => {
  const { member, setIsMemberModalOpen } = useEcom();

  return (
    <div className="flex flex-col min-h-screen bg-slate-50 overflow-x-hidden">
      {/* 1. Promo Banners Carousel */}
      <div className="w-full bg-white">
        <DiscountedProductsCarousel />
      </div>

      {/* 2. User Greeting Widget */}
      <div className="bg-white px-4 py-3 mb-2 mt-[-10px] relative z-10 rounded-t-2xl shadow-sm border-b border-slate-100 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-[0.8rem] bg-brand-red flex items-center justify-center border-2 border-white shadow-sm overflow-hidden">
            <span className="text-white font-black text-xl italic mt-0.5">S</span>
          </div>
          <div className="flex flex-col">
            <h3 className="text-sm font-bold text-slate-800">
              Hai, {member ? member.name.split(' ')[0] : 'Sahabat'}!
            </h3>
            <p className="text-[11px] text-slate-500">
              {member ? 'Selamat berbelanja kembali~' : 'Akses semua fitur, yuk~'}
            </p>
          </div>
        </div>
        {!member && (
          <button 
            onClick={() => setIsMemberModalOpen(true)}
            className="bg-brand-green text-white text-xs font-bold px-4 py-1.5 rounded-lg shadow-sm"
          >
            Masuk
          </button>
        )}
        {member && (
          <div className="bg-brand-blue/10 text-brand-blue text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 cursor-pointer">
            <span className="text-amber-500">🏆</span> {member.points} Poin <ChevronRight size={14} />
          </div>
        )}
      </div>

      {/* 3. Category Icons (Horizontal Scroll) */}
      <div className="bg-white py-4 mb-2 shadow-sm">
        <CategoryIcons />
      </div>

      {/* 4. Product Grid (Includes Tabs) */}
      <div className="bg-slate-50 flex-grow pt-2">
        <ProductGrid />
      </div>
    </div>
  );
};

export default Home;
