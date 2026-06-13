"use client";

import Link from "next/link";
import { ShoppingBag, MapPin, Phone, Mail } from "lucide-react";
import { FaInstagram, FaFacebook, FaTwitter } from "react-icons/fa";
import { useCompanyProfile } from "@/lib/hooks";

export function Footer() {
  const { settings } = useCompanyProfile();
  
  const logoPath = settings?.['logo'];
  const companyName = settings?.['company_name'] || "Toserba Selamat";
  const companyDescription = settings?.['company_description'] || "Jaringan ritel dan hospitality modern, ramah keluarga, dan berprinsip syariah dengan lebih dari 26 cabang yang tersebar untuk memenuhi kebutuhan Anda.";
  
  const address = settings?.['address'] || "Jl. A. Yani No. 1, Pusat Kota, Indonesia. 40111";
  const phone = settings?.['phone'] || "+62 811 2345 6789";
  const email = settings?.['email'] || "info@toserbaselamat.com";
  
  const instagram = settings?.['instagram'] || "#";
  const facebook = settings?.['facebook'] || "#";
  const twitter = settings?.['twitter'] || "#";

  return (
    <footer className="bg-white border-t border-slate-200 pt-16 pb-8">
      <div className="container mx-auto px-4 md:px-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
          {/* Brand */}
          <div className="space-y-4">
            <Link href="/" className="flex items-center gap-2 group mb-6">
              {logoPath ? (
                <img 
                  src={`/storage/${logoPath}`} 
                  alt="Logo" 
                  className="h-12 w-auto object-contain drop-shadow-sm group-hover:scale-105 transition-transform"
                />
              ) : (
                <>
                  <div className="bg-primary text-white p-2 rounded-lg">
                    <ShoppingBag size={24} />
                  </div>
                  <span className="font-bold text-2xl text-primary tracking-tight">
                    Toserba <span className="text-secondary">Selamat</span>
                  </span>
                </>
              )}
            </Link>
            <p className="text-slate-600 leading-relaxed text-sm">
              {companyDescription}
            </p>
            <div className="flex items-center gap-4 pt-4">
              <a href={instagram} className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center hover:bg-primary transition-colors text-slate-600 hover:text-white">
                <FaInstagram size={18} />
              </a>
              <a href={facebook} className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center hover:bg-primary transition-colors text-slate-600 hover:text-white">
                <FaFacebook size={18} />
              </a>
              <a href={twitter} className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center hover:bg-primary transition-colors text-slate-600 hover:text-white">
                <FaTwitter size={18} />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="text-slate-900 font-bold text-lg mb-6">Tautan Cepat</h3>
            <ul className="space-y-3">
              <li>
                <Link href="/about" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">Tentang Kami</Link>
              </li>
              <li>
                <Link href="/facilities" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">Fasilitas & Layanan</Link>
              </li>
              <li>
                <Link href="/locations" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">Lokasi Cabang</Link>
              </li>
              <li>
                <a href="http://shopping.toserbaselamat.id" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">
                  Belanja Online
                </a>
              </li>
              <li>
                <Link href="/careers" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">Karir</Link>
              </li>
              <li>
                <Link href="/news" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">Berita & Promo</Link>
              </li>
              <li>
                <Link href="/partnership" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">Peluang Kemitraan</Link>
              </li>
              <li>
                <Link href="/contact" className="text-slate-600 hover:text-primary transition-colors text-sm font-medium">Hubungi Kami</Link>
              </li>
            </ul>
          </div>

          {/* Business Units */}
          <div>
            <h3 className="text-slate-900 font-bold text-lg mb-6">Unit Bisnis</h3>
            <ul className="space-y-3">
              <li className="text-sm text-slate-600 hover:text-primary cursor-pointer transition-colors font-medium">Supermarket & Fashion</li>
              <li className="text-sm text-slate-600 hover:text-primary cursor-pointer transition-colors font-medium">Hotel Syariah</li>
              <li className="text-sm text-slate-600 hover:text-primary cursor-pointer transition-colors font-medium">SHSC Fitness Center</li>
              <li className="text-sm text-slate-600 hover:text-primary cursor-pointer transition-colors font-medium">Padel Court</li>
              <li className="text-sm text-slate-600 hover:text-primary cursor-pointer transition-colors font-medium">Jajanan Subuh</li>
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="text-slate-900 font-bold text-lg mb-6">Hubungi Kami</h3>
            <ul className="space-y-4">
              <li className="flex items-start gap-3">
                <MapPin size={20} className="text-primary shrink-0 mt-0.5" />
                <span className="text-sm text-slate-600 font-medium">{address}</span>
              </li>
              <li className="flex items-center gap-3">
                <Phone size={20} className="text-primary shrink-0" />
                <span className="text-sm text-slate-600 font-medium">{phone}</span>
              </li>
              <li className="flex items-center gap-3">
                <Mail size={20} className="text-primary shrink-0" />
                <span className="text-sm text-slate-600 font-medium">{email}</span>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-slate-200 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
          <p className="text-sm text-slate-500 font-medium">
            &copy; {new Date().getFullYear()} {companyName}. Hak cipta dilindungi.
          </p>
          <div className="flex items-center gap-6 text-sm text-slate-500 font-medium">
            <Link href="/privacy" className="hover:text-primary transition-colors">Privasi</Link>
            <Link href="/terms" className="hover:text-primary transition-colors">Syarat & Ketentuan</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
