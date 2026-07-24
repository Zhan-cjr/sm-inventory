import DiscountedProductsCarousel from '../components/DiscountedProductsCarousel';
import CategoryIcons from '../components/CategoryIcons';
import ProductGrid from '../components/ProductGrid';
import { useEcom } from '../context/EcomContext';
import { ChevronRight, Award, Sparkles } from 'lucide-react';

const Home = () => {
  const { member, setIsMemberModalOpen } = useEcom();

  return (
    <div className="flex flex-col min-h-screen bg-slate-50 overflow-x-hidden">
      
      {/* 1. Hero Promo Banners Carousel */}
      <div className="w-full bg-white">
        <DiscountedProductsCarousel />
      </div>

      {/* 2. User Greeting & Member Rewards Bar */}
      <div className="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-[-14px] relative z-10">
        <div className="bg-white/95 backdrop-blur-md px-4 py-3.5 rounded-2xl shadow-sm border border-slate-200/80 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl gradient-bg-emerald flex items-center justify-center border-2 border-emerald-100 shadow-xs">
              <Sparkles size={20} className="text-white animate-pulse-subtle" />
            </div>
            <div className="flex flex-col">
              <h3 className="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
                Hai, {member ? member.name.split(' ')[0] : 'Pelanggan Setia'}!
              </h3>
              <p className="text-[11px] font-medium text-slate-500">
                {member ? 'Nikmati harga khusus member hari ini' : 'Daftar member gratis & kumpulkan poin diskon'}
              </p>
            </div>
          </div>

          {!member ? (
            <button 
              onClick={() => setIsMemberModalOpen(true)}
              className="gradient-bg-gold text-slate-950 text-xs font-black px-4 py-2 rounded-xl shadow-xs hover:opacity-95 transition-opacity cursor-pointer whitespace-nowrap badge-glow-gold"
            >
              Masuk Member
            </button>
          ) : (
            <div className="bg-amber-50 border border-amber-200/80 text-amber-800 text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs">
              <Award size={16} className="text-amber-500" />
              <span>{member.points} Poin</span>
              <ChevronRight size={14} className="text-amber-400" />
            </div>
          )}
        </div>
      </div>

      {/* 3. Category Icons Grid */}
      <div className="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-5">
        <div className="flex items-center justify-between mb-2">
          <h2 className="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
            <span className="w-2 h-4 rounded-full gradient-bg-emerald inline-block"></span>
            Kategori Belanja
          </h2>
        </div>
        <div className="bg-white rounded-2xl p-2 shadow-xs border border-slate-100">
          <CategoryIcons />
        </div>
      </div>

      {/* 4. Product Catalog Grid */}
      <div className="bg-slate-50 flex-grow pb-12" id="catalog-section">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <ProductGrid />
        </div>
      </div>
    </div>
  );
};

export default Home;
