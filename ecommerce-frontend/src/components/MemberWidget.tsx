
import { useEcom } from '../context/EcomContext';
import { Award, ChevronRight, User } from 'lucide-react';

const MemberWidget = () => {
  const { member, setIsMemberModalOpen } = useEcom();

  if (!member) return null;

  return (
    <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 sm:mt-6">
      <div 
        onClick={() => setIsMemberModalOpen(true)}
        className="bg-gradient-to-r from-brand-blue to-indigo-900 rounded-2xl p-4 sm:p-5 flex items-center justify-between cursor-pointer shadow-md hover:shadow-lg transition-shadow relative overflow-hidden"
      >
        {/* Background Decorations */}
        <div className="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mt-10 -mr-10"></div>
        <div className="absolute bottom-0 right-1/4 w-24 h-24 bg-brand-green/20 rounded-full blur-xl -mb-10"></div>

        <div className="flex items-center gap-3 sm:gap-4 relative z-10">
          <div className="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/30 flex-shrink-0">
            <User size={24} />
          </div>
          <div>
            <div className="flex items-center gap-2 mb-1">
              <h3 className="text-white font-bold text-sm sm:text-base leading-none">
                Hai, {member.name.split(' ')[0]}
              </h3>
              <div className="px-2 py-0.5 rounded-full bg-brand-green/20 border border-brand-green/30 text-brand-green text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                <Award size={10} />
                {member.member_tier || 'BRONZE'}
              </div>
            </div>
            <div className="flex items-end gap-1">
              <span className="text-white text-lg sm:text-xl font-black leading-none drop-shadow-sm">
                {member.points || 0}
              </span>
              <span className="text-blue-200 text-[10px] sm:text-xs font-semibold leading-relaxed mb-0.5">
                Koin Selamat
              </span>
            </div>
          </div>
        </div>

        <div className="relative z-10 w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-colors flex-shrink-0">
          <ChevronRight size={20} />
        </div>
      </div>
    </div>
  );
};

export default MemberWidget;
