"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { MapPin, Phone, Mail, MessageSquare, Send, Clock, Building2, CheckCircle2, MessageCircle } from "lucide-react";
import Link from "next/link";

export default function ContactPage() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "Kritik & Saran",
    message: "",
  });

  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
    setTimeout(() => {
      setSubmitted(false);
      setFormData({ name: "", email: "", phone: "", subject: "Kritik & Saran", message: "" });
    }, 3000);
  };

  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[700px] h-[700px] bg-primary/10 rounded-full blur-[140px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[700px] h-[700px] bg-secondary/10 rounded-full blur-[140px] pointer-events-none" />

      <div className="container mx-auto px-4 md:px-8 max-w-7xl relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-16 space-y-4">
          <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-widest">
            <MessageSquare size={15} /> Pusat Bantuan &amp; Kontak
          </span>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black text-slate-900 tracking-tight">
            Hubungi <span className="bg-gradient-to-r from-primary via-primary-light to-secondary text-gradient">Toserba Selamat</span>
          </h1>
          <p className="text-slate-600 font-medium text-base sm:text-lg leading-relaxed">
            Tim Customer Care kami siap membantu pertanyaan seputar belanja, keanggotaan member, masukan cabang, maupun kerjasama B2B.
          </p>
        </div>

        <div className="grid lg:grid-cols-12 gap-8 lg:gap-12">
          
          {/* Left Column: Corporate Info & Direct WA */}
          <div className="lg:col-span-5 space-y-6">
            <div className="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-lg space-y-6">
              <h3 className="text-2xl font-black text-slate-900">Layanan Pelanggan</h3>

              <div className="space-y-5">
                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <MapPin size={24} />
                  </div>
                  <div>
                    <h4 className="font-bold text-slate-900 text-sm">Alamat Kantor Pusat</h4>
                    <p className="text-xs text-slate-600 font-medium leading-relaxed">Jl. Siliwangi No. 88, Pusat Kota Cianjur, Jawa Barat 43212</p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
                    <Phone size={24} />
                  </div>
                  <div>
                    <h4 className="font-bold text-slate-900 text-sm">Call Center &amp; WhatsApp</h4>
                    <p className="text-xs text-slate-600 font-medium">+62 811 2345 6789 (Senin - Minggu)</p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 rounded-2xl bg-accent/20 text-emerald-800 flex items-center justify-center shrink-0">
                    <Mail size={24} />
                  </div>
                  <div>
                    <h4 className="font-bold text-slate-900 text-sm">Alamat Email Resmi</h4>
                    <p className="text-xs text-slate-600 font-medium">cs@toserbaselamat.id</p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                    <Clock size={24} />
                  </div>
                  <div>
                    <h4 className="font-bold text-slate-900 text-sm">Jam Layanan</h4>
                    <p className="text-xs text-slate-600 font-medium">08:00 - 21:00 WIB (Setiap Hari)</p>
                  </div>
                </div>
              </div>

              {/* Direct WhatsApp Action Button */}
              <div className="pt-2">
                <a
                  href="https://wa.me/6281123456789?text=Halo%20Toserba%20Selamat,%20saya%20ingin%20bertanya%20mengenai..."
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-xs flex items-center justify-center gap-2.5 transition-all shadow-md shadow-emerald-600/20"
                >
                  <MessageCircle size={18} /> Chat Langsung via WhatsApp CS
                </a>
              </div>
            </div>
          </div>

          {/* Right Column: Contact Form */}
          <div className="lg:col-span-7">
            <div className="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-xl space-y-6">
              <div>
                <h3 className="text-2xl font-black text-slate-900">Kirim Pesan / Masukan</h3>
                <p className="text-xs text-slate-500 font-medium">Sampaikan pesan Anda dan tim kami akan membalas via email/WhatsApp.</p>
              </div>

              {submitted ? (
                <div className="p-8 bg-emerald-50 border border-emerald-200 rounded-3xl text-center space-y-3">
                  <CheckCircle2 size={40} className="text-emerald-600 mx-auto" />
                  <h4 className="font-extrabold text-slate-900 text-lg">Pesan Berhasil Terkirim!</h4>
                  <p className="text-xs text-slate-600 font-medium">Terima kasih atas perhatian dan masukan Anda bagi Toserba Selamat.</p>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                      <input
                        type="text"
                        required
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        placeholder="Contoh: Budi Pratama"
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none focus:bg-white focus:ring-2 focus:ring-primary"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp *</label>
                      <input
                        type="tel"
                        required
                        value={formData.phone}
                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                        placeholder="0812xxxx"
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none focus:bg-white focus:ring-2 focus:ring-primary"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Email *</label>
                      <input
                        type="email"
                        required
                        value={formData.email}
                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        placeholder="budi@example.com"
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none focus:bg-white focus:ring-2 focus:ring-primary"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">Topik Pesan *</label>
                      <select
                        value={formData.subject}
                        onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                        className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none focus:bg-white focus:ring-2 focus:ring-primary"
                      >
                        <option value="Kritik & Saran">Kritik &amp; Saran Pelayanan</option>
                        <option value="Tanya Member">Pertanyaan Poin Member</option>
                        <option value="Penawaran Kerjasama">Penawaran Kerjasama / Tenant</option>
                        <option value="Lainnya">Lainnya</option>
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Isi Pesan / Pertanyaan *</label>
                    <textarea
                      rows={4}
                      required
                      value={formData.message}
                      onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                      placeholder="Tuliskan detail pertanyaan atau masukan Anda..."
                      className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none focus:bg-white focus:ring-2 focus:ring-primary"
                    />
                  </div>

                  <button
                    type="submit"
                    className="w-full py-4 bg-primary hover:bg-primary-light text-white font-bold text-xs rounded-2xl transition-all shadow-md shadow-primary/20 flex items-center justify-center gap-2"
                  >
                    <span>Kirim Pesan CS</span>
                    <Send size={15} />
                  </button>
                </form>
              )}

            </div>
          </div>

        </div>

      </div>
    </div>
  );
}
