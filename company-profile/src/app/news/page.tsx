"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Calendar, Tag, ArrowRight, Newspaper, Search, Share2, X, Clock } from "lucide-react";

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

const defaultArticles: Article[] = [
  {
    id: 101,
    title: "Promo Berkah Akhir Bulan - Diskon Spesial Hingga 50% Untuk Member",
    slug: "promo-berkah-akhir-bulan",
    type: "Promo",
    content: "Nikmati potongan harga spesial untuk produk bahan segar, kebutuhan dapur, susu anak, dan perlengkapan rumah tangga di seluruh 26+ cabang Toserba Selamat.",
    image_url: "https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=2574&auto=format&fit=crop",
    published_at: "2026-07-20",
    created_at: "2026-07-20"
  },
  {
    id: 102,
    title: "Grand Opening Cabang Baru Toserba Selamat Garut Superstore",
    slug: "grand-opening-garut",
    type: "Berita",
    content: "Resmi dibuka! Cabang ke-27 Toserba Selamat kini hadir di Garut dengan fasilitas supermarket syariah lengkap, Fitness Center, dan area Foodcourt keluarga.",
    image_url: "https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=2574&auto=format&fit=crop",
    published_at: "2026-07-15",
    created_at: "2026-07-15"
  },
  {
    id: 103,
    title: "Program Bakti Sosial Berkah Ramadhan & Santunan Anak Yatim",
    slug: "santunan-anak-yatim-2026",
    type: "Kegiatan",
    content: "Sebagai komitmen kepedulian sosial corporate syariah, Toserba Selamat telah menyalurkan 5.000 paket sembako gratis bagi masyarakat membutuhkan.",
    image_url: "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=2574&auto=format&fit=crop",
    published_at: "2026-07-01",
    created_at: "2026-07-01"
  }
];

