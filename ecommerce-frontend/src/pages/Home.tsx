
import PromoCarousel from '../components/PromoCarousel';
import CategoryIcons from '../components/CategoryIcons';
import ProductGrid from '../components/ProductGrid';
import MemberWidget from '../components/MemberWidget';
import { Truck, ShieldCheck, CreditCard, RotateCcw } from 'lucide-react';
import { useEcom } from '../context/EcomContext';

const Home = () => {
  const { setIsMemberModalOpen } = useEcom();

  return (
    <div className="flex flex-col min-h-screen bg-slate-50">
      <MemberWidget />
      <PromoCarousel />
      <CategoryIcons />
      
      {/* Value Propositions / Features */}
      <div className="bg-white/60 backdrop-blur-3xl py-8 sm:py-12 mt-4 shadow-sm border-y border-slate-200/50 relative z-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
            <div className="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-4 group cursor-default">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-brand-blue/5 flex items-center justify-center text-brand-blue flex-shrink-0 transition-all duration-300 group-hover:bg-brand-blue group-hover:text-white group-hover:shadow-lg group-hover:-translate-y-1">
                <Truck className="size-6 sm:size-7" />
              </div>
              <div className="pt-1">
                <h4 className="font-bold text-slate-800 text-sm sm:text-base">Ambil di Toko</h4>
                <p className="text-xs text-slate-500 mt-1 leading-relaxed">Pesan via aplikasi, ambil pesanan langsung di cabang tanpa antre panjang</p>
              </div>
            </div>
            
            <div className="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-4 group cursor-default">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-brand-green/10 flex items-center justify-center text-brand-green flex-shrink-0 transition-all duration-300 group-hover:bg-brand-green group-hover:text-white group-hover:shadow-lg group-hover:-translate-y-1">
                <ShieldCheck className="size-6 sm:size-7" />
              </div>
              <div className="pt-1">
                <h4 className="font-bold text-slate-800 text-sm sm:text-base">Kualitas Terjamin</h4>
                <p className="text-xs text-slate-500 mt-1 leading-relaxed">Produk selalu segar dan melalui proses sortir sebelum diserahkan</p>
              </div>
            </div>
            
            <div className="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-4 group cursor-default">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-brand-red/10 flex items-center justify-center text-brand-red flex-shrink-0 transition-all duration-300 group-hover:bg-brand-red group-hover:text-white group-hover:shadow-lg group-hover:-translate-y-1">
                <CreditCard className="size-6 sm:size-7" />
              </div>
              <div className="pt-1">
                <h4 className="font-bold text-slate-800 text-sm sm:text-base">Pembayaran Mudah</h4>
                <p className="text-xs text-slate-500 mt-1 leading-relaxed">Dukung QRIS, transfer antar bank, hingga pembayaran tunai di kasir</p>
              </div>
            </div>
            
            <div className="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-4 group cursor-default">
              <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-500 flex-shrink-0 transition-all duration-300 group-hover:bg-amber-500 group-hover:text-white group-hover:shadow-lg group-hover:-translate-y-1">
                <RotateCcw className="size-6 sm:size-7" />
              </div>
              <div className="pt-1">
                <h4 className="font-bold text-slate-800 text-sm sm:text-base">Garansi Penukaran</h4>
                <p className="text-xs text-slate-500 mt-1 leading-relaxed">Proses retur cepat jika barang cacat produksi atau kadaluarsa</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <ProductGrid />

      {/* Call to Action Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="bg-gradient-to-r from-brand-blue to-indigo-900 rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden shadow-2xl border border-brand-blue/20">
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
              className="bg-white text-brand-blue font-bold px-8 py-4 rounded-2xl hover:bg-slate-50 hover:shadow-lg hover:-translate-y-1 transition-all flex-shrink-0"
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
