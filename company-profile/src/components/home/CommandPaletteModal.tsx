"use client";

import { useEffect, useState } from "react";
import { Search, MapPin, ShoppingCart, Newspaper, Briefcase, X, ArrowRight } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import Link from "next/link";

interface SearchItem {
  id: string;
  title: string;
  category: "Cabang" | "Fasilitas" | "Promo/Berita" | "Karir";
  subtitle: string;
  url: string;
  icon: any;
}

const mockSearchItems: SearchItem[] = [
  { id: "1", title: "Toserba Selamat Cianjur City Center", category: "Cabang", subtitle: "Jl. Siliwangi No. 88, Cianjur", url: "/locations", icon: MapPin },
  { id: "2", title: "Toserba Selamat Sukabumi Plaza", category: "Cabang", subtitle: "Jl. Ahmad Yani No. 12, Sukabumi", url: "/locations", icon: MapPin },
  { id: "3", title: "Toserba Selamat Bandung Superstore", category: "Cabang", subtitle: "Jl. Soekarno Hatta No. 450", url: "/locations", icon: MapPin },
  { id: "4", title: "Supermarket Syariah & Produk Halal", category: "Fasilitas", subtitle: "Area belanja bahan segar & kebutuhan harian", url: "/facilities", icon: ShoppingCart },
  { id: "5", title: "Selamat Hotel & Executive Lounge", category: "Fasilitas", subtitle: "Akomodasi nyaman dan ramah keluarga", url: "/facilities", icon: ShoppingCart },
  { id: "6", title: "Foodcourt & Culinary Zone", category: "Fasilitas", subtitle: "Pusat kuliner nusantara higienis", url: "/facilities", icon: ShoppingCart },
  { id: "7", title: "Promo Berkah Akhir Bulan - Diskon 50%", category: "Promo/Berita", subtitle: "Potongan harga spesial member", url: "/news", icon: Newspaper },
  { id: "8", title: "Grand Opening Cabang Baru Sukabumi", category: "Promo/Berita", subtitle: "Nikmati voucher belanja gratis", url: "/news", icon: Newspaper },
  { id: "9", title: "Store Manager Trainee", category: "Karir", subtitle: "Full-time • Penempatan Cianjur & Bandung", url: "/careers", icon: Briefcase },
  { id: "10", title: "Digital Marketing Specialist", category: "Karir", subtitle: "Full-time • Head Office Cianjur", url: "/careers", icon: Briefcase },
];

export default function CommandPaletteModal({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const [query, setQuery] = useState("");

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === "k") {
        e.preventDefault();
        if (isOpen) onClose();
      }
      if (e.key === "Escape" && isOpen) {
        onClose();
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isOpen, onClose]);

  const filtered = query.trim() === ""
    ? mockSearchItems
    : mockSearchItems.filter(
        item => item.title.toLowerCase().includes(query.toLowerCase()) || item.subtitle.toLowerCase().includes(query.toLowerCase()) || item.category.toLowerCase().includes(query.toLowerCase())
      );

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4 sm:px-6">
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
          />

          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: -20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: -20 }}
            transition={{ duration: 0.2 }}
            className="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden z-10"
          >
            {/* Search Input Bar */}
            <div className="flex items-center px-6 py-4 border-b border-slate-100 bg-slate-50/50">
              <Search className="w-5 h-5 text-slate-400 mr-3" />
              <input
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Cari cabang, fasilitas, promo, atau lowongan karir..."
                className="w-full bg-transparent text-slate-900 placeholder-slate-400 focus:outline-none text-base font-medium"
                autoFocus
              />
              <button
                onClick={onClose}
                className="p-1.5 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Search Results */}
            <div className="max-h-96 overflow-y-auto p-4 space-y-2 custom-scrollbar">
              {filtered.length > 0 ? (
                filtered.map((item) => {
                  const Icon = item.icon;
                  return (
                    <Link
                      key={item.id}
                      href={item.url}
                      onClick={onClose}
                      className="flex items-center justify-between p-3.5 rounded-2xl hover:bg-primary/5 transition-all group"
                    >
                      <div className="flex items-center gap-3.5">
                        <div className="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                          <Icon className="w-5 h-5" />
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <h4 className="text-sm font-bold text-slate-900 group-hover:text-primary transition-colors">
                              {item.title}
                            </h4>
                            <span className="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                              {item.category}
                            </span>
                          </div>
                          <p className="text-xs text-slate-500 font-medium">{item.subtitle}</p>
                        </div>
                      </div>
                      <ArrowRight className="w-4 h-4 text-slate-400 group-hover:text-primary group-hover:translate-x-1 transition-all" />
                    </Link>
                  );
                })
              ) : (
                <div className="text-center py-12 text-slate-400">
                  <p className="font-medium text-sm">Tidak ada hasil untuk &quot;{query}&quot;</p>
                </div>
              )}
            </div>

            {/* Footer tips */}
            <div className="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400 font-medium">
              <span>Tekan <kbd className="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-600 shadow-xs">ESC</kbd> untuk menutup</span>
              <span className="text-primary font-bold">Toserba Selamat Ecosystem</span>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
