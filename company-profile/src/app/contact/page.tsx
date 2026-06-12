"use client";

import { motion } from "framer-motion";
import { MapPin, Phone, Mail, MessageSquare, Send, Clock, Building2 } from "lucide-react";

const fadeIn = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.8 } }
};

export default function ContactPage() {
  return (
    <div className="bg-slate-950 min-h-screen pt-28 pb-32 text-slate-50 relative overflow-hidden">
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[800px] h-[800px] bg-red-900/10 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-blue-900/20 rounded-full blur-[100px] pointer-events-none" />

      <div className="container mx-auto px-4 max-w-7xl relative z-10">
        
        {/* Header */}
        <motion.div 
          initial="hidden"
          animate="visible"
          variants={{
            hidden: { opacity: 0 },
            visible: { opacity: 1, transition: { staggerChildren: 0.2 } }
          }}
          className="text-center mb-16 sm:mb-24"
        >
          <motion.div variants={fadeIn} className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-panel border-white/10 text-lime-400 text-xs sm:text-sm font-semibold tracking-widest uppercase mb-6 bg-lime-500/10 border-lime-500/20">
            <MessageSquare size={16} /> Hubungi Kami
          </motion.div>
          <motion.h1 variants={fadeIn} className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-white mb-6 sm:mb-8 tracking-tight">
            Mari <span className="bg-gradient-to-r from-blue-400 to-red-500 text-gradient">Terhubung</span>
          </motion.h1>
          <motion.p variants={fadeIn} className="text-base sm:text-lg md:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed px-2">
            Kami siap mendengar dari Anda. Sampaikan pertanyaan, masukan, keluhan, atau penawaran kerjasama kepada tim layanan kami.
          </motion.p>
        </motion.div>

        <div className="grid lg:grid-cols-5 gap-8 lg:gap-12">
          {/* Contact Info */}
          <motion.div 
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8 }}
            className="lg:col-span-2 space-y-6"
          >
            <div className="glass-panel-dark p-8 rounded-3xl border border-white/5 relative overflow-hidden group">
              <div className="absolute top-0 right-0 p-4 opacity-10">
                <Building2 size={120} />
              </div>
              <h3 className="text-2xl font-bold text-white mb-6 relative z-10">Kantor Pusat</h3>
              <div className="space-y-6 relative z-10">
                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                    <MapPin size={24} />
                  </div>
                  <div>
                    <h4 className="text-white font-semibold mb-1">Alamat</h4>
                    <p className="text-slate-400 text-sm leading-relaxed">Jl. Raya Sukabumi No. 123, Kel. Gunungpuyuh, Kec. Gunungpuyuh, Kota Sukabumi, Jawa Barat 43123</p>
                  </div>
                </div>
                
                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 shrink-0">
                    <Phone size={24} />
                  </div>
                  <div>
                    <h4 className="text-white font-semibold mb-1">Telepon & WhatsApp</h4>
                    <p className="text-slate-400 text-sm mb-1">(0266) 123456</p>
                    <p className="text-slate-400 text-sm">+62 812 3456 7890 (WA Only)</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-lime-500/10 border border-lime-500/20 flex items-center justify-center text-lime-400 shrink-0">
                    <Mail size={24} />
                  </div>
                  <div>
                    <h4 className="text-white font-semibold mb-1">Email</h4>
                    <p className="text-slate-400 text-sm">cs@toserbaselamat.id</p>
                    <p className="text-slate-400 text-sm">partnership@toserbaselamat.id</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-slate-800/80 border border-white/10 flex items-center justify-center text-slate-300 shrink-0">
                    <Clock size={24} />
                  </div>
                  <div>
                    <h4 className="text-white font-semibold mb-1">Jam Operasional</h4>
                    <p className="text-slate-400 text-sm">Senin - Minggu: 08:00 - 21:00 WIB</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="glass-panel p-8 rounded-3xl border border-blue-500/20 bg-gradient-to-br from-blue-900/30 to-transparent">
              <h3 className="text-xl font-bold text-white mb-2">Penawaran Supplier?</h3>
              <p className="text-slate-400 text-sm mb-6 leading-relaxed">Bagi UMKM dan Perusahaan yang ingin mengajukan penawaran produk untuk dijual di Toserba Selamat, silakan gunakan portal khusus kami.</p>
              <button className="w-full bg-white text-blue-900 font-bold py-3 px-6 rounded-xl hover:bg-blue-50 transition-colors shadow-lg shadow-black/20">
                Portal Partnership
              </button>
            </div>
          </motion.div>

          {/* Contact Form */}
          <motion.div 
            initial={{ opacity: 0, x: 50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8 }}
            className="lg:col-span-3"
          >
            <div className="glass-panel-dark p-8 sm:p-12 rounded-[2rem] border border-white/5 h-full relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-bl from-blue-500/5 to-transparent pointer-events-none" />
              
              <h2 className="text-3xl font-bold text-white mb-2">Kirim Pesan</h2>
              <p className="text-slate-400 mb-8">Isi formulir di bawah ini dan tim Customer Service kami akan membalas Anda secepatnya.</p>
              
              <form className="space-y-6 relative z-10" onSubmit={(e) => e.preventDefault()}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Nama Lengkap</label>
                    <input 
                      type="text" 
                      placeholder="Masukkan nama Anda" 
                      className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Email</label>
                    <input 
                      type="email" 
                      placeholder="Masukkan alamat email" 
                      className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                    />
                  </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Nomor Handphone / WA</label>
                    <input 
                      type="tel" 
                      placeholder="Contoh: 081234567890" 
                      className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Subjek</label>
                    <select className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all appearance-none cursor-pointer">
                      <option value="umum">Pertanyaan Umum</option>
                      <option value="keluhan">Keluhan & Saran</option>
                      <option value="karir">Seputar Karir & Lowongan</option>
                      <option value="lainnya">Lainnya</option>
                    </select>
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium text-slate-300 ml-1">Pesan Anda</label>
                  <textarea 
                    rows={5}
                    placeholder="Tulis pesan Anda secara detail..." 
                    className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all resize-none"
                  ></textarea>
                </div>

                <button 
                  type="submit"
                  className="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-xl font-bold transition-all shadow-[0_0_20px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] hover:-translate-y-1 w-full sm:w-auto inline-flex justify-center items-center gap-3"
                >
                  <Send size={18} /> Kirim Pesan Sekarang
                </button>
              </form>
            </div>
          </motion.div>
        </div>
      </div>
    </div>
  );
}
