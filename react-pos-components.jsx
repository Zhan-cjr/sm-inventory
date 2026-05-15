/**
 * REACT PWA IMPLEMENTATION
 * Production-ready POS Frontend dengan offline-first capability
 */

// ============================================================================
// 1. OFFLINE TRANSACTION MANAGER (useOfflineSync Hook)
// ============================================================================

import { useEffect, useState, useCallback } from 'react';

/**
 * Custom hook untuk manage offline transactions dan sync
 */
export const useOfflineSync = (branchId, authToken) => {
  const [syncStatus, setSyncStatus] = useState('idle'); // idle, syncing, success, error
  const [syncError, setSyncError] = useState(null);
  const [pendingCount, setPendingCount] = useState(0);
  const db = useRef(null);

  // Initialize IndexedDB
  useEffect(() => {
    const initDB = async () => {
      return new Promise((resolve, reject) => {
        const request = indexedDB.open('PosDatabase', 1);
        
        request.onupgradeneeded = (e) => {
          const db = e.target.result;
          if (!db.objectStoreNames.contains('transactions')) {
            db.createObjectStore('transactions', { keyPath: 'localId' });
            db.createObjectStore('stocks', { keyPath: 'id' });
            db.createObjectStore('promos', { keyPath: 'id' });
          }
        };
        
        request.onsuccess = () => {
          db.current = request.result;
          resolve(request.result);
        };
        
        request.onerror = () => reject(request.error);
      });
    };

    initDB().catch(err => console.error('IndexedDB init failed:', err));
  }, []);

  // Store transaction locally
  const storeLocalTransaction = useCallback(async (transaction) => {
    if (!db.current) return null;

    const localTx = {
      localId: `${branchId}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
      branchId,
      ...transaction,
      syncStatus: 'PENDING',
      createdAt: new Date().toISOString()
    };

    return new Promise((resolve, reject) => {
      const store = db.current
        .transaction('transactions', 'readwrite')
        .objectStore('transactions');
      
      const req = store.add(localTx);
      req.onsuccess = () => {
        setPendingCount(prev => prev + 1);
        resolve(localTx);
      };
      req.onerror = () => reject(req.error);
    });
  }, [branchId]);

  // Get pending transactions
  const getPendingTransactions = useCallback(async () => {
    if (!db.current) return [];

    return new Promise((resolve, reject) => {
      const store = db.current
        .transaction('transactions', 'readonly')
        .objectStore('transactions');
      
      const req = store.getAll();
      req.onsuccess = () => {
        const pending = req.result.filter(tx => tx.syncStatus === 'PENDING');
        resolve(pending);
      };
      req.onerror = () => reject(req.error);
    });
  }, []);

  // Sync dengan server
  const syncTransactions = useCallback(async () => {
    setSyncStatus('syncing');
    setSyncError(null);

    try {
      const pendingTxs = await getPendingTransactions();
      
      if (pendingTxs.length === 0) {
        setSyncStatus('idle');
        return { synced: 0, conflicts: [] };
      }

      const response = await fetch('/api/v1/transactions/batch-sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`,
          'X-Device-ID': navigator.userAgent.split('/')[0],
        },
        body: JSON.stringify({
          transactions: pendingTxs,
          deviceId: branchId,
          branchId: branchId,
          syncTimestamp: new Date().toISOString()
        })
      });

      if (!response.ok) {
        throw new Error(`Sync failed: ${response.statusText}`);
      }

      const result = await response.json();

      // Update local cache
      for (const syncedId of result.syncedIds) {
        await updateLocalTransactionStatus(syncedId, 'SYNCED');
      }

      // Invalidate cache
      await cachePromos(result.latestPromos);
      await cacheStocks(result.latestStocks);

      setPendingCount(prev => Math.max(0, prev - result.syncedIds.length));
      setSyncStatus('success');

      return {
        synced: result.syncedIds.length,
        conflicts: result.conflicts
      };

    } catch (error) {
      console.error('Sync error:', error);
      setSyncError(error.message);
      setSyncStatus('error');
      
      // Retry in 5 minutes
      setTimeout(() => syncTransactions(), 300000);
      
      return { synced: 0, conflicts: [] };
    }
  }, [getPendingTransactions, authToken, branchId]);

  // Update local transaction status
  const updateLocalTransactionStatus = useCallback(async (localId, status) => {
    if (!db.current) return;

    return new Promise((resolve, reject) => {
      const store = db.current
        .transaction('transactions', 'readwrite')
        .objectStore('transactions');
      
      const req = store.get(localId);
      req.onsuccess = () => {
        const tx = req.result;
        tx.syncStatus = status;
        store.put(tx);
        resolve();
      };
      req.onerror = () => reject(req.error);
    });
  }, []);

  // Cache promos locally
  const cachePromos = useCallback(async (promos) => {
    if (!db.current) return;

    return new Promise((resolve) => {
      const store = db.current
        .transaction('promos', 'readwrite')
        .objectStore('promos');
      
      store.clear();
      promos.forEach(promo => store.add(promo));
      resolve();
    });
  }, []);

  // Cache stocks locally
  const cacheStocks = useCallback(async (stocks) => {
    if (!db.current) return;

    return new Promise((resolve) => {
      const store = db.current
        .transaction('stocks', 'readwrite')
        .objectStore('stocks');
      
      store.clear();
      stocks.forEach(stock => store.add(stock));
      resolve();
    });
  }, []);

  return {
    syncStatus,
    syncError,
    pendingCount,
    storeLocalTransaction,
    syncTransactions,
    getPendingTransactions,
    cachePromos,
    cacheStocks
  };
};

