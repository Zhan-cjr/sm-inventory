"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";
import { ShoppingCart, Sparkles, Building2, CheckCircle2, ArrowRight, X, Info } from "lucide-react";

export default function FacilitiesPage() {
  const { facilities, isLoading } = useCompanyProfile();
  const [activeCategory, setActiveCategory] = useState("semua");
  const [selectedFacility, setSelectedFacility] = useState<any>(null);

  useEffect(() => {
    if (!isLoading && facilities.length > 0) {
      const hash = window.location.hash;
      if (hash) {
        setTimeout(() => {
          const element = document.querySelector(hash);
          if (element) {
            const y = element.getBoundingClientRect().top + window.scrollY - 100;
            window.scrollTo({ top: y, behavior: 'smooth' });
          }
        }, 300);
      }
    }
  }, [isLoading, facilities]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="animate-spin rounded-full h-16 w-16 border-4 border-primary border-t-transparent shadow-md" />
      </div>
    );
  }

  const categories = [
    { id: "semua", label: "Semua Fasilitas" },
    { id: "ritel", label: "Supermarket & Ritel" },
    { id: "hospitality", label: "Hotel & Lounge" },
    { id: "olahraga", label: "Arena Olahraga" },
    { id: "kuliner", label: "Pusat Kuliner" },
  ];

  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      
      {/* Background Decor */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-4xl h-[400px] bg-primary/10 rounded-full blur-[140px] pointer-events-none" />

      <div className="container mx-auto px-4 md:px-8 max-w-7xl relative z-10">
        
        {/* Page Header */}
        <div className="text-center max-w-3xl mx-auto mb-14 space-y-4">
          <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-widest">
            <Sparkles size={15} className="text-accent" /> Ekosistem Toserba Selamat
          </span>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black text-slate-900 tracking-tight">
            Fasilitas &amp; <span className="bg-gradient-to-r from-primary via-primary-light to-secondary text-gradient">Layanan Terpadu</span>
          </h1>
          <p className="text-slate-600 font-medium text-base sm:text-lg leading-relaxed">
            Jelajahi seluruh fasilitas unggulan yang kami sediakan untuk kenyamanan belanja, tempat tinggal, olahraga, dan rekreasi keluarga Anda.
          </p>
        </div>

        {/* Category Filter Chips */}
        <div className="flex flex-wrap items-center justify-center gap-2.5 mb-14">
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setActiveCategory(cat.id)}
              className={`px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all ${
                activeCategory === cat.id
                  ? "bg-primary text-white shadow-lg shadow-primary/25 scale-105"
                  : "bg-white text-slate-700 hover:bg-slate-100 border border-slate-200"
              }`}
            >
              {cat.label}
            </button>
          ))}
        </div>

        {/* Facilities Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {facilities.map((facility: any, idx: number) => {
            const Icon = IconMapper[facility.icon] || ShoppingCart;
            const origin = typeof window !== 'undefined' ? window.location.origin : 'https://admin.toserbaselamat.id';
            const imgUrl = facility.image_url ? (facility.image_url.startsWith('http') ? facility.image_url : `${origin}/storage/${facility.image_url}`) : "https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=2574&auto=format&fit=crop";

            return (
              <motion.div
                key={facility.id || idx}
                id={`facility-${facility.id}`}
                initial={{ opacity: 0, y: 30 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: idx * 0.1 }}
                className="bg-white rounded-3xl border border-slate-200/80 shadow-md hover:shadow-2xl hover:border-primary/30 transition-all duration-400 overflow-hidden flex flex-col justify-between group"
              >
                <div>
                  {/* Facility Image */}
                  <div className="relative h-56 w-full overflow-hidden bg-slate-100">
                    <img
                      src={imgUrl}
                      alt={facility.name}
                      className="w-full h-full object-cover group-hover:scale-108 transition-transform duration-700"
                    />
                    <div className="absolute top-4 left-4">
                      <span className="px-3 py-1 bg-slate-900/80 backdrop-blur-md text-white rounded-full text-[10px] font-extrabold uppercase tracking-wider">
                        SYARIAH VERIFIED
                      </span>
                    </div>
                  </div>

                  {/* Card Content */}
                  <div className="p-6 space-y-3">
                    <div className="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-2 group-hover:bg-primary group-hover:text-white transition-colors">
                      <Icon size={24} />
                    </div>
                    <h3 className="text-xl font-extrabold text-slate-900 group-hover:text-primary transition-colors">
                      {facility.name}
                    </h3>
                    <p className="text-slate-600 text-xs sm:text-sm font-medium leading-relaxed line-clamp-3">
                      {facility.description}
                    </p>
                  </div>
                </div>

                {/* Card Footer Button */}
                <div className="p-6 pt-0">
                  <button
                    onClick={() => setSelectedFacility(facility)}
                    className="w-full py-3 bg-slate-100 hover:bg-primary hover:text-white text-slate-800 font-bold text-xs rounded-2xl transition-colors flex items-center justify-center gap-2"
                  >
                    <span>Informasi Selengkapnya</span>
                    <Info size={15} />
                  </button>
                </div>
              </motion.div>
            );
          })}
        </div>

      </div>

      {/* Facility Detail Modal */}
      <AnimatePresence>
        {selectedFacility && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <motion.div
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-white rounded-3xl max-w-xl w-full p-8 shadow-2xl relative border border-slate-200 overflow-hidden"
            >
              <button
                onClick={() => setSelectedFacility(null)}
                className="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
              >
                <X size={20} />
              </button>

              <div className="space-y-4">
                <span className="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider inline-block">
                  DETAIL FASILITAS
                </span>
                <h3 className="text-2xl font-black text-slate-900">{selectedFacility.name}</h3>
                <p className="text-slate-600 text-sm font-medium leading-relaxed">
                  {selectedFacility.description}
                </p>

                <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                  <h4 className="font-extrabold text-xs text-slate-800 uppercase tracking-wider">Keunggulan Layanan:</h4>
                  <ul className="space-y-1.5 text-xs text-slate-600 font-medium">
                    <li className="flex items-center gap-2">
                      <CheckCircle2 size={16} className="text-accent" /> Didukung area parkir luas &amp; aman 24 jam
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 size={16} className="text-accent" /> Kebersihan terjamin &amp; musholla bersih di setiap lokasi
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 size={16} className="text-accent" /> Terhubung langsung dengan sistem poin Member Digital
                    </li>
                  </ul>
                </div>

                <div className="pt-2 flex gap-3">
                  <button
                    onClick={() => setSelectedFacility(null)}
                    className="flex-1 py-3 bg-primary text-white font-bold text-xs rounded-xl hover:bg-primary-light transition-colors"
                  >
                    Tutup
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
