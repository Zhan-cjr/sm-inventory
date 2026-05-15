export class DiscountEngine {
  constructor(promos) {
    this.promos = Array.isArray(promos) ? promos : [];
    console.log('DiscountEngine initialized with promos:', this.promos);
  }

  calculateTotalDiscount(items, customer, subtotal) {
    let totalDiscount = 0;
    const appliedPromos = [];

    if (!items || items.length === 0) return { totalDiscount: 0, appliedPromos: [] };

    // Sort promos to apply most aggressive/specific ones first
    const sortedPromos = this.sortPromosByPriority(this.promos);

    for (const promo of sortedPromos) {
      if (!this.isPromoValid(promo)) continue;

      const minPurchase = parseFloat(promo.min_purchase_amount || 0);
      if (minPurchase > 0 && subtotal < minPurchase) continue;

      let eligibleItems = items;
      
      // Handle Applicability
      if (promo.applicable_to === 'PRODUCT') {
        const targetIds = Array.isArray(promo.target_ids) ? promo.target_ids : [];
        eligibleItems = items.filter(i => targetIds.includes(String(i.productId)));
      } else if (promo.applicable_to === 'CATEGORY') {
        const targetIds = Array.isArray(promo.target_ids) ? promo.target_ids : [];
        eligibleItems = items.filter(i => targetIds.includes(String(i.categoryId)));
      }

      if (eligibleItems.length === 0 && promo.applicable_to !== 'ALL') continue;

      const baseAmount = eligibleItems.reduce((sum, i) => sum + (i.quantity * parseFloat(i.unitPrice)), 0);
      const discountValue = parseFloat(promo.discount_value || 0);
      let discount = 0;

      switch (promo.promo_type) {
        case 'PERCENTAGE':
          discount = Math.floor(baseAmount * (discountValue / 100));
          break;

        case 'FIXED':
          discount = Math.min(discountValue, baseAmount);
          break;

        case 'TIERED':
          discount = this.applyTieredDiscount(baseAmount, promo);
          break;

        case 'BUNDLING':
          discount = this.applyBundlingDiscount(eligibleItems, promo);
          break;

        case 'FLASH_SALE':
          discount = this.applyFlashSaleDiscount(eligibleItems, promo);
          break;
      }

      const maxDiscount = parseFloat(promo.max_discount_per_transaction || 0);
      if (maxDiscount > 0) {
        discount = Math.min(discount, maxDiscount);
      }

      if (discount > 0) {
        totalDiscount += discount;
        appliedPromos.push({
          promoId: promo.id,
          promoName: promo.name,
          discountAmount: discount
        });
        
        // If it's a flash sale or specific type, we might want to stop or mark items as discounted
        // For simplicity, we currently allow stacking unless logic here changes
      }
    }

    return {
      totalDiscount: Math.min(totalDiscount, subtotal),
      appliedPromos
    };
  }

  applyBundlingDiscount(items, promo) {
    if (!promo.promo_config?.rules) return 0;
    
    let matchedCount = Infinity; // Find how many times the whole bundle can be satisfied
    
    for (const rule of promo.promo_config.rules) {
      const item = items.find(i => i.productId === rule.productId);
      if (!item || item.quantity < rule.minQty) {
        matchedCount = 0;
        break;
      }
      matchedCount = Math.min(matchedCount, Math.floor(item.quantity / rule.minQty));
    }
    
    if (matchedCount === Infinity) matchedCount = 0;
    return matchedCount * parseFloat(promo.promo_config.bundleDiscount || 0);
  }

  applyTieredDiscount(amount, promo) {
    const tiers = Array.isArray(promo.promo_config?.tiers) ? promo.promo_config.tiers : [];
    const tier = [...tiers]
      .sort((a, b) => b.minAmount - a.minAmount) // Check highest tier first
      .find(t => amount >= parseFloat(t.minAmount));
      
    return tier ? Math.floor(amount * (parseFloat(tier.discountPercent) / 100)) : 0;
  }

  applyFlashSaleDiscount(items, promo) {
    const targetIds = Array.isArray(promo.target_ids) ? promo.target_ids : [];
    const eligible = items.filter(i => targetIds.includes(String(i.productId)));
    const amount = eligible.reduce((sum, i) => sum + (i.quantity * parseFloat(i.unitPrice)), 0);
    const discountValue = parseFloat(promo.discount_value || 0);
    return Math.floor(amount * (discountValue / 100));
  }

  isPromoValid(promo) {
    if (!promo || !promo.is_active) return false;
    const now = new Date();
    const from = new Date(promo.valid_from);
    const until = new Date(promo.valid_until);
    return from <= now && until >= now;
  }

  sortPromosByPriority(promos) {
    const priority = { 'FLASH_SALE': 1, 'BUNDLING': 2, 'TIERED': 3, 'PERCENTAGE': 4, 'FIXED': 5 };
    return [...promos].sort((a, b) =>
      (priority[a.promo_type] || 99) - (priority[b.promo_type] || 99)
    );
  }
}

