import { useRef, useState, useEffect } from 'react';
import { useEcom } from '../context/EcomContext';
import { ProductCard } from './ProductGrid';
import { ChevronLeft, ChevronRight, Timer, Zap } from 'lucide-react';
import axios from 'axios';
import { getImageUrl } from '../utils/api';

const DiscountedProductsCarousel = () => {
  const { selectedBranch } = useEcom();
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(true);

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
    if (scrollRef.current) {
      const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
      setCanScrollLeft(scrollLeft > 0);
      setCanScrollRight(Math.ceil(scrollLeft + clientWidth) < scrollWidth);
    }
  };

  useEffect(() => {
    handleScroll();
    window.addEventListener('resize', handleScroll);
    return () => window.removeEventListener('resize', handleScroll);
  }, [discountedProducts]);

  const scroll = (direction: 'left' | 'right') => {
    if (scrollRef.current) {
      const clientWidth = scrollRef.current.clientWidth;
      const scrollAmount = direction === 'left' ? -clientWidth / 2 : clientWidth / 2;
      scrollRef.current.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
  };

  if (loading) {
    return (
      <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div className="w-full h-64 bg-slate-200 rounded-2xl animate-pulse"></div>
      </div>
    );
  }

  if (discountedProducts.length === 0) return null;

  return (
    <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 sm:mt-8">
      <div className="bg-gradient-to-br from-brand-red to-rose-600 rounded-3xl p-4 sm:p-6 shadow-xl relative overflow-hidden">
        {/* Background Accents */}
        <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mt-20 -mr-20 pointer-events-none" />
        <div className="absolute bottom-0 left-0 w-40 h-40 bg-black/10 rounded-full blur-2xl -mb-10 -ml-10 pointer-events-none" />

        <div className="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
          <div className="flex items-center gap-3">
            <div className="bg-white text-brand-red p-2 rounded-xl shadow-sm">
              <Zap className="fill-brand-red stroke-none animate-pulse" size={24} />
            </div>
            <div>
              <h2 className="text-xl sm:text-2xl font-bold text-white tracking-wide">
                Flash Sale Spesial
              </h2>
              <p className="text-red-100 text-xs sm:text-sm mt-0.5">
                Diskon terbatas, jangan sampai kehabisan!
              </p>
            </div>
          </div>
          
          <div className="flex items-center gap-2 bg-black/20 backdrop-blur-md px-4 py-2 rounded-full border border-white/10">
            <Timer className="text-white" size={16} />
            <span className="text-white font-mono font-bold text-sm">BERAKHIR HARI INI</span>
          </div>
        </div>

        <div className="relative group">
          <div 
            ref={scrollRef}
            onScroll={handleScroll}
            className="flex overflow-x-auto gap-4 snap-x snap-mandatory hide-scrollbar pb-4 -mx-2 px-2"
            style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
          >
            {discountedProducts.map(product => (
              <div key={product.id} className="min-w-[160px] sm:min-w-[200px] md:min-w-[240px] max-w-[280px] flex-shrink-0 snap-start">
                <ProductCard product={product} />
              </div>
            ))}
          </div>

          {/* Navigation Buttons */}
          {canScrollLeft && (
            <button 
              onClick={() => scroll('left')}
              className="absolute left-[-10px] top-1/2 -translate-y-1/2 w-10 h-10 bg-white shadow-xl rounded-full flex items-center justify-center text-slate-800 opacity-0 group-hover:opacity-100 transition-opacity z-20 hover:scale-110 hidden sm:flex"
            >
              <ChevronLeft size={20} />
            </button>
          )}
          {canScrollRight && (
            <button 
              onClick={() => scroll('right')}
              className="absolute right-[-10px] top-1/2 -translate-y-1/2 w-10 h-10 bg-white shadow-xl rounded-full flex items-center justify-center text-slate-800 opacity-0 group-hover:opacity-100 transition-opacity z-20 hover:scale-110 hidden sm:flex"
            >
              <ChevronRight size={20} />
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default DiscountedProductsCarousel;
