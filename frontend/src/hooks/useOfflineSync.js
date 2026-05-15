import { useEffect, useState, useRef, useCallback } from 'react';

export const useOfflineSync = (branchId, authToken) => {
  const [syncStatus, setSyncStatus] = useState('idle');
  const [syncError, setSyncError] = useState(null);
  const [pendingCount, setPendingCount] = useState(0);
  const db = useRef(null);

  useEffect(() => {
    const initDB = async () => {
      return new Promise((resolve, reject) => {
        const request = indexedDB.open('PosDatabase', 1);
        
        request.onupgradeneeded = (e) => {
          const database = e.target.result;
          if (!database.objectStoreNames.contains('transactions')) {
            database.createObjectStore('transactions', { keyPath: 'localId' });
            database.createObjectStore('stocks', { keyPath: 'id' });
            database.createObjectStore('promos', { keyPath: 'id' });
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

  const storeLocalTransaction = useCallback(async (transaction) => {
    if (!db.current) return null;

    const localTx = {
      localId: `${branchId}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
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

  const cachePromos = useCallback(async (promos) => {
    if (!db.current) return;
    return new Promise((resolve) => {
      const store = db.current.transaction('promos', 'readwrite').objectStore('promos');
      store.clear();
      promos.forEach(promo => store.add(promo));
      resolve();
    });
  }, []);

  const cacheStocks = useCallback(async (stocks) => {
    if (!db.current) return;
    return new Promise((resolve) => {
      const store = db.current.transaction('stocks', 'readwrite').objectStore('stocks');
      store.clear();
      stocks.forEach(stock => store.add(stock));
      resolve();
    });
  }, []);

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

      for (const syncedId of result.syncedIds) {
        await updateLocalTransactionStatus(syncedId, 'SYNCED');
      }

      if (result.latestPromos) await cachePromos(result.latestPromos);
      if (result.latestStocks) await cacheStocks(result.latestStocks);

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
      return { synced: 0, conflicts: [] };
    }
  }, [getPendingTransactions, authToken, branchId, updateLocalTransactionStatus, cachePromos, cacheStocks]);

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
