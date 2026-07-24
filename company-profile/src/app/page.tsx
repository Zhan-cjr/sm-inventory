"use client";

import { motion, useInView } from "framer-motion";
import Link from "next/link";
import { ArrowRight, MapPin, ShoppingCart, Sparkles, Building2, Users, Star, Quote, ShieldCheck, HeartHandshake, CheckCircle2, Award, Search } from "lucide-react";
import { companyStats } from "@/lib/data";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";
import { useEffect, useRef, useState } from "react";
import MembershipSection from "@/components/home/MembershipSection";

// Counter animation component
function AnimatedCounter({ value, label }: { value: string, label: string }) {
  const numericValue = parseInt(value.replace(/[^0-9]/g, '')) || 0;
  const suffix = value.replace(/[0-9]/g, '');
  const [count, setCount] = useState(0);
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: "-50px" });

  useEffect(() => {
    if (isInView && numericValue > 0) {
      let start = 0;
      const duration = 2000;
      const stepTime = Math.abs(Math.floor(duration / numericValue));
      
      const timer = setInterval(() => {
        start += 1;
        setCount(start);
        if (start >= numericValue) {
          setCount(numericValue);
          clearInterval(timer);
        }
      }, stepTime > 0 ? stepTime : 20);
      
      return () => clearInterval(timer);
    }
  }, [isInView, numericValue]);

  return (
    <div ref={ref} className="text-center px-4 relative z-10 group">
      <div className="absolute inset-0 bg-primary/10 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
      <h3 className="text-4xl sm:text-5xl md:text-6xl font-black text-primary mb-2 tracking-tight">
        {count}{suffix}
      </h3>
      <p className="text-slate-500 font-bold tracking-wider uppercase text-xs sm:text-sm">{label}</p>
    </div>
  );
}