// ============================================================================
// 2. POS TRANSACTION COMPONENT
// ============================================================================

import React, { useState, useRef } from 'react';

export const POSTransaction = ({ branchId, authToken }) => {
  const [items, setItems] = useState([]);
  const [discounts, setDiscounts] = useState([]);
  const [paymentMethod, setPaymentMethod] = useState('CASH');
  const [isProcessing, setIsProcessing] = useState(false);
  const barcodeInput = useRef(null);
  
  const { storeLocalTransaction, syncTransactions } = useOfflineSync(branchId, authToken);
  const discountEngine = useRef(new DiscountEngine([]));

  // Handle barcode scan
  const handleBarcodeScan = async (barcode) => {
    try {
      // Try to fetch product from server
      const response = await fetch(`/api/v1/products/search?barcode=${barcode}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });

      if (response.ok) {
        const product = await response.json();
        addItemToTransaction(product);
      } else {
        // Try local cache
        const localProduct = await getLocalProductByBarcode(barcode);
        if (localProduct) {
          addItemToTransaction(localProduct);
        } else {
          alert('Produk tidak ditemukan');
        }
      }
    } catch (error) {
      console.error('Product search failed:', error);
    }

    barcodeInput.current?.focus();
  };

  const addItemToTransaction = (product) => {
    const existingItem = items.find(i => i.productId === product.id);
    
    if (existingItem) {
      setItems(items.map(i => 
        i.productId === product.id 
          ? { ...i, quantity: i.quantity + 1 }
          : i
      ));
    } else {
      setItems([...items, {
        productId: product.id,
        sku: product.sku,
        name: product.name,
        quantity: 1,
        unitPrice: product.selling_price,
        discountPerItem: 0
      }]);
    }
  };

  const removeItem = (productId) => {
    setItems(items.filter(i => i.productId !== productId));
  };

  const updateQuantity = (productId, quantity) => {
    if (quantity <= 0) {
      removeItem(productId);
    } else {
      setItems(items.map(i =>
        i.productId === productId
          ? { ...i, quantity }
          : i
      ));
    }
  };

  // Calculate totals with discounts
  const subtotal = items.reduce((sum, item) => 
    sum + (item.quantity * item.unitPrice), 0
  );

  const { totalDiscount, appliedPromos } = discountEngine.current.calculateTotalDiscount(
    items,
    { memberTier: 'REGULAR' },
    subtotal
  );

  const finalAmount = subtotal - totalDiscount;

  // Process transaction
  const processTransaction = async () => {
    if (items.length === 0) {
      alert('Tambahkan item terlebih dahulu');
      return;
    }

    setIsProcessing(true);

    try {
      const transaction = {
        items,
        totalAmount: subtotal,
        discountAmount: totalDiscount,
        finalAmount,
        paymentMethod,
        appliedPromos
      };

      // Store locally first (offline-first pattern)
      const localTx = await storeLocalTransaction(transaction);

      // Try to sync immediately if online
      if (navigator.onLine) {
        const syncResult = await syncTransactions();
        if (syncResult.synced > 0) {
          showSuccessMessage('Transaksi berhasil disimpan');
        } else if (syncResult.conflicts.length > 0) {
          showWarningMessage('Transaksi disimpan lokal, akan disinkronisasi nanti');
        }
      } else {
        showInfoMessage('Mode offline: Transaksi akan disinkronisasi saat online');
      }

      // Reset form
      setItems([]);
      setDiscounts([]);
      barcodeInput.current?.focus();

    } catch (error) {
      console.error('Transaction error:', error);
      alert(`Error: ${error.message}`);
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <div className="pos-terminal">
      <div className="pos-header">
        <h2>POS Terminal - {branchId}</h2>
        <div className="connection-status">
          {navigator.onLine ? '🟢 Online' : '🔴 Offline'}
        </div>
      </div>

      {/* Barcode Input */}
      <div className="barcode-section">
        <input
          ref={barcodeInput}
          type="text"
          placeholder="Scan barcode atau ketik SKU..."
          onKeyPress={(e) => {
            if (e.key === 'Enter') {
              handleBarcodeScan(e.target.value);
              e.target.value = '';
            }
          }}
          autoFocus
          className="barcode-input"
        />
      </div>

      {/* Items Cart */}
      <div className="items-section">
        <h3>Item Transaksi</h3>
        <table className="items-table">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Qty</th>
              <th>Harga</th>
              <th>Subtotal</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {items.map(item => (
              <tr key={item.productId}>
                <td>{item.name}</td>
                <td>
                  <input
                    type="number"
                    min="1"
                    value={item.quantity}
                    onChange={(e) => updateQuantity(item.productId, parseInt(e.target.value))}
                    className="qty-input"
                  />
                </td>
                <td>Rp {item.unitPrice.toLocaleString('id-ID')}</td>
                <td>Rp {(item.quantity * item.unitPrice).toLocaleString('id-ID')}</td>
                <td>
                  <button onClick={() => removeItem(item.productId)}>Hapus</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Summary */}
      <div className="summary-section">
        <div className="summary-row">
          <span>Subtotal:</span>
          <span>Rp {subtotal.toLocaleString('id-ID')}</span>
        </div>
        {totalDiscount > 0 && (
          <div className="summary-row discount">
            <span>Diskon:</span>
            <span>-Rp {totalDiscount.toLocaleString('id-ID')}</span>
          </div>
        )}
        <div className="summary-row total">
          <span>Total:</span>
          <span>Rp {finalAmount.toLocaleString('id-ID')}</span>
        </div>
      </div>

      {/* Applied Promos */}
      {appliedPromos.length > 0 && (
        <div className="promos-section">
          <h4>Promo yang Berlaku:</h4>
          <ul>
            {appliedPromos.map(promo => (
              <li key={promo.promoId}>
                {promo.promoName}: -Rp {promo.discountAmount.toLocaleString('id-ID')}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Payment Method */}
      <div className="payment-section">
        <label>Metode Pembayaran:</label>
        <select 
          value={paymentMethod}
          onChange={(e) => setPaymentMethod(e.target.value)}
        >
          <option value="CASH">Tunai</option>
          <option value="CARD">Kartu Kredit</option>
          <option value="EWALLET">E-Wallet</option>
        </select>
      </div>

      {/* Action Buttons */}
      <div className="actions-section">
        <button 
          onClick={() => setItems([])}
          className="btn-secondary"
          disabled={isProcessing}
        >
          Batal
        </button>
        <button 
          onClick={processTransaction}
          className="btn-primary"
          disabled={isProcessing || items.length === 0}
        >
          {isProcessing ? 'Memproses...' : 'Bayar'}
        </button>
      </div>
    </div>
  );
};

// ============================================================================
// 3. DISCOUNT ENGINE (Frontend Kalkulasi)
// ============================================================================

export class DiscountEngine {
  constructor(promos) {
    this.promos = promos;
  }

  calculateTotalDiscount(items, customer, subtotal) {
    let totalDiscount = 0;
    const appliedPromos = [];

    for (const promo of this.sortPromosByPriority(this.promos)) {
      if (!this.isPromoValid(promo)) continue;

      let discount = 0;

      switch (promo.promo_type) {
        case 'PERCENTAGE':
          discount = Math.floor(subtotal * promo.discount_value / 100);
          break;

        case 'FIXED':
          discount = Math.min(promo.discount_value, subtotal - totalDiscount);
          break;

        case 'TIERED':
          discount = this.applyTieredDiscount(subtotal - totalDiscount, promo);
          break;

        case 'BUNDLING':
          discount = this.applyBundlingDiscount(items, promo);
          break;

        case 'FLASH_SALE':
          discount = this.applyFlashSaleDiscount(items, promo);
          break;
      }

      // Cap discount
      if (promo.max_discount_per_transaction) {
        discount = Math.min(discount, promo.max_discount_per_transaction);
      }

      if (discount > 0) {
        totalDiscount += discount;
        appliedPromos.push({
          promoId: promo.id,
          promoName: promo.name,
          discountAmount: discount
        });
      }
    }

    return {
      totalDiscount: Math.min(totalDiscount, subtotal),
      appliedPromos
    };
  }

  applyBundlingDiscount(items, promo) {
    let matchedCount = 0;
    for (const bundleRule of promo.promo_config?.rules || []) {
      const allMatched = bundleRule.requiredItems.every(required =>
        items.find(i => i.productId === required.productId && i.quantity >= required.minQty)
      );
      if (allMatched) matchedCount++;
    }
    return matchedCount * (promo.promo_config?.bundleDiscount || 0);
  }

  applyTieredDiscount(amount, promo) {
    const tier = (promo.promo_config?.tiers || []).find(t =>
      amount >= t.minAmount && (!t.maxAmount || amount <= t.maxAmount)
    );
    return tier ? Math.floor(amount * tier.discountPercent / 100) : 0;
  }

  applyFlashSaleDiscount(items, promo) {
    const eligible = items.filter(i => promo.target_ids?.includes(i.productId));
    const amount = eligible.reduce((sum, i) => sum + (i.quantity * i.unitPrice), 0);
    return Math.min(
      Math.floor(amount * promo.discount_value / 100),
      promo.max_discount_per_transaction || Infinity
    );
  }

  isPromoValid(promo) {
    const now = new Date();
    return promo.id && // Check ID exists
           new Date(promo.valid_from) <= now &&
           new Date(promo.valid_until) >= now;
  }

  sortPromosByPriority(promos) {
    const priority = { 'FLASH_SALE': 1, 'BUNDLING': 2, 'TIERED': 3, 'PERCENTAGE': 4, 'FIXED': 5 };
    return [...promos].sort((a, b) =>
      (priority[a.promo_type] || 999) - (priority[b.promo_type] || 999)
    );
  }
}

// ============================================================================
// 4. SERVICE WORKER REGISTRATION (offline caching)
// ============================================================================

export const registerServiceWorker = async () => {
  if ('serviceWorker' in navigator) {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js');
      console.log('Service Worker registered:', registration);
      
      // Listen for updates
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            console.log('New Service Worker available');
            // Prompt user to refresh
          }
        });
      });

      return registration;
    } catch (error) {
      console.error('Service Worker registration failed:', error);
    }
  }
};

// Service Worker Code (sw.js)
export const SW_CODE = `
const CACHE_VERSION = 'v1';
const ASSETS_TO_CACHE = [
  '/',
  '/index.html',
  '/css/main.css',
  '/js/main.js',
  '/images/logo.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((versions) => {
      return Promise.all(
        versions
          .filter((version) => version !== CACHE_VERSION)
          .map((version) => caches.delete(version))
      );
    })
  );
});

self.addEventListener('fetch', (event) => {
  // Network-first for API calls
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          if (response.ok) {
            caches.open(CACHE_VERSION).then((cache) => {
              cache.put(event.request, response.clone());
            });
          }
          return response;
        })
        .catch(() => {
          return caches.match(event.request)
            .then((response) => response || new Response('Offline', { status: 503 }));
        })
    );
  } else {
    // Cache-first for static assets
    event.respondWith(
      caches.match(event.request)
        .then((response) => response || fetch(event.request))
        .catch(() => new Response('Offline', { status: 503 }))
    );
  }
});
`;
