import { useState, useEffect } from 'react';
import { X, ShoppingCart, Star, PackageOpen, Sparkles, Plus, Minus, Check } from 'lucide-react';
import { useEcom } from '../context/EcomContext';

const ProductDetailModal = () => {
  const { 
    selectedProductForModal, 
    isProductModalOpen, 
    setIsProductModalOpen,
    addToCart,
    cart,
    updateQuantity,
    member,
    setIsMemberModalOpen,
    selectedBranch
  } = useEcom();

  const [quantity, setQuantity] = useState(1);
  const [isAdded, setIsAdded] = useState(false);
  const [loginAlert, setLoginAlert] = useState(false);

  // Reset quantity when modal opens with a new product
  useEffect(() => {
    if (isProductModalOpen && selectedProductForModal) {
      const cartItem = cart.find(item => item.product.id === selectedProductForModal.id);
      setQuantity(cartItem ? cartItem.quantity : 1);
      setIsAdded(false);
    }
  }, [isProductModalOpen, selectedProductForModal, cart]);

  if (!isProductModalOpen || !selectedProductForModal) return null;

  const product = selectedProductForModal;
  const price = parseFloat(product.selling_price) || 0;
  const originalPrice = product.original_price ? parseFloat(product.original_price) : null;
  const rating = product.rating || 5.0;
  const sold = product.sold || 0;

  const isOutOfStock = product.stock <= 0;
  
  const cartItem = cart.find(item => item.product.id === product.id);

  const handleClose = () => {
    setIsProductModalOpen(false);
  };

  const handleIncrease = () => {
    if (quantity < (selectedBranch ? product.stock : 99)) {
      setQuantity(q => q + 1);
    }
  };

  const handleDecrease = () => {
    if (quantity > 1) {
      setQuantity(q => q - 1);
    }
  };

  const handleAddToCart = () => {
    if (!member) {
      setLoginAlert(true);
      setTimeout(() => {
        setLoginAlert(false);
        handleClose();
        setIsMemberModalOpen(true);
      }, 2000);
      return;
    }

    if (isOutOfStock || (selectedBranch && quantity > product.stock)) return;

    if (cartItem) {
      updateQuantity(product.id, quantity);
    } else {
      // Add product multiple times or update logic to allow bulk add
      // Since addToCart only adds 1, we can call it then updateQuantity
      addToCart(product);
      setTimeout(() => {
        updateQuantity(product.id, quantity);
      }, 50);
    }

    setIsAdded(true);
    
    // Trigger animation event for navbar cart
    const event = new CustomEvent('cart-item-added');
    window.dispatchEvent(event);

    setTimeout(() => {
      handleClose();
    }, 600);
  };

  return (
    <div className="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
      <div className="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col sm:flex-row overflow-hidden transform transition-all animate-scale-up border border-slate-100 relative">
        
        {/* Mobile Close Button & Handle */}
        <div className="sm:hidden w-full flex justify-center pt-3 pb-1 absolute top-0 z-20">
          <div className="w-12 h-1.5 bg-slate-200 rounded-full"></div>
        </div>
        <button 
          onClick={handleClose}
          className="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all shadow-sm"
        >
          <X size={18} />
        </button>

        {/* Image Section */}
        <div className="w-full sm:w-2/5 aspect-square sm:aspect-auto sm:h-auto bg-slate-50 relative flex items-center justify-center overflow-hidden flex-shrink-0">
          {product.image_url ? (
            <img 
              src={product.image_url} 
              alt={product.name} 
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="flex flex-col items-center justify-center text-slate-300">
              <PackageOpen size={64} className="mb-4" />
              <span className="text-sm font-medium">Tidak ada gambar</span>
            </div>
          )}
          
          {product.is_promo && (
            <div className="absolute top-4 left-4 bg-brand-red text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-md flex items-center gap-1.5">
              <Sparkles size={14} />
              Promo Spesial
            </div>
          )}
          
          {isOutOfStock && (
             <div className="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px] flex items-center justify-center">
              <span className="bg-brand-red text-white text-sm font-bold px-4 py-2 rounded-xl shadow-lg uppercase tracking-wider">
                Stok Habis
              </span>
            </div>
          )}
        </div>

        {/* Details Section */}
        <div className="w-full sm:w-3/5 p-5 sm:p-8 flex flex-col overflow-y-auto">
          <div className="flex-grow">
            <div className="text-xs text-brand-blue font-bold uppercase tracking-wider mb-2">
              {(product.category && product.category.name) || product.category_id || 'Kategori Umum'}
            </div>
            
            <h2 className="text-xl sm:text-2xl font-bold text-slate-800 leading-tight mb-3 pr-8 sm:pr-0">
              {product.name}
            </h2>

            <div className="flex items-center gap-4 text-sm text-slate-500 mb-6">
              <div className="flex items-center gap-1">
                <Star className="text-yellow-400 fill-yellow-400 w-4 h-4" />
                <span className="font-semibold text-slate-700">{rating}</span>
              </div>
              <div className="w-1 h-1 rounded-full bg-slate-300"></div>
              <div>{sold > 1000 ? (sold/1000).toFixed(1) + 'rb' : sold} Terjual</div>
            </div>

            <div className="mb-6 bg-slate-50 p-4 rounded-2xl border border-slate-100">
              {originalPrice && (
                <div className="text-sm text-slate-400 line-through mb-1">
                  Rp {originalPrice.toLocaleString('id-ID')}
                </div>
              )}
              <div className="text-3xl font-black text-brand-red">
                Rp {price.toLocaleString('id-ID')}
              </div>
            </div>

            <div className="mb-8">
              <h3 className="font-bold text-slate-800 mb-2">Deskripsi Produk</h3>
              <p className="text-sm text-slate-600 leading-relaxed">
                {(product as any).description || 'Belum ada deskripsi lengkap untuk produk ini. Produk dijamin asli dan berkualitas dari Toserba Selamat.'}
              </p>
            </div>
          </div>

          {/* Action Footer */}
          <div className="mt-auto pt-4 pb-8 sm:pb-0 border-t border-slate-100 bg-white sticky bottom-0 z-10">
            {loginAlert && (
              <div className="mb-3 p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xl font-medium animate-fade-in">
                Silakan masuk ke akun member Anda terlebih dahulu untuk mulai berbelanja.
              </div>
            )}
            <div className="flex items-center justify-between mb-4">
              <span className="text-sm font-bold text-slate-700">Atur Jumlah</span>
              <div className="flex items-center justify-end text-xs font-semibold">
                <span className={isOutOfStock ? 'text-red-500' : 'text-slate-500'}>
                  Sisa Stok: {product.stock}
                </span>
              </div>
            </div>

            <div className="flex items-center gap-4">
              {/* Quantity Controls */}
              <div className="flex items-center bg-slate-100 rounded-xl p-1 border border-slate-200">
                <button 
                  onClick={handleDecrease}
                  disabled={quantity <= 1 || isOutOfStock}
                  className="w-10 h-10 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm disabled:opacity-50 transition-all"
                >
                  <Minus size={18} />
                </button>
                <span className="w-12 text-center font-bold text-slate-800 text-lg">
                  {quantity}
                </span>
                <button 
                  onClick={handleIncrease}
                  disabled={quantity >= (selectedBranch ? product.stock : 99) || isOutOfStock}
                  className="w-10 h-10 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm disabled:opacity-50 transition-all"
                >
                  <Plus size={18} />
                </button>
              </div>

              {/* Add to Cart Button */}
              <button 
                onClick={handleAddToCart}
                disabled={isOutOfStock || Boolean(selectedBranch && quantity > product.stock)}
                className={`flex-grow h-12 rounded-xl flex items-center justify-center gap-2 font-bold transition-all ${
                  isOutOfStock 
                    ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
                    : isAdded
                    ? 'bg-brand-green text-white shadow-lg shadow-brand-green/30'
                    : 'bg-brand-blue text-white hover:bg-brand-blue/90 hover:shadow-lg shadow-brand-blue/20 active:scale-[0.98]'
                }`}
              >
                {isAdded ? (
                  <>
                    <Check size={20} />
                    Berhasil Ditambahkan
                  </>
                ) : (
                  <>
                    <ShoppingCart size={20} />
                    {cartItem ? 'Perbarui Keranjang' : 'Tambah ke Keranjang'}
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProductDetailModal;