export default function NewsPage() {
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState("Semua");
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedArticle, setSelectedArticle] = useState<Article | null>(null);

  useEffect(() => {
    fetch(`${process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id/api/company-profile'}/articles`)
      .then(res => {
        if (!res.ok) throw new Error("Failed to fetch articles");
        return res.json();
      })
      .then(data => {
        if (Array.isArray(data) && data.length > 0) {
          setArticles(data);
        } else {
          setArticles(defaultArticles);
        }
        setLoading(false);
      })
      .catch(() => {
        setArticles(defaultArticles);
        setLoading(false);
      });
  }, []);

  const filteredArticles = articles.filter(a => {
    const matchCategory = filter === "Semua" || a.type.toLowerCase() === filter.toLowerCase();
    const matchSearch = a.title.toLowerCase().includes(searchQuery.toLowerCase()) || a.content.toLowerCase().includes(searchQuery.toLowerCase());
    return matchCategory && matchSearch;
  });

  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[700px] h-[700px] bg-primary/10 rounded-full blur-[140px] pointer-events-none" />

      <div className="container mx-auto px-4 md:px-8 max-w-7xl relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-14 space-y-4">
          <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent/20 text-slate-800 text-xs font-bold uppercase tracking-widest">
            <Newspaper size={15} className="text-primary" /> Kabar &amp; Promo Resmi
          </span>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black text-slate-900 tracking-tight">
            Berita &amp; <span className="bg-gradient-to-r from-primary via-primary-light to-secondary text-gradient">Promo Terbaru</span>
          </h1>
          <p className="text-slate-600 font-medium text-base sm:text-lg leading-relaxed">
            Dapatkan informasi terupdate mengenai diskon mingguan member, event pembukaan cabang baru, dan wawasan syariah keluarga.
          </p>

          {/* Search & Filter Bar */}
          <div className="pt-4 space-y-4 max-w-xl mx-auto">
            <div className="relative">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
              <input
                type="text"
                placeholder="Cari judul berita atau promo..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 shadow-md focus:ring-2 focus:ring-primary outline-none text-xs font-medium bg-white"
              />
            </div>

            <div className="flex flex-wrap items-center justify-center gap-2">
              {['Semua', 'Promo', 'Berita', 'Kegiatan'].map((cat) => (
                <button
                  key={cat}
                  onClick={() => setFilter(cat)}
                  className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                    filter === cat
                      ? "bg-primary text-white shadow-md shadow-primary/20"
                      : "bg-white text-slate-600 border border-slate-200 hover:bg-slate-100"
                  }`}
                >
                  {cat}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Article Cards Grid */}
        {loading ? (
          <div className="flex justify-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-4 border-primary border-t-transparent" />
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {filteredArticles.map((article) => {
              const origin = typeof window !== 'undefined' ? window.location.origin : 'https://admin.toserbaselamat.id';
              const imgUrl = article.image_url
                ? (article.image_url.startsWith('http') ? article.image_url : `${origin}/storage/${article.image_url}`)
                : "https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=2574&auto=format&fit=crop";

              return (
                <motion.div
                  key={article.id}
                  initial={{ opacity: 0, y: 30 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5 }}
                  className="bg-white rounded-3xl border border-slate-200/80 shadow-md hover:shadow-2xl hover:border-primary/30 transition-all overflow-hidden flex flex-col justify-between group"
                >
                  <div>
                    <div className="relative h-52 w-full overflow-hidden bg-slate-100">
                      <img
                        src={imgUrl}
                        alt={article.title}
                        className="w-full h-full object-cover group-hover:scale-108 transition-transform duration-700"
                      />
                      <div className="absolute top-4 left-4">
                        <span className="px-3 py-1 bg-primary text-white rounded-full text-[10px] font-extrabold uppercase tracking-wider shadow-sm">
                          {article.type}
                        </span>
                      </div>
                    </div>

                    <div className="p-6 space-y-3">
                      <div className="flex items-center gap-3 text-slate-400 text-xs font-semibold">
                        <span className="flex items-center gap-1"><Calendar size={13} /> {new Date(article.published_at || article.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                        <span>•</span>
                        <span className="flex items-center gap-1"><Clock size={13} /> 3 Menit Baca</span>
                      </div>

                      <h3 className="text-lg font-extrabold text-slate-900 leading-snug group-hover:text-primary transition-colors line-clamp-2">
                        {article.title}
                      </h3>

                      <p className="text-slate-600 text-xs font-medium leading-relaxed line-clamp-3">
                        {article.content}
                      </p>
                    </div>
                  </div>

                  <div className="p-6 pt-0">
                    <button
                      onClick={() => setSelectedArticle(article)}
                      className="w-full py-3 bg-slate-100 hover:bg-primary hover:text-white text-slate-800 font-bold text-xs rounded-2xl transition-colors flex items-center justify-center gap-2"
                    >
                      <span>Baca Artikel Lengkap</span>
                      <ArrowRight size={15} />
                    </button>
                  </div>
                </motion.div>
              );
            })}
          </div>
        )}

      </div>

      {/* Article Detail Modal */}
      <AnimatePresence>
        {selectedArticle && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.95, opacity: 0 }} className="bg-white rounded-3xl max-w-2xl w-full p-8 shadow-2xl relative border border-slate-200 max-h-[85vh] overflow-y-auto custom-scrollbar">
              <button onClick={() => setSelectedArticle(null)} className="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
                <X size={18} />
              </button>

              <span className="px-3 py-1 rounded-full bg-primary/10 text-primary font-bold text-[10px] uppercase tracking-wider inline-block mb-3">
                {selectedArticle.type}
              </span>

              <h2 className="text-2xl font-black text-slate-900 mb-3">{selectedArticle.title}</h2>
              <p className="text-xs text-slate-400 font-semibold mb-6 flex items-center gap-2">
                <Calendar size={14} /> Dipublikasikan {new Date(selectedArticle.published_at || selectedArticle.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
              </p>

              <div className="space-y-4 text-slate-700 text-sm font-medium leading-relaxed mb-8">
                <p>{selectedArticle.content}</p>
                <p>Kunjungi cabang Toserba Selamat terdekat di kota Anda atau gunakan fitur Belanja Online untuk mendapatkan keuntungannya sekarang juga!</p>
              </div>

              <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
                <button onClick={() => setSelectedArticle(null)} className="py-2.5 px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                  Tutup
                </button>
                <a href="http://shopping.toserbaselamat.id" target="_blank" rel="noreferrer" className="py-2.5 px-6 bg-secondary text-white font-bold text-xs rounded-xl shadow-md flex items-center gap-2">
                  Lihat Promo Belanja Online <ArrowRight size={14} />
                </a>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
