"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu, X, ShoppingBag } from "lucide-react";
import { cn } from "@/lib/utils";
import { motion, AnimatePresence } from "framer-motion";
import { useCompanyProfile } from "@/lib/hooks";

const navLinks = [
  { name: "Beranda", href: "/" },
  { name: "Tentang Kami", href: "/about" },
  { name: "Fasilitas", href: "/facilities" },
  { name: "Lokasi Cabang", href: "/locations" },
  { name: "Berita", href: "/news" },
  { name: "Kemitraan", href: "/partnership" },
  { name: "Karir", href: "/careers" },
  { name: "Hubungi Kami", href: "/contact" },
];

export function Navbar() {
  const [isOpen, setIsOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const pathname = usePathname();
  const { settings } = useCompanyProfile();
  const logoPath = settings?.['logo'];

  useEffect(() => {
    const handleScroll = () => {
      setScrolled(window.scrollY > 20);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  return (
    <header
      className={cn(
        "fixed top-0 w-full z-50 transition-all duration-500",
        scrolled
          ? "bg-white/90 backdrop-blur-md shadow-md py-3 border-b border-slate-200"
          : "bg-transparent py-6"
      )}
    >
      <div className="container mx-auto px-4 md:px-6">
        <div className="flex items-center justify-between">
          {/* Logo */}
          <Link href="/" className="flex items-center gap-2 group">
            {logoPath ? (
              <img 
                src={`/storage/${logoPath}`} 
                alt="Logo" 
                className="h-10 w-auto object-contain drop-shadow-sm group-hover:scale-105 transition-transform"
              />
            ) : (
              <div className="bg-primary text-white border border-primary/30 p-2 rounded-xl group-hover:bg-primary-light transition-colors shadow-sm">
                <ShoppingBag size={24} />
              </div>
            )}
          </Link>

          {/* Desktop Nav */}
          <nav className="hidden md:flex items-center gap-8">
            {navLinks.map((link) => (
              <Link
                key={link.name}
                href={link.href}
                className={cn(
                  "text-sm font-semibold transition-all hover:text-primary drop-shadow-sm",
                  pathname === link.href
                    ? "text-primary"
                    : "text-slate-700"
                )}
              >
                {link.name}
              </Link>
            ))}
            <a href="http://shopping.toserbaselamat.id" className="hidden md:flex items-center gap-2 bg-secondary hover:bg-secondary-light text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-[0_4px_15px_rgba(230,0,18,0.3)] hover:shadow-[0_6px_20px_rgba(230,0,18,0.4)] hover:-translate-y-0.5">
              <ShoppingBag size={18} />
              Belanja Online
            </a>
          </nav>

          {/* Mobile Menu Toggle */}
          <button
            className={cn(
              "md:hidden p-2 rounded-md transition-colors",
              scrolled || isOpen ? "text-slate-900 hover:text-primary" : "text-slate-900 hover:text-primary"
            )}
            onClick={() => setIsOpen(!isOpen)}
          >
            {isOpen ? <X size={24} /> : <Menu size={24} />}
          </button>
        </div>
      </div>

      {/* Mobile Nav */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            className="md:hidden bg-white border-t border-slate-200 shadow-2xl overflow-hidden"
          >
            <div className="container mx-auto px-4 py-6 flex flex-col gap-4">
              {navLinks.map((link) => (
                <Link
                  key={link.name}
                  href={link.href}
                  className={cn(
                    "block px-4 py-3 rounded-xl text-base font-medium transition-all",
                    pathname === link.href
                      ? "bg-primary/10 text-primary border border-primary/20"
                      : "text-slate-700 hover:bg-slate-50 hover:text-primary"
                  )}
                  onClick={() => setIsOpen(false)}
                >
                  {link.name}
                </Link>
              ))}
              <a
                href="http://shopping.toserbaselamat.id"
                className="flex items-center gap-2 bg-secondary hover:bg-secondary-light text-white px-5 py-4 rounded-xl font-bold transition-all mt-4 justify-center shadow-md"
              >
                <ShoppingBag size={18} />
                Belanja Online Sekarang
              </a>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
}
