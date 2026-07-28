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
  AlertCircle,
  X,
  CreditCard,
  Crown,
  Gift,
  CheckCircle2
} from "lucide-react";
import Link from "next/link";
import { useCompanyProfile } from "@/lib/hooks";
import MembershipModal from "./MembershipModal";
import TransactionHistoryModal from "./TransactionHistoryModal";

interface MemberTier {
  name: string;
  color: string;
  badge: string;
  perks: string[];
  minSpend: string;
}

const defaultTiers: MemberTier[] = [
  {
    name: "Bronze Family",
    color: "from-amber-700 via-amber-800 to-amber-950",
    badge: "MEMBER BARU",
    perks: ["Poin belanja 1% setiap transaksi", "Voucher selamat datang Rp 15.000", "Diskon hari ulang tahun"],
    minSpend: "Gratis Pendaftaran"
  },
  {
    name: "Silver Privilege",
    color: "from-slate-400 via-slate-600 to-slate-900",
    badge: "PALING POPULER",
    perks: ["Poin belanja 2.5%", "Akses ke Promo Jajanan Subuh & Syariah", "Gratis ongkir belanja online 2x/bulan"],
    minSpend: "Transaksi > Rp 1.500.000 / bln"
  },
  {
    name: "Gold Executive",
    color: "from-amber-400 via-yellow-500 to-amber-700",
    badge: "VIP SYARIAH",
    perks: ["Poin belanja 5%", "Layanan jalur antrean prioritas", "Diskon 15% Hotel & Lounge Selamat", "Undangan Event Syariah Eksklusif"],
    minSpend: "Transaksi > Rp 5.000.000 / bln"
  },
  {
    name: "Platinum Sultan",
    color: "from-indigo-900 via-purple-900 to-slate-950",
    badge: "ULTIMATE",
    perks: ["Poin belanja 8%", "Personal Personal Shopping Assistant", "Voucher Belanja Sultan Rp 100.000/Bulan", "Cashback Poin Berkah Unlimited"],
    minSpend: "Undangan Khusus"
  }
];

