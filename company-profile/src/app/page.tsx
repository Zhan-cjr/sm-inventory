"use client";

import { motion, useAnimation, useInView } from "framer-motion";
import Link from "next/link";
import { ArrowRight, MapPin, ShoppingCart, Sparkles, Building2, Users } from "lucide-react";
import { companyStats } from "@/lib/data";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";
import { useEffect, useRef, useState } from "react";
import { Star, Quote } from "lucide-react";

// Counter animation component
function AnimatedCounter({ value, label }: { value: string, label: string }) {
  const numericValue = parseInt(value.replace(/[^0-9]/g, ''));
  const suffix = value.replace(/[0-9]/g, '');
  const [count, setCount] = useState(0);
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: "-50px" });

  useEffect(() => {
    if (isInView) {
      let start = 0;
      const duration = 2000;
      const stepTime = Math.abs(Math.floor(duration / numericValue));
      
      const timer = setInterval(() => {
        start += 1;
        setCount(start);
        if (start === numericValue) clearInterval(timer);
      }, stepTime > 0 ? stepTime : 10);
      
      return () => clearInterval(timer);
    }
  }, [isInView, numericValue]);

  return (
    <div ref={ref} className="text-center px-4 relative z-10 group">
      <div className="absolute inset-0 bg-blue-500/10 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
      <h3 className="text-5xl md:text-6xl font-bold bg-gradient-to-r from-blue-400 to-blue-600 text-gradient mb-3 drop-shadow-sm">
        {count}{suffix}
      </h3>
      <p className="text-slate-400 font-medium tracking-wide uppercase text-sm">{label}</p>
    </div>
  );
}

