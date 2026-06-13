"use client";

import { motion } from "framer-motion";
import { MapPin, Phone, Mail, MessageSquare, Send, Clock, Building2 } from "lucide-react";

const fadeIn = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.8 } }
};

export default function ContactPage() {
  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[800px] h-[800px] bg-secondary/10 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-primary/10 rounded-full blur-[100px] pointer-events-none" />

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
          <motion.div variants={fadeIn} className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-panel border-accent/30 text-accent text-xs sm:text-sm font-bold tracking-widest uppercase mb-6 bg-white/80 shadow-sm">
            <MessageSquare size={16} /> Hubungi Kami
          </motion.div>
          <motion.h1 variants={fadeIn} className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-slate-900 mb-6 sm:mb-8 tracking-tight">
            Mari <span className="bg-gradient-to-r from-primary to-secondary text-gradient">Terhubung</span>
          </motion.h1>
          <motion.p variants={fadeIn} className="text-base sm:text-lg md:text-xl text-slate-600 font-medium max-w-3xl mx-auto leading-relaxed px-2">
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
            <div className="bg-white shadow-xl p-8 rounded-3xl border border-slate-200 relative overflow-hidden group">
              <div className="absolute top-0 right-0 p-4 opacity-10">
                <Building2 size={120} />
              </div>
              <h3 className="text-2xl font-bold text-slate-900 mb-6 relative z-10">Kantor Pusat</h3>
              <div className="space-y-6 relative z-10">
                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                    <MapPin size={24} />
                  </div>
                  <div>
                    <h4 className="text-slate-900 font-bold mb-1">Alamat</h4>
                    <p className="text-slate-600 font-medium text-sm leading-relaxed">Jl. Raya Sukabumi No. 123, Kel. Gunungpuyuh, Kec. Gunungpuyuh, Kota Sukabumi, Jawa Barat 43123</p>
                  </div>
                </div>
                
                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-secondary/10 border border-secondary/20 flex items-center justify-center text-secondary shrink-0">
                    <Phone size={24} />
                  </div>
                  <div>
                    <h4 className="text-slate-900 font-bold mb-1">Telepon & WhatsApp</h4>
                    <p className="text-slate-600 font-medium text-sm mb-1">(0266) 123456</p>
                    <p className="text-slate-600 font-medium text-sm">+62 812 3456 7890 (WA Only)</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-accent/10 border border-accent/20 flex items-center justify-center text-accent shrink-0">
                    <Mail size={24} />
                  </div>
                  <div>
                    <h4 className="text-slate-900 font-bold mb-1">Email</h4>
                    <p className="text-slate-600 font-medium text-sm">cs@toserbaselamat.id</p>
                    <p className="text-slate-600 font-medium text-sm">partnership@toserbaselamat.id</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0">
                    <Clock size={24} />
                  </div>
                  <div>
                    <h4 className="text-slate-900 font-bold mb-1">Jam Operasional</h4>
                    <p className="text-slate-600 font-medium text-sm">Senin - Minggu: 08:00 - 21:00 WIB</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="bg-primary text-white p-8 rounded-3xl shadow-lg relative overflow-hidden group">
              <h3 className="text-xl font-bold text-white mb-2 relative z-10">Penawaran Supplier?</h3>
              <p className="text-white/80 font-medium text-sm mb-6 leading-relaxed relative z-10">Bagi UMKM dan Perusahaan yang ingin mengajukan penawaran produk untuk dijual di Toserba Selamat, silakan gunakan portal khusus kami.</p>
              <button className="w-full bg-white text-primary font-bold py-3 px-6 rounded-xl hover:bg-slate-50 transition-colors shadow-lg shadow-black/10 relative z-10">
                Portal Partnership
              </button>
              <div className="absolute -right-8 -bottom-8 w-40 h-40 bg-white/10 rounded-full blur-2xl group-hover:scale-110 transition-transform"></div>
            </div>
          </motion.div>

          {/* Contact Form */}
          <motion.div 
            initial={{ opacity: 0, x: 50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8 }}
            className="lg:col-span-3"
          >
            <div className="bg-white shadow-xl p-8 sm:p-12 rounded-[2rem] border border-slate-200 h-full relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-bl from-primary/5 to-transparent pointer-events-none" />
              
              <h2 className="text-3xl font-bold text-slate-900 mb-2 relative z-10">Kirim Pesan</h2>
              <p className="text-slate-600 font-medium mb-8 relative z-10">Isi formulir di bawah ini dan tim Customer Service kami akan membalas Anda secepatnya.</p>
              
              <form className="space-y-6 relative z-10" onSubmit={(e) => e.preventDefault()}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-sm font-bold text-slate-700 ml-1">Nama Lengkap</label>
                    <input 
                      type="text" 
                      placeholder="Masukkan nama Anda" 
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary/50 transition-all font-medium"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-bold text-slate-700 ml-1">Email</label>
                    <input 
                      type="email" 
                      placeholder="Masukkan alamat email" 
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary/50 transition-all font-medium"
                    />
                  </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-sm font-bold text-slate-700 ml-1">Nomor Handphone / WA</label>
                    <input 
                      type="tel" 
                      placeholder="Contoh: 081234567890" 
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary/50 transition-all font-medium"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-bold text-slate-700 ml-1">Subjek</label>
                    <select className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary/50 transition-all appearance-none cursor-pointer font-medium">
                      <option value="umum">Pertanyaan Umum</option>
                      <option value="keluhan">Keluhan & Saran</option>
                      <option value="karir">Seputar Karir & Lowongan</option>
                      <option value="lainnya">Lainnya</option>
                    </select>
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-bold text-slate-700 ml-1">Pesan Anda</label>
                  <textarea 
                    rows={5}
                    placeholder="Tulis pesan Anda secara detail..." 
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary/50 transition-all resize-none font-medium"
                  ></textarea>
                </div>

                <button 
                  type="submit"
                  className="bg-primary hover:bg-primary/90 text-white px-8 py-4 rounded-xl font-bold transition-all shadow-[0_0_20px_rgba(36,42,122,0.2)] hover:shadow-[0_0_25px_rgba(36,42,122,0.4)] hover:-translate-y-1 w-full sm:w-auto inline-flex justify-center items-center gap-3"
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
