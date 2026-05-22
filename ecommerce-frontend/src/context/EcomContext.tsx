import React, { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

export interface Product {
  id: string;
  name: string;
  category_id: string;
  category?: {
    id: string;
    name: string;
  } | null;
  selling_price: string;
  original_price?: string;
  rating?: number;
  sold?: number;
  image_url: string | null;
  is_promo: boolean;
  quantity_on_hand?: number;
  stock: number;
}

export interface Branch {
  id: string;
  name: string;
  code: string;
  address: string;
  phone: string | null;
  latitude: string | null;
  longitude: string | null;
}

export interface Member {
  id: string;
  name: string;
  phone: string;
  email: string | null;
  address: string | null;
  member_tier: string;
  points: number;
}

export interface CartItem {
  product: Product;
  quantity: number;
}

interface EcomContextType {
  cart: CartItem[];
  addToCart: (product: Product) => void;
  removeFromCart: (productId: string) => void;
  updateQuantity: (productId: string, quantity: number) => void;
  clearCart: () => void;
  selectedBranch: Branch | null;
  setSelectedBranch: (branch: Branch | null) => void;
  searchQuery: string;
  setSearchQuery: (query: string) => void;
  isCartOpen: boolean;
  setIsCartOpen: (open: boolean) => void;
  isBranchModalOpen: boolean;
  setIsBranchModalOpen: (open: boolean) => void;
  isCheckoutModalOpen: boolean;
  setIsCheckoutModalOpen: (open: boolean) => void;
  checkoutSuccessOrder: any | null;
  setCheckoutSuccessOrder: (order: any | null) => void;
  isMemberModalOpen: boolean;
  setIsMemberModalOpen: (open: boolean) => void;
  selectedCategory: string;
  setSelectedCategory: (category: string) => void;
  member: Member | null;
  setMember: (member: Member | null) => void;
  logoutMember: () => void;
  syncMemberPoints: () => Promise<void>;
}

const EcomContext = createContext<EcomContextType | undefined>(undefined);

export const EcomProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [cart, setCart] = useState<CartItem[]>(() => {
    const saved = localStorage.getItem('ecom_cart');
    return saved ? JSON.parse(saved) : [];
  });

  const [selectedBranch, setSelectedBranchState] = useState<Branch | null>(() => {
    const saved = localStorage.getItem('ecom_selected_branch');
    return saved ? JSON.parse(saved) : null;
  });

  const [member, setMemberState] = useState<Member | null>(() => {
    const saved = localStorage.getItem('ecom_member');
    return saved ? JSON.parse(saved) : null;
  });

  const [searchQuery, setSearchQuery] = useState('');
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isBranchModalOpen, setIsBranchModalOpen] = useState(false);
  const [isCheckoutModalOpen, setIsCheckoutModalOpen] = useState(false);
  const [isMemberModalOpen, setIsMemberModalOpen] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [checkoutSuccessOrder, setCheckoutSuccessOrder] = useState<any | null>(null);

  useEffect(() => {
    localStorage.setItem('ecom_cart', JSON.stringify(cart));
  }, [cart]);

  const setSelectedBranch = (branch: Branch | null) => {
    setSelectedBranchState(branch);
    if (branch) {
      localStorage.setItem('ecom_selected_branch', JSON.stringify(branch));
    } else {
      localStorage.removeItem('ecom_selected_branch');
    }
  };

  const setMember = (newMember: Member | null) => {
    setMemberState(newMember);
    if (newMember) {
      localStorage.setItem('ecom_member', JSON.stringify(newMember));
    } else {
      localStorage.removeItem('ecom_member');
    }
  };

  const logoutMember = () => {
    setMember(null);
  };

  const syncMemberPoints = async () => {
    const saved = localStorage.getItem('ecom_member');
    if (!saved) return;
    try {
      const parsed = JSON.parse(saved);
      if (!parsed?.phone) return;
      const response = await axios.get('/ecommerce/members/profile', {
        params: { phone: parsed.phone }
      });
      if (response.data.member) {
        setMember(response.data.member);
      }
    } catch (error) {
      console.error('Failed to sync member info:', error);
      if (axios.isAxiosError(error) && error.response?.status === 404) {
        logoutMember();
      }
    }
  };

  useEffect(() => {
    const saved = localStorage.getItem('ecom_member');
    if (saved) {
      syncMemberPoints();
    }
  }, []);

  const addToCart = (product: Product) => {
    setCart((prev) => {
      const existing = prev.find((item) => item.product.id === product.id);
      if (existing) {
        return prev.map((item) =>
          item.product.id === product.id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      }
      return [...prev, { product, quantity: 1 }];
    });
    // Removed setIsCartOpen(true) so that adding product to cart does not open cart drawer automatically
  };

  const removeFromCart = (productId: string) => {
    setCart((prev) => prev.filter((item) => item.product.id !== productId));
  };

  const updateQuantity = (productId: string, quantity: number) => {
    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }
    setCart((prev) =>
      prev.map((item) =>
        item.product.id === productId ? { ...item, quantity } : item
      )
    );
  };

  const clearCart = () => {
    setCart([]);
  };

  return (
    <EcomContext.Provider
      value={{
        cart,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        selectedBranch,
        setSelectedBranch,
        searchQuery,
        setSearchQuery,
        isCartOpen,
        setIsCartOpen,
        isBranchModalOpen,
        setIsBranchModalOpen,
        isCheckoutModalOpen,
        setIsCheckoutModalOpen,
        checkoutSuccessOrder,
        setCheckoutSuccessOrder,
        isMemberModalOpen,
        setIsMemberModalOpen,
        selectedCategory,
        setSelectedCategory,
        member,
        setMember,
        logoutMember,
        syncMemberPoints,
      }}
    >
      {children}
    </EcomContext.Provider>
  );
};

export const useEcom = () => {
  const context = useContext(EcomContext);
  if (!context) {
    throw new Error('useEcom must be used within an EcomProvider');
  }
  return context;
};
