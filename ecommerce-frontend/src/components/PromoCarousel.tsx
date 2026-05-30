import { useState, useEffect, useRef } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import axios from 'axios';


const PromoCarousel = () => {
  const [banners, setBanners] = useState<any[]>([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [loading, setLoading] = useState(true);
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    // For now, we fetch from ecommerce/settings for the single banner, 
    // but we can mock a few or use it if available.
    // If backend only has one banner, we will duplicate it for demo purposes 
    // to show the carousel effect until backend supports multiple banners.
    const fetchBanners = async () => {
      try {
        const response = await axios.get('/ecommerce/settings');
        const urls = response.data.ecommerce_banner_images_urls;
        
        if (urls && Array.isArray(urls) && urls.length > 0) {
          const loadedBanners = urls.map((url, idx) => ({
            id: idx + 1,
            image_url: url,
            title: '' // We can just omit title if we have the actual images
          }));
          setBanners(loadedBanners);
        } else {
          // Default mock banners if backend hasn't configured them
          // Rekomendasi dimensi: 1200x400px atau 1200x300px
          setBanners([
            { id: 1, image_url: null, title: 'Promo Belanja Murah & Berkah' },
            { id: 2, image_url: null, title: 'Gratis Ongkir Ambil di Cabang' },
            { id: 3, image_url: null, title: 'Daftar Member Dapat Poin' },
          ]);
        }
      } catch (error) {
        console.error('Error fetching banners:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchBanners();
  }, []);

  useEffect(() => {
    if (banners.length <= 1) return;
    const interval = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % banners.length);
    }, 4000);
    return () => clearInterval(interval);
  }, [banners.length]);

  useEffect(() => {
    if (scrollRef.current && banners.length > 0) {
      const scrollWidth = scrollRef.current.clientWidth;
      scrollRef.current.scrollTo({
        left: currentIndex * scrollWidth,
        behavior: 'smooth'
      });
    }
  }, [currentIndex, banners.length]);

  const handleNext = () => {
    setCurrentIndex((prev) => (prev + 1) % banners.length);
  };

  const handlePrev = () => {
    setCurrentIndex((prev) => (prev - 1 + banners.length) % banners.length);
  };

  if (loading) {
    return (
      <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 animate-pulse">
        <div className="w-full h-40 sm:h-60 md:h-80 bg-slate-200 rounded-2xl"></div>
      </div>
    );
  }

  if (banners.length === 0) return null;

  return (
    <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 sm:mt-6 relative group">
      <div 
        ref={scrollRef}
        className="w-full flex overflow-x-hidden snap-x snap-mandatory rounded-2xl shadow-sm"
      >
        {banners.map((banner) => (
          <div 
            key={banner.id} 
            className="w-full flex-shrink-0 snap-center relative"
          >
            <div className="aspect-[21/9] sm:aspect-[24/7] w-full bg-slate-100 relative overflow-hidden">
              {banner.image_url ? (
                <img 
                  src={banner.image_url} 
                  alt={banner.title || 'Promo Banner'} 
                  className="w-full h-full object-cover object-center"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center bg-gradient-to-r from-brand-blue to-indigo-900 text-white">
                  <h2 className="text-xl sm:text-3xl font-bold">{banner.title}</h2>
                </div>
              )}
              {/* Optional Gradient Overlay for text if needed */}
              <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 sm:opacity-100 flex items-end">
                <div className="p-6">
                  <h3 className="text-white font-bold text-lg sm:text-2xl drop-shadow-md">{banner.title}</h3>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Navigation Buttons (Hidden on mobile, show on hover on desktop) */}
      {banners.length > 1 && (
        <>
          <button 
            onClick={handlePrev}
            className="absolute left-6 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 backdrop-blur-sm rounded-full flex items-center justify-center text-slate-800 shadow-md opacity-0 group-hover:opacity-100 transition-opacity disabled:opacity-0 hidden sm:flex hover:bg-white"
          >
            <ChevronLeft size={24} />
          </button>
          <button 
            onClick={handleNext}
            className="absolute right-6 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 backdrop-blur-sm rounded-full flex items-center justify-center text-slate-800 shadow-md opacity-0 group-hover:opacity-100 transition-opacity disabled:opacity-0 hidden sm:flex hover:bg-white"
          >
            <ChevronRight size={24} />
          </button>
        </>
      )}

      {/* Indicators */}
      {banners.length > 1 && (
        <div className="absolute bottom-3 sm:bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-1.5 sm:gap-2">
          {banners.map((_, idx) => (
            <button
              key={idx}
              onClick={() => setCurrentIndex(idx)}
              className={`transition-all duration-300 rounded-full ${
                currentIndex === idx 
                  ? 'w-4 sm:w-6 h-1.5 sm:h-2 bg-brand-red' 
                  : 'w-1.5 sm:w-2 h-1.5 sm:h-2 bg-white/60 hover:bg-white/90'
              }`}
            />
          ))}
        </div>
      )}
    </div>
  );
};

export default PromoCarousel;
