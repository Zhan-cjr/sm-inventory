import { useState, useEffect } from 'react';

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    console.warn(`[Storage Warning] Failed to save key "${key}" to localStorage:`, e);
  }
};

export const usePosCart = ({
  dbProducts = [],
  dbPromos = [],
  aprioriRules = [],
  discountEngine,
  setAlertMsg,
  barcodeInput,
  onAfterHold
}) => {
  const [items, setItems] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('pos_active_cart') || '[]');
    } catch (e) {
      console.error('Failed to parse pos_active_cart:', e);
      return [];
    }
  });

  const [pwpUpsellPrompt, setPwpUpsellPrompt] = useState(null);
  const [queuedDiscount, setQueuedDiscount] = useState(null);
  const [manualTotalDiscount, setManualTotalDiscount] = useState(0);

  // Auto dismiss PWP upsell prompt after 15 seconds
  useEffect(() => {
    if (pwpUpsellPrompt) {
      const timer = setTimeout(() => {
        setPwpUpsellPrompt(null);
      }, 15000);
      return () => clearTimeout(timer);
    }
  }, [pwpUpsellPrompt]);

  // Sync active cart to localStorage
  useEffect(() => {
    safeSetItem('pos_active_cart', JSON.stringify(items));
  }, [items]);

  // Next Item Qty
  const [nextItemQty, setNextItemQty] = useState('');
  const [isQtyModalOpen, setIsQtyModalOpen] = useState(false);

  // Open Price
  const [isOpenPriceModalOpen, setIsOpenPriceModalOpen] = useState(false);
  const [openPriceTargetItem, setOpenPriceTargetItem] = useState(null);
  const [newOpenPrice, setNewOpenPrice] = useState('');

  // Discount Modal
  const [discountModal, setDiscountModal] = useState(null);
  const [discountInputVal, setDiscountInputVal] = useState('');

  // Digital Product Input
  const [isDigitalInputModalOpen, setIsDigitalInputModalOpen] = useState(false);
  const [pendingDigitalProduct, setPendingDigitalProduct] = useState(null);
  const [customerNoInput, setCustomerNoInput] = useState('');

  // Customer / Member
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [isMemberModalOpen, setIsMemberModalOpen] = useState(false);
  const [memberSearchQuery, setMemberSearchQuery] = useState('');
  const [lastScannedProductId, setLastScannedProductId] = useState(null);

  // Return Transaction Mode
  const [isReturnModalOpen, setIsReturnModalOpen] = useState(false);
  const [isReturnMode, setIsReturnMode] = useState(false);

  // Hold / Recall
  const [heldTransactions, setHeldTransactions] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_held_transactions') || '[]'); } catch (e) { return []; }
  });
  const [isRecallModalOpen, setIsRecallModalOpen] = useState(false);

  // Add Item
  const addItemToTransaction = (product, explicitQty = null, customerNo = null) => {
    if (product.product_type === 'digital' && !customerNo) {
      setPendingDigitalProduct({ product, explicitQty });
      setCustomerNoInput('');
      setIsDigitalInputModalOpen(true);
      return;
    }

    const existingItem = items.find(i => i.productId === product.id && i.customerNo === customerNo);
    let manualDiscount = 0;
    const qtyToAdd = explicitQty !== null ? parseFloat(explicitQty) : (parseFloat(nextItemQty) || 1);

    const allowMinusStock = (() => {
      try {
        const userObj = JSON.parse(localStorage.getItem('pos_user'));
        return userObj?.allow_minus_stock !== false;
      } catch (e) {
        return true;
      }
    })();

    if (!product.is_service && product.product_type !== 'digital') {
      const currentQty = existingItem ? existingItem.quantity : 0;
      if (!allowMinusStock && currentQty + qtyToAdd > (product.quantity_on_hand || 0)) {
        setAlertMsg({ text: `Stok tidak mencukupi! Sisa stok: ${product.quantity_on_hand || 0}`, type: 'error' });
        setTimeout(() => setAlertMsg(null), 3000);
        return;
      }
    }

    if (queuedDiscount) {
      if (queuedDiscount.type === 'PERCENT') {
        manualDiscount = (product.selling_price * queuedDiscount.value) / 100;
      } else {
        manualDiscount = queuedDiscount.value;
      }
      setQueuedDiscount(null);
      setAlertMsg(null);
    }

    if (existingItem) {
      const updatedItem = { ...existingItem, quantity: existingItem.quantity + qtyToAdd, manualDiscount: manualDiscount || existingItem.manualDiscount };
      const otherItems = items.filter(i => !(i.productId === product.id && i.customerNo === customerNo));
      setItems([updatedItem, ...otherItems]);
    } else {
      setItems([{
        productId: product.id,
        categoryId: product.category_id,
        sku: product.sku,
        name: product.name,
        quantity: qtyToAdd,
        unitPrice: product.selling_price,
        manualDiscount: manualDiscount,
        discountPerItem: 0,
        isService: product.is_service || false,
        productType: product.product_type || 'physical',
        customerNo: customerNo
      }, ...items]);
    }

    // Dismiss PWP / Bundling banner if this scanned product is the recommended product itself
    if (pwpUpsellPrompt && String(pwpUpsellPrompt.rewardProduct?.id) === String(product.id)) {
      setPwpUpsellPrompt(null);
    }

    // PWP (Subsidi Silang / Tebus Murah) & BUNDLING Notification & Upsell Prompt
    let promoMatched = false;
    if (dbPromos && dbPromos.length > 0 && dbProducts && dbProducts.length > 0) {
      const addedId = String(product.id);
      const now = new Date();

      // 1. Cek Promo PWP (Subsidi Silang)
      const pwpPromos = dbPromos.filter(p => {
        if (!p.is_active || p.promo_type !== 'PWP') return false;
        const from = new Date(p.valid_from);
        const until = new Date(p.valid_until);
        if (from > now || until < now) return false;
        return String(p.promo_config?.pwp_trigger_product_id) === addedId;
      });

      if (pwpPromos.length > 0) {
        for (const promo of pwpPromos) {
          const rewardId = String(promo.promo_config?.pwp_reward_product_id);
          const rewardProd = dbProducts.find(p => String(p.id) === rewardId);
          if (rewardProd) {
            const maxAllowed = promo.promo_config?.pwp_max_reward_per_transaction 
              ? parseInt(promo.promo_config.pwp_max_reward_per_transaction, 10) 
              : Infinity;
            const currentRewardInCart = items.find(i => String(i.productId) === rewardId);
            const currentRewardQty = currentRewardInCart ? currentRewardInCart.quantity : 0;

            if (currentRewardQty < maxAllowed) {
              const normalPrice = parseFloat(rewardProd.selling_price || 0);
              const discType = promo.promo_config?.pwp_discount_type || 'SPECIAL_PRICE';
              const discVal = parseFloat(promo.promo_config?.pwp_discount_value || 0);

              let finalPrice = normalPrice;
              let hemat = 0;
              let hematText = '';

              if (discType === 'SPECIAL_PRICE') {
                finalPrice = discVal;
                hemat = Math.max(0, normalPrice - discVal);
                hematText = hemat > 0 ? `Hemat Rp ${Math.round(hemat).toLocaleString('id-ID')}` : '';
              } else if (discType === 'DISCOUNT_NOMINAL') {
                finalPrice = Math.max(0, normalPrice - discVal);
                hemat = discVal;
                hematText = `Hemat Rp ${Math.round(discVal).toLocaleString('id-ID')}`;
              } else if (discType === 'DISCOUNT_PERCENT') {
                finalPrice = Math.max(0, normalPrice * (1 - (discVal / 100)));
                hemat = normalPrice - finalPrice;
                hematText = `Diskon ${discVal}%`;
              }

              setPwpUpsellPrompt({
                type: 'PWP',
                promoId: promo.id,
                promoName: promo.name,
                triggerName: product.name,
                rewardProduct: rewardProd,
                rewardNormalPrice: normalPrice,
                rewardFinalPrice: finalPrice,
                hematText: hematText,
                hematAmount: hemat
              });
              promoMatched = true;
              break;
            }
          }
        }
      }

      // 2. Cek Promo Bundling
      if (!promoMatched) {
        const bundlingPromos = dbPromos.filter(p => {
          if (!p.is_active || p.promo_type !== 'BUNDLING') return false;
          const from = new Date(p.valid_from);
          const until = new Date(p.valid_until);
          if (from > now || until < now) return false;

          const rules = p.promo_config?.rules || [];
          if (rules.length > 0) {
            return rules.some(r => String(r.productId) === addedId);
          }
          if (p.promo_config?.buy_product_id && p.promo_config?.get_product_id) {
            return String(p.promo_config.buy_product_id) === addedId || String(p.promo_config.get_product_id) === addedId;
          }
          return false;
        });

        for (const promo of bundlingPromos) {
          let missingProductId = null;
          const rules = promo.promo_config?.rules || [];

          if (rules.length > 0) {
            const missingRule = rules.find(r => {
              const rId = String(r.productId);
              const targetQty = parseInt(r.minQty, 10) || 1;
              const inCart = items.find(i => String(i.productId) === rId);
              const currentQty = inCart ? inCart.quantity : (rId === addedId ? qtyToAdd : 0);
              return currentQty < targetQty;
            });
            if (missingRule) {
              missingProductId = String(missingRule.productId);
            }
          } else if (promo.promo_config?.buy_product_id && promo.promo_config?.get_product_id) {
            const buyId = String(promo.promo_config.buy_product_id);
            const getId = String(promo.promo_config.get_product_id);
            const partnerId = (addedId === buyId) ? getId : buyId;
            const inCart = items.find(i => String(i.productId) === partnerId);
            if (!inCart || inCart.quantity < 1) {
              missingProductId = partnerId;
            }
          }

          if (missingProductId && missingProductId !== addedId) {
            const companionProd = dbProducts.find(p => String(p.id) === missingProductId);
            if (companionProd) {
              const bundleDiscount = parseFloat(promo.promo_config?.bundleDiscount || promo.discount_value || 0);
              const compNormalPrice = parseFloat(companionProd.selling_price || 0);

              setPwpUpsellPrompt({
                type: 'BUNDLING',
                promoId: promo.id,
                promoName: promo.name,
                triggerName: product.name,
                rewardProduct: companionProd,
                rewardNormalPrice: compNormalPrice,
                rewardFinalPrice: compNormalPrice,
                bundleDiscount: bundleDiscount,
                hematText: bundleDiscount > 0 ? `Hemat Rp ${Math.round(bundleDiscount).toLocaleString('id-ID')}` : '',
                hematAmount: bundleDiscount
              });
              promoMatched = true;
              break;
            }
          }
        }
      }
    }

    // AI Upsell Recommendation Logic
    if (!promoMatched && aprioriRules && aprioriRules.length > 0) {
      const addedId = String(product.id).toLowerCase();
      const rule = aprioriRules.find(r => String(r.product_id_1).toLowerCase() === addedId || String(r.product_id_2).toLowerCase() === addedId);

      if (rule) {
        const recId = String(rule.product_id_1).toLowerCase() === addedId ? String(rule.product_id_2).toLowerCase() : String(rule.product_id_1).toLowerCase();
        const recName = String(rule.product_id_1).toLowerCase() === addedId ? rule.item2 : rule.item1;

        const alreadyInCart = items.some(i => String(i.productId).toLowerCase() === recId) || addedId === recId;

        if (!alreadyInCart) {
          console.log('[AI Upsell] Match found! Recommending:', recName);
          setAlertMsg({ text: `💡 AI Upsell: Konsumen biasanya juga membeli ${recName} (Peluang ${rule.confidence}).`, type: 'info', persist: true });
          setTimeout(() => setAlertMsg(null), 8000);
        }
      }
    }

    if (nextItemQty !== '') {
      setNextItemQty('');
    }
    setLastScannedProductId(product.id);
    setTimeout(() => setLastScannedProductId(null), 3000);
  };

  const handleDigitalProductSubmit = (e) => {
    e.preventDefault();
    if (!customerNoInput.trim()) {
      setAlertMsg({ text: 'Nomor Tujuan harus diisi!', type: 'error' });
      return;
    }
    if (pendingDigitalProduct) {
      addItemToTransaction(pendingDigitalProduct.product, pendingDigitalProduct.explicitQty, customerNoInput);
      setPendingDigitalProduct(null);
      setIsDigitalInputModalOpen(false);
      setCustomerNoInput('');
    }
  };

  const removeItem = (productId) => {
    setItems(items.filter(i => i.productId !== productId));
  };

  const updateQuantity = (productId, quantity) => {
    if (!productId) return;
    if (quantity <= 0) removeItem(productId);
    else {
      const item = items.find(i => i.productId === productId);
      if (item && !item.isService) {
        const prod = dbProducts.find(p => p.id === productId);
        if (prod && prod.product_type === 'digital') {
          // Skip stock validation for digital products
        } else {
          const allowMinusStock = (() => {
            try {
              const userObj = JSON.parse(localStorage.getItem('pos_user'));
              return userObj?.allow_minus_stock !== false;
            } catch (e) {
              return true;
            }
          })();

          if (prod && !allowMinusStock && quantity > (prod.quantity_on_hand || 0)) {
            setAlertMsg({ text: `Stok tidak mencukupi! Sisa stok: ${prod.quantity_on_hand || 0}`, type: 'error' });
            setTimeout(() => setAlertMsg(null), 3000);
            return;
          }
        }
      }
      setItems(items.map(i => i.productId === productId ? { ...i, quantity } : i));
    }
  };

  // Calculations
  const subtotal = items.reduce((sum, item) => sum + (item.quantity * parseFloat(item.unitPrice)) - (item.quantity * (item.manualDiscount || 0)), 0);
  const { totalDiscount, appliedPromos } = discountEngine?.current?.calculateTotalDiscount(
    items,
    selectedCustomer ? { memberTier: selectedCustomer.member_tier, tierDiscountPercent: selectedCustomer.tier_discount_percent } : { memberTier: 'REGULAR', tierDiscountPercent: 0 },
    subtotal
  ) || { totalDiscount: 0, appliedPromos: [] };
  const finalAmount = Math.round(subtotal - totalDiscount - manualTotalDiscount);

  const handleManualDiscountItem = (type) => {
    setDiscountModal({ target: 'ITEM', type });
    setDiscountInputVal('');
  };

  const handleManualTotalDiscount = (type) => {
    setDiscountModal({ target: 'TOTAL', type });
    setDiscountInputVal('');
  };

  const applyEnteredDiscount = () => {
    const rawVal = (discountModal && discountModal.type === 'RUPIAH') ? discountInputVal.replace(/\./g, '') : discountInputVal;
    const val = parseFloat(rawVal);
    if (isNaN(val) || val <= 0) {
      setAlertMsg({ text: 'Nilai diskon harus berupa angka lebih besar dari 0!', type: 'error' });
      setTimeout(() => setAlertMsg(null), 2000);
      return;
    }

    if (discountModal.target === 'ITEM') {
      setQueuedDiscount({ type: discountModal.type, value: val });
      setAlertMsg({ text: `Diskon ${discountModal.type === 'PERCENT' ? val + '%' : 'Rp ' + val} disiapkan. Silakan scan barang.`, type: 'info', persist: true });
    } else {
      let discount = 0;
      if (discountModal.type === 'PERCENT') {
        discount = (subtotal * val) / 100;
      } else {
        discount = val;
      }
      setManualTotalDiscount(discount);
      setAlertMsg({ text: `Diskon Total sebesar ${discountModal.type === 'PERCENT' ? val + '%' : 'Rp ' + val} berhasil diterapkan.`, type: 'success' });
      setTimeout(() => setAlertMsg(null), 3000);
    }

    setDiscountModal(null);
    setDiscountInputVal('');
    setTimeout(() => barcodeInput?.current?.focus(), 100);
  };

  const handleHoldTransaction = () => {
    if (items.length === 0) return;

    const newHeld = [
      ...heldTransactions,
      {
        id: Date.now(),
        items,
        subtotal,
        finalAmount,
        timestamp: new Date().toISOString(),
        itemCount: items.length
      }
    ];

    setHeldTransactions(newHeld);
    safeSetItem('pos_held_transactions', JSON.stringify(newHeld));
    setItems([]);
    setPwpUpsellPrompt(null);
    setManualTotalDiscount(0);
    setIsReturnMode(false);
    if (typeof onAfterHold === 'function') {
      onAfterHold();
    }
    setAlertMsg({ text: 'Transaksi ditunda (HOLD).', type: 'info' });
    setTimeout(() => setAlertMsg(null), 2000);
  };

  const handleRecallTransaction = (held) => {
    setItems(held.items);

    const newHeld = heldTransactions.filter(h => h.id !== held.id);
    setHeldTransactions(newHeld);
    safeSetItem('pos_held_transactions', JSON.stringify(newHeld));
    setIsRecallModalOpen(false);

    setAlertMsg({ text: 'Transaksi berhasil dipanggil (RECALL).', type: 'info' });
    setTimeout(() => setAlertMsg(null), 2000);
  };

  const handleReturnSuccess = (returnItems, originalReceiptId) => {
    setItems(prev => [...returnItems, ...prev]);
    setIsReturnMode(true);
    setIsReturnModalOpen(false);
    setAlertMsg({ text: `Mode Retur Aktif untuk Nota: ${originalReceiptId}`, type: 'info', persist: true });
    setTimeout(() => setAlertMsg(null), 5000);
  };

  return {
    items,
    setItems,
    pwpUpsellPrompt,
    setPwpUpsellPrompt,
    queuedDiscount,
    setQueuedDiscount,
    manualTotalDiscount,
    setManualTotalDiscount,
    nextItemQty,
    setNextItemQty,
    isQtyModalOpen,
    setIsQtyModalOpen,
    isOpenPriceModalOpen,
    setIsOpenPriceModalOpen,
    openPriceTargetItem,
    setOpenPriceTargetItem,
    newOpenPrice,
    setNewOpenPrice,
    discountModal,
    setDiscountModal,
    discountInputVal,
    setDiscountInputVal,
    isDigitalInputModalOpen,
    setIsDigitalInputModalOpen,
    pendingDigitalProduct,
    setPendingDigitalProduct,
    customerNoInput,
    setCustomerNoInput,
    selectedCustomer,
    setSelectedCustomer,
    isMemberModalOpen,
    setIsMemberModalOpen,
    memberSearchQuery,
    setMemberSearchQuery,
    lastScannedProductId,
    setLastScannedProductId,
    isReturnModalOpen,
    setIsReturnModalOpen,
    isReturnMode,
    setIsReturnMode,
    heldTransactions,
    setHeldTransactions,
    isRecallModalOpen,
    setIsRecallModalOpen,
    subtotal,
    totalDiscount,
    appliedPromos,
    finalAmount,
    addItemToTransaction,
    handleDigitalProductSubmit,
    removeItem,
    updateQuantity,
    handleManualDiscountItem,
    handleManualTotalDiscount,
    applyEnteredDiscount,
    handleHoldTransaction,
    handleRecallTransaction,
    handleReturnSuccess
  };
};
