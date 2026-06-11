"use client";

import { motion } from "framer-motion";
import { CheckCircle2 } from "lucide-react";

export default function AboutPage() {
  const values = [
    { title: "Customer-first", desc: "Prioritas utama kami adalah kepuasan dan kenyamanan berbelanja pelanggan." },
    { title: "Modern & Inovatif", desc: "Terus beradaptasi dengan teknologi dan gaya hidup modern untuk layanan terbaik." },
    { title: "Ramah Keluarga (Islami)", desc: "Menyediakan lingkungan yang aman, nyaman, dan berprinsip syariah untuk keluarga." }
  ];

  return (
    <div className="pt-28 pb-20">
      <div className="container mx-auto px-4 max-w-5xl">
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-center mb-16"
        >
          <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-6">Tentang Toserba Selamat</h1>
          <p className="text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed">
            Lebih dari sekadar pusat perbelanjaan, kami adalah destinasi gaya hidup terpadu yang memadukan kebutuhan modern dengan nilai-nilai luhur dan keramahan keluarga.
          </p>
        </motion.div>

        <div className="grid md:grid-cols-2 gap-12 items-center mb-24">
          <motion.div 
            initial={{ opacity: 0, x: -30 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.2 }}
            className="rounded-3xl overflow-hidden shadow-xl"
          >
            <img 
              src="https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=2574&auto=format&fit=crop" 
              alt="Sejarah Kami" 
              className="w-full h-auto object-cover"
            />
          </motion.div>
          <motion.div
            initial={{ opacity: 0, x: 30 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 0.3 }}
          >
            <h2 className="text-3xl font-bold text-slate-900 mb-6">Perjalanan Kami</h2>
            <p className="text-slate-600 mb-4 leading-relaxed text-lg">
              Berawal dari sebuah toko sederhana, Toserba Selamat telah berkembang menjadi jaringan ritel dan hospitality terkemuka dengan lebih dari 26 cabang. 
            </p>
            <p className="text-slate-600 leading-relaxed text-lg mb-8">
              Komitmen kami tidak pernah berubah: menyajikan produk berkualitas tinggi dengan harga terjangkau dalam balutan pelayanan yang sepenuh hati dan fasilitas terintegrasi yang lengkap.
            </p>
            <div className="flex items-center gap-4 p-4 bg-emerald-50 rounded-xl border border-emerald-100">
              <div className="text-emerald-600 font-bold text-3xl">26+</div>
              <div className="text-emerald-800 font-medium">Cabang Tersebar untuk melayani Anda lebih dekat.</div>
            </div>
          </motion.div>
        </div>

        <motion.div 
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="bg-slate-900 rounded-3xl p-10 md:p-16 text-white shadow-2xl"
        >
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Nilai Inti Perusahaan</h2>
            <p className="text-slate-400 text-lg">Prinsip yang membimbing setiap langkah dan pelayanan kami.</p>
          </div>
          
          <div className="grid md:grid-cols-3 gap-8">
            {values.map((val, idx) => (
              <div key={idx} className="bg-slate-800/50 p-6 rounded-2xl border border-slate-700 hover:bg-slate-800 transition-colors">
                <CheckCircle2 className="text-amber-500 mb-4" size={32} />
                <h3 className="text-xl font-bold mb-3">{val.title}</h3>
                <p className="text-slate-400 leading-relaxed">{val.desc}</p>
              </div>
            ))}
          </div>
        </motion.div>
      </div>
    </div>
  );
}
