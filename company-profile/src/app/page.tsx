"use client";

import { motion } from "framer-motion";
import Link from "next/link";
import { ArrowRight, MapPin, ShoppingCart } from "lucide-react";
import { companyStats } from "@/lib/data";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";

const fadeIn = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.6 } }
};

const staggerContainer = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: {
      staggerChildren: 0.1
    }
  }
};

export default function Home() {
  const { facilities, settings, isLoading } = useCompanyProfile();

  if (isLoading) {
    return (
      <div className="h-screen flex justify-center items-center">
        <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-emerald-600"></div>
      </div>
    );
  }

  return (
    <>
      {/* Hero Section */}
      <section className="relative h-screen min-h-[600px] flex items-center justify-center overflow-hidden">
        {/* Placeholder for Video/Image Background */}
        <div className="absolute inset-0 z-0 bg-slate-900">
          <div className="absolute inset-0 bg-gradient-to-r from-black/70 to-black/40 z-10" />
          <img 
            src="https://images.unsplash.com/photo-1604719312566-8912e9227c6a?q=80&w=2574&auto=format&fit=crop" 
            alt="Toserba Selamat Hero" 
            className="w-full h-full object-cover opacity-60"
          />
        </div>

        <div className="container mx-auto px-4 relative z-20 pt-20">
          <motion.div 
            initial="hidden"
            animate="visible"
            variants={staggerContainer}
            className="max-w-3xl text-white"
          >
            <motion.h1 variants={fadeIn} className="text-5xl md:text-7xl font-bold leading-tight mb-6 text-balance">
              Lengkap, Nyaman, dan Penuh Berkah.
            </motion.h1>
            <motion.p variants={fadeIn} className="text-lg md:text-xl text-slate-200 mb-10 text-balance max-w-2xl">
              Toserba Selamat menghadirkan pengalaman belanja dan fasilitas premium untuk seluruh keluarga dengan nilai-nilai syariah yang menenangkan.
            </motion.p>
            
            <motion.div variants={fadeIn} className="flex flex-wrap gap-4">
              <Link 
                href="/locations" 
                className="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-full font-semibold text-lg transition-all flex items-center gap-2 hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-500/30"
              >
                <MapPin size={20} />
                Cari Cabang Terdekat
              </Link>
              <Link 
                href="/facilities" 
                className="bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/30 text-white px-8 py-4 rounded-full font-semibold text-lg transition-all flex items-center gap-2"
              >
                Jelajahi Fasilitas
                <ArrowRight size={20} />
              </Link>
            </motion.div>
          </motion.div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-16 bg-white relative -mt-10 z-30 rounded-t-[3rem] shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-slate-100">
            {companyStats.map((stat, index) => (
               <motion.div 
                 key={index}
                 initial={{ opacity: 0, y: 20 }}
                 whileInView={{ opacity: 1, y: 0 }}
                 viewport={{ once: true }}
                 transition={{ delay: index * 0.1, duration: 0.5 }}
                 className="text-center px-4"
               >
                 <h3 className="text-4xl md:text-5xl font-bold text-emerald-600 mb-2">{stat.value}</h3>
                 <p className="text-slate-500 font-medium">{stat.label}</p>
               </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Facilities Grid */}
      <section className="py-24 bg-slate-50">
        <div className="container mx-auto px-4">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Layanan & Unit Bisnis</h2>
            <p className="text-slate-600 text-lg">Lebih dari sekadar tempat belanja, kami menawarkan ekosistem fasilitas lengkap untuk gaya hidup sehat dan modern.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {facilities.slice(0, 6).map((facility: any, index: number) => {
              const Icon = IconMapper[facility.icon] || ShoppingCart;
              return (
                <motion.div
                  key={facility.id}
                  initial={{ opacity: 0, y: 30 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true, margin: "-50px" }}
                  transition={{ duration: 0.5, delay: index * 0.05 }}
                  className="group bg-white rounded-2xl p-8 border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden relative"
                >
                  <div className="absolute top-0 right-0 w-32 h-32 bg-emerald-600/5 rounded-bl-full -z-0 transition-transform duration-500 group-hover:scale-150" />
                  <div className="relative z-10">
                    <div className="w-14 h-14 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                      <Icon size={28} />
                    </div>
                    <h3 className="text-xl font-bold text-slate-900 mb-3">{facility.name}</h3>
                    <p className="text-slate-600 leading-relaxed">{facility.description}</p>
                  </div>
                </motion.div>
              );
            })}
          </div>
          
          <div className="mt-16 text-center">
            <Link 
              href="/facilities" 
              className="inline-flex items-center gap-2 text-emerald-600 font-semibold hover:text-emerald-700 transition-colors"
            >
              Lihat Detail Fasilitas <ArrowRight size={18} />
            </Link>
          </div>
        </div>
      </section>

      {/* Highlights & Branches CTA */}
      <section className="py-24 bg-white">
        <div className="container mx-auto px-4">
          <div className="bg-slate-900 rounded-3xl overflow-hidden flex flex-col lg:flex-row shadow-2xl relative">
            <div className="absolute top-0 right-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5 pointer-events-none" />
            <div className="lg:w-1/2 p-10 lg:p-16 flex flex-col justify-center relative z-10">
              <h2 className="text-3xl md:text-4xl font-bold text-white mb-6 leading-tight">
                Hadir Lebih Dekat di <span className="text-amber-500">26 Titik Lokasi</span>
              </h2>
              <p className="text-slate-300 text-lg mb-8 leading-relaxed max-w-md">
                Ekspansi kami berfokus pada kemudahan akses bagi keluarga Anda. Temukan Toserba Selamat terdekat dan nikmati fasilitas modern di kota Anda.
              </p>
              <Link 
                href="/locations" 
                className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-400 text-white px-6 py-3 rounded-xl font-semibold w-max transition-all"
              >
                Lihat Peta Cabang <MapPin size={18} />
              </Link>
            </div>
            <div className="lg:w-1/2 min-h-[300px] lg:min-h-full relative">
              <img 
                src="https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=2574&auto=format&fit=crop" 
                alt="Cabang Toserba Selamat" 
                className="absolute inset-0 w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent lg:hidden" />
              <div className="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-slate-900 hidden lg:block" />
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
