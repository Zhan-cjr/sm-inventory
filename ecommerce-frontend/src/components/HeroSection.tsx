import { useState, useEffect } from 'react';
import { ArrowRight, ShoppingBag, ShieldCheck, ThumbsUp, MapPin } from 'lucide-react';
import axios from 'axios';
import { getImageUrl } from '../utils/api';

const HeroSection = () => {
  const [settings, setSettings] = useState<{
    logo_url: string | null;
    ecommerce_banner_title: string;
    ecommerce_banner_subtitle: string;
    ecommerce_banner_image_url: string | null;
    ecommerce_banner_cta_text: string;
  }>({
    logo_url: null,
    ecommerce_banner_title: 'Belanja Untung, Murah, Manfaat',
    ecommerce_banner_subtitle: 'Dan InsyaAllah Berkah. Temukan berbagai kebutuhan keluarga muslim dengan harga terbaik dari cabang Toserba Selamat terdekat Anda.',
    ecommerce_banner_image_url: null,
    ecommerce_banner_cta_text: 'Mulai Belanja',
  });

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await axios.get('/ecommerce/settings');
        setSettings({
          logo_url: getImageUrl(response.data.logo_url) || null,
          ecommerce_banner_title: response.data.ecommerce_banner_title || 'Belanja Untung, Murah, Manfaat',
          ecommerce_banner_subtitle: response.data.ecommerce_banner_subtitle || 'Dan InsyaAllah Berkah. Temukan berbagai kebutuhan keluarga muslim dengan harga terbaik dari cabang Toserba Selamat terdekat Anda.',
          ecommerce_banner_image_url: getImageUrl(response.data.ecommerce_banner_image_url) || null,
          ecommerce_banner_cta_text: response.data.ecommerce_banner_cta_text || 'Mulai Belanja',
        });
      } catch (error) {
        console.error('Error fetching settings in Hero:', error);
      }
    };
    fetchSettings();
  }, []);

  const scrollToCatalog = () => {
    document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <div className="relative overflow-hidden bg-white">
      {/* Background decoration */}
      <div className="absolute top-0 right-0 -mr-20 -mt-20 w-[500px] h-[500px] bg-brand-green/10 rounded-full blur-3xl" />
      <div className="absolute bottom-0 left-0 -ml-20 -mb-20 w-[400px] h-[400px] bg-brand-blue/5 rounded-full blur-3xl" />
 
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-24 relative z-10">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          
          {/* Text Content */}
          <div className="flex flex-col gap-6">
            <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-green/10 text-brand-green font-semibold text-sm w-fit border border-brand-green/20">
              <span className="relative flex h-2 w-2">
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-green opacity-75"></span>
                <span className="relative inline-flex rounded-full h-2 w-2 bg-brand-green"></span>
              </span>
              Pasar Ummat
            </div>
            
            <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-brand-red leading-tight">
              {settings.ecommerce_banner_title}
            </h1>
            
            <p className="text-sm sm:text-base md:text-lg text-slate-600 max-w-lg leading-relaxed whitespace-pre-line">
              {(() => {
                const subtitle = settings.ecommerce_banner_subtitle;
                const target = "Dan InsyaAllah Berkah.";
                if (subtitle.toLowerCase().startsWith(target.toLowerCase())) {
                  const remaining = subtitle.substring(target.length);
                  return (
                    <>
                      <strong className="font-bold text-slate-800">{subtitle.substring(0, target.length)}</strong>
                      {remaining}
                    </>
                  );
                }
                const firstDotIndex = subtitle.indexOf('.');
                if (firstDotIndex !== -1) {
                  return (
                    <>
                      <strong className="font-bold text-slate-800">{subtitle.substring(0, firstDotIndex + 1)}</strong>
                      {subtitle.substring(firstDotIndex + 1)}
                    </>
                  );
                }
                return subtitle;
              })()}
            </p>
            
            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 mt-4">
              <button 
                onClick={scrollToCatalog}
                className="flex items-center justify-center gap-2 bg-brand-blue text-white px-8 py-3.5 rounded-xl font-semibold hover:bg-brand-blue/90 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 text-sm sm:text-base"
              >
                <ShoppingBag size={20} />
                {settings.ecommerce_banner_cta_text}
              </button>
              <button 
                onClick={scrollToCatalog}
                className="flex items-center justify-center gap-2 bg-white text-brand-blue border border-brand-blue/20 px-8 py-3.5 rounded-xl font-semibold hover:bg-slate-50 transition-colors text-sm sm:text-base"
              >
                Lihat Promo
                <ArrowRight size={20} />
              </button>
            </div>

            {/* Feature Highlights */}
            <div className="flex items-center gap-4 sm:gap-8 mt-8 pt-8 border-t border-slate-100">
              <div className="flex flex-col gap-0.5">
                <div className="flex items-center gap-1.5 text-slate-800 font-semibold text-sm sm:text-base">
                  <ShieldCheck className="text-brand-green" size={18} />
                  100% Halal
                </div>
                <span className="text-xs text-slate-500">Terjamin kualitasnya</span>
              </div>
              <div className="flex flex-col gap-0.5">
                <div className="flex items-center gap-1.5 text-slate-800 font-semibold text-sm sm:text-base">
                  <ThumbsUp className="text-brand-red" size={18} />
                  Harga Jujur
                </div>
                <span className="text-xs text-slate-500">Lebih hemat & murah</span>
              </div>
            </div>
          </div>

          {/* Image/Visual Content */}
          <div className="relative mt-8 lg:mt-0">
            <div className="absolute inset-0 bg-gradient-to-tr from-brand-blue/20 to-brand-green/20 rounded-[2rem] lg:rounded-[2.5rem] transform rotate-3 scale-105" />
            <div className="relative h-[220px] sm:h-[320px] lg:h-[500px] w-full bg-slate-100 rounded-[2rem] lg:rounded-[2.5rem] overflow-hidden shadow-xl lg:shadow-2xl border border-white/50">
              <div className="absolute inset-0 flex items-center justify-center bg-slate-50">
                {settings.ecommerce_banner_image_url ? (
                  <img src={settings.ecommerce_banner_image_url} alt="Banner" className="w-full h-full object-cover" />
                ) : settings.logo_url ? (
                  <img src={settings.logo_url} alt="Logo" className="max-h-[140px] sm:max-h-[220px] lg:max-h-[300px] w-auto object-contain p-6 sm:p-8" />
                ) : (
                  <div className="text-center p-8">
                    <div className="w-16 h-16 sm:w-24 sm:h-24 bg-brand-red rounded-xl lg:rounded-2xl mx-auto flex items-center justify-center text-white text-3xl sm:text-5xl font-bold mb-4 shadow-lg">S</div>
                    <h3 className="text-lg sm:text-2xl font-bold text-brand-blue">Toserba Selamat</h3>
                    <p className="text-xs sm:text-brand-green font-semibold">The Moslem Family</p>
                  </div>
                )}
              </div>
            </div>
            
            {/* Floating Badge */}
            <div className="absolute -bottom-4 -left-4 sm:-bottom-6 sm:-left-6 bg-white p-3 sm:p-4 rounded-xl sm:rounded-2xl shadow-xl border border-slate-100 flex items-center gap-3 sm:gap-4 animate-bounce" style={{animationDuration: '3s'}}>
              <div className="w-10 h-10 sm:w-12 sm:h-12 bg-brand-green/10 rounded-full flex items-center justify-center text-brand-green">
                <MapPin size={20} className="sm:size-6" />
              </div>
              <div>
                <div className="text-[10px] sm:text-sm text-slate-500 font-medium">Bisa Diambil Di</div>
                <div className="text-xs sm:text-base text-slate-800 font-bold">Cabang Terdekat</div>
              </div>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  );
};

export default HeroSection;
