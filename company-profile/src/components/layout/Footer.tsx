"use client";

import { useState } from "react";
import Link from "next/link";
import { ShoppingBag, MapPin, Phone, Mail, ShieldCheck, ArrowRight, CheckCircle2 } from "lucide-react";
import { FaInstagram, FaFacebook, FaTwitter, FaYoutube, FaTiktok } from "react-icons/fa";
import { useCompanyProfile } from "@/lib/hooks";

export function Footer() {
  const { settings } = useCompanyProfile();
  const [subscribed, setSubscribed] = useState(false);
  const [emailInput, setEmailInput] = useState("");

  const logoPath = settings?.['logo'];
  const companyName = settings?.['company_name'] || "Toserba Selamat";
  const companyDescription = settings?.['company_description'] || "Pusat perbelanjaan & ekosistem ritel-hospitality terpadu yang modern, ramah keluarga, dan berprinsip syariah dengan lebih dari 26 cabang.";
  
  const address = settings?.['address'] || "Jl. A. Yani No. 1, Pusat Kota, Indonesia";
  const phone = settings?.['phone'] || "+62 811 2345 6789";
  const email = settings?.['email'] || "info@toserbaselamat.com";
  
  const instagram = settings?.['instagram'] || "#";
  const facebook = settings?.['facebook'] || "#";
  const twitter = settings?.['twitter'] || "#";

  const handleSubscribe = (e: React.FormEvent) => {
    e.preventDefault();
    if (emailInput.trim()) {
      setSubscribed(true);
      setEmailInput("");
    }
  };

  return (
    <footer className="bg-slate-900 text-white relative overflow-hidden pt-20 pb-10">
      {/* Background Ambient Glows */}
      <div className="absolute top-0 right-1/4 w-96 h-96 bg-primary/20 rounded-full blur-[140px] pointer-events-none" />
      <div className="absolute bottom-0 left-10 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none" />

      <div className="container mx-auto px-4 md:px-8 relative z-10">
        
        {/* Newsletter & Promo Banner */}
        <div className="bg-gradient-to-r from-primary-dark via-primary to-primary-light rounded-3xl p-8 md:p-12 mb-16 shadow-2xl border border-white/10 relative overflow-hidden">
          <div className="absolute -right-10 -bottom-10 w-60 h-60 bg-accent/20 rounded-full blur-3xl" />
          <div className="grid lg:grid-cols-12 gap-8 items-center relative z-10">
            <div className="lg:col-span-7">
              <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/20 text-accent font-bold text-xs uppercase tracking-widest mb-3">
                <ShieldCheck size={14} /> Berkah Bulletin
              </span>
              <h3 className="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white mb-2">
                Dapatkan Promo & Informasi Cabang Terbaru
              </h3>
              <p className="text-slate-300 text-sm sm:text-base font-medium">
                Berlangganan newsletter untuk info promo mingguan, kupon member, dan event spesial keluarga.
              </p>
            </div>
            <div className="lg:col-span-5">
              {subscribed ? (
                <div className="flex items-center gap-3 p-4 bg-white/10 border border-white/20 rounded-2xl text-accent font-bold">
                  <CheckCircle2 size={24} />
                  <span>Terima kasih! Anda berhasil terdaftar dalam promo bulanan.</span>
                </div>
              ) : (
                <form onSubmit={handleSubscribe} className="flex flex-col sm:flex-row gap-3">
                  <input
                    type="email"
                    required
                    value={emailInput}
                    onChange={(e) => setEmailInput(e.target.value)}
                    placeholder="Masukkan alamat email Anda..."
                    className="w-full px-5 py-4 rounded-2xl bg-white/10 text-white placeholder-slate-400 border border-white/20 focus:outline-none focus:bg-white/20 text-sm font-medium"
                  />
                  <button
                    type="submit"
                    className="whitespace-nowrap px-6 py-4 rounded-2xl bg-secondary hover:bg-secondary-light text-white font-bold text-sm transition-all shadow-lg shadow-secondary/30 flex items-center justify-center gap-2 hover:scale-105"
                  >
                    <span>Daftar Gratis</span>
                    <ArrowRight size={16} />
                  </button>
                </form>
              )}
            </div>
          </div>
        </div>

        {/* Navigation & Info Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-12 mb-16">
          
          {/* Brand Identity */}
          <div className="lg:col-span-4 space-y-5">
            <Link href="/" className="flex items-center gap-3 group">
              {logoPath ? (
                <img 
                  src={`/storage/${logoPath}`} 
                  alt="Logo" 
                  className="h-12 w-auto object-contain drop-shadow-sm group-hover:scale-105 transition-transform"
                />
              ) : (
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-white">
                    <ShoppingBag size={24} />
                  </div>
                  <div className="flex flex-col">
                    <span className="font-extrabold text-xl text-white tracking-tight">
                      Toserba <span className="text-secondary">Selamat</span>
                    </span>
                    <span className="text-[10px] font-bold text-accent tracking-widest uppercase">
                      The Moslem Family
                    </span>
                  </div>
                </div>
              )}
            </Link>

            <p className="text-slate-400 leading-relaxed text-sm font-medium">
              {companyDescription}
            </p>

            <div className="flex items-center gap-3 pt-2">
              <a href={instagram} target="_blank" rel="noreferrer" aria-label="Instagram" className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-primary hover:border-primary transition-all text-slate-300 hover:text-white">
                <FaInstagram size={18} />
              </a>
              <a href={facebook} target="_blank" rel="noreferrer" aria-label="Facebook" className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-primary hover:border-primary transition-all text-slate-300 hover:text-white">
                <FaFacebook size={18} />
              </a>
              <a href={twitter} target="_blank" rel="noreferrer" aria-label="Twitter" className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-primary hover:border-primary transition-all text-slate-300 hover:text-white">
                <FaTwitter size={18} />
              </a>
              <a href="#" aria-label="YouTube" className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-secondary hover:border-secondary transition-all text-slate-300 hover:text-white">
                <FaYoutube size={18} />
              </a>
              <a href="#" aria-label="TikTok" className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-white/20 transition-all text-slate-300 hover:text-white">
                <FaTiktok size={16} />
              </a>
            </div>
          </div>

          {/* Quick Links Matrix */}
          <div className="lg:col-span-3">
            <h4 className="text-white font-bold text-base mb-5 border-l-2 border-accent pl-3">Jelajahi Portal</h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link href="/about" className="text-slate-400 hover:text-white transition-colors font-medium hover:translate-x-1 inline-block">Tentang Kami &amp; Nilai Syariah</Link>
              </li>
              <li>
                <Link href="/facilities" className="text-slate-400 hover:text-white transition-colors font-medium hover:translate-x-1 inline-block">Fasilitas &amp; Ekosistem Ritel</Link>
              </li>
              <li>
                <Link href="/locations" className="text-slate-400 hover:text-white transition-colors font-medium hover:translate-x-1 inline-block">Peta 26+ Cabang Terdekat</Link>
              </li>
              <li>
                <a href="http://shopping.toserbaselamat.id" target="_blank" rel="noreferrer" className="text-slate-400 hover:text-accent transition-colors font-medium hover:translate-x-1 inline-block">
                  Pusat Belanja Online
                </a>
              </li>
              <li>
                <Link href="/news" className="text-slate-400 hover:text-white transition-colors font-medium hover:translate-x-1 inline-block">Promo Mingguan &amp; Event</Link>
              </li>
              <li>
                <Link href="/partnership" className="text-slate-400 hover:text-white transition-colors font-medium hover:translate-x-1 inline-block">Peluang Kemitraan &amp; Supplier</Link>
              </li>
            </ul>
          </div>

          {/* Business Units */}
          <div className="lg:col-span-2">
            <h4 className="text-white font-bold text-base mb-5 border-l-2 border-secondary pl-3">Unit Ekosistem</h4>
            <ul className="space-y-2.5 text-sm text-slate-400 font-medium">
              <li className="hover:text-white transition-colors cursor-pointer">Supermarket &amp; Fresh Food</li>
              <li className="hover:text-white transition-colors cursor-pointer">Fashion &amp; Moslem Apparel</li>
              <li className="hover:text-white transition-colors cursor-pointer">Hotel &amp; Lounge Syariah</li>
              <li className="hover:text-white transition-colors cursor-pointer">Kidz Zone &amp; Playground</li>
              <li className="hover:text-white transition-colors cursor-pointer">SHSC Fitness &amp; Gym Arena</li>
              <li className="hover:text-white transition-colors cursor-pointer">Kuliner Jajanan Subuh</li>
            </ul>
          </div>

          {/* Contact Details */}
          <div className="lg:col-span-3">
            <h4 className="text-white font-bold text-base mb-5 border-l-2 border-primary-light pl-3">Kantor Pusat</h4>
            <ul className="space-y-3.5 text-sm">
              <li className="flex items-start gap-3 text-slate-400">
                <MapPin size={18} className="text-accent shrink-0 mt-0.5" />
                <span className="font-medium leading-relaxed">{address}</span>
              </li>
              <li className="flex items-center gap-3 text-slate-400">
                <Phone size={18} className="text-accent shrink-0" />
                <span className="font-medium">{phone}</span>
              </li>
              <li className="flex items-center gap-3 text-slate-400">
                <Mail size={18} className="text-accent shrink-0" />
                <span className="font-medium">{email}</span>
              </li>
            </ul>
          </div>
        </div>

        {/* Footer Bottom Bar */}
        <div className="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-400 font-medium">
          <p>&copy; {new Date().getFullYear()} {companyName}. Hak Cipta Dilindungi Undang-Undang.</p>
          <div className="flex items-center gap-6">
            <span className="flex items-center gap-1.5 text-accent font-bold">
              <ShieldCheck size={14} /> Terverifikasi Ramah Keluarga &amp; Syariah
            </span>
            <Link href="/privacy" className="hover:text-white transition-colors">Kebijakan Privasi</Link>
            <Link href="/terms" className="hover:text-white transition-colors">Syarat &amp; Ketentuan</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
