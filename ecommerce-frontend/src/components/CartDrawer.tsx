import React from 'react';
import { useEcom } from '../context/EcomContext';
import { X, ShoppingBag, Plus, Minus, Trash2, ArrowRight } from 'lucide-react';
import { ProductImage } from './ProductImage';

export const CartDrawer: React.FC = () => {
  const {
    cart,
    isCartOpen,
    setIsCartOpen,
    updateQuantity,
    removeFromCart,
    setIsCheckoutModalOpen,
    member,
    setIsMemberModalOpen,
  } = useEcom();

  if (!isCartOpen) return null;

  const totalAmount = cart.reduce(
    (sum, item) => sum + parseFloat(item.product.selling_price) * item.quantity,
    0
  );

  const handleCheckoutClick = () => {
    if (!member) {
      setIsCartOpen(false);
      alert('Silakan masuk ke akun member Anda terlebih dahulu untuk melanjutkan pembelian.');
      setIsMemberModalOpen(true);
      return;
    }
    setIsCartOpen(false);
    setIsCheckoutModalOpen(true);
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      {/* Backdrop */}
      <div 
        onClick={() => setIsCartOpen(false)}
        className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-300"
      />

      {/* Drawer Container */}
      <div className="absolute inset-y-0 right-0 max-w-full flex">
        <div className="w-screen max-w-md bg-white shadow-2xl flex flex-col h-full border-l border-slate-100 pb-14 md:pb-0">
          
          {/* Header */}
          <div className="p-5 border-b border-slate-100 flex items-center justify-between">
            <div className="flex items-center gap-2.5">
              <div className="w-10 h-10 bg-brand-green/10 text-brand-green rounded-xl flex items-center justify-center">
                <ShoppingBag size={20} />
              </div>
              <div>
                <h3 className="font-bold text-slate-800 text-lg">Keranjang Belanja</h3>
                <p className="text-xs text-slate-500">{cart.length} Jenis Produk</p>
              </div>
            </div>
            <button 
              onClick={() => setIsCartOpen(false)}
              className="p-2 rounded-lg hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors"
            >
              <X size={20} />
            </button>
          </div>

          {/* Cart Items List */}
          <div className="flex-grow overflow-y-auto p-5 flex flex-col gap-4">
            {cart.length > 0 ? (
              cart.map((item) => {
                const product = item.product;
                const price = parseFloat(product.selling_price) || 0;
                return (
                  <div 
                    key={product.id} 
                    className="flex gap-4 p-3 rounded-xl border border-slate-100 bg-white hover:shadow-md transition-shadow group"
                  >
                    {/* Thumbnail */}
                    <div className="w-20 h-20 bg-slate-50 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center border border-slate-100">
                      <ProductImage 
                        src={product.image_url} 
                        alt={product.name} 
                        className="w-full h-full object-cover bg-white"
                        fallbackIconSize={24}
                      />
                    </div>

                    {/* Details */}
                    <div className="flex-grow flex flex-col justify-between">
                      <div>
                        <h4 className="font-semibold text-slate-800 text-sm line-clamp-1 group-hover:text-brand-blue transition-colors">
                          {product.name}
                        </h4>
                        <span className="text-xs font-bold text-brand-red mt-1 block">
                          Rp {price.toLocaleString('id-ID')}
                        </span>
                      </div>

                      {/* Controls */}
                      <div className="flex items-center justify-between mt-2">
                        <div className="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                          <button
                            onClick={() => updateQuantity(product.id, item.quantity - 1)}
                            className="p-1 px-2.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                          >
                            <Minus size={12} />
                          </button>
                          <span className="px-2 text-xs font-bold text-slate-700 min-w-[20px] text-center">
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateQuantity(product.id, item.quantity + 1)}
                            className="p-1 px-2.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                          >
                            <Plus size={12} />
                          </button>
                        </div>

                        <button
                          onClick={() => removeFromCart(product.id)}
                          className="text-slate-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-red-50 transition-colors"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </div>
                  </div>
                );
              })
            ) : (
              <div className="flex flex-col items-center justify-center h-full text-slate-400 gap-3">
                <ShoppingBag size={48} className="text-slate-200" />
                <span className="text-sm font-semibold">Keranjang Anda masih kosong</span>
                <button
                  onClick={() => setIsCartOpen(false)}
                  className="text-xs text-brand-blue font-bold hover:underline"
                >
                  Mulai Belanja Sekarang
                </button>
              </div>
            )}
          </div>

          {/* Footer / Summary */}
          {cart.length > 0 && (
            <div className="p-5 border-t border-slate-100 bg-slate-50 flex flex-col gap-4">
              <div className="flex justify-between items-center text-slate-700">
                <span className="text-sm font-medium">Subtotal</span>
                <span className="font-bold text-lg text-brand-red">
                  Rp {totalAmount.toLocaleString('id-ID')}
                </span>
              </div>
              <p className="text-[0.7rem] text-slate-500 leading-normal">
                *Harga sudah termasuk PPN. Ongkos kirim (jika memilih kurir) akan ditentukan saat checkout.
              </p>
              
              <button
                onClick={handleCheckoutClick}
                className="flex items-center justify-center gap-2.5 w-full bg-brand-green text-white py-3.5 rounded-xl font-bold hover:bg-brand-green/90 transition-colors shadow-md shadow-brand-green/10"
              >
                Lanjut ke Checkout
                <ArrowRight size={18} />
              </button>
            </div>
          )}

        </div>
      </div>
    </div>
  );
};
