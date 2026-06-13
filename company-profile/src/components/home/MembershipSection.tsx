"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { 
  Percent, 
  QrCode, 
  Ticket, 
  MapPin, 
  ShoppingCart, 
  Newspaper, 
  Sparkles, 
  CalendarDays, 
  Receipt,
  UserCircle2,
  LogOut,
  ChevronRight,
  AlertCircle,
  Phone,
  Lock,
  Loader2,
  X
} from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import MembershipModal from "./MembershipModal";

export default function MembershipSection() {
  const [member, setMember] = useState<any>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [showAlert, setShowAlert] = useState(false);
  const [alertMessage, setAlertMessage] = useState("Silakan Masuk / Daftar terlebih dahulu.");
  const [showQRModal, setShowQRModal] = useState(false);
  const router = useRouter();

  useEffect(() => {
    // Load member from localStorage on mount
    const saved = localStorage.getItem('ecom_member');
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        setMember(parsed);
        if (parsed.phone) {
          syncMemberPoints(parsed.phone);
        }
      } catch (e) {
        console.error("Failed to parse member from localStorage", e);
      }
    }
  }, []);

  const getEcomApiUrl = () => {
    return '/api/v1';
  };

  const syncMemberPoints = async (memberPhone: string) => {
    try {
      const res = await fetch(`${getEcomApiUrl()}/ecommerce/members/profile?phone=${memberPhone}`);
      if (res.ok) {
        const data = await res.json();
        if (data.member) {
          setMember(data.member);
          localStorage.setItem('ecom_member', JSON.stringify(data.member));
        }
      }
    } catch (error) {
      console.error('Failed to sync member info:', error);
    }
  };

  const handleModalSuccess = (data: any) => {
    setMember(data);
    localStorage.setItem('ecom_member', JSON.stringify(data));
  };

  const handleLogout = () => {
    setMember(null);
    localStorage.removeItem('ecom_member');
  };

  const handleRequiresAuth = (action: () => void, customAlert?: string) => {
    if (!member) {
      setAlertMessage(customAlert || "Silakan Masuk / Daftar terlebih dahulu.");
      setShowAlert(true);
      setTimeout(() => setShowAlert(false), 3000);
      return;
    }
    action();
  };

  const handleShowComingSoon = () => {
    setAlertMessage("Fitur ini segera hadir (Coming Soon)!");
    setShowAlert(true);
    setTimeout(() => setShowAlert(false), 3000);
  };

  return (
    <section className="py-12 relative z-20 -mt-10 sm:-mt-16 mb-8">
      <div className="container mx-auto px-4 flex justify-center">
        {/* Card Container simulating mobile app layout */}
        <div className="w-full max-w-md bg-slate-50/80 backdrop-blur-xl rounded-[2rem] p-4 sm:p-6 shadow-2xl border border-slate-200/60 relative overflow-hidden">
          
          {/* Alert Popup */}
          <AnimatePresence>
            {showAlert && (
              <motion.div 
                initial={{ opacity: 0, y: -20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                className="absolute top-4 left-4 right-4 bg-secondary/90 backdrop-blur-md text-white p-3 rounded-xl shadow-lg z-50 flex items-center gap-3"
              >
                <AlertCircle size={20} />
                <span className="text-sm font-medium">{alertMessage}</span>
              </motion.div>
            )}
          </AnimatePresence>

          {/* QR Code Modal for Scan Member */}
          <AnimatePresence>
            {showQRModal && member && (
              <motion.div 
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
              >
                <motion.div 
                  initial={{ scale: 0.95, opacity: 0, y: 20 }}
                  animate={{ scale: 1, opacity: 1, y: 0 }}
                  exit={{ scale: 0.95, opacity: 0, y: 20 }}
                  className="bg-white rounded-3xl p-6 shadow-2xl max-w-sm w-full text-center relative border border-slate-100"
                >
                  <button 
                    onClick={() => setShowQRModal(false)}
                    className="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-all"
                  >
                    <X size={18} />
                  </button>
                  <h3 className="text-xl font-extrabold text-slate-800 mb-1 mt-2">Kartu Member Digital</h3>
                  <p className="text-xs text-slate-500 mb-6">Tunjukkan QR Code ini kepada kasir saat transaksi offline.</p>
                  
                  <div className="bg-slate-50 border border-slate-200 p-4 rounded-2xl mx-auto inline-block">
                    <img 
                      src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(member.id)}`} 
                      alt="QR Code Member" 
                      className="w-48 h-48 sm:w-56 sm:h-56 object-contain mix-blend-multiply"
                    />
                  </div>
                  
                  <div className="mt-6 bg-primary/10 rounded-xl py-3 border border-primary/20">
                    <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">ID MEMBER</p>
                    <p className="font-mono text-lg font-black tracking-widest text-primary">{member.id}</p>
                    <p className="font-bold text-sm text-slate-800 mt-2 uppercase">{member.name}</p>
                  </div>
                </motion.div>
              </motion.div>
            )}
          </AnimatePresence>

          {/* User Info / Login Block */}
          <div className="bg-white rounded-2xl p-4 shadow-sm mb-4 flex items-center justify-between border border-slate-100 min-h-[80px]">
            {member ? (
              <>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary overflow-hidden">
                    <UserCircle2 size={24} />
                  </div>
                  <div>
                    <p className="text-xs text-slate-500 font-medium">Hi,</p>
                    <h3 className="font-bold text-slate-900 text-sm truncate max-w-[120px] uppercase">
                      {member.name}
                    </h3>
                  </div>
                </div>
                <div className="flex items-center gap-4 text-center">
                  <div className="flex flex-col items-center">
                    <div className="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white mb-1 shadow-sm">
                      <Percent size={14} strokeWidth={3} />
                    </div>
                    <span className="text-xs font-bold text-slate-700">0</span>
                  </div>
                  <div className="flex flex-col items-center">
                    <div className="w-8 h-8 rounded-full bg-amber-400 flex items-center justify-center text-white mb-1 shadow-sm">
                      <span className="font-bold text-sm leading-none">Y</span>
                    </div>
                    <span className="text-xs font-bold text-slate-700">{member.points || 0}</span>
                  </div>
                  <button onClick={handleLogout} className="text-slate-400 hover:text-secondary ml-1 transition-colors" title="Keluar">
                    <LogOut size={18} />
                  </button>
                </div>
              </>
            ) : (
              <div className="flex items-center justify-between w-full">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                    <UserCircle2 size={24} />
                  </div>
                  <div>
                    <h3 className="font-bold text-slate-900 text-sm">Selamat Datang</h3>
                    <p className="text-[11px] text-slate-500 font-medium leading-snug">Masuk untuk nikmati promo</p>
                  </div>
                </div>
                <button 
                  onClick={() => setIsModalOpen(true)}
                  className="bg-primary text-white text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-primary/90 transition-colors shadow-sm"
                >
                  Masuk Member
                </button>
              </div>
            )}
          </div>

          {/* Action Block 1 (Promo, Scan, Redeem) */}
          <div className="bg-white rounded-2xl p-5 shadow-sm mb-4 border border-slate-100">
            <div className="grid grid-cols-3 gap-2">
              <a href="https://shopping.toserbaselamat.id/?category=promo" target="_blank" rel="noopener noreferrer" className="flex flex-col items-center group text-center">
                <div className="w-12 h-12 flex items-center justify-center text-[#F97316] mb-2 group-hover:scale-110 transition-transform">
                  <Percent size={32} strokeWidth={2.5} />
                </div>
                <span className="text-xs font-bold text-slate-700">Promo</span>
              </a>
              <button onClick={() => handleRequiresAuth(() => setShowQRModal(true))} className="flex flex-col items-center group text-center bg-transparent border-none p-0 outline-none">
                <div className="w-12 h-12 flex items-center justify-center text-[#F97316] mb-2 group-hover:scale-110 transition-transform mx-auto">
                  <QrCode size={32} strokeWidth={2.5} />
                </div>
                <span className="text-xs font-bold text-slate-700">Scan<br/>Member</span>
              </button>
              <button onClick={handleShowComingSoon} className="flex flex-col items-center group text-center bg-transparent border-none p-0 outline-none">
                <div className="w-12 h-12 flex items-center justify-center text-[#F97316] mb-2 group-hover:scale-110 transition-transform mx-auto">
                  <Ticket size={32} strokeWidth={2.5} />
                </div>
                <span className="text-xs font-bold text-slate-700">Redeem All<br/>Voucher</span>
              </button>
            </div>
          </div>

          {/* Action Block 2 (Main Grid) */}
          <div className="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-100">
            <div className="grid grid-cols-3 gap-y-6 gap-x-2">
              <Link href="/locations" className="flex flex-col items-center group text-center">
                <div className="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center text-red-500 mb-3 group-hover:bg-red-100 transition-colors">
                  <MapPin size={24} strokeWidth={2} />
                </div>
                <span className="text-[11px] leading-tight font-medium text-slate-600">Cabang</span>
              </Link>
              
              <a href="https://shopping.toserbaselamat.id" target="_blank" rel="noopener noreferrer" className="flex flex-col items-center group text-center">
                <div className="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 mb-3 group-hover:bg-amber-100 transition-colors">
                  <ShoppingCart size={24} strokeWidth={2} />
                </div>
                <span className="text-[11px] leading-tight font-medium text-slate-600">Belanja<br/>Online</span>
              </a>
              
              <Link href="/news" className="flex flex-col items-center group text-center">
                <div className="w-14 h-14 rounded-full bg-teal-50 flex items-center justify-center text-teal-500 mb-3 group-hover:bg-teal-100 transition-colors">
                  <Newspaper size={24} strokeWidth={2} />
                </div>
                <span className="text-[11px] leading-tight font-medium text-slate-600">Berita</span>
              </Link>

              <Link href="/facilities" className="flex flex-col items-center group text-center">
                <div className="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 mb-3 group-hover:bg-blue-100 transition-colors">
                  <Sparkles size={24} strokeWidth={2} />
                </div>
                <span className="text-[11px] leading-tight font-medium text-slate-600">Fasilitas</span>
              </Link>

              <Link href="/facilities#padel" className="flex flex-col items-center group text-center">
                <div className="w-14 h-14 rounded-full bg-rose-50 flex items-center justify-center text-rose-500 mb-3 group-hover:bg-rose-100 transition-colors">
                  <CalendarDays size={24} strokeWidth={2} />
                </div>
                <span className="text-[11px] leading-tight font-medium text-slate-600">Booking<br/>Lapangan</span>
              </Link>

              <button 
                onClick={() => handleRequiresAuth(() => console.log('Buka Riwayat Transaksi'))} 
                className="flex flex-col items-center group text-center bg-transparent border-none p-0 outline-none"
              >
                <div className="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 mb-3 group-hover:bg-purple-100 transition-colors mx-auto">
                  <Receipt size={24} strokeWidth={2} />
                </div>
                <span className="text-[11px] leading-tight font-medium text-slate-600">Riwayat<br/>Transaksi</span>
              </button>
            </div>
          </div>

        </div>
      </div>
      
      <MembershipModal 
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSuccess={handleModalSuccess}
      />
    </section>
  );
}
