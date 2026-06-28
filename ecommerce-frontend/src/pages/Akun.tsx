import { User, LogOut } from 'lucide-react';
import { useEcom } from '../context/EcomContext';

const Akun = () => {
  const { member, logoutMember, setIsMemberModalOpen } = useEcom();

  return (
    <div className="flex flex-col min-h-[70vh] bg-slate-50 overflow-x-hidden pt-6">
      <div className="max-w-7xl mx-auto px-4 w-full">
        <h1 className="text-xl font-bold text-slate-800 mb-6">Akun Saya</h1>
        
        {!member ? (
          <div className="bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-100">
            <User size={48} className="mx-auto text-slate-300 mb-4" />
            <h3 className="text-lg font-semibold text-slate-800">Anda belum masuk</h3>
            <p className="text-sm text-slate-500 mt-2 mb-6">Masuk sekarang untuk mengelola akun Anda.</p>
            <button 
              onClick={() => setIsMemberModalOpen(true)}
              className="bg-brand-blue text-white px-6 py-2.5 rounded-xl font-bold hover:bg-brand-blue/90 transition-all"
            >
              Masuk / Daftar
            </button>
          </div>
        ) : (
          <div className="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col items-center">
            <div className="w-20 h-20 bg-brand-red rounded-full flex items-center justify-center text-white text-3xl font-bold mb-4 shadow-md border-4 border-white">
              {member.name.charAt(0).toUpperCase()}
            </div>
            <h2 className="text-xl font-bold text-slate-800">{member.name}</h2>
            <p className="text-slate-500 text-sm mb-4">{member.phone}</p>
            
            <div className="w-full bg-slate-50 rounded-xl p-4 flex justify-between items-center mb-6 border border-slate-100">
              <span className="text-slate-600 font-medium">Poin Terkumpul</span>
              <span className="text-amber-500 font-bold text-lg">{member.points} Poin</span>
            </div>
            
            <button 
              onClick={() => logoutMember()}
              className="w-full flex items-center justify-center gap-2 bg-red-50 text-red-500 py-3 rounded-xl font-bold hover:bg-red-100 transition-colors"
            >
              <LogOut size={20} />
              Keluar Akun
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default Akun;
