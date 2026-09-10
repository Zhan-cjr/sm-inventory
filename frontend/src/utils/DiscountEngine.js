export class DiscountEngine {
  constructor(promos) {
    this.promos = Array.isArray(promos) ? promos : [];
    console.log('DiscountEngine initialized with promos:', this.promos);
  }

  calculateTotalDiscount(items, customer, subtotal) {
    let totalDiscount = 0;
    const appliedPromos = [];

    if (!items || items.length === 0) return { totalDiscount: 0, appliedPromos: [] };

    // Reset accumulated discountPerItem and promotionId on each render to prevent React mutation accumulation
    items.forEach(item => {
      item.discountPerItem = 0;
      item.discount_per_item = 0; // Clear snake_case artifact
      item.promotionId = null;
    });

    // Apply base tier discount first
    if (customer && customer.tierDiscountPercent && customer.tierDiscountPercent > 0) {
      const tierDiscountValue = Math.floor(subtotal * (parseFloat(customer.tierDiscountPercent) / 100));
      if (tierDiscountValue > 0) {
        totalDiscount += tierDiscountValue;
        appliedPromos.push({
          promoId: `TIER_${customer.memberTier}`,
          promoName: `Diskon Member ${customer.memberTier} (${customer.tierDiscountPercent}%)`,
          discountAmount: tierDiscountValue
        });
        subtotal -= tierDiscountValue; // reduce subtotal for subsequent promos
      }
    }

    // Sort promos to apply most aggressive/specific ones first
    const sortedPromos = this.sortPromosByPriority(this.promos);

    for (const promo of sortedPromos) {
      if (!this.isPromoValid(promo, customer)) continue;

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
      const limitType = promo.promo_config?.discount_limit_type || 'PER_TRANSACTION';
      const maxDiscount = parseFloat(promo.max_discount_per_transaction || 0);

      if (promo.promo_type === 'PWP') {
        discount = this.applyPwpDiscount(items, promo);
        if (maxDiscount > 0) {
          discount = Math.min(discount, maxDiscount);
        }
      } else if (limitType === 'PER_ITEM' && maxDiscount > 0 && (promo.promo_type === 'PERCENTAGE' || promo.promo_type === 'FLASH_SALE')) {
        // Calculate discount per item line and cap it
        discount = eligibleItems.reduce((sum, item) => {
           let itemDiscount = Math.floor((item.quantity * parseFloat(item.unitPrice)) * (discountValue / 100));
           const appliedItemDiscount = Math.min(itemDiscount, maxDiscount);
           item.discountPerItem = (item.discountPerItem || 0) + (appliedItemDiscount / item.quantity);
           item.promotionId = promo.id;
           return sum + appliedItemDiscount; 
        }, 0);
      } else {
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

          case 'PERCENTAGE_PER_ITEM':
          case 'NOMINAL_PER_ITEM':
            discount = this.applyPerItemDiscount(eligibleItems, promo, promo.promo_type);
            break;
        }

        if (maxDiscount > 0) {
          discount = Math.min(discount, maxDiscount);
        }
        
        // Distribute discount to items so backend can record promotion_id
        if (discount > 0 && eligibleItems.length > 0 && promo.promo_type !== 'PWP') {
            let remainingDiscount = discount;
            eligibleItems.forEach((item, index) => {
                const itemBase = item.quantity * parseFloat(item.unitPrice);
                const portion = baseAmount > 0 ? Math.floor((itemBase / baseAmount) * discount) : Math.floor(discount / eligibleItems.length);
                const applied = (index === eligibleItems.length - 1) ? remainingDiscount : portion;
                item.discountPerItem = (item.discountPerItem || 0) + (applied / item.quantity);
                item.promotionId = promo.id;
                remainingDiscount -= applied;
            });
        }
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
      totalDiscount: subtotal > 0 ? Math.min(totalDiscount, subtotal) : 0,
      appliedPromos
    };
  }

  applyBundlingDiscount(items, promo) {
    if (!promo.promo_config?.rules) return 0;
    
    let matchedCount = Infinity; // Find how many times the whole bundle can be satisfied
    
    for (const rule of promo.promo_config.rules) {
      const item = items.find(i => String(i.productId) === String(rule.productId));
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

  applyPerItemDiscount(items, promo, type) {
    let discount = 0;
    const discountValue = parseFloat(promo.discount_value || 0);
    
    if (type === 'PERCENTAGE_PER_ITEM') {
        const amount = items.reduce((sum, i) => sum + (i.quantity * parseFloat(i.unitPrice)), 0);
        discount = Math.floor(amount * (discountValue / 100));
    } else if (type === 'NOMINAL_PER_ITEM') {
        const totalQuantity = items.reduce((sum, i) => sum + i.quantity, 0);
        discount = discountValue * totalQuantity;
    }
    return discount;
  }

  applyPwpDiscount(items, promo) {
    const config = promo.promo_config;
    if (!config || !config.pwp_trigger_product_id || !config.pwp_reward_product_id) return 0;

    const triggerId = String(config.pwp_trigger_product_id);
    const rewardId = String(config.pwp_reward_product_id);
    const triggerMinQty = Math.max(1, parseInt(config.pwp_trigger_min_qty, 10) || 1);
    const rewardPerTrigger = Math.max(1, parseInt(config.pwp_reward_qty_per_trigger, 10) || 1);
    const maxRewardPerTransaction = config.pwp_max_reward_per_transaction 
      ? parseInt(config.pwp_max_reward_per_transaction, 10) 
      : Infinity;

    const triggerItem = items.find(i => String(i.productId) === triggerId);
    const rewardItem = items.find(i => String(i.productId) === rewardId);

    if (!triggerItem || !rewardItem) return 0;
    if (triggerItem.quantity < triggerMinQty || rewardItem.quantity <= 0) return 0;

    // Hitung berapa kuota reward yang berhak ditebus berdasarkan kuantitas trigger dan batasan transaksi
    const triggersEarned = Math.floor(triggerItem.quantity / triggerMinQty);
    const maxRewardAllowed = Math.min(triggersEarned * rewardPerTrigger, maxRewardPerTransaction);

    // Kuantitas reward yang benar-benar ada di keranjang untuk diberikan diskon
    const eligibleRewardQty = Math.min(rewardItem.quantity, maxRewardAllowed);
    if (eligibleRewardQty <= 0) return 0;

    const rewardUnitPrice = parseFloat(rewardItem.unitPrice || 0);
    const discountType = config.pwp_discount_type || 'SPECIAL_PRICE';
    const discountVal = parseFloat(config.pwp_discount_value || 0);

    let unitDiscount = 0;
    if (discountType === 'SPECIAL_PRICE') {
      // Harga tebus murah (contoh: harga normal 18.000, ditebus 10.000 -> diskon 8.000 per unit)
      unitDiscount = Math.max(0, rewardUnitPrice - discountVal);
    } else if (discountType === 'DISCOUNT_NOMINAL') {
      unitDiscount = Math.min(rewardUnitPrice, discountVal);
    } else if (discountType === 'DISCOUNT_PERCENT') {
      unitDiscount = Math.floor(rewardUnitPrice * (Math.min(100, Math.max(0, discountVal)) / 100));
    }

    const totalDiscount = Math.floor(eligibleRewardQty * unitDiscount);

    if (totalDiscount > 0) {
      // Alokasikan diskon ke rewardItem agar tercatat di struk dan backend
      rewardItem.discountPerItem = (rewardItem.discountPerItem || 0) + (totalDiscount / rewardItem.quantity);
      rewardItem.promotionId = promo.id;
    }

    return totalDiscount;
  }

  isPromoValid(promo, customer) {
    if (!promo || !promo.is_active) return false;
    const now = new Date();
    const from = new Date(promo.valid_from);
    const until = new Date(promo.valid_until);
    if (from > now || until < now) return false;

    // Check member tiers if specified in promo_config
    if (promo.promo_config?.member_tiers && promo.promo_config.member_tiers.length > 0) {
      const customerTier = customer?.memberTier || 'REGULAR';
      if (!promo.promo_config.member_tiers.includes(customerTier)) {
        return false;
      }
    }

    // Check applicable days if specified (e.g. ['MONDAY', 'FRIDAY'])
    if (promo.promo_config?.applicable_days && promo.promo_config.applicable_days.length > 0) {
      const dayNames = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
      const currentDayName = dayNames[now.getDay()];
      if (!promo.promo_config.applicable_days.includes(currentDayName)) {
        return false;
      }
    }

    return true;
  }

  sortPromosByPriority(promos) {
    const priority = { 'PWP': 0, 'PERCENTAGE_PER_ITEM': 1, 'NOMINAL_PER_ITEM': 2, 'BUNDLING': 3, 'TIERED': 4, 'PERCENTAGE': 5, 'FIXED': 6 };
    return [...promos].sort((a, b) =>
      (priority[a.promo_type] || 99) - (priority[b.promo_type] || 99)
    );
  }
}

