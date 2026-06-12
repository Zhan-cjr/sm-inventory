"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { Calendar, Tag, ArrowRight, Newspaper } from "lucide-react";
import Link from "next/link";

interface Article {
  id: number;
  title: string;
  slug: string;
  type: string;
  content: string;
  image_url: string | null;
  published_at: string;
  created_at: string;
}

const fadeIn = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.8 } }
};

export default function NewsPage() {
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState("Semua");

  useEffect(() => {
    fetch(`${process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id/api/company-profile'}/articles`)
      .then(res => {
        if (!res.ok) throw new Error("Failed to fetch articles");
        return res.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          setArticles(data);
        } else {
          console.error("API did not return an array", data);
          setArticles([]);
        }
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setArticles([]);
        setLoading(false);
      });
  }, []);

  const filteredArticles = filter === "Semua" 
    ? articles 
    : articles.filter(a => a.type === filter);

  return (
    <div className="bg-slate-950 min-h-screen pt-28 pb-32 text-slate-50 relative overflow-hidden">
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[800px] h-[800px] bg-blue-900/20 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-lime-900/10 rounded-full blur-[100px] pointer-events-none" />

      <div className="container mx-auto px-4 max-w-7xl relative z-10">
        
        {/* Header */}
        <motion.div 
          initial="hidden"
          animate="visible"
          variants={{
            hidden: { opacity: 0 },
            visible: { opacity: 1, transition: { staggerChildren: 0.2 } }
          }}
          className="text-center mb-16"
        >
          <motion.div variants={fadeIn} className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-panel border-white/10 text-lime-400 text-xs sm:text-sm font-semibold tracking-widest uppercase mb-6 bg-lime-500/10 border-lime-500/20">
            <Newspaper size={16} /> Berita & Pembaruan
          </motion.div>
          <motion.h1 variants={fadeIn} className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-white mb-6 tracking-tight">
            Kabar <span className="bg-gradient-to-r from-blue-400 to-lime-400 text-gradient">Terbaru</span>
          </motion.h1>
          <motion.p variants={fadeIn} className="text-base sm:text-lg md:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed px-2">
            Ikuti informasi terkini seputar promo menarik, kegiatan sosial, dan perkembangan bisnis Toserba Selamat.
          </motion.p>
        </motion.div>

        {/* Filters */}
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.4 }}
          className="flex flex-wrap justify-center gap-3 mb-16"
        >
          {['Semua', 'Berita', 'Promo', 'Kegiatan'].map((type) => (
            <button
              key={type}
              onClick={() => setFilter(type)}
              className={`px-6 py-2.5 rounded-full font-medium text-sm transition-all border ${
                filter === type 
                ? 'bg-blue-600 border-blue-500 text-white shadow-[0_0_15px_rgba(59,130,246,0.5)]' 
                : 'glass-panel text-slate-400 border-white/10 hover:text-white hover:border-white/30'
              }`}
            >
              {type}
            </button>
          ))}
        </motion.div>

        {/* Content */}
        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
          </div>
        ) : filteredArticles.length === 0 ? (
          <div className="text-center py-20 glass-panel-dark rounded-3xl border border-white/5">
            <Newspaper size={48} className="mx-auto text-slate-600 mb-4" />
            <h3 className="text-xl font-bold text-white mb-2">Belum Ada Artikel</h3>
            <p className="text-slate-400">Belum ada publikasi berita atau promo saat ini. Silakan kembali lagi nanti.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {filteredArticles.map((article, i) => (
              <motion.div 
                key={article.id}
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: i * 0.1 }}
                className="glass-panel-dark rounded-3xl border border-white/5 overflow-hidden group hover:border-blue-500/30 transition-all flex flex-col"
              >
                <div className="relative h-56 overflow-hidden bg-slate-800">
                  {article.image_url ? (
                    <img 
                      src={`${new URL(process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id').origin}/storage/${article.image_url}`} 
                      alt={article.title} 
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-slate-600">
                      <Newspaper size={48} />
                    </div>
                  )}
                  <div className="absolute top-4 left-4">
                    <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider backdrop-blur-md ${
                      article.type === 'Promo' ? 'bg-red-500/80 text-white' : 
                      article.type === 'Kegiatan' ? 'bg-lime-500/80 text-slate-900' : 
                      'bg-blue-600/80 text-white'
                    }`}>
                      {article.type}
                    </span>
                  </div>
                </div>
                
                <div className="p-6 flex flex-col flex-grow">
                  <div className="flex items-center gap-4 text-slate-400 text-sm mb-4">
                    <div className="flex items-center gap-1.5">
                      <Calendar size={14} />
                      <span>{new Date(article.published_at || article.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</span>
                    </div>
                  </div>
                  
                  <h3 className="text-xl font-bold text-white mb-3 group-hover:text-blue-400 transition-colors line-clamp-2">
                    {article.title}
                  </h3>
                  
                  <div className="text-slate-400 text-sm leading-relaxed mb-6 line-clamp-3" dangerouslySetInnerHTML={{ __html: article.content }}></div>
                  
                  <div className="mt-auto pt-4 border-t border-white/10">
                    <Link href={`/news/detail?slug=${article.slug}`} className="text-red-400 font-semibold hover:text-red-300 flex items-center gap-2 group/link text-sm mt-auto">
                    Baca Selengkapnya 
                    <ArrowRight size={16} className="group-hover/link:translate-x-1 transition-transform" />
                  </Link>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