export default function MembershipSection() {
  const { memberTiers: apiTiers } = useCompanyProfile();
  const [member, setMember] = useState<any>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [activeTierIdx, setActiveTierIdx] = useState(1);

  const displayTiers: MemberTier[] = apiTiers && apiTiers.length > 0 ? apiTiers.map((t: any) => ({
    name: t.name,
    color: t.color_gradient || "from-slate-700 via-slate-800 to-slate-950",
    badge: t.badge || "MEMBERSHIP",
    perks: Array.isArray(t.perks) ? t.perks : (t.perks ? (typeof t.perks === 'string' ? JSON.parse(t.perks) : []) : []),
    minSpend: t.min_spend_text || `Minimal ${t.min_points || 0} Poin`
  })) : defaultTiers;

  const activeTier = displayTiers[activeTierIdx] || displayTiers[0];

  const [showAlert, setShowAlert] = useState(false);
  const [alertMessage, setAlertMessage] = useState("Silakan Masuk / Daftar terlebih dahulu.");
  const [showQRModal, setShowQRModal] = useState(false);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const [showHistoryModal, setShowHistoryModal] = useState(false);

  useEffect(() => {
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
    if (process.env.NEXT_PUBLIC_API_URL) return process.env.NEXT_PUBLIC_API_URL;
    if (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
      return 'http://localhost:8080/api/v1';
    }
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
    setShowLogoutConfirm(false);
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
    <section className="py-16 sm:py-24 relative z-20 bg-gradient-to-b from-slate-50 via-white to-slate-50 border-y border-slate-200/60 overflow-hidden">
      
      {/* Background Decor */}
      <div className="absolute top-1/2 left-0 w-96 h-96 bg-primary/10 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 right-0 w-96 h-96 bg-accent/10 rounded-full blur-[120px] pointer-events-none" />

      <div className="container mx-auto px-4 max-w-6xl">
        
        {/* Section Header */}
        <div className="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
          <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-widest mb-4">
            <Crown size={15} className="text-amber-500" /> Executive Loyalty Privilege
          </div>
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight mb-4">
            Keanggotaan <span className="text-primary">Digital Selamat</span>
          </h2>
          <p className="text-slate-600 text-base sm:text-lg font-medium leading-relaxed">
            Dapatkan poin di setiap belanjaan, kupon cashback eksklusif, serta keuntungan khusus keluarga di seluruh 26+ cabang Toserba Selamat.
          </p>
        </div>

        <div className="grid lg:grid-cols-12 gap-8 lg:gap-12 items-center">
          
          {/* Left Column: Interactive 3D Member Card & Tier Picker */}
          <div className="lg:col-span-6 space-y-6">
            
            {/* Interactive Tier Switcher Tabs */}
            <div className="flex items-center justify-between p-1.5 bg-slate-200/80 rounded-2xl">
              {displayTiers.map((tier, idx) => (
                <button
                  key={tier.name}
                  onClick={() => setActiveTierIdx(idx)}
                  className={`flex-1 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all ${
                    activeTierIdx === idx
                      ? "bg-white text-slate-900 shadow-md scale-102"
                      : "text-slate-600 hover:text-slate-900"
                  }`}
                >
                  {tier.name.split(" ")[0]}
                </button>
              ))}
            </div>

            {/* Simulated 3D Digital Card */}
            <motion.div
              key={activeTier.name}
              initial={{ rotateY: 90, opacity: 0 }}
              animate={{ rotateY: 0, opacity: 1 }}
              transition={{ duration: 0.5 }}
              className={`relative rounded-3xl p-6 sm:p-8 bg-gradient-to-tr ${activeTier.color} text-white shadow-2xl overflow-hidden border border-white/20 group hover:scale-102 transition-transform duration-500`}
            >
              {/* Card Holographic Effect */}
              <div className="absolute top-0 right-0 w-80 h-80 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-colors pointer-events-none" />
              
              <div className="flex items-center justify-between mb-8 relative z-10">
                <div className="flex items-center gap-2">
                  <CreditCard className="w-8 h-8 text-accent" />
                  <div>
                    <h3 className="font-black text-lg sm:text-xl tracking-tight leading-tight">Toserba Selamat</h3>
                    <span className="text-[10px] font-bold tracking-widest text-slate-300 uppercase">OFFICIAL MEMBER</span>
                  </div>
                </div>
                <span className="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold tracking-wider uppercase border border-white/30 text-white">
                  {activeTier.badge}
                </span>
              </div>

              {/* Card Body Details */}
              <div className="space-y-4 my-6 relative z-10">
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-300 font-mono">TINGKAT MEMBER</span>
                  <span className="font-bold text-base text-accent">{activeTier.name}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-xs text-slate-300 font-mono">STATUS POHON POIN</span>
                  <span className="font-bold text-sm text-white">1 Poin = Rp 10 Cashback</span>
                </div>
              </div>

              {/* Card Bottom / User Bar */}
              <div className="pt-6 border-t border-white/15 flex items-center justify-between relative z-10">
                <div>
                  <p className="text-[10px] text-slate-300 uppercase font-mono">PEMEGANG KARTU</p>
                  <p className="font-bold text-sm sm:text-base uppercase tracking-wide">
                    {member ? member.name : "NAMA ANDA DISINI"}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-[10px] text-slate-300 uppercase font-mono">KRITERIA</p>
                  <p className="font-bold text-xs text-white">{activeTier.minSpend}</p>
                </div>
              </div>
            </motion.div>

            {/* Perks Included Box */}
            <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
              <h4 className="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                <Gift className="w-4 h-4 text-secondary" /> Keuntungan Level {activeTier.name}:
              </h4>
              <div className="space-y-2">
                {activeTier.perks.map((perk, i) => (
                  <div key={i} className="flex items-start gap-2.5 text-xs text-slate-700 font-medium">
                    <CheckCircle2 className="w-4 h-4 text-accent shrink-0 mt-0.5" />
                    <span>{perk}</span>
                  </div>
                ))}
              </div>
            </div>

          </div>

          {/* Right Column: Member Mobile Card Controller Widget */}
          <div className="lg:col-span-6 flex justify-center">
            <div className="w-full max-w-md bg-white rounded-[2.5rem] p-6 shadow-2xl border border-slate-200/80 relative overflow-hidden">
              
              {/* Alert Notification */}
              <AnimatePresence>
                {showAlert && (
                  <motion.div
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    className="absolute top-4 left-4 right-4 bg-secondary text-white p-3 rounded-2xl shadow-lg z-50 flex items-center gap-3"
                  >
                    <AlertCircle size={20} />
                    <span className="text-xs font-bold">{alertMessage}</span>
                  </motion.div>
                )}
              </AnimatePresence>

              {/* QR Modal */}
              <AnimatePresence>
                {showQRModal && member && (
                  <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
                  >
                    <motion.div
                      initial={{ scale: 0.95, opacity: 0 }}
                      animate={{ scale: 1, opacity: 1 }}
                      exit={{ scale: 0.95, opacity: 0 }}
                      className="bg-white rounded-3xl p-6 shadow-2xl max-w-sm w-full text-center relative border border-slate-100"
                    >
                      <button
                        onClick={() => setShowQRModal(false)}
                        className="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-all"
                      >
                        <X size={18} />
                      </button>
                      <h3 className="text-xl font-extrabold text-slate-900 mb-1 mt-2">Kartu Member Digital</h3>
                      <p className="text-xs text-slate-500 mb-6 font-medium">Tunjukkan QR Code ini ke kasir toko offline Toserba Selamat.</p>

                      <div className="bg-slate-50 border border-slate-200 p-4 rounded-2xl mx-auto inline-block">
                        <img
                          src={`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(member.id)}`}
                          alt="QR Code Member"
                          className="w-48 h-48 sm:w-56 sm:h-56 object-contain mix-blend-multiply"
                        />
                      </div>

                      <div className="mt-6 bg-primary/10 rounded-2xl py-3 border border-primary/20">
                        <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">ID MEMBER SYARIAH</p>
                        <p className="font-mono text-lg font-black tracking-widest text-primary">{member.id}</p>
                        <p className="font-bold text-sm text-slate-800 mt-1 uppercase">{member.name}</p>
                      </div>
                    </motion.div>
                  </motion.div>
                )}
              </AnimatePresence>

              {/* Logout Confirmation */}
              <AnimatePresence>
                {showLogoutConfirm && (
                  <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
                  >
                    <motion.div
                      initial={{ scale: 0.95, opacity: 0 }}
                      animate={{ scale: 1, opacity: 1 }}
                      exit={{ scale: 0.95, opacity: 0 }}
                      className="bg-white rounded-3xl p-6 shadow-2xl max-w-sm w-full text-center relative border border-slate-100"
                    >
                      <div className="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center text-red-500 mx-auto mb-4">
                        <LogOut size={26} />
                      </div>
                      <h3 className="text-xl font-extrabold text-slate-900 mb-2">Keluar dari Member?</h3>
                      <p className="text-xs text-slate-500 mb-6 font-medium">Anda perlu masuk kembali untuk mengakses poin dan promo member.</p>

                      <div className="flex gap-3">
                        <button
                          onClick={() => setShowLogoutConfirm(false)}
                          className="flex-1 py-3 bg-slate-100 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-200 transition-colors"
                        >
                          Batal
                        </button>
                        <button
                          onClick={handleLogout}
                          className="flex-1 py-3 bg-secondary text-white font-bold text-xs rounded-xl hover:bg-secondary-light transition-colors shadow-md shadow-secondary/20"
                        >
                          Keluar
                        </button>
                      </div>
                    </motion.div>
                  </motion.div>
                )}
              </AnimatePresence>

              {/* User Profile Bar */}
              <div className="bg-slate-50 rounded-2xl p-4 shadow-xs mb-5 flex items-center justify-between border border-slate-200/80">
                {member ? (
                  <>
                    <div className="flex items-center gap-3">
                      <div className="w-11 h-11 rounded-2xl bg-primary text-white flex items-center justify-center font-bold shadow-md shadow-primary/20">
                        <UserCircle2 size={26} />
                      </div>
                      <div>
                        <p className="text-[11px] text-slate-400 font-bold uppercase tracking-wider">MEMBER AKTIF</p>
                        <h4 className="font-extrabold text-slate-900 text-sm truncate max-w-[130px] uppercase">
                          {member.name}
                        </h4>
                      </div>
                    </div>
                    <div className="flex items-center gap-3 text-center">
                      <div className="bg-amber-100 border border-amber-200 px-3 py-1.5 rounded-xl">
                        <p className="text-[10px] text-amber-700 font-bold">SM POIN</p>
                        <p className="text-sm font-extrabold text-amber-900">{member.points || 0}</p>
                      </div>
                      <button onClick={() => setShowLogoutConfirm(true)} className="p-2 rounded-xl text-slate-400 hover:bg-red-50 hover:text-secondary transition-colors" title="Keluar">
                        <LogOut size={20} />
                      </button>
                    </div>
                  </>
                ) : (
                  <div className="flex items-center justify-between w-full">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <UserCircle2 size={24} />
                      </div>
                      <div>
                        <h4 className="font-extrabold text-slate-900 text-sm">Member Toserba Selamat</h4>
                        <p className="text-[11px] text-slate-500 font-medium">Nikmati kemudahan &amp; cashback</p>
                      </div>
                    </div>
                    <button
                      onClick={() => setIsModalOpen(true)}
                      className="bg-primary hover:bg-primary-light text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-md shadow-primary/20"
                    >
                      Masuk / Daftar
                    </button>
                  </div>
                )}
              </div>

              {/* Quick Actions Grid */}
              <div className="grid grid-cols-3 gap-3 mb-5">
                <a href="https://shopping.toserbaselamat.id/?category=promo" target="_blank" rel="noopener noreferrer" className="p-3 bg-slate-50 hover:bg-primary/5 rounded-2xl flex flex-col items-center border border-slate-200/60 transition-all group text-center">
                  <div className="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <Percent size={22} strokeWidth={2.5} />
                  </div>
                  <span className="text-xs font-bold text-slate-800">Kupon Promo</span>
                </a>

                <button onClick={() => handleRequiresAuth(() => setShowQRModal(true))} className="p-3 bg-slate-50 hover:bg-primary/5 rounded-2xl flex flex-col items-center border border-slate-200/60 transition-all group text-center">
                  <div className="w-10 h-10 rounded-xl bg-blue-100 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <QrCode size={22} strokeWidth={2.5} />
                  </div>
                  <span className="text-xs font-bold text-slate-800">Scan QR</span>
                </button>

                <button onClick={handleShowComingSoon} className="p-3 bg-slate-50 hover:bg-primary/5 rounded-2xl flex flex-col items-center border border-slate-200/60 transition-all group text-center">
                  <div className="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <Ticket size={22} strokeWidth={2.5} />
                  </div>
                  <span className="text-xs font-bold text-slate-800">Redeem Poin</span>
                </button>
              </div>

              {/* Ecosystem Shortcuts */}
              <div className="bg-slate-50/70 rounded-2xl p-4 border border-slate-200/60">
                <p className="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-3">Layanan Terhubung</p>
                <div className="grid grid-cols-3 gap-y-4 gap-x-2">
                  <Link href="/locations" className="flex flex-col items-center group text-center">
                    <div className="w-11 h-11 rounded-xl bg-red-100 text-red-600 flex items-center justify-center mb-1 group-hover:bg-red-500 group-hover:text-white transition-colors">
                      <MapPin size={20} />
                    </div>
                    <span className="text-[11px] font-bold text-slate-700">26+ Cabang</span>
                  </Link>

                  <a href="https://shopping.toserbaselamat.id" target="_blank" rel="noopener noreferrer" className="flex flex-col items-center group text-center">
                    <div className="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-1 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                      <ShoppingCart size={20} />
                    </div>
                    <span className="text-[11px] font-bold text-slate-700">Belanja Online</span>
                  </a>

                  <Link href="/news" className="flex flex-col items-center group text-center">
                    <div className="w-11 h-11 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center mb-1 group-hover:bg-teal-500 group-hover:text-white transition-colors">
                      <Newspaper size={20} />
                    </div>
                    <span className="text-[11px] font-bold text-slate-700">Berita</span>
                  </Link>

                  <Link href="/facilities" className="flex flex-col items-center group text-center">
                    <div className="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-1 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                      <Sparkles size={20} />
                    </div>
                    <span className="text-[11px] font-bold text-slate-700">Fasilitas</span>
                  </Link>

                  <Link href="/facilities" className="flex flex-col items-center group text-center">
                    <div className="w-11 h-11 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mb-1 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                      <CalendarDays size={20} />
                    </div>
                    <span className="text-[11px] font-bold text-slate-700">Fitness Center</span>
                  </Link>

                  <button onClick={() => handleRequiresAuth(() => setShowHistoryModal(true))} className="flex flex-col items-center group text-center bg-transparent border-none p-0">
                    <div className="w-11 h-11 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mb-1 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                      <Receipt size={20} />
                    </div>
                    <span className="text-[11px] font-bold text-slate-700">Riwayat</span>
                  </button>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>

      <MembershipModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSuccess={handleModalSuccess}
      />

      <TransactionHistoryModal
        isOpen={showHistoryModal}
        onClose={() => setShowHistoryModal(false)}
        member={member}
      />
    </section>
  );
}
