"use client";

import { motion } from "framer-motion";
import { Briefcase, MapPin, Clock, Users, ArrowRight, ShieldCheck, HeartHandshake, Zap } from "lucide-react";
import Link from "next/link";

const fadeIn = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.8 } }
};

const jobListings = [
  {
    id: 1,
    title: "Store Manager",
    department: "Operations",
    type: "Full-Time",
    location: "Bandung, Jawa Barat",
    desc: "Memimpin operasional toko, mengelola tim, dan memastikan target penjualan tercapai dengan standar pelayanan terbaik."
  },
  {
    id: 2,
    title: "Digital Marketing Specialist",
    department: "Marketing",
    type: "Full-Time",
    location: "Head Office - Sukabumi",
    desc: "Merancang dan mengeksekusi strategi kampanye digital, mengelola media sosial, dan menganalisis tren pasar."
  },
  {
    id: 3,
    title: "Customer Service Representative",
    department: "Service",
    type: "Shift",
    location: "Semua Cabang",
    desc: "Memberikan pelayanan prima kepada pelanggan, menangani keluhan, dan memberikan informasi promo terkini."
  },
  {
    id: 4,
    title: "IT Support Staff",
    department: "IT",
    type: "Full-Time",
    location: "Cianjur, Jawa Barat",
    desc: "Memelihara infrastruktur jaringan, hardware kasir, dan memberikan dukungan teknis untuk staf cabang."
  }
];

