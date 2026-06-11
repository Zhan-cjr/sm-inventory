import Link from "next/link";
import { ShoppingBag, MapPin, Phone, Mail } from "lucide-react";
import { FaInstagram, FaFacebook, FaTwitter } from "react-icons/fa";

export function Footer() {
  return (
    <footer className="bg-slate-900 text-slate-300 pt-16 pb-8">
      <div className="container mx-auto px-4 md:px-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
          {/* Brand */}
          <div className="space-y-4">
            <Link href="/" className="flex items-center gap-2 group mb-6">
              <div className="bg-emerald-600 text-white p-2 rounded-lg">
                <ShoppingBag size={24} />
              </div>
              <span className="font-bold text-2xl text-white tracking-tight">
                Toserba <span className="text-emerald-600">Selamat</span>
              </span>
            </Link>
            <p className="text-slate-400 leading-relaxed text-sm">
              Jaringan ritel dan hospitality modern, ramah keluarga, dan berprinsip syariah dengan lebih dari 26 cabang yang tersebar untuk memenuhi kebutuhan Anda.
            </p>
            <div className="flex items-center gap-4 pt-4">
              <a href="#" className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-emerald-600 transition-colors text-white">
                <FaInstagram size={18} />
              </a>
              <a href="#" className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-emerald-600 transition-colors text-white">
                <FaFacebook size={18} />
              </a>
              <a href="#" className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-emerald-600 transition-colors text-white">
                <FaTwitter size={18} />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="text-white font-semibold text-lg mb-6">Tautan Cepat</h3>
            <ul className="space-y-3">
              <li>
                <Link href="/about" className="hover:text-emerald-600 transition-colors text-sm">Tentang Kami</Link>
              </li>
              <li>
                <Link href="/facilities" className="hover:text-emerald-600 transition-colors text-sm">Fasilitas & Layanan</Link>
              </li>
              <li>
                <Link href="/locations" className="hover:text-emerald-600 transition-colors text-sm">Lokasi Cabang</Link>
              </li>
              <li>
                <a href="http://shopping.toserbaselamat.id" className="hover:text-emerald-600 transition-colors text-sm">
                  Belanja Online
                </a>
              </li>
              <li>
                <Link href="/careers" className="hover:text-emerald-600 transition-colors text-sm">Karir</Link>
              </li>
            </ul>
          </div>

          {/* Business Units */}
          <div>
            <h3 className="text-white font-semibold text-lg mb-6">Unit Bisnis</h3>
            <ul className="space-y-3">
              <li className="text-sm hover:text-emerald-600 cursor-pointer transition-colors">Supermarket & Fashion</li>
              <li className="text-sm hover:text-emerald-600 cursor-pointer transition-colors">Hotel Syariah</li>
              <li className="text-sm hover:text-emerald-600 cursor-pointer transition-colors">SHSC Fitness Center</li>
              <li className="text-sm hover:text-emerald-600 cursor-pointer transition-colors">Padel Court</li>
              <li className="text-sm hover:text-emerald-600 cursor-pointer transition-colors">Jajanan Subuh</li>
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="text-white font-semibold text-lg mb-6">Hubungi Kami</h3>
            <ul className="space-y-4">
              <li className="flex items-start gap-3">
                <MapPin size={20} className="text-emerald-600 shrink-0 mt-0.5" />
                <span className="text-sm">Jl. A. Yani No. 1, Pusat Kota, Indonesia. 40111</span>
              </li>
              <li className="flex items-center gap-3">
                <Phone size={20} className="text-emerald-600 shrink-0" />
                <span className="text-sm">+62 811 2345 6789</span>
              </li>
              <li className="flex items-center gap-3">
                <Mail size={20} className="text-emerald-600 shrink-0" />
                <span className="text-sm">info@toserbaselamat.com</span>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-slate-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
          <p className="text-sm text-slate-500">
            &copy; {new Date().getFullYear()} Toserba Selamat Group. Hak cipta dilindungi.
          </p>
          <div className="flex items-center gap-6 text-sm text-slate-500">
            <Link href="/privacy" className="hover:text-white transition-colors">Privasi</Link>
            <Link href="/terms" className="hover:text-white transition-colors">Syarat & Ketentuan</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
