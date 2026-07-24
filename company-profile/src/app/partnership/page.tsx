"use client";

import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Handshake, Store, TrendingUp, CheckCircle2, Building2, Send, AlertCircle, HelpCircle, ChevronDown } from "lucide-react";

const faqList = [
  { q: "Apa saja kriteria produk supplier yang bisa masuk Toserba Selamat?", a: "Produk harus terjamin kehalalannya (bersertifikat halal MUI/BPJPH), memiliki izin edar resmi (BPOM/P-IRT), kemasan higienis, dan kontinuitas stok yang terjamin." },
  { q: "Berapa lama proses evaluasi pengajuan kemitraan?", a: "Tim Merchandising kami akan meninjau dokumen dalam waktu 3-5 hari kerja. Anda akan dihubungi langsung via Telepon/WhatsApp untuk proses sampel." },
  { q: "Apakah UMKM lokal mendapatkan prioritas tempat di store?", a: "Ya! Toserba Selamat berkomitmen mendukung pemberdayaan UMKM lokal melalui program 'Pojok Berkah UMKM' di setiap cabang kami." },
];

export default function PartnershipPage() {
  const [step, setStep] = useState(1);
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
  const [openFaq, setOpenFaq] = useState<number | null>(null);

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
      setStep(1);
    } catch (err: any) {
      console.error(err);
      setStatus("error");
      setErrorMessage(err.message || "Gagal mengirim formulir kemitraan.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[700px] h-[700px] bg-secondary/10 rounded-full blur-[140px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[700px] h-[700px] bg-primary/10 rounded-full blur-[140px] pointer-events-none" />

      <div className="container mx-auto px-4 md:px-8 max-w-7xl relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-16 space-y-4">
          <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent/20 text-slate-800 text-xs font-bold uppercase tracking-widest">
            <Handshake size={15} className="text-primary" /> Kemitraan Strategis &amp; Supplier
          </span>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black text-slate-900 tracking-tight">
            Tumbuh Bersama <span className="bg-gradient-to-r from-primary via-primary-light to-secondary text-gradient">Toserba Selamat</span>
          </h1>
          <p className="text-slate-600 font-medium text-base sm:text-lg leading-relaxed">
            Toserba Selamat membuka kesempatan emas bagi UMKM, Produsen, dan Pemilik Merek untuk memasarkan produk di 26+ cabang store kami.
          </p>
        </div>

        <div className="grid lg:grid-cols-12 gap-8 lg:gap-12 mb-24">
          
          {/* Left Column: Advantages */}
          <div className="lg:col-span-5 space-y-6">
            <div className="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-lg space-y-6">
              <h3 className="text-2xl font-black text-slate-900">Mengapa Bermitra Dengan Kami?</h3>
              
              <div className="space-y-5">
                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <Store size={24} />
                  </div>
                  <div>
                    <h4 className="font-bold text-slate-900 text-base">Jaringan 26+ Store Physical</h4>
                    <p className="text-xs text-slate-600 font-medium leading-relaxed">Produk Anda langsung dilihat dan dijangkau oleh ratusan ribu pembeli harian.</p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
                    <TrendingUp size={24} />
                  </div>
                  <div>
                    <h4 className="font-bold text-slate-900 text-base">Pertumbuhan Penjualan Stabil</h4>
                    <p className="text-xs text-slate-600 font-medium leading-relaxed">Didukung promosi katalog mingguan dan sistem inventaris digital terkoneksi.</p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 rounded-2xl bg-accent/20 text-emerald-800 flex items-center justify-center shrink-0">
                    <Building2 size={24} />
                  </div>
                  <div>
                    <h4 className="font-bold text-slate-900 text-base">Sewa Space &amp; Booth Tenant</h4>
                    <p className="text-xs text-slate-600 font-medium leading-relaxed">Tersedia space sewa tenant makanan/minuman dengan trafik pengunjung tinggi.</p>
                  </div>
                </div>
              </div>

              <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-600 font-medium leading-relaxed">
                💡 <strong>Program Pemberdayaan UMKM:</strong> Kami memberikan pendampingan sertifikasi halal dan tempat khusus bagi produk khas daerah.
              </div>
            </div>
          </div>

          {/* Right Column: Multi-step Interactive Wizard Form */}
          <div className="lg:col-span-7">
            <div className="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-xl space-y-6 relative overflow-hidden">
              
              <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                  <h3 className="text-xl font-extrabold text-slate-900">Formulir Pengajuan Kemitraan</h3>
                  <p className="text-xs text-slate-500 font-medium">Lengkapi data berikut untuk dihubungi oleh tim merchandising kami.</p>
                </div>
                <div className="flex items-center gap-2">
                  <span className={`w-8 h-8 rounded-full font-extrabold text-xs flex items-center justify-center ${step === 1 ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600'}`}>1</span>
                  <span className={`w-8 h-8 rounded-full font-extrabold text-xs flex items-center justify-center ${step === 2 ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600'}`}>2</span>
                </div>
              </div>

              {status === "success" && (
                <div className="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center gap-3">
                  <CheckCircle2 size={20} />
                  <span>Pengajuan kemitraan berhasil dikirim! Tim kami akan menghubungi Anda dalam 3 hari kerja.</span>
                </div>
              )}

              {status === "error" && (
                <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-xs font-bold flex items-center gap-3">
                  <AlertCircle size={20} />
                  <span>{errorMessage}</span>
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-5">
                {step === 1 ? (
                  <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} className="space-y-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">Kategori Kemitraan *</label>
                      <select
                        name="category"
                        value={formData.category}
                        onChange={handleChange}
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary outline-none"
                      >
                        <option value="Pemasok Barang (Supplier)">Pemasok Barang (Supplier Supermarket)</option>
                        <option value="Sewa Space / Tenant Store">Sewa Space / Tenant Store Area</option>
                        <option value="Pemasok Bahan Segar (Fresh Food)">Pemasok Bahan Segar (Buah, Sayur, Daging)</option>
                        <option value="Kerjasama Event & Sponsorship">Kerjasama Event &amp; Sponsorship</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">Nama Perusahaan / Brand UMKM *</label>
                      <input
                        type="text"
                        name="business_name"
                        required
                        value={formData.business_name}
                        onChange={handleChange}
                        placeholder="Contoh: PT Berkah Food Nusantara"
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">Nama Pemilik / Penanggung Jawab *</label>
                      <input
                        type="text"
                        name="owner_name"
                        required
                        value={formData.owner_name}
                        onChange={handleChange}
                        placeholder="Contoh: H. Ahmad Subandi"
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary outline-none"
                      />
                    </div>

                    <button
                      type="button"
                      onClick={() => {
                        if (formData.business_name && formData.owner_name) setStep(2);
                        else alert("Harap isi nama usaha dan penanggung jawab.");
                      }}
                      className="w-full py-3.5 bg-primary hover:bg-primary-light text-white font-bold text-xs rounded-2xl transition-all shadow-md shadow-primary/20"
                    >
                      Lanjut Ke Kontak &amp; Detail Produk
                    </button>
                  </motion.div>
                ) : (
                  <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} className="space-y-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">Nomor Telepon / WhatsApp *</label>
                      <input
                        type="tel"
                        name="phone"
                        required
                        value={formData.phone}
                        onChange={handleChange}
                        placeholder="Contoh: 081234567890"
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email *</label>
                      <input
                        type="email"
                        name="email"
                        required
                        value={formData.email}
                        onChange={handleChange}
                        placeholder="Contoh: kontak@berkahfood.com"
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Produk / Pengajuan *</label>
                      <textarea
                        name="description"
                        rows={3}
                        required
                        value={formData.description}
                        onChange={handleChange}
                        placeholder="Jelaskan jenis produk, keunggulan, sertifikasi halal/BPOM, dan kapasitas produksi..."
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary outline-none"
                      />
                    </div>

                    <div className="flex gap-3">
                      <button
                        type="button"
                        onClick={() => setStep(1)}
                        className="py-3.5 px-5 bg-slate-100 text-slate-600 font-bold text-xs rounded-2xl hover:bg-slate-200 transition-colors"
                      >
                        Kembali
                      </button>
                      <button
                        type="submit"
                        disabled={loading}
                        className="flex-1 py-3.5 bg-secondary hover:bg-secondary-light text-white font-bold text-xs rounded-2xl transition-all shadow-md shadow-secondary/20 flex items-center justify-center gap-2"
                      >
                        {loading ? "Mengirim..." : "Kirim Pengajuan Kemitraan"}
                        <Send size={15} />
                      </button>
                    </div>
                  </motion.div>
                )}
              </form>

            </div>
          </div>

        </div>

        {/* Partnership FAQ Accordion */}
        <div className="max-w-3xl mx-auto space-y-6">
          <div className="text-center space-y-2">
            <span className="text-xs font-bold text-primary uppercase tracking-widest">PERTANYAAN UMUM</span>
            <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900">FAQ Kemitraan</h2>
          </div>

          <div className="space-y-3">
            {faqList.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div key={idx} className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                  <button
                    onClick={() => setOpenFaq(isOpen ? null : idx)}
                    className="w-full p-5 text-left font-bold text-sm text-slate-900 flex items-center justify-between hover:text-primary transition-colors"
                  >
                    <span>{faq.q}</span>
                    <ChevronDown size={18} className={`transition-transform ${isOpen ? 'rotate-180 text-primary' : 'text-slate-400'}`} />
                  </button>
                  <AnimatePresence>
                    {isOpen && (
                      <motion.div initial={{ height: 0 }} animate={{ height: "auto" }} exit={{ height: 0 }} className="px-5 pb-5 text-xs text-slate-600 font-medium leading-relaxed border-t border-slate-100 pt-3">
                        {faq.a}
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              );
            })}
          </div>
        </div>

      </div>
    </div>
  );
}