export default function CareersPage() {
  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[800px] h-[800px] bg-primary/10 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-secondary/10 rounded-full blur-[100px] pointer-events-none" />

      <div className="container mx-auto px-4 max-w-6xl relative z-10">
        
        {/* Header */}
        <motion.div 
          initial="hidden"
          animate="visible"
          variants={{
            hidden: { opacity: 0 },
            visible: { opacity: 1, transition: { staggerChildren: 0.2 } }
          }}
          className="text-center mb-20 sm:mb-24"
        >
          <motion.div variants={fadeIn} className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-panel border-accent/30 text-accent text-xs sm:text-sm font-bold tracking-widest uppercase mb-6 bg-white/80 shadow-sm">
            <Users size={16} /> Bergabung Bersama Kami
          </motion.div>
          <motion.h1 variants={fadeIn} className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-slate-900 mb-6 sm:mb-8 tracking-tight">
            Berkarir di <span className="bg-gradient-to-r from-primary via-primary/80 to-secondary text-gradient">Toserba Selamat</span>
          </motion.h1>
          <motion.p variants={fadeIn} className="text-base sm:text-lg md:text-xl text-slate-600 font-medium max-w-3xl mx-auto leading-relaxed px-2">
            Kami mengundang talenta-talenta terbaik untuk tumbuh dan berkembang bersama jaringan ritel terkemuka yang menjunjung tinggi nilai-nilai Islami dan kekeluargaan.
          </motion.p>
        </motion.div>

        {/* Culture Section */}
        <motion.div 
          initial={{ opacity: 0, y: 50 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
          className="mb-24 sm:mb-32"
        >
          <div className="text-center mb-12 sm:mb-16">
            <h2 className="text-3xl sm:text-4xl font-bold mb-4 text-slate-900">Budaya Kerja <span className="text-primary">Kami</span></h2>
            <p className="text-slate-600 font-medium text-base sm:text-lg">Lingkungan yang mendukung perkembangan karir dan spiritual Anda.</p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
            <div className="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-lg hover:shadow-xl hover:border-primary/30 transition-all group relative overflow-hidden">
              <div className="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors" />
              <HeartHandshake className="text-primary w-12 h-12 mb-6" />
              <h3 className="text-xl font-bold mb-3 text-slate-900">Kekeluargaan</h3>
              <p className="text-slate-600 font-medium text-sm sm:text-base leading-relaxed">Kami membangun relasi yang kuat antar karyawan, menciptakan suasana kerja yang hangat dan saling mendukung layaknya sebuah keluarga besar.</p>
            </div>
            <div className="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-lg hover:shadow-xl hover:border-primary/30 transition-all group relative overflow-hidden">
              <div className="absolute -right-10 -top-10 w-40 h-40 bg-secondary/5 rounded-full blur-3xl group-hover:bg-secondary/10 transition-colors" />
              <ShieldCheck className="text-secondary w-12 h-12 mb-6" />
              <h3 className="text-xl font-bold mb-3 text-slate-900">Prinsip Islami</h3>
              <p className="text-slate-600 font-medium text-sm sm:text-base leading-relaxed">Integritas, kejujuran, dan profesionalisme berlandaskan syariah menjadi panduan utama kami dalam melayani masyarakat.</p>
            </div>
            <div className="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-lg hover:shadow-xl hover:border-primary/30 transition-all group relative overflow-hidden">
              <div className="absolute -right-10 -top-10 w-40 h-40 bg-accent/5 rounded-full blur-3xl group-hover:bg-accent/10 transition-colors" />
              <Zap className="text-accent w-12 h-12 mb-6" />
              <h3 className="text-xl font-bold mb-3 text-slate-900">Inovatif & Dinamis</h3>
              <p className="text-slate-600 font-medium text-sm sm:text-base leading-relaxed">Kami terus beradaptasi dengan tren industri dan teknologi terbaru, memberikan ruang bagi Anda untuk berkreasi dan berinovasi.</p>
            </div>
          </div>
        </motion.div>

        {/* Job Openings */}
        <motion.div 
          initial={{ opacity: 0, y: 50 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
        >
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 sm:mb-12 gap-4">
            <div>
              <h2 className="text-3xl sm:text-4xl font-bold text-slate-900 mb-2">Posisi <span className="text-primary">Tersedia</span></h2>
              <p className="text-slate-600 font-medium text-base sm:text-lg">Temukan peluang karir yang sesuai dengan passion Anda.</p>
            </div>
            <button className="px-6 py-2.5 rounded-xl bg-white text-primary hover:text-white border border-primary hover:bg-primary/90 shadow-sm transition-all font-bold text-sm">
              Lihat Semua
            </button>
          </div>

          <div className="space-y-4 sm:space-y-6">
            {jobListings.map((job) => (
              <div 
                key={job.id} 
                className="bg-white shadow-lg p-6 sm:p-8 rounded-[2rem] border border-slate-200 hover:border-primary/30 transition-all group relative overflow-hidden flex flex-col md:flex-row gap-6 items-start md:items-center justify-between"
              >
                <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary opacity-0 group-hover:opacity-100 transition-opacity" />
                
                <div className="flex-1">
                  <div className="flex flex-wrap items-center gap-3 mb-3">
                    <span className="px-3 py-1 rounded-lg bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider border border-primary/20">{job.department}</span>
                    <span className="px-3 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-wider">{job.type}</span>
                  </div>
                  <h3 className="text-2xl font-bold text-slate-900 mb-2 group-hover:text-primary transition-colors">{job.title}</h3>
                  <div className="flex items-center gap-2 text-slate-600 font-medium text-sm mb-4">
                    <MapPin size={16} />
                    <span>{job.location}</span>
                  </div>
                  <p className="text-slate-600 font-medium text-sm sm:text-base leading-relaxed max-w-2xl">{job.desc}</p>
                </div>

                <button className="w-full md:w-auto mt-4 md:mt-0 flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-[0_0_20px_rgba(36,42,122,0.2)] hover:-translate-y-1">
                  Lamar Sekarang <ArrowRight size={18} />
                </button>
              </div>
            ))}
          </div>
          
          <div className="mt-16 p-8 sm:p-12 bg-white shadow-xl rounded-[2rem] border border-slate-200 text-center relative overflow-hidden">
            <div className="absolute inset-0 bg-primary/5 mix-blend-overlay pointer-events-none" />
            <Briefcase className="w-16 h-16 mx-auto text-primary mb-6 opacity-50" />
            <h3 className="text-2xl font-bold text-slate-900 mb-4">Belum menemukan posisi yang cocok?</h3>
            <p className="text-slate-600 font-medium max-w-2xl mx-auto mb-8 text-base sm:text-lg">Kirimkan CV dan portofolio Anda ke database kami. Kami akan menghubungi Anda jika ada posisi yang sesuai dengan profil Anda.</p>
            <button className="bg-primary text-white hover:bg-primary/90 px-8 py-4 rounded-xl font-bold transition-all shadow-xl hover:scale-105 inline-flex items-center gap-2">
              Kirimkan CV <ArrowRight size={18} />
            </button>
          </div>
        </motion.div>

      </div>
    </div>
  );
}
