"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { ArrowLeft, Calendar, Tag, ChevronRight } from "lucide-react";
import Link from "next/link";
import { Navbar } from "@/components/layout/Navbar";
import { Footer } from "@/components/layout/Footer";
import { motion } from "framer-motion";

interface Article {
  id: number;
  title: string;
  slug: string;
  type: string;
  content: string;
  image_url: string | null;
  published_at: string;
}

export default function ArticleDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [article, setArticle] = useState<Article | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    // We fetch all articles and find the matching slug.
    // In a real app with a proper API, we would fetch by slug directly: /api/company-profile/articles/{slug}
    fetch(`${process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id/api/company-profile'}/articles`)
      .then(res => {
        if (!res.ok) throw new Error("Failed to fetch");
        return res.json();
      })
      .then((data: any) => {
        if (Array.isArray(data)) {
          const found = data.find(a => a.slug === params.slug);
          if (found) {
            setArticle(found);
          } else {
            setError(true);
          }
        } else {
          setError(true);
        }
        setIsLoading(false);
      })
      .catch(err => {
        console.error(err);
        setError(true);
        setIsLoading(false);
      });
  }, [params.slug]);

  if (isLoading) {
    return (
      <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col">
        <Navbar />
        <div className="flex-grow flex items-center justify-center pt-24">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
        </div>
        <Footer />
      </div>
    );
  }

  if (error || !article) {
    return (
      <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col">
        <Navbar />
        <div className="flex-grow flex flex-col items-center justify-center pt-24 text-center px-4">
          <h1 className="text-4xl font-bold mb-4">Artikel Tidak Ditemukan</h1>
          <p className="text-slate-600 font-medium mb-8">Maaf, berita atau artikel yang Anda cari tidak dapat ditemukan atau mungkin telah dihapus.</p>
          <button 
            onClick={() => router.push('/news')}
            className="flex items-center gap-2 bg-secondary hover:bg-secondary/90 text-white font-bold px-6 py-3 rounded-full transition-colors"
          >
            <ArrowLeft size={18} />
            Kembali ke Halaman Berita
          </button>
        </div>
        <Footer />
      </div>
    );
  }

  // Format date
  const dateObj = new Date(article.published_at);
  const formattedDate = dateObj.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });

  return (
    <div className="min-h-screen bg-slate-50 text-slate-800 selection:bg-secondary/20">
      <Navbar />

      <main className="pt-24 pb-20">
        <div className="container mx-auto px-4 md:px-6">
          {/* Breadcrumbs */}
          <div className="flex items-center gap-2 text-sm text-slate-500 font-medium mb-8 pt-4">
            <Link href="/" className="hover:text-primary transition-colors">Beranda</Link>
            <ChevronRight size={14} />
            <Link href="/news" className="hover:text-primary transition-colors">Berita</Link>
            <ChevronRight size={14} />
            <span className="text-secondary font-bold truncate max-w-[200px] md:max-w-xs">{article.title}</span>
          </div>

          <div className="max-w-4xl mx-auto">
            {/* Header */}
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="mb-8"
            >
            <div className="flex flex-wrap items-center gap-4 mb-4">
                <span className="bg-secondary/10 text-secondary px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border border-secondary/20 flex items-center gap-1.5">
                  <Tag size={12} />
                  {article.type}
                </span>
                <span className="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                  <Calendar size={14} />
                  {formattedDate}
                </span>
              </div>
              
              <h1 className="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight mb-8">
                {article.title}
              </h1>
            </motion.div>

            {/* Hero Image */}
            {article.image_url && (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.1 }}
                className="w-full aspect-video md:aspect-[21/9] rounded-2xl overflow-hidden mb-12 shadow-xl shadow-slate-300/50 border border-slate-200"
              >
                <img 
                  src={`${new URL(process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id').origin}/storage/${article.image_url}`} 
                  alt={article.title}
                  className="w-full h-full object-cover"
                />
              </motion.div>
            )}

            {/* Content Body */}
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.2 }}
              className="prose prose-lg max-w-none prose-a:text-secondary prose-a:font-bold prose-a:no-underline hover:prose-a:text-secondary/80 prose-img:rounded-xl prose-headings:text-slate-900 prose-p:text-slate-600 prose-li:text-slate-600"
              dangerouslySetInnerHTML={{ __html: article.content }}
            />

            {/* Footer actions */}
            <div className="mt-16 pt-8 border-t border-slate-200 flex justify-between items-center">
              <button 
                onClick={() => router.push('/news')}
                className="flex items-center gap-2 text-slate-500 font-bold hover:text-primary transition-colors"
              >
                <ArrowLeft size={18} />
                Kembali ke Berita
              </button>
            </div>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
