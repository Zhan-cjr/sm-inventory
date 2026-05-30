
import PromoCarousel from '../components/PromoCarousel';
import CategoryIcons from '../components/CategoryIcons';
import ProductGrid from '../components/ProductGrid';
import MemberWidget from '../components/MemberWidget';
import { Truck, Clock, CreditCard, RotateCcw } from 'lucide-react';
import { useEcom } from '../context/EcomContext';

const Home = () => {
  const { setIsMemberModalOpen } = useEcom();

  return (
    <div className="flex flex-col min-h-screen bg-slate-50">
      <MemberWidget />
      <PromoCarousel />
      <CategoryIcons />
      
      {/* Value Propositions / Features */}
      <div className="bg-white border-y border-slate-100 py-6 sm:py-10 mt-2">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-8">
            <div className="flex items-center gap-3 sm:gap-4">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-brand-blue/5 flex items-center justify-center text-brand-blue flex-shrink-0">
                <Truck className="size-5 sm:size-6" />
              </div>
              <div>
                <h4 className="font-bold text-slate-800 text-xs sm:text-sm">Ambil di Cabang</h4>
                <p className="text-[10px] sm:text-xs text-slate-500 mt-0.5">Praktis & bebas ongkir</p>
              </div>
            </div>
            
            <div className="flex items-center gap-3 sm:gap-4">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-brand-green/10 flex items-center justify-center text-brand-green flex-shrink-0">
                <Clock className="size-5 sm:size-6" />
              </div>
              <div>
                <h4 className="font-bold text-slate-800 text-xs sm:text-sm">Layanan 24 Jam</h4>
                <p className="text-[10px] sm:text-xs text-slate-500 mt-0.5">Belanja kapan saja</p>
              </div>
            </div>
            
            <div className="flex items-center gap-3 sm:gap-4">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-brand-red/10 flex items-center justify-center text-brand-red flex-shrink-0">
                <CreditCard className="size-5 sm:size-6" />
              </div>
              <div>
                <h4 className="font-bold text-slate-800 text-xs sm:text-sm">Pembayaran Aman</h4>
                <p className="text-[10px] sm:text-xs text-slate-500 mt-0.5">Beragam metode transfer</p>
              </div>
            </div>
            
            <div className="flex items-center gap-3 sm:gap-4">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 flex-shrink-0">
                <RotateCcw className="size-5 sm:size-6" />
              </div>
              <div>
                <h4 className="font-bold text-slate-800 text-xs sm:text-sm">Garansi Produk</h4>
                <p className="text-[10px] sm:text-xs text-slate-500 mt-0.5">Retur jika rusak/kadaluarsa</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <ProductGrid />

      {/* Call to Action Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="bg-gradient-to-r from-brand-blue to-indigo-900 rounded-3xl p-8 md:p-12 relative overflow-hidden shadow-2xl">
          <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mt-20 -mr-20" />
          <div className="absolute bottom-0 left-0 w-40 h-40 bg-brand-red/20 rounded-full blur-2xl -mb-10 -ml-10" />
          
          <div className="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
            <div className="text-center md:text-left">
              <h2 className="text-3xl font-bold text-white mb-2">Daftar Jadi Member Sekarang</h2>
              <p className="text-blue-100 max-w-lg">
                Dapatkan poin setiap belanja, nikmati diskon khusus member, dan jadilah bagian dari keluarga besar Toserba Selamat.
              </p>
            </div>
            <button 
              onClick={() => setIsMemberModalOpen(true)}
              className="bg-white text-brand-blue font-bold px-8 py-4 rounded-xl hover:bg-slate-50 hover:shadow-lg transition-all flex-shrink-0"
            >
              Daftar Gratis
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Home;
