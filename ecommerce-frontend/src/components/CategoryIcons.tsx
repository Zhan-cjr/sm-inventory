
import { useEcom } from '../context/EcomContext';
import { 
  Apple, Carrot, Beef, Milk, Coffee, 
  Baby, Home, Heart, PackageOpen, Tag 
} from 'lucide-react';

const CategoryIcons = () => {
  const { setSelectedCategory } = useEcom();

  // Alfagift-style icon categories mapping
  // We use standard names that can be mapped or matched to actual categories
  const categories = [
    { id: 'all', name: 'Semua Kategori', icon: <PackageOpen size={24} />, color: 'bg-slate-100 text-slate-600' },
    { id: 'promo', name: 'Promo Spesial', icon: <Tag size={24} />, color: 'bg-red-50 text-brand-red' },
    { id: 'sayur', name: 'Sayuran', icon: <Carrot size={24} />, color: 'bg-orange-50 text-orange-500' },
    { id: 'buah', name: 'Buah Segar', icon: <Apple size={24} />, color: 'bg-red-50 text-red-500' },
    { id: 'daging', name: 'Daging', icon: <Beef size={24} />, color: 'bg-rose-50 text-rose-600' },
    { id: 'susu', name: 'Susu & Telur', icon: <Milk size={24} />, color: 'bg-blue-50 text-blue-500' },
    { id: 'minuman', name: 'Minuman', icon: <Coffee size={24} />, color: 'bg-amber-50 text-amber-600' },
    { id: 'bayi', name: 'Kebutuhan Bayi', icon: <Baby size={24} />, color: 'bg-sky-50 text-sky-500' },
    { id: 'rumah', name: 'Perawatan Rumah', icon: <Home size={24} />, color: 'bg-teal-50 text-teal-500' },
    { id: 'kesehatan', name: 'Kesehatan', icon: <Heart size={24} />, color: 'bg-pink-50 text-pink-500' },
  ];

  const handleCategoryClick = (id: string, name: string) => {
    // If it's a predefined ID, set it. Otherwise, set the name so ProductGrid can filter it.
    // 'promo' is a special case we might handle in ProductGrid or just set 'promo'.
    setSelectedCategory(id === 'all' || id === 'promo' ? id : name);
    
    // Smooth scroll to catalog
    document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 sm:mt-8 mb-4">
      <div className="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-10 gap-x-2 gap-y-6 sm:gap-4">
        {categories.map((cat) => (
          <button
            key={cat.id}
            onClick={() => handleCategoryClick(cat.id, cat.name)}
            className="flex flex-col items-center justify-start gap-2 group cursor-pointer"
          >
            <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110 group-active:scale-95 shadow-sm border border-white/50 ${cat.color}`}>
              {cat.icon}
            </div>
            <span className="text-[10px] sm:text-xs text-center font-medium text-slate-700 leading-tight group-hover:text-brand-blue transition-colors max-w-[60px] sm:max-w-[70px]">
              {cat.name}
            </span>
          </button>
        ))}
      </div>
    </div>
  );
};

export default CategoryIcons;
