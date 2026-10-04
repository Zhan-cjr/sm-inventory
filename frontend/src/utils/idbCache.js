// IndexedDB Helper for Large POS Caches (Products, etc.)

export const idbCache = {
  async open() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open('POSCacheDB', 1);
      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains('caches')) {
          db.createObjectStore('caches');
        }
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  },
  async set(key, data) {
    try {
      const db = await this.open();
      return new Promise((resolve, reject) => {
        const tx = db.transaction('caches', 'readwrite');
        const store = tx.objectStore('caches');
        const req = store.put(data, key);
        req.onsuccess = () => resolve();
        req.onerror = () => reject(req.error);
      });
    } catch (e) {
      console.warn('IDB Set Error:', e);
    }
  },
  async get(key) {
    try {
      const db = await this.open();
      return new Promise((resolve, reject) => {
        const tx = db.transaction('caches', 'readonly');
        const store = tx.objectStore('caches');
        const req = store.get(key);
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
      });
    } catch (e) {
      console.warn('IDB Get Error:', e);
      return null;
    }
  }
};

export default idbCache;
