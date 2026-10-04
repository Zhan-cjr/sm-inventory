import { useState, useEffect } from 'react';
import { initEcho } from '../utils/echo';
import { idbCache } from '../utils/idbCache';
import { DiscountEngine } from '../utils/DiscountEngine';

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    console.warn(`[Storage Warning] Failed to save key "${key}" to localStorage:`, e);
  }
};

export const usePosCatalog = ({
  branchId,
  branchName,
  authToken,
  lockedTerminalId,
  lockedTerminalName,
  userRole,
  isOnline,
  discountEngine,
  setItems,
  checkActiveShift,
  setActiveShift,
  setTerminalInfo,
  setIsCheckingShift,
  setIsTerminalModalOpen,
  setIsOpenShiftModalOpen,
  setBranchMismatchInfo
}) => {
  const [dbProducts, setDbProducts] = useState([]);
  const [dbPromos, setDbPromos] = useState([]);
  const [banks, setBanks] = useState(() => {
    const cached = localStorage.getItem('pos_cached_banks');
    return cached ? JSON.parse(cached) : [];
  });
  const [branchSettings, setBranchSettings] = useState(() => {
    const cached = localStorage.getItem('pos_cached_branch_settings');
    return cached ? JSON.parse(cached) : null;
  });
  const [posSettings, setPosSettings] = useState(() => {
    const cached = localStorage.getItem('pos_cached_settings');
    return cached ? JSON.parse(cached) : null;
  });
  const [customers, setCustomers] = useState(() => {
    const cached = localStorage.getItem('pos_cached_customers');
    return cached ? JSON.parse(cached) : [];
  });
  const [aprioriRules, setAprioriRules] = useState([]);
  const [allTerminals, setAllTerminals] = useState(() => {
    const cached = localStorage.getItem('pos_cached_terminals');
    return cached ? JSON.parse(cached) : [];
  });
  const [serverOffset, setServerOffset] = useState(() =>
    parseInt(localStorage.getItem('pos_server_offset') || '0', 10)
  );

  // --- Real-time WebSockets Integration (Laravel Reverb) ---
  useEffect(() => {
    if (branchId && authToken) {
      let echoInstance = null;
      try {
        echoInstance = initEcho(authToken);

        // Listen for Stock Updates
        echoInstance
          .private(`branch.${branchId}.stock`)
          .listen('.stock.updated', (e) => {
            console.log('[WebSockets] StockUpdated received:', e);
            setDbProducts((prev) => {
              const updated = prev.map((p) => {
                if (p.id === e.product_id) {
                  const updatedStockData = { ...p, stock: e.quantity_on_hand };
                  if (
                    e.selling_price !== null &&
                    e.selling_price !== undefined &&
                    parseFloat(e.selling_price) > 0
                  ) {
                    updatedStockData.selling_price = e.selling_price;
                  }
                  return updatedStockData;
                }
                return p;
              });

              idbCache.set('pos_cached_products', updated).catch(() => {});
              return updated;
            });

            // Perbarui juga harga keranjang jika dipengaruhi harga cabang (stock override)
            if (
              e.selling_price !== null &&
              e.selling_price !== undefined &&
              parseFloat(e.selling_price) > 0
            ) {
              setItems((prevItems) =>
                prevItems.map((item) => {
                  if (item.productId === e.product_id) {
                    return { ...item, unitPrice: parseFloat(e.selling_price) };
                  }
                  return item;
                })
              );
            }
          });

        // Listen for Transaction Creations (Other Terminals)
        echoInstance
          .private(`branch.${branchId}.transactions`)
          .listen('.transaction.created', (e) => {
            console.log('[WebSockets] TransactionCreated received from another terminal:', e);
          });

        // Listen for Global Catalog Updates (e.g. price change)
        echoInstance.channel(`catalog`).listen('.product.updated', (e) => {
          console.log('[WebSockets] ProductUpdated received (Price/Details change):', e);
          if (e.product) {
            setDbProducts((prev) => {
              const updated = prev.map((p) => {
                if (p.id === e.product.id) {
                  return { ...p, ...e.product, stock: p.stock };
                }
                return p;
              });
              idbCache.set('pos_cached_products', updated).catch(() => {});
              return updated;
            });
          }
        });
      } catch (err) {
        console.warn('Failed to initialize WebSockets connection:', err);
      }

      return () => {
        if (echoInstance) {
          echoInstance.leave(`branch.${branchId}.stock`);
          echoInstance.leave(`branch.${branchId}.transactions`);
          echoInstance.leave(`catalog`);
          echoInstance.disconnect();
        }
      };
    }
  }, [branchId, authToken, setItems]);

  // --- Initial & Periodic Catalog Synchronization ---
  useEffect(() => {
    fetch('/api/v1/server-time', {
      headers: { Authorization: `Bearer ${authToken}` }
    })
      .then((res) => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then((data) => {
        const serverMs = data.timestamp;
        const clientMs = Date.now();
        const offset = serverMs - clientMs;
        setServerOffset(offset);
        safeSetItem('pos_server_offset', offset.toString());
        safeSetItem('pos_last_sync_time', serverMs.toString());
      })
      .catch((err) => console.error('Failed to sync server time:', err));

    const fetchCatalog = () => {
      fetch('/api/v1/products', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          if (Array.isArray(data)) {
            setDbProducts((prev) => {
              const services = prev.filter((p) => p.is_service);
              return [...data, ...services];
            });
            idbCache.set('pos_cached_products', data);
          }
        })
        .catch(async (err) => {
          console.warn('Failed to load products, using cache:', err);
          const parsed = await idbCache.get('pos_cached_products');
          if (parsed && Array.isArray(parsed)) {
            setDbProducts((prev) => {
              const services = prev.filter((p) => p.is_service);
              return [...parsed, ...services];
            });
          }
        });

      fetch('/api/v1/promotions', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          setDbPromos(data);
          if (discountEngine?.current) {
            discountEngine.current = new DiscountEngine(data);
          }
          safeSetItem('pos_cached_promotions', JSON.stringify(data));
        })
        .catch((err) => {
          console.error('Failed to load promotions:', err);
          const cached = localStorage.getItem('pos_cached_promotions');
          if (cached) {
            try {
              const parsed = JSON.parse(cached);
              setDbPromos(parsed);
              if (discountEngine?.current) {
                discountEngine.current = new DiscountEngine(parsed);
              }
            } catch (e) {}
          }
        });

      fetch('/api/v1/banks', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          setBanks(data);
          safeSetItem('pos_cached_banks', JSON.stringify(data));
        })
        .catch((err) => {
          console.error('Failed to load banks:', err);
          const cached = localStorage.getItem('pos_cached_banks');
          if (cached) {
            try {
              setBanks(JSON.parse(cached));
            } catch (e) {}
          }
        });

      fetch('/api/v1/branches', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          if (Array.isArray(data)) {
            const activeBranch = data.find((b) => b.id === branchId) || data[0];
            if (activeBranch) {
              setBranchSettings(activeBranch);
              safeSetItem('pos_cached_branch_settings', JSON.stringify(activeBranch));
            }
          }
        })
        .catch((err) => {
          console.error('Failed to load branch settings:', err);
          const cached = localStorage.getItem('pos_cached_branch_settings');
          if (cached) {
            try {
              setBranchSettings(JSON.parse(cached));
            } catch (e) {}
          }
        });

      fetch('/api/v1/pos-settings', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          setPosSettings(data);
          safeSetItem('pos_cached_settings', JSON.stringify(data));
        })
        .catch((err) => {
          console.error('Failed to load pos settings:', err);
          const cached = localStorage.getItem('pos_cached_settings');
          if (cached) {
            try {
              setPosSettings(JSON.parse(cached));
            } catch (e) {}
          }
        });

      // Refresh user settings (e.g. allow_minus_stock) on load
      fetch('/api/v1/user', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          if (data.user) {
            const existingUser = JSON.parse(localStorage.getItem('pos_user') || '{}');
            const updatedUser = { ...existingUser, ...data.user };
            safeSetItem('pos_user', JSON.stringify(updatedUser));
          }
        })
        .catch((err) => console.error('Failed to refresh user profile:', err));

      fetch('/api/v1/customers', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          setCustomers(data);
          safeSetItem('pos_cached_customers', JSON.stringify(data));
        })
        .catch((err) => {
          console.error('Failed to load customers:', err);
          const cached = localStorage.getItem('pos_cached_customers');
          if (cached) {
            try {
              setCustomers(JSON.parse(cached));
            } catch (e) {}
          }
        });

      // Fetch Apriori Rules for AI Upselling
      fetch(`/api/v1/bi/apriori?branch_id=${branchId}`, {
        headers: { Authorization: `Bearer ${authToken}`, Accept: 'application/json' }
      })
        .then((res) => res.json())
        .then((data) => {
          if (Array.isArray(data)) {
            console.log('[AI Upsell] Rules loaded:', data);
            setAprioriRules(data);
          }
        })
        .catch((err) => console.error('[AI Upsell] Failed to load apriori rules:', err));

      fetch('/api/v1/services', {
        headers: { Authorization: `Bearer ${authToken}` }
      })
        .then((res) => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then((data) => {
          if (Array.isArray(data)) {
            const servicesAsProducts = data.map((s) => ({
              id: s.id,
              sku: s.code,
              barcode: s.code,
              name: s.name + ' (Jasa)',
              selling_price: s.price,
              category_id: null,
              is_service: true
            }));
            setDbProducts((prev) => {
              const productsOnly = prev.filter((p) => !p.is_service);
              return [...productsOnly, ...servicesAsProducts];
            });
            safeSetItem('pos_cached_services', JSON.stringify(data));
          }
        })
        .catch((err) => {
          console.warn('Failed to load services, using cache:', err);
          const cached = localStorage.getItem('pos_cached_services');
          if (cached) {
            try {
              const parsed = JSON.parse(cached);
              if (Array.isArray(parsed)) {
                const servicesAsProducts = parsed.map((s) => ({
                  id: s.id,
                  sku: s.code,
                  barcode: s.code,
                  name: s.name + ' (Jasa)',
                  selling_price: s.price,
                  category_id: null,
                  is_service: true
                }));
                setDbProducts((prev) => {
                  const productsOnly = prev.filter((p) => !p.is_service);
                  return [...productsOnly, ...servicesAsProducts];
                });
              }
            } catch (e) {}
          }
        });
    };

    fetchCatalog();
    const catalogTimer = setInterval(() => {
      if (navigator.onLine) fetchCatalog();
    }, 60000); // 1 menit

    fetch(`/api/v1/terminals?branch_id=${branchId}`, {
      headers: { Authorization: `Bearer ${authToken}` }
    })
      .then((res) => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then((data) => {
        setAllTerminals(data);
        safeSetItem('pos_cached_terminals', JSON.stringify(data));

        // Force bind locked terminal properties on startup if present
        let activeTerminalId = lockedTerminalId || localStorage.getItem('pos_terminal_id');
        if (lockedTerminalId) {
          safeSetItem('pos_terminal_id', lockedTerminalId);
          if (lockedTerminalName) safeSetItem('pos_terminal_name', lockedTerminalName);
          safeSetItem('pos_terminal_branch_id', branchId);
          safeSetItem('pos_terminal_branch_name', branchName);
        }

        if (activeTerminalId) {
          const terminal = data.find((t) => t.id === activeTerminalId);
          if (terminal) {
            setTerminalInfo(terminal);
            checkActiveShift(activeTerminalId);
          } else {
            // Mismatch: previously saved terminal belongs to another branch!
            if (userRole !== 'ADMIN') {
              localStorage.removeItem('pos_terminal_id');
              localStorage.removeItem('pos_terminal_name');
              localStorage.removeItem('pos_terminal_branch_id');
              localStorage.removeItem('pos_active_shift');
              setBranchMismatchInfo({
                userBranch: branchName || 'Cabang Anda',
                terminalBranch: 'Cabang Terminal Lain'
              });
              setIsCheckingShift(false);
            } else {
              checkActiveShift(activeTerminalId);
            }
          }
        } else {
          setIsCheckingShift(false);
          if (data.length > 0) {
            setIsTerminalModalOpen(true);
          }
        }
      })
      .catch((err) => {
        console.error('Failed to load terminals:', err);
        const cachedTerminals = localStorage.getItem('pos_cached_terminals');
        let terminals = [];
        if (cachedTerminals) {
          try {
            terminals = JSON.parse(cachedTerminals);
            setAllTerminals(terminals);
          } catch (e) {
            console.error('Failed to parse cached terminals:', e);
          }
        }

        // Force bind locked terminal properties offline if present
        let activeTerminalId = lockedTerminalId || localStorage.getItem('pos_terminal_id');
        if (lockedTerminalId) {
          safeSetItem('pos_terminal_id', lockedTerminalId);
          if (lockedTerminalName) safeSetItem('pos_terminal_name', lockedTerminalName);
          safeSetItem('pos_terminal_branch_id', branchId);
          safeSetItem('pos_terminal_branch_name', branchName);
        }

        const terminalBranchId = localStorage.getItem('pos_terminal_branch_id');

        // Check for offline branch mismatch (skip validation if locked by backend)
        if (
          !lockedTerminalId &&
          activeTerminalId &&
          terminalBranchId &&
          terminalBranchId !== branchId &&
          userRole !== 'ADMIN'
        ) {
          localStorage.removeItem('pos_terminal_id');
          localStorage.removeItem('pos_terminal_name');
          localStorage.removeItem('pos_terminal_branch_id');
          localStorage.removeItem('pos_active_shift');
          setBranchMismatchInfo({
            userBranch: branchName || 'Cabang Anda',
            terminalBranch: 'Cabang Terminal Lain'
          });
          setIsCheckingShift(false);
          return;
        }

        if (activeTerminalId) {
          const terminal = terminals.find((t) => t.id === activeTerminalId);
          if (terminal) setTerminalInfo(terminal);

          // Restore cached shift when offline
          const cachedShift = localStorage.getItem('pos_active_shift');
          if (cachedShift) {
            try {
              const parsed = JSON.parse(cachedShift);
              if (parsed && parsed.terminal_id === activeTerminalId) {
                setActiveShift(parsed);
              } else {
                setIsOpenShiftModalOpen(true);
              }
            } catch (e) {
              setIsOpenShiftModalOpen(true);
            }
          } else {
            setIsOpenShiftModalOpen(true);
          }
        } else {
          if (terminals.length > 0) {
            setIsTerminalModalOpen(true);
          }
        }
        setIsCheckingShift(false);
      });

    return () => clearInterval(catalogTimer);
  }, [authToken, branchId]);

  return {
    dbProducts,
    setDbProducts,
    dbPromos,
    setDbPromos,
    banks,
    setBanks,
    branchSettings,
    setBranchSettings,
    posSettings,
    setPosSettings,
    customers,
    setCustomers,
    aprioriRules,
    setAprioriRules,
    allTerminals,
    setAllTerminals,
    serverOffset,
    setServerOffset
  };
};

export default usePosCatalog;
