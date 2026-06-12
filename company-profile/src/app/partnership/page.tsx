"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { Handshake, Store, TrendingUp, CheckCircle2, Building2, Send, AlertCircle } from "lucide-react";

const fadeIn = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.8 } }
};

export default function PartnershipPage() {
  const [formData, setFormData] = useState({
    business_name: "",
    owner_name: "",
    phone: "",
    email: "",
    category: "Pemasok Barang (Supplier)",
    description: "",
  });
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState<"idle" | "success" | "error">("idle");
  const [errorMessage, setErrorMessage] = useState("");

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setStatus("idle");
    setErrorMessage("");

    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id/api/v1'}/partnership`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json"
        },
        body: JSON.stringify(formData)
      });

      if (!res.ok) {
        const errorData = await res.json();
        throw new Error(errorData.message || "Terjadi kesalahan pada server");
      }

      setStatus("success");
      setFormData({
        business_name: "",
        owner_name: "",
        phone: "",
        email: "",
        category: "Pemasok Barang (Supplier)",
        description: "",
      });
    } catch (err: any) {
      console.error(err);
      setStatus("error");
      setErrorMessage(err.message);
    } finally {
      setLoading(false);
    }
  };

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
            <Handshake size={16} /> Kemitraan UMKM & B2B
          </motion.div>
          <motion.h1 variants={fadeIn} className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-white mb-6 sm:mb-8 tracking-tight">
            Tumbuh Bersama <span className="bg-gradient-to-r from-blue-400 to-lime-400 text-gradient">Kami</span>
          </motion.h1>
          <motion.p variants={fadeIn} className="text-base sm:text-lg md:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed px-2">
            Toserba Selamat membuka peluang kemitraan bagi UMKM dan Perusahaan yang memiliki produk unggulan untuk dipasarkan di seluruh jaringan ritel kami.
          </motion.p>
        </motion.div>

        <div className="grid lg:grid-cols-5 gap-8 lg:gap-12">
          {/* Info Section */}
          <motion.div 
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8 }}
            className="lg:col-span-2 space-y-6"
          >
            <div className="glass-panel-dark p-8 rounded-3xl border border-white/5 relative overflow-hidden group h-full">
              <h3 className="text-2xl font-bold text-white mb-6">Mengapa Bermitra?</h3>
              
              <div className="space-y-6">
                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                    <Store size={24} />
                  </div>
                  <div>
                    <h4 className="text-white font-semibold mb-1">Jaringan Penjualan Luas</h4>
                    <p className="text-slate-400 text-sm leading-relaxed">Akses ke puluhan cabang kami yang tersebar strategis dengan ribuan pelanggan aktif setiap harinya.</p>
                  </div>
                </div>
                
                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-lime-500/10 border border-lime-500/20 flex items-center justify-center text-lime-400 shrink-0">
                    <TrendingUp size={24} />
                  </div>
                  <div>
                    <h4 className="text-white font-semibold mb-1">Pertumbuhan Bisnis</h4>
                    <p className="text-slate-400 text-sm leading-relaxed">Ekspansi pasar produk Anda secara masif melalui ekosistem offline dan online Toserba Selamat.</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 shrink-0">
                    <Building2 size={24} />
                  </div>
                  <div>
                    <h4 className="text-white font-semibold mb-1">Pembayaran Terjamin</h4>
                    <p className="text-slate-400 text-sm leading-relaxed">Sistem keuangan yang profesional, transparan, dan dapat diandalkan untuk kenyamanan berbisnis.</p>
                  </div>
                </div>
              </div>

              <div className="mt-12 p-6 rounded-2xl bg-slate-900/50 border border-white/5">
                <h4 className="text-white font-semibold mb-3 flex items-center gap-2"><CheckCircle2 className="text-lime-400" size={18} /> Kategori Mitra Prioritas</h4>
                <ul className="space-y-2 text-sm text-slate-400">
                  <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Makanan & Minuman Kemasan</li>
                  <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Fashion & Pakaian Muslim</li>
                  <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Kebutuhan Rumah Tangga</li>
                  <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Alat Tulis & Kantor</li>
                  <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Supplier Bahan Baku Resto/Hotel</li>
                </ul>
              </div>
            </div>
          </motion.div>

          {/* Registration Form */}
          <motion.div 
            initial={{ opacity: 0, x: 50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8 }}
            className="lg:col-span-3"
          >
            <div className="glass-panel-dark p-8 sm:p-12 rounded-[2rem] border border-blue-500/20 h-full relative overflow-hidden">
              <div className="absolute inset-0 bg-gradient-to-bl from-blue-500/5 to-transparent pointer-events-none" />
              
              <h2 className="text-3xl font-bold text-white mb-2">Formulir Pengajuan Kemitraan</h2>
              <p className="text-slate-400 mb-8">Lengkapi data usaha Anda. Tim kurasi kami akan menghubungi Anda untuk proses kurasi lebih lanjut jika memenuhi kriteria.</p>
              
              {status === "success" && (
                <div className="mb-8 p-4 rounded-xl bg-lime-500/10 border border-lime-500/30 text-lime-400 flex items-start gap-3">
                  <CheckCircle2 className="shrink-0 mt-0.5" />
                  <div>
                    <h4 className="font-bold mb-1">Berhasil Terkirim!</h4>
                    <p className="text-sm">Terima kasih atas pengajuan Anda. Data Anda telah masuk ke sistem kami dan sedang dalam antrean peninjauan.</p>
                  </div>
                </div>
              )}

              {status === "error" && (
                <div className="mb-8 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 flex items-start gap-3">
                  <AlertCircle className="shrink-0 mt-0.5" />
                  <div>
                    <h4 className="font-bold mb-1">Pengiriman Gagal</h4>
                    <p className="text-sm">{errorMessage}</p>
                  </div>
                </div>
              )}

              <form className="space-y-6 relative z-10" onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Nama Usaha / Merek</label>
                    <input 
                      type="text" 
                      name="business_name"
                      value={formData.business_name}
                      onChange={handleChange}
                      required
                      placeholder="Contoh: Keripik Singkong Barokah" 
                      className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Nama Pemilik / PIC</label>
                    <input 
                      type="text" 
                      name="owner_name"
                      value={formData.owner_name}
                      onChange={handleChange}
                      required
                      placeholder="Nama lengkap PIC" 
                      className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                    />
                  </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Nomor Handphone / WA</label>
                    <input 
                      type="tel" 
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      required
                      placeholder="Nomor WA aktif" 
                      className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-medium text-slate-300 ml-1">Email Resmi</label>
                    <input 
                      type="email" 
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      placeholder="opsional@email.com" 
                      className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium text-slate-300 ml-1">Bentuk Kemitraan</label>
                  <select 
                    name="category"
                    value={formData.category}
                    onChange={handleChange}
                    className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all appearance-none cursor-pointer"
                  >
                    <option value="Pemasok Barang (Supplier)">Pemasok Barang (Supplier)</option>
                    <option value="Sewa Tempat (Tenant)">Sewa Tempat Jualan (Tenant)</option>
                    <option value="Konsinyasi (Titip Jual)">Konsinyasi (Titip Jual)</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium text-slate-300 ml-1">Deskripsi Produk/Usaha</label>
                  <textarea 
                    name="description"
                    value={formData.description}
                    onChange={handleChange}
                    required
                    rows={4}
                    placeholder="Ceritakan secara singkat tentang produk Anda, keunggulannya, estimasi kapasitas produksi, dll..." 
                    className="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all resize-none"
                  ></textarea>
                </div>

                <button 
                  type="submit"
                  disabled={loading}
                  className="bg-blue-600 hover:bg-blue-500 disabled:bg-blue-600/50 disabled:cursor-not-allowed text-white px-8 py-4 rounded-xl font-bold transition-all shadow-[0_0_20px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] hover:-translate-y-1 w-full inline-flex justify-center items-center gap-3"
                >
                  {loading ? (
                    <div className="animate-spin rounded-full h-5 w-5 border-t-2 border-b-2 border-white"></div>
                  ) : (
                    <><Send size={18} /> Kirim Pengajuan</>
                  )}
                </button>
              </form>
            </div>
          </motion.div>
        </div>
      </div>
    </div>
  );
}
