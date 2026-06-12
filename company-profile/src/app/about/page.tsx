"use client";

import { motion } from "framer-motion";
import { CheckCircle2, HeartHandshake, Zap, ShieldCheck } from "lucide-react";

const fadeIn = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.8, ease: "easeOut" } }
};

export default function AboutPage() {
  const values = [
    { 
      title: "Customer First", 
      desc: "Prioritas utama kami adalah kepuasan dan kenyamanan berbelanja pelanggan.",
      icon: HeartHandshake,
      color: "from-rose-500 to-pink-500",
      span: "md:col-span-2 lg:col-span-1"
    },
    { 
      title: "Modern & Inovatif", 
      desc: "Terus beradaptasi dengan teknologi dan gaya hidup modern untuk layanan terbaik.",
      icon: Zap,
      color: "from-blue-500 to-cyan-500",
      span: "md:col-span-1"
    },
    { 
      title: "Ramah Keluarga & Islami", 
      desc: "Menyediakan lingkungan yang aman, nyaman, dan berprinsip syariah untuk seluruh anggota keluarga.",
      icon: ShieldCheck,
      color: "from-emerald-500 to-teal-500",
      span: "md:col-span-3 lg:col-span-1"
    }
  ];

  return (
    <div className="bg-slate-950 min-h-screen pt-28 pb-32 text-slate-50 relative overflow-hidden">
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[800px] h-[800px] bg-emerald-900/20 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-blue-900/10 rounded-full blur-[100px] pointer-events-none" />

      <div className="container mx-auto px-4 max-w-6xl relative z-10">
        
        {/* Header */}
        <motion.div 
          initial="hidden"
          animate="visible"
          variants={{
            hidden: { opacity: 0 },
            visible: { opacity: 1, transition: { staggerChildren: 0.2 } }
          }}
          className="text-center mb-24"
        >
          <motion.div variants={fadeIn} className="inline-block px-4 py-1.5 rounded-full glass-panel border-white/10 text-emerald-400 text-xs sm:text-sm font-semibold tracking-widest uppercase mb-6">
            Mengenal Lebih Dekat
          </motion.div>
          <motion.h1 variants={fadeIn} className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-white mb-6 sm:mb-8 tracking-tight">
            Tentang <span className="bg-gradient-to-r from-emerald-400 to-teal-500 text-gradient">Toserba Selamat</span>
          </motion.h1>
          <motion.p variants={fadeIn} className="text-base sm:text-lg md:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed px-2">
            Lebih dari sekadar pusat perbelanjaan, kami adalah destinasi gaya hidup terpadu yang memadukan kebutuhan modern dengan nilai-nilai luhur dan keramahan keluarga.
          </motion.p>
        </motion.div>

        {/* The Journey (Timeline Alternative) */}
        <div className="mb-32 relative">
          <div className="absolute left-1/2 -translate-x-1/2 top-0 bottom-0 w-px bg-gradient-to-b from-emerald-500/0 via-emerald-500/50 to-emerald-500/0 hidden lg:block" />
          
          <div className="grid lg:grid-cols-2 gap-10 sm:gap-16 lg:gap-24 items-center relative">
            <motion.div 
              initial={{ opacity: 0, x: -50 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true, margin: "-100px" }}
              transition={{ duration: 0.8 }}
              className="relative lg:text-right lg:pr-12"
            >
              <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4 sm:mb-6">Perjalanan Kami</h2>
              <p className="text-slate-400 mb-4 sm:mb-6 leading-relaxed text-base sm:text-lg">
                Berawal dari sebuah toko sederhana, Toserba Selamat telah berkembang pesat menjadi jaringan ritel dan hospitality terkemuka.
              </p>
              <p className="text-slate-400 leading-relaxed text-base sm:text-lg mb-6 sm:mb-8">
                Komitmen kami tidak pernah berubah: menyajikan produk berkualitas tinggi dengan harga terjangkau dalam balutan pelayanan yang sepenuh hati dan fasilitas terintegrasi yang lengkap.
              </p>
              <div className="inline-flex flex-col items-start lg:items-end p-5 sm:p-6 glass-panel-dark rounded-2xl border border-white/5 shadow-2xl relative overflow-hidden group">
                <div className="absolute inset-0 bg-emerald-500/10 translate-y-full group-hover:translate-y-0 transition-transform duration-500" />
                <div className="text-4xl sm:text-5xl font-black text-emerald-400 mb-2 relative z-10">26+</div>
                <div className="text-sm sm:text-base text-slate-300 font-medium relative z-10">Cabang Tersebar untuk melayani Anda lebih dekat.</div>
              </div>
            </motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true, margin: "-100px" }}
              transition={{ duration: 0.8 }}
              className="relative lg:pl-12"
            >
              <div className="absolute top-1/2 -left-[11px] w-5 h-5 rounded-full bg-slate-950 border-4 border-emerald-500 z-20 hidden lg:block shadow-[0_0_15px_rgba(16,185,129,0.5)]" />
              <div className="rounded-3xl overflow-hidden glass-panel p-2 shadow-2xl relative">
                <div className="absolute inset-0 bg-gradient-to-tr from-emerald-500/20 to-transparent mix-blend-overlay z-10" />
                <img 
                  src="https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=2574&auto=format&fit=crop" 
                  alt="Sejarah Kami" 
                  className="w-full h-auto object-cover rounded-2xl grayscale hover:grayscale-0 transition-all duration-700"
                />
              </div>
            </motion.div>
          </div>
        </div>

        {/* Core Values Bento Grid */}
        <motion.div 
          initial={{ opacity: 0, y: 50 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
          className="pt-10"
        >
          <div className="text-center mb-12 sm:mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold mb-4 text-white">Nilai Inti <span className="text-emerald-500">Perusahaan</span></h2>
            <p className="text-slate-400 text-base sm:text-lg md:text-xl">Prinsip yang membimbing setiap langkah dan pelayanan kami.</p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
            {values.map((val, idx) => {
              const Icon = val.icon;
              return (
                <div 
                  key={idx} 
                  className={`glass-panel-dark p-6 sm:p-8 md:p-10 rounded-3xl sm:rounded-[2rem] border border-white/5 hover:border-white/10 transition-colors group relative overflow-hidden ${val.span}`}
                >
                  <div className={`absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl ${val.color} opacity-0 group-hover:opacity-10 transition-opacity duration-700 rounded-full blur-[80px] -mr-20 -mt-20`} />
                  
                  <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-2xl mb-6 sm:mb-8 flex items-center justify-center bg-gradient-to-br ${val.color} shadow-lg shadow-black/20 group-hover:scale-110 transition-transform duration-500`}>
                    <Icon className="text-white sm:w-8 sm:h-8" size={28} />
                  </div>
                  
                  <h3 className="text-xl sm:text-2xl font-bold mb-3 sm:mb-4 text-white group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-white group-hover:to-slate-400 transition-all">{val.title}</h3>
                  <p className="text-slate-400 leading-relaxed text-sm sm:text-base md:text-lg">{val.desc}</p>
                </div>
              );
            })}
          </div>
        </motion.div>
      </div>
    </div>
  );
}
