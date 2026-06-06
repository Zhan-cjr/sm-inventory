
import { useEcom } from '../context/EcomContext';
import { 
  Apple, Carrot, Beef, Milk, Coffee, 
  Baby, Home, Heart, PackageOpen, Tag,
  ShoppingBag, Shirt, MonitorSmartphone, Scissors
} from 'lucide-react';

const CategoryIcons = () => {
  const { setSelectedCategory, availableCategories } = useEcom();

  const getCategoryVisuals = (id: string, name: string) => {
    if (id === 'all') return { icon: <PackageOpen size={24} />, color: 'bg-slate-100 text-slate-600' };
    if (id === 'promo') return { icon: <Tag size={24} />, color: 'bg-red-50 text-brand-red' };
    
    const n = name.toLowerCase();
    if (n.includes('sayur')) return { icon: <Carrot size={24} />, color: 'bg-orange-50 text-orange-500' };
    if (n.includes('buah')) return { icon: <Apple size={24} />, color: 'bg-red-50 text-red-500' };
    if (n.includes('daging')) return { icon: <Beef size={24} />, color: 'bg-rose-50 text-rose-600' };
    if (n.includes('susu') || n.includes('telur')) return { icon: <Milk size={24} />, color: 'bg-blue-50 text-blue-500' };
    if (n.includes('minum')) return { icon: <Coffee size={24} />, color: 'bg-amber-50 text-amber-600' };
    if (n.includes('bayi') || n.includes('anak')) return { icon: <Baby size={24} />, color: 'bg-sky-50 text-sky-500' };
    if (n.includes('rumah') || n.includes('dapur')) return { icon: <Home size={24} />, color: 'bg-teal-50 text-teal-500' };
    if (n.includes('sehat') || n.includes('obat') || n.includes('herbal')) return { icon: <Heart size={24} />, color: 'bg-pink-50 text-pink-500' };
    if (n.includes('pakaian') || n.includes('baju')) return { icon: <Shirt size={24} />, color: 'bg-purple-50 text-purple-500' };
    if (n.includes('elektronik') || n.includes('gadget')) return { icon: <MonitorSmartphone size={24} />, color: 'bg-cyan-50 text-cyan-500' };
    if (n.includes('kecantikan') || n.includes('rambut')) return { icon: <Scissors size={24} />, color: 'bg-fuchsia-50 text-fuchsia-500' };
    
    return { icon: <ShoppingBag size={24} />, color: 'bg-slate-100 text-slate-600' };
  };

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
        {availableCategories.length === 0 ? (
          <div className="col-span-4 sm:col-span-5 md:col-span-10 text-center py-4 text-xs text-slate-400">
            Memuat kategori...
          </div>
        ) : (
          availableCategories.map((cat) => {
            const visual = getCategoryVisuals(cat.id, cat.name);
            return (
              <button
                key={cat.id}
                onClick={() => handleCategoryClick(cat.id, cat.name)}
                className="flex flex-col items-center justify-start gap-2 group cursor-pointer"
              >
                <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110 group-active:scale-95 shadow-sm border border-white/50 ${visual.color}`}>
                  {visual.icon}
                </div>
                <span className="text-[10px] sm:text-xs text-center font-medium text-slate-700 leading-tight group-hover:text-brand-blue transition-colors max-w-[60px] sm:max-w-[70px]">
                  {cat.name}
                </span>
              </button>
            );
          })
        )}
      </div>
    </div>
  );
};

export default CategoryIcons;
