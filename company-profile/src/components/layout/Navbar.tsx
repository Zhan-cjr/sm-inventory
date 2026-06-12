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
          ? "glass-panel-dark shadow-xl py-3 border-b border-white/5"
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
                className="h-10 w-auto object-contain drop-shadow-[0_0_15px_rgba(255,255,255,0.2)] group-hover:scale-105 transition-transform"
              />
            ) : (
              <div className="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 p-2 rounded-xl group-hover:bg-emerald-500/30 transition-colors shadow-[0_0_15px_rgba(16,185,129,0.3)]">
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
                  "text-sm font-semibold transition-all hover:text-emerald-400 drop-shadow-sm",
                  pathname === link.href
                    ? "text-emerald-400"
                    : "text-slate-300"
                )}
              >
                {link.name}
              </Link>
            ))}
            <a href="http://shopping.toserbaselamat.id" className="hidden md:flex items-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-5 py-2.5 rounded-xl font-bold transition-all shadow-[0_0_20px_rgba(16,185,129,0.3)] hover:shadow-[0_0_25px_rgba(16,185,129,0.5)] hover:-translate-y-0.5">
              <ShoppingBag size={18} />
              Belanja Online
            </a>
          </nav>

          {/* Mobile Menu Toggle */}
          <button
            className={cn(
              "md:hidden p-2 rounded-md transition-colors",
              scrolled || isOpen ? "text-white hover:text-emerald-400" : "text-white hover:text-emerald-400"
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
            className="md:hidden glass-panel-dark border-t border-white/10 shadow-2xl overflow-hidden backdrop-blur-xl"
          >
            <div className="container mx-auto px-4 py-6 flex flex-col gap-4">
              {navLinks.map((link) => (
                <Link
                  key={link.name}
                  href={link.href}
                  className={cn(
                    "block px-4 py-3 rounded-xl text-base font-medium transition-all",
                    pathname === link.href
                      ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                      : "text-slate-300 hover:bg-white/5 hover:text-white"
                  )}
                  onClick={() => setIsOpen(false)}
                >
                  {link.name}
                </Link>
              ))}
              <a
                href="http://shopping.toserbaselamat.id"
                className="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-5 py-4 rounded-xl font-bold transition-all mt-4 justify-center shadow-[0_0_20px_rgba(16,185,129,0.2)]"
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
