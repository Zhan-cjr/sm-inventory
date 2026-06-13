import React, { useState, useEffect, useRef } from 'react';
import { ShoppingCart, Star, PackageOpen, Sparkles, Check, Plus } from 'lucide-react';
import { ProductImage } from './ProductImage';
import { useEcom, Product } from '../context/EcomContext';
import axios from 'axios';
import { getImageUrl } from '../utils/api';

const ProductCard = ({ product }: { product: Product }) => {
  const { addToCart, selectedBranch, cart, member, setIsMemberModalOpen, setSelectedProductForModal, setIsProductModalOpen } = useEcom();
  const [isAdded, setIsAdded] = useState(false);
  const [showPlusOne, setShowPlusOne] = useState(false);

  const price = parseFloat(product.selling_price) || 0;
  const originalPrice = product.original_price ? parseFloat(product.original_price) : null;
  const rating = product.rating || 5.0;
  const sold = product.sold || 0;

  // Cek ketersediaan stok
  const isOutOfStock = product.stock <= 0;

  // Cek apakah jumlah di keranjang sudah mencapai batas stok
  const cartItem = cart.find(item => item.product.id === product.id);
  const currentQuantityInCart = cartItem ? cartItem.quantity : 0;
  const isStockLimitReached = !!selectedBranch && currentQuantityInCart >= product.stock;

  const handleAddToCart = (e: React.MouseEvent<HTMLButtonElement>) => {
    if (!member) {
      setIsMemberModalOpen(true);
      return;
    }

    if (isOutOfStock || isStockLimitReached) return;
    
    // Animasi Melayang (Fly to Cart)
    const button = e.currentTarget;
    const cardElement = button.closest('.group');
    const imgElement = cardElement ? cardElement.querySelector('img') : null;
    const startRect = imgElement ? imgElement.getBoundingClientRect() : button.getBoundingClientRect();
    
    const isMobile = window.innerWidth < 768;
    const targetElement = document.getElementById(isMobile ? 'navbar-cart-button-mobile' : 'navbar-cart-button-desktop');
    
    if (targetElement) {
      const targetRect = targetElement.getBoundingClientRect();
      const flyEl = document.createElement('div');
      flyEl.style.position = 'fixed';
      flyEl.style.left = `${startRect.left}px`;
      flyEl.style.top = `${startRect.top}px`;
      flyEl.style.width = `${startRect.width}px`;
      flyEl.style.height = `${startRect.height}px`;
      flyEl.style.borderRadius = imgElement ? '12px' : '50%';
      flyEl.style.overflow = 'hidden';
      flyEl.style.zIndex = '99999';
      flyEl.style.pointerEvents = 'none';
      flyEl.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
      flyEl.style.transition = 'all 0.8s cubic-bezier(0.25, 1, 0.5, 1)';
      
      if (imgElement && imgElement.src) {
        flyEl.innerHTML = `<img src="${imgElement.src}" style="width:100%;height:100%;object-fit:cover;" />`;
      } else {
        flyEl.style.background = '#3b82f6';
        flyEl.style.display = 'flex';
        flyEl.style.alignItems = 'center';
        flyEl.style.justifyContent = 'center';
        flyEl.style.color = '#fff';
        flyEl.style.fontWeight = 'bold';
        flyEl.innerHTML = 'S';
      }
      document.body.appendChild(flyEl);
      
      requestAnimationFrame(() => {
        flyEl.style.left = `${targetRect.left + targetRect.width / 2 - 15}px`;
        flyEl.style.top = `${targetRect.top + targetRect.height / 2 - 15}px`;
        flyEl.style.width = '30px';
        flyEl.style.height = '30px';
        flyEl.style.opacity = '0.4';
        flyEl.style.transform = 'scale(0.2) rotate(720deg)';
      });
      
      setTimeout(() => {
        flyEl.remove();
        // Memicu event agar ikon keranjang di Navbar bergoyang/bounce setelah barang sampai
        const event = new CustomEvent('cart-item-added');
        window.dispatchEvent(event);
      }, 800);
    } else {
      // Fallback jika tombol target tidak ditemukan
      const event = new CustomEvent('cart-item-added');
      window.dispatchEvent(event);
    }

    addToCart(product);
    setIsAdded(true);
    setShowPlusOne(true);
    
    setTimeout(() => {
      setIsAdded(false);
    }, 1000);

    setTimeout(() => {
      setShowPlusOne(false);
    }, 800);
  };

  const handleCardClick = () => {
    setSelectedProductForModal(product);
    setIsProductModalOpen(true);
  };

  return (
    <div className={`group glass-panel-dark rounded-[2rem] border border-slate-200/60 overflow-hidden shadow-sm hover:shadow-2xl hover:border-brand-blue/30 transition-all duration-500 flex flex-col h-full relative ${isOutOfStock ? 'opacity-60 grayscale-[10%]' : ''}`}>
      
      {/* Plus One Animation Floating Particle */}
      {showPlusOne && (
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-30 pointer-events-none bg-brand-green text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1 animate-ping-once">
          <Plus size={10} />
          <span>1 ke Keranjang</span>
        </div>
      )}

      {/* Image Container */}
      <div 
        className="relative aspect-square overflow-hidden bg-slate-50 flex items-center justify-center cursor-pointer"
        onClick={handleCardClick}
      >
        <ProductImage 
          src={product.image_url} 
          alt={product.name} 
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 bg-white"
          fallbackIconSize={48}
        />
        
        {product.is_promo && (
          <div className="absolute top-3 left-3 bg-brand-red text-white text-xs font-bold px-2.5 py-1 rounded-lg shadow-sm flex items-center gap-1">
            <Sparkles size={12} />
            Promo
          </div>
        )}

        {isOutOfStock ? (
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px] flex items-center justify-center">
            <span className="bg-brand-red text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-lg uppercase tracking-wider scale-105 animate-pulse">
              Stok Habis
            </span>
          </div>
        ) : isStockLimitReached ? (
          <div className="absolute inset-0 bg-slate-900/20 backdrop-blur-[0.5px] flex items-center justify-center">
            <span className="bg-amber-600 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-md uppercase tracking-wider">
              Batas Stok Dicapai
            </span>
          </div>
        ) : null}
      </div>

      {/* Content Container */}
      <div className="p-3 sm:p-4 flex flex-col flex-grow">
        <h3 
          className="font-semibold text-slate-800 leading-snug mb-1 line-clamp-2 group-hover:text-brand-blue transition-colors mt-1 text-xs sm:text-sm cursor-pointer"
          onClick={handleCardClick}
        >
          {product.name}
        </h3>
        
        <div className="flex items-center justify-between text-[10px] sm:text-xs text-slate-500 mb-2 mt-1">
          <div className="flex items-center gap-0.5 sm:gap-1">
            <Star className="text-yellow-400 fill-yellow-400 w-3 h-3 sm:w-3.5 sm:h-3.5" />
            <span className="font-medium text-slate-600">{rating}</span>
            <span className="text-slate-400">| {sold > 1000 ? (sold/1000).toFixed(1) + 'rb' : sold} terjual</span>
          </div>
          <span className={`font-semibold ${isOutOfStock ? 'text-red-500' : 'text-slate-600'}`}>
            {isOutOfStock ? 'Stok Habis' : `Stok: ${product.stock}`}
          </span>
        </div>

        <div className="flex items-end justify-between mt-auto pt-2">
          <div>
            {originalPrice && (
              <div className="text-[10px] sm:text-xs text-slate-400 line-through mb-0.5">
                Rp {originalPrice.toLocaleString('id-ID')}
              </div>
            )}
            <div className="font-bold text-sm sm:text-base text-brand-red">
              Rp {price.toLocaleString('id-ID')}
            </div>
          </div>
          
          <button 
            disabled={isOutOfStock || isStockLimitReached}
            onClick={handleAddToCart}
            className={`w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center shadow-md active:scale-90 transition-all duration-300 ${
              isOutOfStock || isStockLimitReached
                ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none'
                : isAdded
                ? 'bg-brand-green text-white rotate-[360deg] scale-110 shadow-brand-green/30'
                : 'bg-brand-blue text-white hover:bg-brand-blue/90 hover:shadow-brand-blue/30'
            }`}
          >
            {isAdded ? (
              <Check size={14} className="sm:size-[18px]" />
            ) : (
              <ShoppingCart size={12} className="sm:size-[16px]" />
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

const ProductGrid = () => {
  const { selectedBranch, searchQuery, selectedCategory, setSelectedCategory, setAvailableCategories } = useEcom();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [ecommerceCategories, setEcommerceCategories] = useState<string[]>([]);

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await axios.get('/ecommerce/settings');
        if (response.data && Array.isArray(response.data.ecommerce_categories)) {
          setEcommerceCategories(response.data.ecommerce_categories);
        }
      } catch (error) {
        console.error('Error fetching settings in ProductGrid:', error);
      }
    };
    fetchSettings();
  }, []);

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

  // Reset selected category filter when branch changes, except on initial load
  const isFirstBranchLoad = useRef(true);
  useEffect(() => {
    if (isFirstBranchLoad.current) {
      isFirstBranchLoad.current = false;
      return;
    }
    setSelectedCategory('all');
  }, [selectedBranch]);

  // Extract unique categories from settings, and fallback to products' categories if settings are empty
  const categories = [
    { id: 'all', name: 'Semua Kategori' },
    { id: 'promo', name: '🔥 Sedang Promo' },
    ...(ecommerceCategories.length > 0
      ? ecommerceCategories.map((name) => ({ id: name, name }))
      : Array.from(
          new Map(
            products
              .filter((p: any) => p.ecommerce_category || (p.category && p.category.name))
              .map((p: any) => {
                const catName = p.ecommerce_category || p.category.name;
                return [catName, catName];
              })
          ).entries()
        ).map(([id, name]) => ({ id, name }))
    )
  ];

  // Update global context so other components (Footer, CategoryIcons) can sync
  useEffect(() => {
    // Only update if it actually changed to prevent infinite loops
    // We can just set it directly since React batches state updates, but let's be safe
    setAvailableCategories(categories);
  }, [products.length, ecommerceCategories.length, setAvailableCategories]);

  // Local/real-time filter based on category and search query
  const filteredProducts = products.filter((product: any) => {
    let matchesCategory = false;
    
    if (selectedCategory === 'all') {
      matchesCategory = true;
    } else if (selectedCategory === 'promo') {
      matchesCategory = product.is_promo === true || product.is_promo === 1;
    } else {
      matchesCategory = 
        (product.ecommerce_category && product.ecommerce_category.toLowerCase() === selectedCategory.toLowerCase()) ||
        (!product.ecommerce_category && (
          product.category_id === selectedCategory || 
          (product.category && product.category.id === selectedCategory) ||
          (product.category && product.category.name.toLowerCase() === selectedCategory.toLowerCase()) ||
          (product.category && product.category.name.toLowerCase().includes(selectedCategory.toLowerCase()))
        ));
    }
      
    const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  return (
    <div id="catalog-section" className="bg-slate-50 py-16 scroll-mt-24">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-end mb-8">
          <div>
            <h2 className="text-2xl md:text-3xl font-bold text-slate-900">Katalog Produk</h2>
            <p className="text-slate-500 mt-2">
              {selectedBranch 
                ? `Menampilkan produk terbaik dari cabang ${selectedBranch.name}` 
                : 'Belanja hemat dan penuh berkah di Toserba Selamat.'}
            </p>
          </div>
        </div>

        {/* Category Slider */}
        {!loading && products.length > 0 && (
          <div className="flex gap-2 overflow-x-auto pb-4 mb-6 scrollbar-hide scroll-smooth">
            {categories.map((cat) => {
              const isCatSelected = selectedCategory === cat.id || 
                (selectedCategory.toLowerCase() === cat.name.toLowerCase()) ||
                (cat.name.toLowerCase().includes(selectedCategory.toLowerCase()) && selectedCategory !== 'all' && cat.id !== 'all');
                
              return (
                <button
                  key={cat.id}
                  onClick={() => setSelectedCategory(cat.id)}
                  className={`px-5 py-2.5 rounded-2xl text-xs font-bold whitespace-nowrap transition-all duration-300 ${
                    isCatSelected
                      ? 'bg-brand-blue text-white shadow-[0_8px_20px_rgba(36,42,122,0.3)] hover:-translate-y-0.5'
                      : 'bg-white/80 backdrop-blur-md text-slate-600 border border-slate-200/60 hover:border-slate-300 hover:bg-white hover:-translate-y-0.5 shadow-sm'
                  }`}
                >
                  {cat.name}
                </button>
              );
            })}
          </div>
        )}

        {loading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-blue"></div>
          </div>
        ) : filteredProducts.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            {filteredProducts.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        ) : (
          <div className="text-center py-20 glass-panel-dark rounded-[2rem] border border-slate-200/60 shadow-sm">
            <PackageOpen size={48} className="mx-auto text-slate-300 mb-4" />
            <h3 className="text-lg font-semibold text-slate-800">
              {searchQuery || selectedCategory !== 'all' ? 'Produk Tidak Ditemukan' : 'Belum Ada Produk'}
            </h3>
            <p className="text-slate-500 mt-1">
              {searchQuery 
                ? `Tidak ada produk dengan kata kunci "${searchQuery}"`
                : selectedCategory !== 'all'
                ? 'Tidak ada produk di kategori ini.'
                : 'Produk belum tersedia di katalog e-commerce saat ini.'}
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProductGrid;
