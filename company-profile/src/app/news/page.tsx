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
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[800px] h-[800px] bg-primary/20 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-accent/20 rounded-full blur-[100px] pointer-events-none" />

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
          <motion.div variants={fadeIn} className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white text-accent text-xs sm:text-sm font-bold tracking-widest uppercase mb-6 border border-accent/30 shadow-sm">
            <Newspaper size={16} /> Berita & Pembaruan
          </motion.div>
          <motion.h1 variants={fadeIn} className="text-4xl sm:text-5xl md:text-7xl font-extrabold text-slate-900 mb-6 tracking-tight">
            Kabar <span className="bg-gradient-to-r from-primary to-accent text-gradient">Terbaru</span>
          </motion.h1>
          <motion.p variants={fadeIn} className="text-base sm:text-lg md:text-xl text-slate-600 font-medium max-w-3xl mx-auto leading-relaxed px-2">
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
              className={`px-6 py-2.5 rounded-full font-bold text-sm transition-all border ${
                filter === type 
                ? 'bg-primary border-primary text-white shadow-md shadow-primary/30' 
                : 'bg-white text-slate-600 border-slate-200 hover:text-primary hover:border-primary/30 hover:shadow-sm'
              }`}
            >
              {type}
            </button>
          ))}
        </motion.div>

        {/* Content */}
        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
          </div>
        ) : filteredArticles.length === 0 ? (
          <div className="text-center py-20 bg-white rounded-3xl border border-slate-200 shadow-sm">
            <Newspaper size={48} className="mx-auto text-slate-400 mb-4" />
            <h3 className="text-xl font-bold text-slate-900 mb-2">Belum Ada Artikel</h3>
            <p className="text-slate-600 font-medium">Belum ada publikasi berita atau promo saat ini. Silakan kembali lagi nanti.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {filteredArticles.map((article, i) => (
              <motion.div 
                key={article.id}
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: i * 0.1 }}
                className="bg-white shadow-md hover:shadow-xl rounded-3xl border border-slate-200 overflow-hidden group hover:border-primary/30 transition-all flex flex-col"
              >
                <div className="relative h-56 overflow-hidden bg-slate-100">
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
                      article.type === 'Promo' ? 'bg-secondary text-white' : 
                      article.type === 'Kegiatan' ? 'bg-accent text-white' : 
                      'bg-primary text-white'
                    }`}>
                      {article.type}
                    </span>
                  </div>
                </div>
                
                <div className="p-6 flex flex-col flex-grow">
                  <div className="flex items-center gap-4 text-slate-500 text-sm font-medium mb-4">
                    <div className="flex items-center gap-1.5">
                      <Calendar size={14} />
                      <span>{new Date(article.published_at || article.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</span>
                    </div>
                  </div>
                  
                  <h3 className="text-xl font-bold text-slate-900 mb-3 group-hover:text-primary transition-colors line-clamp-2">
                    {article.title}
                  </h3>
                  
                  <div className="text-slate-600 text-sm font-medium leading-relaxed mb-6 line-clamp-3" dangerouslySetInnerHTML={{ __html: article.content }}></div>
                  
                  <div className="mt-auto pt-4 border-t border-slate-100">
                    <Link href={`/news/detail?slug=${article.slug}`} className="text-secondary font-bold hover:text-secondary/80 flex items-center gap-2 group/link text-sm mt-auto">
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
