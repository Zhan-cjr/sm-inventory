"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { CheckCircle2, HeartHandshake, Zap, ShieldCheck, Award, Target, Eye, Building2, Users, ArrowRight } from "lucide-react";
import Link from "next/link";

const timelineEvents = [
  { year: "1998", title: "Pendirian Toko Pertama", desc: "Berawal dari toko kelontong keluarga sederhana di Cianjur dengan mengedepankan keramahan dan kejujuran." },
  { year: "2008", title: "Ekspansi Supermarket Syariah", desc: "Transformasi menjadi format Toserba modern ramah keluarga dengan prinsip jaminan 100% halal & thayyib." },
  { year: "2018", title: "Diversifikasi Unit Hospitality", desc: "Membuka Selamat Hotel & Executive Lounge Syariah serta area wahana bermain anak modern." },
  { year: "2024+", title: "Ekosistem 26+ Cabang Terpadu", desc: "Integrasi jaringan ritel fisik, e-commerce belanja online, SHSC Fitness Center, dan keanggotaan digital." },
];

export default function AboutPage() {
  const [activeTab, setActiveTab] = useState<"visi" | "misi">("visi");

  const values = [
    { 
      title: "Customer First & Heartfelt Service", 
      desc: "Prioritas utama kami adalah kepuasan, kenyamanan, dan kehangatan menyambut setiap keluarga yang berbelanja.",
      icon: HeartHandshake,
      color: "from-rose-500 to-pink-600"
    },
    { 
      title: "Modern RETAIL & Innovation", 
      desc: "Terus beradaptasi dengan teknologi digital, aplikasi belanja online, dan ekosistem modern yang efisien.",
      icon: Zap,
      color: "from-blue-600 to-cyan-500"
    },
    { 
      title: "Ramah Keluarga & Syariah", 
      desc: "Menyediakan lingkungan aman, bersih, dan berprinsip syariah untuk perlindungan konsumen dan berkah bersama.",
      icon: ShieldCheck,
      color: "from-emerald-600 to-teal-500"
    }
  ];

  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[700px] h-[700px] bg-primary/10 rounded-full blur-[140px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[700px] h-[700px] bg-accent/15 rounded-full blur-[140px] pointer-events-none" />

      <div className="container mx-auto px-4 md:px-8 max-w-6xl relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-20">
          <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-widest mb-4">
            <Building2 size={14} /> Profil Korporasi
          </span>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black text-slate-900 tracking-tight mb-6">
            Mengenal Lebih Dekat <br />
            <span className="bg-gradient-to-r from-primary via-primary-light to-secondary text-gradient">Toserba Selamat</span>
          </h1>
          <p className="text-slate-600 font-medium text-base sm:text-lg md:text-xl leading-relaxed">
            Lebih dari sekadar pusat perbelanjaan, kami adalah destinasi ekosistem gaya hidup terpadu yang memadukan kebutuhan ritel modern dengan keramahan keluarga dan nilai-nilai syariah.
          </p>
        </div>

        {/* Story Section */}
        <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200/80 shadow-xl mb-24 relative overflow-hidden">
          <div className="grid lg:grid-cols-12 gap-10 items-center">
            
            <div className="lg:col-span-7 space-y-6">
              <span className="text-xs font-bold text-accent uppercase tracking-widest">PERJALANAN KAMI</span>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                Tumbuh Bersama Kepercayaan Keluarga Indonesia
              </h2>
              <p className="text-slate-600 leading-relaxed font-medium text-base">
                Berawal dari toko sederhana pada tahun 1998, Toserba Selamat telah berkembang pesat menjadi jaringan ritel dan hospitality terkemuka dengan lebih dari 26 cabang di Jawa Barat dan sekitarnya.
              </p>
              <p className="text-slate-600 leading-relaxed font-medium text-base">
                Komitmen kami tidak pernah berubah: menyajikan produk berkualitas tinggi dengan harga terjangkau dalam pelayanan yang sepenuh hati serta tempat belanja yang bersih dan menenangkan.
              </p>
              
              <div className="grid grid-cols-2 gap-4 pt-4">
                <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200/80">
                  <h4 className="text-3xl font-black text-primary">26+</h4>
                  <p className="text-xs font-bold text-slate-600">Cabang Ritel &amp; Unit Bisnis</p>
                </div>
                <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200/80">
                  <h4 className="text-3xl font-black text-secondary">1.500+</h4>
                  <p className="text-xs font-bold text-slate-600">Karyawan &amp; Staff Profesional</p>
                </div>
              </div>
            </div>

            <div className="lg:col-span-5">
              <div className="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-slate-100 group">
                <img
                  src="https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=2574&auto=format&fit=crop"
                  alt="Toserba Selamat Store"
                  className="w-full h-80 sm:h-96 object-cover group-hover:scale-105 transition-transform duration-700"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent flex items-end p-6">
                  <div className="text-white">
                    <p className="text-xs font-bold text-accent uppercase tracking-wider">Kantor Pusat &amp; Flagship Store</p>
                    <p className="font-extrabold text-lg">Cianjur City Center, Jawa Barat</p>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        {/* Vision & Mission Interactive Block */}
        <div className="bg-gradient-to-tr from-primary via-primary-dark to-slate-900 text-white rounded-3xl p-8 sm:p-12 shadow-2xl mb-24 relative overflow-hidden">
          <div className="absolute top-0 right-0 w-80 h-80 bg-accent/20 rounded-full blur-3xl pointer-events-none" />
          
          <div className="max-w-3xl mx-auto text-center space-y-6">
            <div className="flex justify-center gap-4">
              <button
                onClick={() => setActiveTab("visi")}
                className={`px-6 py-2.5 rounded-full font-bold text-sm transition-all ${
                  activeTab === "visi"
                    ? "bg-accent text-slate-950 shadow-md"
                    : "bg-white/10 text-white hover:bg-white/20"
                }`}
              >
                Visi Perusahaan
              </button>
              <button
                onClick={() => setActiveTab("misi")}
                className={`px-6 py-2.5 rounded-full font-bold text-sm transition-all ${
                  activeTab === "misi"
                    ? "bg-accent text-slate-950 shadow-md"
                    : "bg-white/10 text-white hover:bg-white/20"
                }`}
              >
                Misi Perusahaan
              </button>
            </div>

            {activeTab === "visi" ? (
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                className="space-y-4 py-4"
              >
                <Eye className="w-12 h-12 text-accent mx-auto" />
                <h3 className="text-2xl sm:text-3xl font-extrabold text-white">Visi Toserba Selamat</h3>
                <p className="text-slate-200 text-lg leading-relaxed font-medium">
                  &quot;Menjadi jaringan ritel &amp; ekosistem gaya hidup ritel-hospitality terkemuka yang paling terpercaya, ramah keluarga, dan berprinsip syariah di Indonesia.&quot;
                </p>
              </motion.div>
            ) : (
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                className="space-y-4 py-4 text-left max-w-2xl mx-auto"
              >
                <Target className="w-12 h-12 text-accent mx-auto" />
                <h3 className="text-2xl sm:text-3xl font-extrabold text-white text-center mb-6">Misi Toserba Selamat</h3>
                <ul className="space-y-3 text-slate-200 text-sm sm:text-base font-medium">
                  <li className="flex items-start gap-3">
                    <CheckCircle2 className="w-5 h-5 text-accent shrink-0 mt-0.5" />
                    <span>Menyediakan produk berkualitas tinggi, segar, dan 100% terjamin halal dengan harga bersaing.</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <CheckCircle2 className="w-5 h-5 text-accent shrink-0 mt-0.5" />
                    <span>Memberikan pelayanan hangat sepenuh hati dalam lingkungan berbelanja yang bersih dan ramah keluarga.</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <CheckCircle2 className="w-5 h-5 text-accent shrink-0 mt-0.5" />
                    <span>Mengembangkan fasilitas terintegrasi (Hotel, Fitness Center, Playground) untuk kenyamanan pengunjung.</span>
                  </li>
                </ul>
              </motion.div>
            )}
          </div>
        </div>

        {/* Corporate Growth Timeline */}
        <div className="mb-24 space-y-12">
          <div className="text-center max-w-2xl mx-auto space-y-3">
            <span className="text-xs font-bold text-primary uppercase tracking-widest">TIMELINE PERKEMBANGAN</span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900">Jejak Langkah &amp; Milestone</h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            {timelineEvents.map((item, idx) => (
              <div key={idx} className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-md relative hover:shadow-xl transition-all">
                <span className="text-3xl font-black text-primary font-mono block mb-2">{item.year}</span>
                <h3 className="text-lg font-bold text-slate-900 mb-2">{item.title}</h3>
                <p className="text-xs text-slate-600 font-medium leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Core Values Cards */}
        <div className="space-y-12">
          <div className="text-center max-w-2xl mx-auto space-y-3">
            <span className="text-xs font-bold text-secondary uppercase tracking-widest">NILAI PERUSAHAAN</span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900">Prinsip &amp; Landasan Kerja</h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {values.map((val, idx) => {
              const Icon = val.icon;
              return (
                <div key={idx} className="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-md hover:shadow-xl transition-all group">
                  <div className={`w-14 h-14 rounded-2xl bg-gradient-to-br ${val.color} text-white flex items-center justify-center mb-6 shadow-md`}>
                    <Icon size={28} />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900 mb-3 group-hover:text-primary transition-colors">{val.title}</h3>
                  <p className="text-slate-600 text-sm font-medium leading-relaxed">{val.desc}</p>
                </div>
              );
            })}
          </div>
        </div>

        {/* CTA Link */}
        <div className="mt-20 text-center">
          <Link
            href="/locations"
            className="inline-flex items-center gap-3 bg-primary text-white font-extrabold px-8 py-4 rounded-2xl transition-all shadow-lg hover:scale-105"
          >
            Temukan Lokasi Cabang Toserba Selamat
            <ArrowRight size={20} />
          </Link>
        </div>

      </div>
    </div>
  );
}
