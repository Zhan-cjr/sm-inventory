import { useRef, useState, useEffect } from 'react';
import { useEcom } from '../context/EcomContext';
import { ChevronRight, Zap, Sparkles, Star } from 'lucide-react';
import axios from 'axios';
import { getImageUrl } from '../utils/api';
import { ProductImage } from './ProductImage';

const DiscountedProductsCarousel = () => {
  const { selectedBranch, setSelectedProductForModal, setIsProductModalOpen } = useEcom();
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const fetchProducts = async () => {
      setLoading(true);
      try {
        const response = await axios.get('/ecommerce/products', {
          params: selectedBranch ? { branch_id: selectedBranch.id } : {},
        });
        const mapped = response.data.map((p: any) => ({
          ...p,
          image_url: getImageUrl(p.image_url),
        }));
        setProducts(mapped);
      } catch (error) {
        console.error('Error fetching products:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchProducts();
  }, [selectedBranch]);

  const discountedProducts = products.filter(
    p => p.is_promo || (p.original_price && parseFloat(p.original_price) > parseFloat(p.selling_price))
  );

  useEffect(() => {
    if (discountedProducts.length === 0) return;
    
    const interval = setInterval(() => {
      if (scrollRef.current) {
        const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
        if (scrollLeft + clientWidth >= scrollWidth - 10) {
          scrollRef.current.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
          // Scroll approximately one banner width
          scrollRef.current.scrollBy({ left: 300, behavior: 'smooth' });
        }
      }
    }, 3500);
    
    return () => clearInterval(interval);
  }, [discountedProducts]);

  if (loading) {
    return (
      <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div className="w-full h-32 bg-slate-200 rounded-2xl animate-pulse"></div>
      </div>
    );
  }

  if (discountedProducts.length === 0) return null;

  return (
    <div className="w-full bg-gradient-to-r from-brand-blue to-indigo-900 pb-6 pt-4 px-0 relative">
      <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mt-20 -mr-20 pointer-events-none" />
      
      <div className="flex items-center justify-between px-4 mb-3">
        <div className="flex items-center gap-2">
          <Zap className="fill-amber-400 text-amber-400" size={20} />
          <h2 className="text-white font-bold text-lg italic">PROMO SPESIAL</h2>
        </div>
        <div className="text-white text-xs font-medium opacity-80 flex items-center gap-1 cursor-pointer">
          Lihat Semua <ChevronRight size={14} />
        </div>
      </div>

      <div className="relative group">
        <div 
          ref={scrollRef}
          className="flex overflow-x-auto gap-3 snap-x snap-mandatory no-scrollbar pb-2 px-4"
        >
          {discountedProducts.map(product => {
            const price = parseFloat(product.selling_price) || 0;
            const originalPrice = product.original_price ? parseFloat(product.original_price) : null;
            
            return (
              <div 
                key={product.id} 
                className="w-[280px] sm:w-[320px] h-[120px] sm:h-[130px] flex-shrink-0 snap-center bg-white rounded-2xl overflow-hidden shadow-lg border border-slate-100 flex items-stretch cursor-pointer hover:shadow-xl transition-all"
                onClick={() => {
                  setSelectedProductForModal(product);
                  setIsProductModalOpen(true);
                }}
              >
                <div className="w-[45%] relative bg-slate-50 flex-shrink-0">
                  <ProductImage src={product.image_url} alt={product.name} className="w-full h-full object-cover" />
                  <div className="absolute top-2 left-2 bg-brand-red text-white text-[10px] font-bold px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-1">
                    <Sparkles size={10} /> Promo
                  </div>
                </div>
                <div className="w-[55%] p-3 flex flex-col justify-between">
                  <div>
                    <h3 className="font-bold text-slate-800 text-xs sm:text-sm line-clamp-2 leading-tight mb-1">{product.name}</h3>
                    <div className="flex items-center gap-1 text-[10px] text-slate-500">
                      <Star className="text-yellow-400 fill-yellow-400 w-3 h-3" />
                      <span>{product.rating || 5.0}</span>
                    </div>
                  </div>
                  <div>
                    {originalPrice && (
                      <div className="text-[10px] text-slate-400 line-through">
                        Rp {originalPrice.toLocaleString('id-ID')}
                      </div>
                    )}
                    <div className="font-bold text-sm sm:text-base text-brand-red">
                      Rp {price.toLocaleString('id-ID')}
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default DiscountedProductsCarousel;