export default function Home() {
  const { facilities, isLoading } = useCompanyProfile();
  const [testimonials, setTestimonials] = useState<any[]>([]);
  const [selectedFacilityCategory, setSelectedFacilityCategory] = useState("semua");

  useEffect(() => {
    fetch(`${process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id/api/company-profile'}/testimonials`)
      .then(res => {
        if (!res.ok) throw new Error("Failed to fetch testimonials");
        return res.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          setTestimonials(data);
        } else {
          setTestimonials([]);
        }
      })
      .catch(() => setTestimonials([]));
  }, []);

  const defaultTestimonials = [
    {
      id: "t1",
      customer_name: "Hj. Ratna Sari",
      role: "Ibu Rumah Tangga & Pelanggan Setia",
      content: "Belanja di Toserba Selamat selalu menenangkan. Tempatnya bersih, produk halal terjamin, dan karyawan sangat ramah melayani keluarga.",
      rating: 5
    },
    {
      id: "t2",
      customer_name: "Dr. Ahmad Farhan",
      role: "Mitra Tenant Kuliner",
      content: "Sebagai mitra tenant space, lalu lintas pengunjung Toserba Selamat luar biasa stabil. Fasilitas lengkap membuat pengunjung betah berbelanja.",
      rating: 5
    },
    {
      id: "t3",
      customer_name: "Budi Santoso",
      role: "Anggota Member Privilege",
      content: "Integrasi poin member dan diskon khusus hotel syariahnya sangat menguntungkan. Aplikasi belanja onlinenya juga cepat!",
      rating: 5
    }
  ];

  const activeTestimonials = testimonials.length > 0 ? testimonials : defaultTestimonials;

  if (isLoading) {
    return (
      <div className="h-screen flex justify-center items-center bg-slate-50">
        <div className="relative flex flex-col items-center gap-4">
          <div className="animate-spin rounded-full h-16 w-16 border-4 border-primary border-t-transparent shadow-md" />
          <p className="text-primary font-bold text-sm tracking-widest uppercase">Memuat Toserba Selamat...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-slate-50 min-h-screen text-slate-900 selection:bg-primary/20">
      
      {/* Dynamic Hero Section */}
      <section className="relative min-h-[90vh] flex items-center justify-center overflow-hidden pt-28 pb-20">
        
        {/* Animated Background Blobs & Grid */}
        <div className="absolute top-10 left-10 w-96 h-96 bg-primary/20 rounded-full blur-[140px] pointer-events-none animate-blob" />
        <div className="absolute top-20 right-10 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none animate-blob animation-delay-2000" />
        <div className="absolute bottom-10 left-1/3 w-96 h-96 bg-accent/20 rounded-full blur-[140px] pointer-events-none animate-blob animation-delay-4000" />

        <div className="container mx-auto px-4 md:px-8 relative z-20">
          <div className="grid lg:grid-cols-12 gap-12 items-center">
            
            {/* Left Hero Content */}
            <motion.div
              initial={{ opacity: 0, x: -40 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.9, ease: "easeOut" }}
              className="lg:col-span-7 space-y-6 text-center lg:text-left"
            >
              <div className="inline-flex items-center gap-2.5 px-4 py-2 rounded-full glass-panel border border-primary/20 text-primary text-xs sm:text-sm font-bold tracking-widest uppercase bg-white/80 shadow-xs">
                <ShieldCheck className="w-4 h-4 text-accent" /> THE MOSLEM FAMILY RETAIL &amp; HOSPITALITY
              </div>

              <h1 className="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black leading-[1.1] text-slate-900 tracking-tight">
                Lengkap, Nyaman, <br />
                <span className="bg-gradient-to-r from-primary via-primary-light to-secondary text-gradient">
                  Penuh Berkah Keluarga.
                </span>
              </h1>

              <p className="text-base sm:text-lg md:text-xl text-slate-600 font-medium leading-relaxed max-w-2xl mx-auto lg:mx-0">
                Toserba Selamat menghadirkan pusat perbelanjaan modern terpadu, supermarket syariah, hotel &amp; lounge, fasilitas olahraga, dan kuliner dalam satu ekosistem ramah keluarga.
              </p>

              {/* Action Buttons */}
              <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                <Link
                  href="/locations"
                  className="w-full sm:w-auto bg-primary hover:bg-primary-light text-white px-8 py-4 rounded-2xl font-extrabold text-base transition-all flex items-center justify-center gap-3 shadow-xl shadow-primary/25 hover:-translate-y-1"
                >
                  <MapPin size={22} />
                  Cari 26+ Cabang Terdekat
                </Link>
                <Link
                  href="/facilities"
                  className="w-full sm:w-auto glass-panel hover:bg-white text-slate-800 border border-slate-300/80 px-8 py-4 rounded-2xl font-extrabold text-base transition-all flex items-center justify-center gap-3 shadow-xs hover:-translate-y-1"
                >
                  Jelajahi Ekosistem
                  <ArrowRight size={22} className="text-secondary" />
                </Link>
              </div>

              {/* Key Trust Highlights */}
              <div className="pt-6 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs font-bold text-slate-600 border-t border-slate-200/80">
                <div className="flex items-center gap-2">
                  <CheckCircle2 size={16} className="text-accent" /> 100% Produk Halal &amp; Thayyib
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle2 size={16} className="text-accent" /> 26+ Titik Cabang Strategis
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle2 size={16} className="text-accent" /> Layanan Ramah Syariah
                </div>
              </div>
            </motion.div>

            {/* Right Hero Interactive Visual Showcase */}
            <motion.div
              initial={{ opacity: 0, scale: 0.9, y: 30 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              transition={{ duration: 1, delay: 0.2 }}
              className="lg:col-span-5 relative"
            >
              <div className="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white glass-panel-dark">
                <div className="bg-gradient-to-tr from-primary to-primary-dark p-8 text-white relative min-h-[380px] flex flex-col justify-between">
                  
                  {/* Floating Top Badge */}
                  <div className="flex items-center justify-between">
                    <span className="px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold uppercase tracking-wider text-accent flex items-center gap-1.5">
                      <Sparkles size={14} /> Ecosystem Highlights
                    </span>
                    <span className="text-xs font-mono font-bold text-slate-300">EST. 1998</span>
                  </div>

                  {/* Main Visual Title */}
                  <div className="space-y-3 my-6">
                    <h3 className="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                      Destinasi Utama Kebutuhan &amp; Gaya Hidup Islami
                    </h3>
                    <p className="text-xs sm:text-sm text-slate-200 leading-relaxed font-medium">
                      Nikmati kemudahan supermarket bahan segar, area wahana bermain anak, hotel syariah, dan fasilitas kebugaran dalam satu kawasan.
                    </p>
                  </div>

                  {/* Floating Action Cards */}
                  <div className="grid grid-cols-2 gap-3 pt-4 border-t border-white/15">
                    <div className="bg-white/10 backdrop-blur-md p-3.5 rounded-2xl border border-white/15">
                      <p className="text-[10px] text-slate-300 font-mono">CABANG AKTIF</p>
                      <p className="text-lg font-black text-white">26+ Kota &amp; Kabupaten</p>
                    </div>
                    <div className="bg-white/10 backdrop-blur-md p-3.5 rounded-2xl border border-white/15">
                      <p className="text-[10px] text-slate-300 font-mono">PENGUNJUNG</p>
                      <p className="text-lg font-black text-accent">500k+ / Bulan</p>
                    </div>
                  </div>

                </div>
              </div>
            </motion.div>

          </div>
        </div>
      </section>

      {/* Interactive Loyalty & Member Section */}
      <MembershipSection />

      {/* Stats Counter Section */}
      <section className="py-14 bg-white border-y border-slate-200/80 shadow-xs relative z-30">
        <div className="container mx-auto px-4 md:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 divide-y md:divide-y-0 md:divide-x divide-slate-200/80">
            {companyStats.map((stat, index) => (
              <AnimatedCounter key={index} value={stat.value} label={stat.label} />
            ))}
          </div>
        </div>
      </section>

      {/* Facilities & Business Ecosystem Grid */}
      <section className="py-20 sm:py-28 relative bg-slate-50">
        <div className="container mx-auto px-4 md:px-8">
          
          <div className="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/20 text-slate-800 text-xs font-bold uppercase tracking-widest">
              <Building2 size={14} className="text-primary" /> Ekosistem Terpadu
            </div>
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight">
              Fasilitas &amp; <span className="text-primary">Unit Bisnis Utama</span>
            </h2>
            <p className="text-slate-600 text-base sm:text-lg font-medium leading-relaxed">
              Memadukan kenyamanan pusat perbelanjaan ritel modern, akomodasi hospitality, arena olahraga, dan sarana ibadah bersih.
            </p>
          </div>

          {/* Facility Cards Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            {facilities.slice(0, 6).map((facility: any, index: number) => {
              const Icon = IconMapper[facility.icon] || ShoppingCart;
              return (
                <motion.div
                  key={facility.id || index}
                  initial={{ opacity: 0, y: 30 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true, margin: "-50px" }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  className="group relative"
                >
                  <div className="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-md hover:shadow-2xl hover:border-primary/30 transition-all duration-400 hover:-translate-y-2 relative overflow-hidden flex flex-col justify-between h-full">
                    
                    <div className="space-y-4">
                      <div className="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors duration-400 shadow-xs">
                        <Icon size={28} />
                      </div>
                      <h3 className="text-xl sm:text-2xl font-extrabold text-slate-900 group-hover:text-primary transition-colors">
                        {facility.name}
                      </h3>
                      <p className="text-slate-600 text-sm leading-relaxed font-medium">
                        {facility.description}
                      </p>
                    </div>

                    <div className="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-primary">
                      <span>Lihat Detail Layanan</span>
                      <ArrowRight size={16} className="group-hover:translate-x-1 transition-transform" />
                    </div>

                  </div>
                </motion.div>
              );
            })}
          </div>

          <div className="mt-14 text-center">
            <Link
              href="/facilities"
              className="inline-flex items-center gap-3 bg-primary hover:bg-primary-light text-white font-extrabold px-8 py-4 rounded-2xl text-base transition-all shadow-lg shadow-primary/20 hover:scale-105"
            >
              Jelajahi Seluruh Fasilitas Toserba Selamat
              <ArrowRight size={20} />
            </Link>
          </div>

        </div>
      </section>

      {/* Customer & Partner Testimonials */}
      <section className="py-20 bg-slate-900 text-white relative overflow-hidden">
        <div className="absolute top-0 right-0 w-96 h-96 bg-primary/30 rounded-full blur-[140px] pointer-events-none" />
        <div className="absolute bottom-0 left-0 w-96 h-96 bg-secondary/20 rounded-full blur-[140px] pointer-events-none" />

        <div className="container mx-auto px-4 md:px-8 relative z-10">
          <div className="text-center max-w-3xl mx-auto mb-16 space-y-3">
            <span className="px-3.5 py-1 rounded-full bg-white/10 text-accent font-bold text-xs uppercase tracking-widest border border-white/20">
              Testimoni Pelanggan
            </span>
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white">
              Apa Kata <span className="text-secondary">Masyarakat &amp; Mitra</span>
            </h2>
            <p className="text-slate-300 text-base font-medium">
              Kepercayaan dan kenyamanan pelanggan adalah motivasi terbesar kami untuk terus berinovasi.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
            {activeTestimonials.map((testi, i) => (
              <motion.div
                key={testi.id || i}
                initial={{ opacity: 0, y: 30 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.1 }}
                className="bg-white/5 border border-white/10 backdrop-blur-xl p-8 rounded-3xl relative flex flex-col justify-between hover:border-white/30 transition-all hover:-translate-y-1"
              >
                <div>
                  <Quote size={36} className="text-accent/40 mb-4" />
                  <div className="flex gap-1 mb-4">
                    {[...Array(5)].map((_, j) => (
                      <Star key={j} size={16} className={j < (testi.rating || 5) ? "text-amber-400 fill-amber-400" : "text-slate-600"} />
                    ))}
                  </div>
                  <p className="text-slate-200 text-sm leading-relaxed font-medium italic mb-6">
                    &quot;{testi.content}&quot;
                  </p>
                </div>

                <div className="flex items-center gap-3.5 pt-4 border-t border-white/10">
                  <div className="w-11 h-11 rounded-full bg-primary flex items-center justify-center font-bold text-white shadow-md">
                    {testi.customer_name.charAt(0)}
                  </div>
                  <div>
                    <h4 className="font-bold text-sm text-white">{testi.customer_name}</h4>
                    <p className="text-xs text-slate-400 font-medium">{testi.role || "Pelanggan Setia"}</p>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Premium Branch CTA Banner */}
      <section className="py-20 sm:py-28 relative overflow-hidden bg-slate-50">
        <div className="container mx-auto px-4 md:px-8 relative z-10">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            whileInView={{ opacity: 1, scale: 1 }}
            viewport={{ once: true }}
            transition={{ duration: 0.7 }}
            className="bg-gradient-to-tr from-primary via-primary-dark to-slate-900 text-white rounded-[2.5rem] p-10 sm:p-16 text-center max-w-5xl mx-auto shadow-2xl border border-primary-light/30 relative overflow-hidden"
          >
            <div className="absolute -top-10 -right-10 w-80 h-80 bg-accent/20 rounded-full blur-[100px] pointer-events-none" />
            
            <Building2 size={48} className="mx-auto text-accent mb-6" />
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-black mb-6 leading-tight">
              Hadir Lebih Dekat di <br className="hidden sm:block"/>
              <span className="text-accent">26 Titik Lokasi Cabang</span>
            </h2>
            <p className="text-slate-200 font-medium text-base sm:text-lg mb-10 max-w-2xl mx-auto leading-relaxed">
              Temukan Toserba Selamat terdekat di kota Anda. Nikmati fasilitas supermarket lengkap, promo mingguan, dan suasana ramah keluarga.
            </p>
            <Link
              href="/locations"
              className="inline-flex items-center justify-center gap-3 bg-secondary hover:bg-secondary-light text-white px-10 py-5 rounded-2xl font-extrabold text-lg transition-all hover:scale-105 shadow-xl shadow-secondary/30"
            >
              Buka Peta Cabang Terdekat <MapPin size={22} />
            </Link>
          </motion.div>
        </div>
      </section>

    </div>
  );
}
