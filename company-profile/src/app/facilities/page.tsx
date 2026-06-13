"use client";

import { motion, useScroll, useTransform } from "framer-motion";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";
import { ShoppingCart, Sparkles } from "lucide-react";
import { useRef, useEffect } from "react";

// Parallax Image Component
function ParallaxImage({ src, alt }: { src: string, alt: string }) {
  const ref = useRef(null);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start end", "end start"]
  });
  
  const y = useTransform(scrollYProgress, [0, 1], ["-15%", "15%"]);

  return (
    <div ref={ref} className="relative w-full h-full overflow-hidden rounded-[2rem] shadow-2xl shadow-slate-300/50 group border border-slate-200">
      <div className="absolute inset-0 bg-primary/10 mix-blend-overlay z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
      <motion.img 
        style={{ y, scale: 1.15 }}
        src={src} 
        alt={alt} 
        className="w-full h-full object-cover origin-center"
      />
    </div>
  );
}

export default function FacilitiesPage() {
  const { facilities, isLoading } = useCompanyProfile();

  useEffect(() => {
    if (!isLoading && facilities.length > 0) {
      const hash = window.location.hash;
      if (hash) {
        setTimeout(() => {
          const element = document.querySelector(hash);
          if (element) {
            // Offset for fixed navbar if any, otherwise standard scrollIntoView
            const y = element.getBoundingClientRect().top + window.scrollY - 100;
            window.scrollTo({ top: y, behavior: 'smooth' });
          }
        }, 300);
      }
    }
  }, [isLoading, facilities]);

  if (isLoading) {
    return (
      <div className="pt-40 pb-0 min-h-screen flex justify-center items-center bg-slate-50">
        <div className="relative">
          <div className="absolute inset-0 bg-primary blur-xl opacity-20 rounded-full animate-pulse" />
          <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-primary relative z-10"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-slate-50 min-h-screen text-slate-900 relative overflow-hidden">
      {/* Premium Hero Section */}
      <div className="relative pt-40 pb-20 lg:pt-48 lg:pb-32 overflow-hidden border-b border-slate-200">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-[500px] bg-primary/20 rounded-full blur-[150px] pointer-events-none" />
        
        <div className="container mx-auto px-4 relative z-10">
          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="text-center max-w-4xl mx-auto"
          >
            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.2 }}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-panel border-primary/20 text-primary bg-white/80 text-sm font-bold tracking-widest uppercase mb-6"
            >
              <Sparkles size={16} /> Layanan Premium
            </motion.div>
            
            <h1 className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-slate-900 mb-6 sm:mb-8 leading-[1.1] tracking-tight">
              Fasilitas & Layanan <br className="hidden md:block"/> 
              <span className="bg-gradient-to-r from-primary via-accent to-secondary text-gradient drop-shadow-sm">Terbaik untuk Anda</span>
            </h1>
            
            <p className="text-base sm:text-xl md:text-2xl text-slate-600 font-medium leading-relaxed max-w-3xl mx-auto px-4 sm:px-0">
              Eksplorasi ekosistem fasilitas kami yang dirancang khusus untuk memenuhi gaya hidup, kebutuhan harian, hiburan, hingga relaksasi keluarga Anda.
            </p>
          </motion.div>
        </div>
      </div>

      {/* Facilities Sections */}
      <div className="flex flex-col relative z-20">
        {facilities.map((facility: any, idx: number) => {
          const Icon = IconMapper[facility.icon] || ShoppingCart;
          const isEven = idx % 2 === 0;
          
          return (
            <section 
              id={facility.name.toLowerCase().includes('padel') ? 'padel' : facility.name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}
              key={facility.id}
              className={`py-16 sm:py-24 lg:py-32 relative overflow-hidden border-b border-slate-200`}
            >
              <div className="container mx-auto px-4 relative z-10">
                <div className={`flex flex-col lg:flex-row items-center gap-12 lg:gap-24`}>
                  
                  {/* Content */}
                  <div className={`w-full lg:w-1/2 ${isEven ? 'lg:order-1' : 'lg:order-2'}`}>
                    <motion.div
                      initial={{ opacity: 0, x: isEven ? -50 : 50 }}
                      whileInView={{ opacity: 1, x: 0 }}
                      viewport={{ once: true, margin: "-100px" }}
                      transition={{ duration: 0.8 }}
                    >
                      <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-[1.5rem] flex items-center justify-center mb-6 sm:mb-8 bg-white border border-primary/20 text-primary shadow-[0_0_30px_rgba(36,42,122,0.1)]">
                        <Icon size={32} className="sm:w-10 sm:h-10" />
                      </div>
                      
                      <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4 sm:mb-6 text-slate-900 tracking-tight">{facility.name}</h2>
                      <p className="text-base sm:text-xl leading-relaxed mb-8 sm:mb-10 text-slate-600 font-medium">
                        {facility.description}
                      </p>
                      
                      <div className="p-6 rounded-2xl bg-white shadow-lg border border-slate-200 relative overflow-hidden group">
                        <div className="absolute inset-0 bg-gradient-to-r from-primary/0 via-primary/5 to-primary/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" />
                        <ul className="space-y-4 relative z-10">
                          <li className="flex items-start gap-4 text-base sm:text-lg text-slate-700">
                            <div className="mt-1.5 w-2 h-2 rounded-full bg-primary shrink-0 shadow-[0_0_8px_rgba(36,42,122,0.5)]" />
                            <span>Tersedia secara eksklusif di cabang-cabang pilihan kami dengan standar kualitas tertinggi.</span>
                          </li>
                          <li className="flex items-start gap-4 text-base sm:text-lg text-slate-700">
                            <div className="mt-1.5 w-2 h-2 rounded-full bg-primary shrink-0 shadow-[0_0_8px_rgba(36,42,122,0.5)]" />
                            <span>Pelayanan profesional, ramah keluarga, dan <span className="font-bold text-primary">berprinsip islami</span>.</span>
                          </li>
                        </ul>
                      </div>

                      {facility.name.toLowerCase().includes('padel') && (
                        <div className="mt-8">
                          <a 
                            href="https://padel.admselamat.my.id" 
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-primary-light hover:-translate-y-1 transition-all"
                          >
                            <Sparkles size={20} />
                            Booking Lapangan Padel
                            <span className="text-xs ml-2 bg-white/20 px-2 py-0.5 rounded-full font-medium tracking-wide">
                              (Tahap Pengembangan)
                            </span>
                          </a>
                        </div>
                      )}
                    </motion.div>
                  </div>
                  
                  {/* Image/Visual Parallax */}
                  <div className={`w-full lg:w-1/2 aspect-square md:aspect-[4/3] lg:aspect-square ${isEven ? 'lg:order-2' : 'lg:order-1'} p-2 sm:p-4 lg:p-8 mt-4 lg:mt-0`}>
                    <ParallaxImage 
                      src={facility.image_url 
                        ? (facility.image_url.startsWith('http') ? facility.image_url : `/storage/${facility.image_url}`)
                        : (isEven 
                          ? "https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=2574&auto=format&fit=crop" 
                          : "https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=2574&auto=format&fit=crop")}
                      alt={facility.name}
                    />
                  </div>

                </div>
              </div>
            </section>
          );
        })}
      </div>
    </div>
  );
}
