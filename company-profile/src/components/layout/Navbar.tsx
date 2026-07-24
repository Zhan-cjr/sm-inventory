"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu, X, ShoppingBag, Search, Sparkles, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";
import { motion, AnimatePresence } from "framer-motion";
import { useCompanyProfile } from "@/lib/hooks";
import CommandPaletteModal from "@/components/home/CommandPaletteModal";

const navLinks = [
  { name: "Beranda", href: "/" },
  { name: "Tentang Kami", href: "/about" },
  { name: "Fasilitas", href: "/facilities" },
  { name: "Lokasi Cabang", href: "/locations" },
  { name: "Berita & Promo", href: "/news" },
  { name: "Kemitraan", href: "/partnership" },
  { name: "Karir", href: "/careers" },
  { name: "Hubungi Kami", href: "/contact" },
];

export function Navbar() {
  const [isOpen, setIsOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
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
    <>
      <header
        className={cn(
          "fixed top-0 w-full z-40 transition-all duration-500",
          scrolled
            ? "bg-white/90 backdrop-blur-xl shadow-md py-3 border-b border-slate-200/80"
            : "bg-transparent py-5"
        )}
      >
        <div className="container mx-auto px-4 md:px-8">
          <div className="flex items-center justify-between">
            {/* Logo Brand */}
            <Link href="/" className="flex items-center gap-3 group">
              {logoPath ? (
                <img 
                  src={`/storage/${logoPath}`} 
                  alt="Toserba Selamat" 
                  className="h-10 w-auto object-contain drop-shadow-sm group-hover:scale-105 transition-transform"
                />
              ) : (
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary to-primary-light flex items-center justify-center text-white shadow-md shadow-primary/20 group-hover:rotate-6 transition-transform">
                    <ShoppingBag size={22} />
                  </div>
                  <div className="flex flex-col">
                    <span className="font-extrabold text-lg text-primary tracking-tight leading-tight">
                      Toserba <span className="text-secondary">Selamat</span>
                    </span>
                    <span className="text-[10px] font-bold text-accent tracking-widest uppercase">
                      The Moslem Family
                    </span>
                  </div>
                </div>
              )}
            </Link>

            {/* Desktop Navigation Menu */}
            <nav className="hidden xl:flex items-center gap-6">
              {navLinks.map((link) => {
                const isActive = pathname === link.href;
                return (
                  <Link
                    key={link.name}
                    href={link.href}
                    className={cn(
                      "relative text-xs lg:text-sm font-bold transition-all py-1.5 px-3 rounded-full",
                      isActive
                        ? "text-primary bg-primary/10 shadow-xs"
                        : "text-slate-700 hover:text-primary hover:bg-slate-100/60"
                    )}
                  >
                    {link.name}
                  </Link>
                );
              })}
            </nav>

            {/* Right Header Actions */}
            <div className="hidden lg:flex items-center gap-3">
              {/* Quick Search Button */}
              <button
                onClick={() => setSearchOpen(true)}
                className="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all border border-slate-200/80"
              >
                <Search size={15} />
                <span>Cari...</span>
                <kbd className="hidden xl:inline-block px-1.5 py-0.5 bg-white border border-slate-300 rounded text-[10px] text-slate-500 font-mono">
                  ⌘K
                </kbd>
              </button>

              {/* Online Shopping CTA */}
              <a
                href="http://shopping.toserbaselamat.id"
                target="_blank"
                rel="noreferrer"
                className="flex items-center gap-2 bg-gradient-to-r from-secondary to-secondary-light hover:opacity-95 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-secondary/20 hover:-translate-y-0.5 transition-all"
              >
                <ShoppingBag size={16} />
                Belanja Online
              </a>
            </div>

            {/* Mobile Menu & Search Actions */}
            <div className="flex xl:hidden items-center gap-2">
              <button
                onClick={() => setSearchOpen(true)}
                className="p-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                aria-label="Cari"
              >
                <Search size={20} />
              </button>
              <button
                className="p-2.5 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
                onClick={() => setIsOpen(!isOpen)}
                aria-label="Menu"
              >
                {isOpen ? <X size={22} /> : <Menu size={22} />}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Navigation Drawer */}
        <AnimatePresence>
          {isOpen && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: "auto" }}
              exit={{ opacity: 0, height: 0 }}
              className="xl:hidden bg-white/95 backdrop-blur-2xl border-t border-slate-200/80 shadow-2xl overflow-hidden"
            >
              <div className="container mx-auto px-4 py-6 flex flex-col gap-2">
                {navLinks.map((link) => {
                  const isActive = pathname === link.href;
                  return (
                    <Link
                      key={link.name}
                      href={link.href}
                      className={cn(
                        "flex items-center justify-between px-4 py-3 rounded-2xl text-sm font-bold transition-all",
                        isActive
                          ? "bg-primary text-white shadow-md shadow-primary/20"
                          : "text-slate-700 hover:bg-slate-100 hover:text-primary"
                      )}
                      onClick={() => setIsOpen(false)}
                    >
                      <span>{link.name}</span>
                      <ChevronRight size={18} className={isActive ? "text-white" : "text-slate-400"} />
                    </Link>
                  );
                })}
                <a
                  href="http://shopping.toserbaselamat.id"
                  target="_blank"
                  rel="noreferrer"
                  className="flex items-center justify-center gap-2 bg-gradient-to-r from-secondary to-secondary-light text-white px-5 py-3.5 rounded-2xl font-bold transition-all mt-3 shadow-lg shadow-secondary/20 text-sm"
                >
                  <ShoppingBag size={18} />
                  Belanja Online Sekarang
                </a>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </header>

      {/* Command Palette Search Modal */}
      <CommandPaletteModal isOpen={searchOpen} onClose={() => setSearchOpen(false)} />
    </>
  );
}
