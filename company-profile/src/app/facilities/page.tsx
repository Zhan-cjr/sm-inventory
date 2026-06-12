"use client";

import { motion } from "framer-motion";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";
import { ShoppingCart } from "lucide-react";

export default function FacilitiesPage() {
  const { facilities, isLoading } = useCompanyProfile();

  if (isLoading) {
    return (
      <div className="pt-40 pb-0 min-h-screen flex justify-center items-center">
        <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-emerald-600"></div>
      </div>
    );
  }

  return (
    <div className="pt-28 pb-0 min-h-screen">
      {/* Hero Section */}
      <div className="container mx-auto px-4 mb-20">
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-center max-w-4xl mx-auto"
        >
          <h1 className="text-4xl md:text-6xl font-bold text-slate-900 mb-6 leading-tight">Fasilitas & Layanan <br className="hidden md:block"/> <span className="text-emerald-600">Terbaik untuk Anda</span></h1>
          <p className="text-xl text-slate-600 leading-relaxed max-w-2xl mx-auto">
            Eksplorasi 11 pilar layanan utama kami yang dirancang khusus untuk memenuhi gaya hidup, kebutuhan harian, hiburan, hingga relaksasi keluarga Anda.
          </p>
        </motion.div>
      </div>

      {/* Facilities Sections */}
      <div className="flex flex-col">
        {facilities.map((facility: any, idx: number) => {
          const Icon = IconMapper[facility.icon] || ShoppingCart;
          const isEven = idx % 2 === 0;
          
          // Determine theme classes based on index
          const sectionClass = isEven ? "bg-slate-900 text-white" : "bg-white text-slate-900";
          const titleClass = isEven ? "text-white" : "text-slate-900";
          const descClass = isEven ? "text-slate-300" : "text-slate-600";
          const iconBg = isEven ? "bg-white/10" : "bg-emerald-50";
          const iconColor = isEven ? "text-emerald-400" : "text-emerald-600";
          const borderColor = isEven ? "border-slate-800" : "border-slate-100";
          const highlightText = isEven ? "text-emerald-400" : "text-emerald-600";
          const dotColor = isEven ? "bg-emerald-500" : "bg-emerald-600";
          const subtleText = isEven ? "text-slate-400" : "text-slate-500";

          return (
            <motion.section 
              key={facility.id}
              initial={{ opacity: 0 }}
              whileInView={{ opacity: 1 }}
              viewport={{ once: true, margin: "-100px" }}
              transition={{ duration: 0.7 }}
              className={`py-24 relative overflow-hidden ${sectionClass} ${!isEven && 'border-y border-slate-100'}`}
            >
              {/* Optional background pattern for even sections */}
              {isEven && (
                <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5 pointer-events-none" />
              )}
              
              <div className="container mx-auto px-4 relative z-10">
                <div className={`flex flex-col md:flex-row items-center gap-12 lg:gap-24`}>
                  
                  {/* Content */}
                  <div className={`w-full md:w-1/2 ${isEven ? 'md:order-1' : 'md:order-2'}`}>
                    <motion.div
                      initial={{ opacity: 0, x: isEven ? -50 : 50 }}
                      whileInView={{ opacity: 1, x: 0 }}
                      viewport={{ once: true }}
                      transition={{ duration: 0.5, delay: 0.2 }}
                    >
                      <div className={`w-20 h-20 rounded-2xl flex items-center justify-center mb-8 ${iconBg} ${iconColor} shadow-lg`}>
                        <Icon size={40} />
                      </div>
                      <h2 className={`text-4xl lg:text-5xl font-bold mb-6 ${titleClass}`}>{facility.name}</h2>
                      <p className={`text-xl leading-relaxed mb-8 ${descClass}`}>
                        {facility.description}
                      </p>
                      
                      <div className={`pt-6 border-t ${borderColor}`}>
                        <ul className="space-y-4">
                          <li className={`flex items-center gap-3 text-lg font-medium ${subtleText}`}>
                            <span className={`w-2 h-2 rounded-full ${dotColor}`} />
                            Tersedia secara eksklusif di cabang-cabang pilihan kami.
                          </li>
                          <li className={`flex items-center gap-3 text-lg font-medium ${subtleText}`}>
                            <span className={`w-2 h-2 rounded-full ${dotColor}`} />
                            Pelayanan profesional dan <span className={`font-bold ${highlightText}`}>berstandar tinggi</span>.
                          </li>
                        </ul>
                      </div>
                    </motion.div>
                  </div>
                  
                  {/* Image/Visual */}
                  <div className={`w-full md:w-1/2 ${isEven ? 'md:order-2' : 'md:order-1'}`}>
                    <motion.div
                      initial={{ opacity: 0, scale: 0.9 }}
                      whileInView={{ opacity: 1, scale: 1 }}
                      viewport={{ once: true }}
                      transition={{ duration: 0.5 }}
                      className={`relative aspect-[4/3] rounded-3xl overflow-hidden shadow-2xl ${isEven ? 'shadow-black/50' : 'shadow-slate-200/50'}`}
                    >
                      <img 
                        src={facility.image_url 
                          ? (facility.image_url.startsWith('http') ? facility.image_url : `/storage/${facility.image_url}`)
                          : (isEven 
                            ? "https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=2574&auto=format&fit=crop" 
                            : "https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=2574&auto=format&fit=crop")} 
                        alt={facility.name} 
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                      />
                      <div className={`absolute inset-0 bg-gradient-to-t ${isEven ? 'from-slate-900/80' : 'from-slate-900/40'} to-transparent`} />
                      <div className="absolute bottom-6 left-6 right-6">
                        <div className="bg-white/20 backdrop-blur-md border border-white/30 text-white p-4 rounded-xl">
                          <p className="font-semibold text-lg flex items-center gap-2">
                            <Icon size={20} /> Preview Ruang {facility.name}
                          </p>
                        </div>
                      </div>
                    </motion.div>
                  </div>

                </div>
              </div>
            </motion.section>
          );
        })}
      </div>
    </div>
  );
}
