
import { useEcom } from '../context/EcomContext';
import { 
  Apple, Carrot, Beef, Milk, Coffee, 
  Baby, Home, Heart, PackageOpen, Tag,
  ShoppingBag, Shirt, MonitorSmartphone, Scissors, Utensils,
  Croissant, Fish, IceCream, SprayCan, Pencil, Gamepad2, Bath, Flame
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
    if (n.includes('ikan') || n.includes('seafood')) return { icon: <Fish size={24} />, color: 'bg-cyan-50 text-cyan-600' };
    if (n.includes('susu') || n.includes('telur') || n.includes('keju')) return { icon: <Milk size={24} />, color: 'bg-blue-50 text-blue-500' };
    if (n.includes('roti') || n.includes('kue') || n.includes('bakery')) return { icon: <Croissant size={24} />, color: 'bg-amber-50 text-amber-700' };
    if (n.includes('es krim') || n.includes('beku') || n.includes('frozen')) return { icon: <IceCream size={24} />, color: 'bg-sky-50 text-sky-400' };
    if (n.includes('minum')) return { icon: <Coffee size={24} />, color: 'bg-amber-50 text-amber-600' };
    if (n.includes('bayi') || n.includes('anak')) return { icon: <Baby size={24} />, color: 'bg-sky-50 text-sky-500' };
    if (n.includes('bersih') || n.includes('cuci') || n.includes('deterjen')) return { icon: <SprayCan size={24} />, color: 'bg-teal-50 text-teal-500' };
    if (n.includes('mandi') || n.includes('sabun') || n.includes('shampo')) return { icon: <Bath size={24} />, color: 'bg-cyan-50 text-cyan-500' };
    if (n.includes('rumah') || n.includes('dapur')) return { icon: <Home size={24} />, color: 'bg-teal-50 text-teal-500' };
    if (n.includes('sehat') || n.includes('obat') || n.includes('herbal') || n.includes('farmasi')) return { icon: <Heart size={24} />, color: 'bg-pink-50 text-pink-500' };
    if (n.includes('pakaian') || n.includes('baju') || n.includes('fashion')) return { icon: <Shirt size={24} />, color: 'bg-purple-50 text-purple-500' };
    if (n.includes('elektronik') || n.includes('gadget') || n.includes('listrik')) return { icon: <MonitorSmartphone size={24} />, color: 'bg-slate-100 text-slate-600' };
    if (n.includes('kecantikan') || n.includes('rambut') || n.includes('kosmetik') || n.includes('makeup')) return { icon: <Scissors size={24} />, color: 'bg-fuchsia-50 text-fuchsia-500' };
    if (n.includes('tulis') || n.includes('kantor') || n.includes('atk')) return { icon: <Pencil size={24} />, color: 'bg-zinc-50 text-zinc-500' };
    if (n.includes('mainan') || n.includes('hobi')) return { icon: <Gamepad2 size={24} />, color: 'bg-indigo-50 text-indigo-500' };
    if (n.includes('rokok') || n.includes('korek')) return { icon: <Flame size={24} />, color: 'bg-stone-50 text-stone-500' };
    if (n.includes('makan') || n.includes('snack') || n.includes('cemilan') || n.includes('biskuit')) return { icon: <Utensils size={24} />, color: 'bg-yellow-50 text-yellow-600' };
    
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
    <div className="w-full pl-4 pr-0 sm:px-6 lg:px-8 mt-2 mb-2 overflow-hidden">
      <div className="flex overflow-x-auto no-scrollbar pb-2 pt-1 gap-4 pr-4 snap-x">
        {availableCategories.length === 0 ? (
          <div className="text-center py-4 text-xs text-slate-400 w-full">
            Memuat kategori...
          </div>
        ) : (
          availableCategories.map((cat) => {
            const visual = getCategoryVisuals(cat.id, cat.name);
            return (
              <button
                key={cat.id}
                onClick={() => handleCategoryClick(cat.id, cat.name)}
                className="flex flex-col items-center justify-start gap-1.5 group cursor-pointer w-[60px] sm:w-[70px] shrink-0 snap-start"
              >
                <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center transition-transform duration-300 group-hover:scale-105 active:scale-95 shadow-sm border border-slate-100 ${visual.color}`}>
                  {visual.icon}
                </div>
                <span className="text-[10px] sm:text-xs text-center font-medium text-slate-700 leading-tight group-hover:text-brand-blue transition-colors max-w-full">
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
