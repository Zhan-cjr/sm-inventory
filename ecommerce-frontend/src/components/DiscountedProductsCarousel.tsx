import { useRef, useState, useEffect } from 'react';
import { useEcom } from '../context/EcomContext';
import { ProductCard } from './ProductGrid';
import { ChevronRight, Zap } from 'lucide-react';
import axios from 'axios';
import { getImageUrl } from '../utils/api';

const DiscountedProductsCarousel = () => {
  const { selectedBranch } = useEcom();
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

  // Filter only products that are promo or have a discount
  const discountedProducts = products.filter(
    p => p.is_promo || (p.original_price && parseFloat(p.original_price) > parseFloat(p.selling_price))
  );

  const handleScroll = () => {
    // Keep reference for future scroll snapping/lazy loading logic if needed
  };

  useEffect(() => {
    handleScroll();
    window.addEventListener('resize', handleScroll);
    return () => window.removeEventListener('resize', handleScroll);
  }, [discountedProducts]);



  if (loading) {
    return (
      <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div className="w-full h-64 bg-slate-200 rounded-2xl animate-pulse"></div>
      </div>
    );
  }

  if (discountedProducts.length === 0) return null;

  return (
    <div className="w-full bg-gradient-to-r from-brand-blue to-indigo-900 pb-10 pt-4 px-0 relative">
      <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mt-20 -mr-20 pointer-events-none" />
      
      <div className="flex items-center justify-between px-4 mb-3">
        <div className="flex items-center gap-2">
          <Zap className="fill-amber-400 text-amber-400" size={20} />
          <h2 className="text-white font-bold text-lg italic">PROMO SPESIAL</h2>
        </div>
        <div className="text-white text-xs font-medium opacity-80 flex items-center gap-1">
          Lihat Semua <ChevronRight size={14} />
        </div>
      </div>

      <div className="relative group">
        <div 
          ref={scrollRef}
          onScroll={handleScroll}
          className="flex overflow-x-auto gap-3 snap-x snap-mandatory no-scrollbar pb-2 px-4"
        >
          {discountedProducts.map(product => (
            <div key={product.id} className="w-[140px] sm:w-[160px] md:w-[200px] flex-shrink-0 snap-start bg-white rounded-xl overflow-hidden shadow-md">
              <ProductCard product={product} />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default DiscountedProductsCarousel;