export default function Home() {
  const { facilities, settings, isLoading } = useCompanyProfile();
  const [testimonials, setTestimonials] = useState<any[]>([]);

  useEffect(() => {
    fetch(`${process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id/api/company-profile'}/testimonials`)
      .then(res => {
        if (!res.ok) throw new Error("Failed to fetch testimonials");
        return res.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          setTestimonials(data);
        } else {
          console.error("Testimonials API did not return an array", data);
          setTestimonials([]);
        }
      })
      .catch(err => {
        console.error(err);
        setTestimonials([]);
      });
  }, []);

  if (isLoading) {
    return (
      <div className="h-screen flex justify-center items-center bg-slate-950">
        <div className="relative">
          <div className="absolute inset-0 bg-blue-500 blur-xl opacity-20 rounded-full animate-pulse" />
          <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-blue-500 relative z-10"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-slate-950 min-h-screen text-slate-50 selection:bg-blue-500/30">
      
      {/* Premium Hero Section */}
      <section className="relative h-screen min-h-[700px] flex items-center justify-center overflow-hidden">
        {/* Animated Background Blobs */}
        <div className="absolute top-0 -left-4 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-[128px] opacity-30 animate-blob" />
        <div className="absolute top-0 -right-4 w-72 h-72 bg-blue-700 rounded-full mix-blend-multiply filter blur-[128px] opacity-30 animate-blob animation-delay-2000" />
        <div className="absolute -bottom-8 left-20 w-72 h-72 bg-blue-900 rounded-full mix-blend-multiply filter blur-[128px] opacity-30 animate-blob animation-delay-4000" />
        
        {/* Subtle Grid Pattern Overlay */}
        <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-[0.03] z-0" />

        <div className="container mx-auto px-4 relative z-20 pt-24">
          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 1, ease: "easeOut" }}
            className="max-w-4xl mx-auto text-center"
          >
            <motion.div 
              initial={{ opacity: 0, scale: 0.8 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.2, duration: 0.8 }}
              className="inline-block px-4 py-1.5 rounded-full glass-panel border-white/10 text-lime-400 text-xs sm:text-sm font-semibold tracking-widest uppercase mb-6 bg-lime-500/10 border-lime-500/20"
            >
              THE MOSLEM FAMILY
            </motion.div>

            <h1 className="text-4xl sm:text-5xl md:text-7xl lg:text-8xl font-extrabold leading-[1.1] mb-6 sm:mb-8 tracking-tight">
              Lengkap, Nyaman, <br className="hidden md:block"/>
              <span className="bg-gradient-to-r from-blue-400 via-blue-500 to-red-400 text-gradient pb-2 drop-shadow-lg">
                Penuh Berkah.
              </span>
            </h1>
            
            <p className="text-base sm:text-lg md:text-2xl text-slate-400 mb-8 sm:mb-12 max-w-2xl mx-auto leading-relaxed px-2">
              Toserba Selamat menghadirkan pengalaman belanja premium dan fasilitas terintegrasi dengan nilai-nilai syariah yang menenangkan.
            </p>
            
            <div className="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-5 px-4 sm:px-0">
              <Link 
                href="/locations" 
                className="w-full sm:w-auto bg-blue-500 hover:bg-blue-400 text-slate-950 px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl font-bold text-base sm:text-lg transition-all flex items-center justify-center gap-3 hover:-translate-y-1 hover:shadow-[0_0_40px_-10px_rgba(59,130,246,0.5)]"
              >
                <MapPin size={22} />
                Cari Cabang Terdekat
              </Link>
              <Link 
                href="/facilities" 
                className="w-full sm:w-auto glass-panel hover:bg-white/10 text-white px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl font-semibold text-base sm:text-lg transition-all flex items-center justify-center gap-3 hover:-translate-y-1"
              >
                Jelajahi Fasilitas
                <ArrowRight size={22} className="text-blue-400" />
              </Link>
            </div>
          </motion.div>
        </div>

        {/* Scroll Indicator */}
        <motion.div 
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 1.5, duration: 1 }}
          className="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-slate-500"
        >
          <span className="text-xs uppercase tracking-widest font-medium">Scroll ke bawah</span>
          <div className="w-[1px] h-12 bg-gradient-to-b from-slate-500 to-transparent" />
        </motion.div>
      </section>

      {/* Stats Section */}
      <section className="py-12 sm:py-20 relative z-30 border-y border-white/5 bg-slate-900/50 backdrop-blur-3xl">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-y-10 gap-x-4 md:gap-8 divide-x divide-white/5">
            {companyStats.map((stat, index) => (
               <AnimatedCounter key={index} value={stat.value} label={stat.label} />
            ))}
          </div>
        </div>
      </section>

      {/* Facilities Grid with 3D Hover */}
      <section className="py-20 sm:py-32 relative">
        <div className="container mx-auto px-4">
          <div className="text-center max-w-3xl mx-auto mb-12 sm:mb-20">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold mb-4 sm:mb-6">Layanan & <span className="text-blue-500">Unit Bisnis</span></h2>
            <p className="text-slate-400 text-base sm:text-lg md:text-xl leading-relaxed">Ekosistem fasilitas lengkap yang dirancang khusus untuk memenuhi gaya hidup sehat, modern, dan islami bagi seluruh anggota keluarga Anda.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
            {facilities.slice(0, 6).map((facility: any, index: number) => {
              const Icon = IconMapper[facility.icon] || ShoppingCart;
              return (
                <motion.div
                  key={facility.id}
                  initial={{ opacity: 0, y: 40 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true, margin: "-100px" }}
                  transition={{ duration: 0.6, delay: index * 0.1 }}
                  className="group relative"
                >
                  <div className="absolute inset-0 bg-gradient-to-b from-blue-500/20 to-transparent opacity-0 group-hover:opacity-100 blur-xl transition-opacity duration-500 rounded-3xl" />
                  <div className="relative glass-panel-dark rounded-3xl p-6 sm:p-8 h-full border border-white/10 hover:border-blue-500/50 transition-all duration-500 hover:-translate-y-2 overflow-hidden">
                    <div className="absolute -right-10 -top-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-colors duration-500" />
                    
                    <div className="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-slate-800/80 border border-white/5 flex items-center justify-center text-blue-400 mb-6 sm:mb-8 shadow-[0_8px_16px_rgb(0_0_0/0.4)] group-hover:scale-110 transition-transform duration-500">
                      <Icon size={28} className="sm:w-8 sm:h-8" />
                    </div>
                    
                    <h3 className="text-xl sm:text-2xl font-bold text-white mb-3 sm:mb-4 group-hover:text-blue-300 transition-colors">{facility.name}</h3>
                    <p className="text-sm sm:text-base text-slate-400 leading-relaxed group-hover:text-slate-300 transition-colors">{facility.description}</p>
                  </div>
                </motion.div>
              );
            })}
          </div>
          
          <div className="mt-20 text-center">
            <Link 
              href="/facilities" 
              className="inline-flex items-center gap-3 text-blue-400 font-bold hover:text-blue-300 transition-colors text-lg group"
            >
              Lihat Detail Seluruh Fasilitas 
              <span className="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center group-hover:bg-blue-500/40 transition-colors">
                <ArrowRight size={20} />
              </span>
            </Link>
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      {testimonials.length > 0 && (
        <section className="py-24 bg-slate-950 relative overflow-hidden">
          <div className="absolute top-1/2 left-0 w-96 h-96 bg-lime-500/10 rounded-full blur-[100px] pointer-events-none" />
          <div className="container mx-auto px-4 max-w-7xl relative z-10">
            <div className="text-center mb-16">
              <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
                Apa Kata <span className="text-blue-500">Mereka</span>
              </h2>
              <p className="text-slate-400 text-base sm:text-lg">Pengalaman nyata dari pelanggan setia dan mitra kami.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {testimonials.slice(0, 3).map((testi, i) => (
                <motion.div 
                  key={testi.id}
                  initial={{ opacity: 0, y: 30 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5, delay: i * 0.1 }}
                  className="glass-panel-dark p-8 rounded-3xl border border-white/5 relative group hover:border-blue-500/30 transition-all"
                >
                  <Quote size={40} className="text-blue-500/20 absolute top-6 right-6" />
                  <div className="flex gap-1 mb-6">
                    {[...Array(5)].map((_, j) => (
                      <Star key={j} size={18} className={j < testi.rating ? "text-yellow-400 fill-yellow-400" : "text-slate-600"} />
                    ))}
                  </div>
                  <p className="text-slate-300 italic mb-8 relative z-10 leading-relaxed">&quot;{testi.content}&quot;</p>
                  
                  <div className="flex items-center gap-4 mt-auto">
                    {testi.avatar_url ? (
                      <img src={`${new URL(process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id').origin}/storage/${testi.avatar_url}`} alt={testi.customer_name} className="w-12 h-12 rounded-full object-cover" />
                    ) : (
                      <div className="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center font-bold text-white">
                        {testi.customer_name.charAt(0)}
                      </div>
                    )}
                    <div>
                      <h4 className="text-white font-bold text-sm">{testi.customer_name}</h4>
                      {testi.role && <p className="text-slate-500 text-xs">{testi.role}</p>}
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Premium CTA Section */}
      <section className="py-16 sm:py-24 relative overflow-hidden">
        <div className="absolute inset-0 bg-blue-950/40" />
        <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=2574&auto=format&fit=crop')] bg-cover bg-fixed bg-center opacity-10 mix-blend-luminosity" />
        <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-slate-950" />
        
        <div className="container mx-auto px-4 relative z-10">
          <motion.div 
            initial={{ opacity: 0, scale: 0.95 }}
            whileInView={{ opacity: 1, scale: 1 }}
            viewport={{ once: true }}
            transition={{ duration: 0.8 }}
            className="glass-panel-dark rounded-[2rem] sm:rounded-[3rem] p-8 sm:p-10 md:p-20 text-center max-w-5xl mx-auto border border-blue-500/20 shadow-[0_0_80px_rgba(59,130,246,0.15)] relative overflow-hidden"
          >
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-3/4 h-1/2 bg-blue-500/20 blur-[100px] rounded-full pointer-events-none" />
            
            <Building2 size={40} className="mx-auto text-blue-500 mb-6 sm:mb-8 sm:w-12 sm:h-12" />
            <h2 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 sm:mb-6 leading-tight">
              Hadir Lebih Dekat di <br className="hidden sm:block"/>
              <span className="text-blue-400">26 Titik Lokasi</span>
            </h2>
            <p className="text-slate-300 text-base sm:text-lg md:text-xl mb-8 sm:mb-12 leading-relaxed max-w-2xl mx-auto">
              Ekspansi kami berfokus pada kemudahan akses bagi keluarga Anda. Temukan Toserba Selamat terdekat dan nikmati fasilitas modern di kota Anda.
            </p>
            <Link 
              href="/locations" 
              className="inline-flex items-center justify-center gap-3 bg-white text-slate-900 hover:bg-blue-50 px-6 sm:px-10 py-4 sm:py-5 rounded-xl sm:rounded-2xl font-bold text-base sm:text-lg w-full sm:w-max transition-all hover:scale-105 shadow-xl"
            >
              Lihat Peta Cabang <MapPin size={20} className="text-blue-600 sm:w-6 sm:h-6" />
            </Link>
          </motion.div>
        </div>
      </section>
    </div>
  );
}
