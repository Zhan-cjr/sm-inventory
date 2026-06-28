import { ClipboardList } from 'lucide-react';
import { useEcom } from '../context/EcomContext';

const Pesanan = () => {
  const { member, setIsMemberModalOpen } = useEcom();

  return (
    <div className="flex flex-col min-h-[70vh] bg-slate-50 overflow-x-hidden pt-6">
      <div className="max-w-7xl mx-auto px-4 w-full">
        <h1 className="text-xl font-bold text-slate-800 mb-6">Daftar Pesanan</h1>
        
        {!member ? (
          <div className="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100">
            <ClipboardList size={48} className="mx-auto text-slate-300 mb-4" />
            <h3 className="text-lg font-semibold text-slate-800">Anda belum masuk</h3>
            <p className="text-sm text-slate-500 mt-2 mb-6">Masuk sekarang untuk melihat pesanan Anda.</p>
            <button 
              onClick={() => setIsMemberModalOpen(true)}
              className="bg-brand-blue text-white px-6 py-2.5 rounded-xl font-bold hover:bg-brand-blue/90 transition-all"
            >
              Masuk / Daftar
            </button>
          </div>
        ) : (
          <div className="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100">
            <ClipboardList size={48} className="mx-auto text-slate-300 mb-4" />
            <h3 className="text-lg font-semibold text-slate-800">Belum Ada Pesanan</h3>
            <p className="text-sm text-slate-500 mt-2">Anda belum memiliki riwayat pesanan saat ini.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default Pesanan;
