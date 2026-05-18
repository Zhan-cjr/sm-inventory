import React, { useState, useRef, useEffect } from 'react';
import { useOfflineSync } from '../hooks/useOfflineSync';
import { DiscountEngine } from '../utils/DiscountEngine';
import { AuthorizationModal } from './AuthorizationModal';
import { ReturnItemModal } from './ReturnItemModal';
import { ReceiptPreview } from './ReceiptPreview';
import {
  LogOut,
  ShoppingCart,
  Trash2,
  Minus,
  Plus,
  Tag,
  Search,
  CreditCard,
  Banknote,
  History,
  Settings,
  User,
  Wifi,
  WifiOff,
  RefreshCw,
  LogIn,
  Package,
  Calculator,
  RotateCcw,
  Eraser,
  Lock,
  Unlock,
  CheckCircle,
  X,
  Clock
} from 'lucide-react';

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    console.warn(`[Storage Warning] Failed to save key "${key}" to localStorage:`, e);
  }
};

export const POSTransaction = ({ branchId, branchName, orgName, authToken, userName, userRole, onLogout }) => {
  const [items, setItems] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('pos_active_cart') || '[]');
    } catch (e) {
      console.error('Failed to parse pos_active_cart:', e);
      return [];
    }
  });
  const [paymentMethod, setPaymentMethod] = useState('CASH');
  const [isProcessing, setIsProcessing] = useState(false);
  const [alertMsg, setAlertMsg] = useState(null);
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [serverOffset, setServerOffset] = useState(parseInt(localStorage.getItem('pos_server_offset') || '0'));
  const [currentTime, setCurrentTime] = useState(new Date(Date.now() + serverOffset));
  const barcodeInput = useRef(null);

  // New State for enhancements
  const [queuedDiscount, setQueuedDiscount] = useState(null);
  const [manualTotalDiscount, setManualTotalDiscount] = useState(0);
  const [isSubtotalMode, setIsSubtotalMode] = useState(false);
  const [receivedAmount, setReceivedAmount] = useState('');
  const [banks, setBanks] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_cached_banks') || '[]'); } catch (e) { return []; }
  });
  const [selectedBank, setSelectedBank] = useState(null);
  const [isBankSelectOpen, setIsBankSelectOpen] = useState(false);
  const [terminalInfo, setTerminalInfo] = useState({ id: localStorage.getItem('pos_terminal_id'), name: localStorage.getItem('pos_terminal_name') || 'Belum Diatur', orgName: orgName });
  const [changeModalInfo, setChangeModalInfo] = useState(null);
  const [posSettings, setPosSettings] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_cached_settings') || '[]'); } catch (e) { return []; }
  });
  const [searchResults, setSearchResults] = useState([]);
  const [highlightedIndex, setHighlightedIndex] = useState(-1);
  const [inputValue, setInputValue] = useState('');
  const [allTerminals, setAllTerminals] = useState([]);
  const [isTerminalModalOpen, setIsTerminalModalOpen] = useState(!localStorage.getItem('pos_terminal_id'));
  const [showReceiptPreview, setShowReceiptPreview] = useState(false);
  const [lastTransaction, setLastTransaction] = useState(null);
  const [heldTransactions, setHeldTransactions] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_held_transactions') || '[]'); } catch (e) { return []; }
  });
  const [isRecallModalOpen, setIsRecallModalOpen] = useState(false);
  const [customers, setCustomers] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_cached_customers') || '[]'); } catch (e) { return []; }
  });
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [isMemberModalOpen, setIsMemberModalOpen] = useState(false);
  const [memberSearchQuery, setMemberSearchQuery] = useState('');
  const [lastScannedProductId, setLastScannedProductId] = useState(null);
  const [activeShift, setActiveShift] = useState(null);
  const [isOpenShiftModalOpen, setIsOpenShiftModalOpen] = useState(false);
  const [isCloseShiftModalOpen, setIsCloseShiftModalOpen] = useState(false);
  const [startingCash, setStartingCash] = useState('');
  const [actualCash, setActualCash] = useState('');
  const [selectedShiftName, setSelectedShiftName] = useState(localStorage.getItem('pos_preselected_shift') || 'Shift 1');
  const [isCheckingShift, setIsCheckingShift] = useState(true);
  const [pendingAuthAction, setPendingAuthAction] = useState(null);
  const [isReturnModalOpen, setIsReturnModalOpen] = useState(false);
  const [isReturnMode, setIsReturnMode] = useState(false);
  const [discountModal, setDiscountModal] = useState(null);
  const [discountInputVal, setDiscountInputVal] = useState('');
  const [lockScreenInfo, setLockScreenInfo] = useState(null);
  const [branchMismatchInfo, setBranchMismatchInfo] = useState(null);

  const { storeLocalTransaction, syncTransactions, pendingCount, syncStatus } = useOfflineSync(branchId, authToken);
  const discountEngine = useRef(new DiscountEngine([]));

  useEffect(() => {
    const checkServerConnection = async () => {
      if (!navigator.onLine) {
        setIsOnline(false);
        return;
      }
      try {
        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), 3000);
        const res = await fetch('/api/v1/server-time', {
          headers: { 'Authorization': `Bearer ${authToken}` },
          signal: controller.signal
        });
        clearTimeout(id);
        setIsOnline(res.ok);
      } catch (e) {
        setIsOnline(false);
      }
    };

    const handleOnline = () => checkServerConnection();
    const handleOffline = () => setIsOnline(false);
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    checkServerConnection();
    const connectionInterval = setInterval(checkServerConnection, 10000);

    const timer = setInterval(() => {
      const offset = parseInt(localStorage.getItem('pos_server_offset') || '0');
      setCurrentTime(new Date(Date.now() + offset));
    }, 1000);

    const handleGlobalKeyPress = () => {
      if (changeModalInfo) setChangeModalInfo(null);
    };
    window.addEventListener('keydown', handleGlobalKeyPress);

    const handleBeforeUnload = (e) => {
      if (pendingCount > 0) {
        e.preventDefault();
        e.returnValue = 'Ada transaksi yang belum sinkron. Data mungkin hilang jika cache dihapus!';
        return e.returnValue;
      }
    };
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      window.removeEventListener('keydown', handleGlobalKeyPress);
      window.removeEventListener('beforeunload', handleBeforeUnload);
      clearInterval(timer);
      clearInterval(connectionInterval);
    };
  }, [changeModalInfo, pendingCount, authToken]);

  useEffect(() => {
    if (isOnline && pendingCount > 0 && syncStatus !== 'syncing') {
      console.log('Online state detected with pending transactions, auto-syncing...');
      syncTransactions();
    }
  }, [isOnline, pendingCount, syncTransactions, syncStatus]);

  useEffect(() => {
    safeSetItem('pos_active_cart', JSON.stringify(items));
  }, [items]);

  useEffect(() => {
    if (isOnline && activeShift && activeShift.is_offline && terminalInfo.id && authToken) {
      console.log('Detecting online state with offline active shift. Registering shift on server...');

      fetch('/api/v1/shifts/open', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify({
          terminal_id: terminalInfo.id,
          shift_name: activeShift.shift_name,
          starting_cash: activeShift.starting_cash
        })
      })
        .then(res => res.json())
        .then(data => {
          if (data.shift) {
            console.log('Offline shift successfully registered on server:', data.shift);
            setActiveShift(data.shift);
            safeSetItem('pos_active_shift', JSON.stringify(data.shift));
            setAlertMsg({ text: `Shift offline Anda ("${activeShift.shift_name}") telah disinkronkan ke server secara otomatis!`, type: 'success' });
            setTimeout(() => setAlertMsg(null), 5000);

            // Trigger sync of pending transactions
            syncTransactions();
          } else {
            console.error('Failed to register offline shift on server:', data.message);
          }
        })
        .catch(err => console.error('Error registering offline shift:', err));
    }
  }, [isOnline, activeShift, terminalInfo.id, authToken]);

  const [dbProducts, setDbProducts] = useState([]);
  const [dbPromos, setDbPromos] = useState([]);

  useEffect(() => {
    fetch('/api/v1/server-time', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        const serverMs = data.timestamp;
        const clientMs = Date.now();
        const offset = serverMs - clientMs;
        setServerOffset(offset);
        safeSetItem('pos_server_offset', offset.toString());
        safeSetItem('pos_last_sync_time', serverMs.toString());
      })
      .catch(err => console.error('Failed to sync server time:', err));

    fetch('/api/v1/products', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setDbProducts(data);
        safeSetItem('pos_cached_products', JSON.stringify(data));
      })
      .catch(err => {
        console.error('Failed to load products:', err);
        const cached = localStorage.getItem('pos_cached_products');
        if (cached) {
          try { setDbProducts(JSON.parse(cached)); } catch (e) { }
        }
      });

    fetch('/api/v1/promotions', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setDbPromos(data);
        discountEngine.current = new DiscountEngine(data);
        safeSetItem('pos_cached_promotions', JSON.stringify(data));
      })
      .catch(err => {
        console.error('Failed to load promotions:', err);
        const cached = localStorage.getItem('pos_cached_promotions');
        if (cached) {
          try {
            const parsed = JSON.parse(cached);
            setDbPromos(parsed);
            discountEngine.current = new DiscountEngine(parsed);
          } catch (e) { }
        }
      });

    fetch('/api/v1/banks', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setBanks(data);
        safeSetItem('pos_cached_banks', JSON.stringify(data));
      })
      .catch(err => {
        console.error('Failed to load banks:', err);
        const cached = localStorage.getItem('pos_cached_banks');
        if (cached) {
          try { setBanks(JSON.parse(cached)); } catch (e) { }
        }
      });

    fetch('/api/v1/pos-settings', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setPosSettings(data);
        safeSetItem('pos_cached_settings', JSON.stringify(data));
      })
      .catch(err => {
        console.error('Failed to load pos settings:', err);
        const cached = localStorage.getItem('pos_cached_settings');
        if (cached) {
          try { setPosSettings(JSON.parse(cached)); } catch (e) { }
        }
      });

    fetch('/api/v1/customers', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setCustomers(data);
        safeSetItem('pos_cached_customers', JSON.stringify(data));
      })
      .catch(err => {
        console.error('Failed to load customers:', err);
        const cached = localStorage.getItem('pos_cached_customers');
        if (cached) {
          try { setCustomers(JSON.parse(cached)); } catch (e) { }
        }
      });

    fetch('/api/v1/services', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        const servicesAsProducts = data.map(s => ({
          id: s.id,
          sku: s.code,
          barcode: s.code,
          name: s.name + ' (Jasa)',
          selling_price: s.price,
          category_id: null,
          is_service: true
        }));
        setDbProducts(prev => [...prev, ...servicesAsProducts]);
        safeSetItem('pos_cached_services', JSON.stringify(data));
      })
      .catch(err => {
        console.error('Failed to load services:', err);
        const cached = localStorage.getItem('pos_cached_services');
        if (cached) {
          try {
            const parsed = JSON.parse(cached);
            const servicesAsProducts = parsed.map(s => ({
              id: s.id,
              sku: s.code,
              barcode: s.code,
              name: s.name + ' (Jasa)',
              selling_price: s.price,
              category_id: null,
              is_service: true
            }));
            setDbProducts(prev => [...prev, ...servicesAsProducts]);
          } catch (e) { }
        }
      });

    fetch(`/api/v1/terminals?branch_id=${branchId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setAllTerminals(data);
        safeSetItem('pos_cached_terminals', JSON.stringify(data));
        const terminalId = localStorage.getItem('pos_terminal_id');
        if (terminalId) {
          const terminal = data.find(t => t.id === terminalId);
          if (terminal) {
            setTerminalInfo(terminal);
            checkActiveShift(terminalId);
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
              checkActiveShift(terminalId);
            }
          }
        } else {
          setIsCheckingShift(false);
          if (data.length > 0) {
            setIsTerminalModalOpen(true);
          }
        }
      })
      .catch(err => {
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

        const terminalId = localStorage.getItem('pos_terminal_id');
        const terminalBranchId = localStorage.getItem('pos_terminal_branch_id');

        // Check for offline branch mismatch
        if (terminalId && terminalBranchId && terminalBranchId !== branchId && userRole !== 'ADMIN') {
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

        if (terminalId) {
          const terminal = terminals.find(t => t.id === terminalId);
          if (terminal) setTerminalInfo(terminal);

          // Restore cached shift when offline
          const cachedShift = localStorage.getItem('pos_active_shift');
          if (cachedShift) {
            try {
              const parsed = JSON.parse(cachedShift);
              if (parsed && parsed.terminal_id === terminalId) {
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
  }, [authToken, branchId]);

  const handleSelectTerminal = (terminal) => {
    safeSetItem('pos_terminal_id', terminal.id);
    safeSetItem('pos_terminal_name', terminal.name);
    safeSetItem('pos_terminal_branch_id', terminal.branch_id);
    safeSetItem('pos_terminal_branch_name', terminal.branch_name || branchName);
    setTerminalInfo({ ...terminalInfo, id: terminal.id, name: terminal.name });
    setIsTerminalModalOpen(false);
    checkActiveShift(terminal.id);
  };



  const handleInputChange = (val) => {
    setInputValue(val);
    if (!isSubtotalMode && val.length > 1) {
      const filtered = dbProducts.filter(p =>
        p.name.toLowerCase().includes(val.toLowerCase()) ||
        p.sku.toLowerCase().includes(val.toLowerCase()) ||
        p.barcode?.toLowerCase().includes(val.toLowerCase())
      ).slice(0, 10);
      setSearchResults(filtered);
      setHighlightedIndex(-1);
    } else {
      setSearchResults([]);
      setHighlightedIndex(-1);
    }
  };

  const handleClearInput = () => {
    setInputValue('');
    setSearchResults([]);
    setHighlightedIndex(-1);
    setIsSubtotalMode(false);
    barcodeInput.current?.focus();
  };

  const handleBarcodeScan = async (barcode) => {
    if (!barcode) return;
    try {
      const product = dbProducts.find(p => p.sku === barcode || p.barcode === barcode);
      if (product) {
        addItemToTransaction(product);
        setSearchResults([]);
        setInputValue('');
      } else {
        setAlertMsg({ text: `Product "${barcode}" tidak ditemukan.`, type: 'error' });
        setTimeout(() => setAlertMsg(null), 2000);
      }
    } catch (error) {
      console.error('Product search failed:', error);
    }
  };

  const checkActiveShift = (terminalId) => {
    setIsCheckingShift(true);
    setLockScreenInfo(null);
    fetch(`/api/v1/shifts/active?terminal_id=${terminalId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'USER_HAS_OTHER_SHIFT' || data.status === 'TERMINAL_IN_USE') {
          setLockScreenInfo({ status: data.status, message: data.message });
          setActiveShift(null);
          localStorage.removeItem('pos_active_shift');
        } else if (data.shift) {
          setActiveShift(data.shift);
          safeSetItem('pos_active_shift', JSON.stringify(data.shift));
          setAlertMsg({
            text: `Melanjutkan ${data.shift.shift_name} yang belum ditutup. Harap tutup shift ini jika ingin membuka shift baru.`,
            type: 'info',
            persist: true
          });
          setTimeout(() => setAlertMsg(null), 7000);
        } else {
          localStorage.removeItem('pos_active_shift');
          setIsOpenShiftModalOpen(true);
        }
      })
      .catch(err => {
        console.error('Failed to check shift:', err);
        // Fallback to cached shift when offline
        const cachedShift = localStorage.getItem('pos_active_shift');
        if (cachedShift) {
          try {
            const parsed = JSON.parse(cachedShift);
            if (parsed && parsed.terminal_id === terminalId) {
              setActiveShift(parsed);
              setAlertMsg({
                text: `Melanjutkan ${parsed.shift_name} (Offline) yang belum ditutup. Harap tutup shift ini jika ingin membuka shift baru.`,
                type: 'info',
                persist: true
              });
              setTimeout(() => setAlertMsg(null), 7000);
            } else {
              setIsOpenShiftModalOpen(true);
            }
          } catch (e) {
            setIsOpenShiftModalOpen(true);
          }
        } else {
          setIsOpenShiftModalOpen(true);
        }
      })
      .finally(() => setIsCheckingShift(false));
  };

  const handleOpenShift = () => {
    if (!startingCash || isNaN(startingCash)) {
      setAlertMsg({ text: 'Modal awal harus diisi dengan angka.', type: 'error' });
      return;
    }

    const startingCashVal = parseFloat(startingCash);

    // Create local offline shift if navigator is offline
    if (!navigator.onLine) {
      const localShift = {
        id: null,
        is_offline: true,
        shift_name: selectedShiftName,
        start_time: new Date().toISOString(),
        starting_cash: startingCashVal,
        status: 'OPEN'
      };
      setActiveShift(localShift);
      safeSetItem('pos_active_shift', JSON.stringify(localShift));
      setIsOpenShiftModalOpen(false);
      setAlertMsg({ text: 'Shift offline berhasil dibuka secara lokal. Selamat bertugas!', type: 'success' });
      return;
    }

    fetch('/api/v1/shifts/open', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
      },
      body: JSON.stringify({
        terminal_id: terminalInfo.id,
        shift_name: selectedShiftName,
        starting_cash: startingCashVal
      })
    })
      .then(res => res.json())
      .then(data => {
        if (data.shift) {
          setActiveShift(data.shift);
          safeSetItem('pos_active_shift', JSON.stringify(data.shift));
          setIsOpenShiftModalOpen(false);
          setAlertMsg({ text: 'Shift berhasil dibuka. Selamat bertugas!', type: 'success' });
        } else {
          setAlertMsg({ text: data.message || 'Gagal membuka shift.', type: 'error' });
        }
      })
      .catch(err => {
        console.error('Failed to open shift online, falling back to offline:', err);
        const localShift = {
          id: null,
          is_offline: true,
          shift_name: selectedShiftName,
          start_time: new Date().toISOString(),
          starting_cash: startingCashVal,
          status: 'OPEN'
        };
        setActiveShift(localShift);
        safeSetItem('pos_active_shift', JSON.stringify(localShift));
        setIsOpenShiftModalOpen(false);
        setAlertMsg({ text: 'Gagal menghubungi server. Shift offline dibuka secara lokal.', type: 'success' });
      });
  };

  const handleCloseShift = () => {
    if (!activeShift) {
      setAlertMsg({ text: 'Tidak ada shift aktif yang ditemukan.', type: 'error' });
      return;
    }

    if (!actualCash || isNaN(actualCash)) {
      setAlertMsg({ text: 'Uang fisik harus diisi dengan angka.', type: 'error' });
      return;
    }

    setIsProcessing(true);

    // If offline shift or navigator is offline, close locally
    if (activeShift.is_offline || !navigator.onLine) {
      localStorage.removeItem('pos_active_shift');
      setActiveShift(null);
      setIsCloseShiftModalOpen(false);
      setIsProcessing(false);
      setAlertMsg({ text: 'Shift offline berhasil ditutup (Lokal). Terima kasih!', type: 'success', persist: true });
      setTimeout(() => onLogout(), 2000);
      return;
    }

    fetch('/api/v1/shifts/close', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
      },
      body: JSON.stringify({
        shift_id: activeShift.id,
        actual_cash: parseFloat(actualCash)
      })
    })
      .then(res => res.json())
      .then(data => {
        setIsProcessing(false);
        if (data.shift) {
          localStorage.removeItem('pos_active_shift');
          setActiveShift(null);
          setIsCloseShiftModalOpen(false);
          setAlertMsg({ text: 'Shift berhasil ditutup. Terima kasih!', type: 'success', persist: true });
          setTimeout(() => onLogout(), 2000);
        } else {
          setAlertMsg({ text: data.message || 'Gagal menutup shift.', type: 'error' });
        }
      })
      .catch(err => {
        console.error('Failed to close shift online, closing locally:', err);
        localStorage.removeItem('pos_active_shift');
        setActiveShift(null);
        setIsCloseShiftModalOpen(false);
        setIsProcessing(false);
        setAlertMsg({ text: 'Koneksi terputus. Shift ditutup secara lokal. Terima kasih!', type: 'success', persist: true });
        setTimeout(() => onLogout(), 2000);
      });
  };

  const addItemToTransaction = (product) => {
    const existingItem = items.find(i => i.productId === product.id);
    let manualDiscount = 0;

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
      setItems(items.map(i =>
        i.productId === product.id ? { ...i, quantity: i.quantity + 1, manualDiscount: manualDiscount || i.manualDiscount } : i
      ));
    } else {
      setItems([...items, {
        productId: product.id,
        categoryId: product.category_id,
        sku: product.sku,
        name: product.name,
        quantity: 1,
        unitPrice: product.selling_price,
        manualDiscount: manualDiscount,
        discountPerItem: 0,
        isService: product.is_service || false
      }]);
    }
    setLastScannedProductId(product.id);
    setTimeout(() => setLastScannedProductId(null), 3000);
  };

  const removeItem = (productId) => {
    setItems(items.filter(i => i.productId !== productId));
  };

  const updateQuantity = (productId, quantity) => {
    if (!productId) return;
    if (quantity <= 0) removeItem(productId);
    else {
      setItems(items.map(i => i.productId === productId ? { ...i, quantity } : i));
    }
  };

  const subtotal = items.reduce((sum, item) => sum + (item.quantity * parseFloat(item.unitPrice)) - (item.quantity * (item.manualDiscount || 0)), 0);
  const { totalDiscount, appliedPromos } = discountEngine.current.calculateTotalDiscount(items, { memberTier: 'REGULAR' }, subtotal);
  const finalAmount = subtotal - totalDiscount - manualTotalDiscount;
  const changeAmount = receivedAmount ? parseFloat(receivedAmount) - finalAmount : 0;

  const handleManualDiscountItem = (type) => {
    setDiscountModal({ target: 'ITEM', type });
    setDiscountInputVal('');
  };

  const handleManualTotalDiscount = (type) => {
    setDiscountModal({ target: 'TOTAL', type });
    setDiscountInputVal('');
  };

  const applyEnteredDiscount = () => {
    const val = parseFloat(discountInputVal);
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
    setTimeout(() => barcodeInput.current?.focus(), 100);
  };

  const handleClearDiscount = () => {
    let cleared = false;
    if (queuedDiscount) {
      setQueuedDiscount(null);
      cleared = true;
    }
    if (manualTotalDiscount > 0) {
      setManualTotalDiscount(0);
      cleared = true;
    }
    if (cleared) {
      setAlertMsg({ text: 'Seluruh diskon manual berhasil dibatalkan/dibersihkan!', type: 'success' });
      setTimeout(() => setAlertMsg(null), 2000);
    } else {
      setAlertMsg({ text: 'Tidak ada diskon aktif yang dapat dibersihkan.', type: 'info' });
      setTimeout(() => setAlertMsg(null), 2000);
    }
    barcodeInput.current?.focus();
  };

  const requestAuthorization = (actionName, callback) => {
    setPendingAuthAction({ name: actionName, callback });
  };

  const handleReturnSuccess = (returnItems, originalReceiptId) => {
    setItems(prev => [...prev, ...returnItems]);
    setIsReturnMode(true);
    setIsReturnModalOpen(false);
    setAlertMsg({ text: `Mode Retur Aktif untuk Nota: ${originalReceiptId}`, type: 'info', persist: true });
    setTimeout(() => setAlertMsg(null), 5000);
  };

  const startPayment = (method) => {
    setPaymentMethod(method);
    if (method === 'CARD') {
      setIsBankSelectOpen(true);
    } else {
      processTransaction(method);
    }
  };

  const processTransaction = async (method = paymentMethod, bankId = selectedBank?.id, overrideReceived = null) => {
    if (items.length === 0) return;
    setIsProcessing(true);
    try {
      const nowCorrected = new Date(Date.now() + serverOffset);
      const lastSync = parseInt(localStorage.getItem('pos_last_sync_time') || '0');

      // Safety check: Prevent backdating or extreme forward dating
      if (lastSync > 0) {
        const driftLimit = 24 * 60 * 60 * 1000; // 24 hours
        const diffFromLast = nowCorrected.getTime() - lastSync;

        if (diffFromLast < -300000) { // More than 5 mins in the past compared to last sync
          setAlertMsg({ text: 'Waktu sistem tidak valid (Mundur dari waktu terakhir). Mohon koreksi jam perangkat.', type: 'error' });
          setIsProcessing(false);
          return;
        }

        if (diffFromLast > driftLimit && !isOnline) {
          setAlertMsg({ text: 'Waktu sistem terlalu jauh dari sinkronisasi terakhir. Mohon online-kan untuk kalibrasi jam.', type: 'error' });
          setIsProcessing(false);
          return;
        }
      }

      const currentFinalAmount = finalAmount;
      const currentReceived = overrideReceived !== null ? parseFloat(overrideReceived) : (parseFloat(receivedAmount) || finalAmount);

      if (currentReceived < currentFinalAmount) {
        setAlertMsg({ text: `Pembayaran kurang! Kurang: ${formatCurrency(currentFinalAmount - currentReceived)}`, type: 'error' });
        setTimeout(() => setAlertMsg(null), 3000);
        setIsProcessing(false);
        return;
      }

      const currentChange = currentReceived - currentFinalAmount;

      const grossTotal = items.reduce((sum, item) => sum + (item.quantity * parseFloat(item.unitPrice)), 0);
      const totalItemManualDiscount = items.reduce((sum, item) => sum + (item.quantity * (item.manualDiscount || 0)), 0);

      const transaction = {
        items,
        totalAmount: grossTotal,
        discountAmount: totalItemManualDiscount + totalDiscount + manualTotalDiscount,
        finalAmount: currentFinalAmount,
        paymentMethod: method,
        bankId: bankId,
        terminalId: terminalInfo.id,
        customerId: selectedCustomer?.id,
        shiftId: activeShift?.id,
        receivedAmount: currentReceived,
        changeAmount: currentChange,
        appliedPromos,
        receipt_number: 'SMI-' + Math.random().toString(36).substring(2, 8).toUpperCase(),
        transaction_type: isReturnMode ? 'RETURN' : 'SALES',
      };

      await storeLocalTransaction(transaction);
      if (isOnline) await syncTransactions();

      // Clear states BEFORE showing modal to avoid flicker
      setItems([]);
      setManualTotalDiscount(0);
      setReceivedAmount('');
      setInputValue('');
      setIsSubtotalMode(false);
      setSelectedBank(null);
      setSelectedCustomer(null);
      setIsBankSelectOpen(false);
      setIsReturnMode(false);

      // Small delay to ensure the key event that triggered this doesn't immediately close the modal
      setTimeout(() => {
        setLastTransaction({
          ...transaction,
          branchName,
          orgName,
          userName,
          customerName: selectedCustomer?.name,
          timestamp: nowCorrected.toISOString(),
        });
        safeSetItem('pos_last_sync_time', nowCorrected.getTime().toString());
        setChangeModalInfo({ amount: currentChange });
      }, 100);

      if (barcodeInput.current) {
        barcodeInput.current.focus();
      }
    } catch (error) {
      console.error('Transaction error:', error);
      setAlertMsg({ text: `Error: ${error.message}`, type: 'error' });
    } finally {
      setIsProcessing(false);
    }
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
    setManualTotalDiscount(0);
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

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
  };

  useEffect(() => {
    const handleShortcuts = (e) => {
      if (e.target.tagName === 'INPUT' && !['F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'F12', 'Escape', 'Delete'].includes(e.key)) {
        return;
      }

      const settingsList = posSettings && posSettings.length > 0 ? posSettings : [
        { key_name: 'btn_pay', shortcut_key: 'F12' },
        { key_name: 'btn_subtotal', shortcut_key: 'F1' },
        { key_name: 'btn_disc_item_rp', shortcut_key: 'F2' },
        { key_name: 'btn_disc_item_pct', shortcut_key: 'F3' },
        { key_name: 'btn_disc_total_rp', shortcut_key: 'F4' },
        { key_name: 'btn_disc_total_pct', shortcut_key: 'F5' },
        { key_name: 'btn_tunai', shortcut_key: 'F6' },
        { key_name: 'btn_card', shortcut_key: 'F7' },
        { key_name: 'btn_void_item', shortcut_key: 'F8' },
        { key_name: 'btn_void_all', shortcut_key: 'F9' }
      ];

      const setting = settingsList.find(s => s.shortcut_key.toLowerCase() === e.key.toLowerCase());
      if (!setting) return;

      e.preventDefault();
      switch (setting.key_name) {
        case 'btn_pay': processTransaction(); break;
        case 'btn_subtotal': setIsSubtotalMode(true); barcodeInput.current?.focus(); break;
        case 'btn_disc_item_rp': handleManualDiscountItem('NOMINAL'); break;
        case 'btn_disc_item_pct': handleManualDiscountItem('PERCENT'); break;
        case 'btn_disc_total_rp': handleManualTotalDiscount('NOMINAL'); break;
        case 'btn_disc_total_pct': handleManualTotalDiscount('PERCENT'); break;
        case 'btn_tunai': startPayment('CASH'); break;
        case 'btn_card': startPayment('CARD'); break;
        case 'btn_void_item': updateQuantity(items[items.length - 1]?.productId, 0); break;
        case 'btn_void_all': setItems([]); break;
        default: break;
      }
    };

    window.addEventListener('keydown', handleShortcuts);
    return () => window.removeEventListener('keydown', handleShortcuts);
  }, [posSettings, items, isSubtotalMode, receivedAmount, activeShift, paymentMethod, subtotal, finalAmount, totalDiscount, manualTotalDiscount, dbProducts]);

  if (isCheckingShift) {
    return (
      <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#0f172a', color: 'white' }}>
        <div style={{ textAlign: 'center' }}>
          <RefreshCw className="spin" size={48} style={{ marginBottom: '1rem', color: '#3b82f6' }} />
          <p style={{ fontSize: '1.25rem', fontWeight: '500' }}>Menyiapkan Terminal Kasir...</p>
        </div>
      </div>
    );
  }

  if (branchMismatchInfo) {
    return (
      <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#0f172a', color: 'white', padding: '1rem' }}>
        <div className="fade-in" style={{ maxWidth: '600px', width: '100%', background: '#1e293b', border: '1px solid rgba(239, 68, 68, 0.3)', borderRadius: '16px', padding: '2.5rem', textAlign: 'center', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)' }}>
          <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
            <Lock size={40} />
          </div>
          <h2 style={{ color: '#ef4444', marginBottom: '1rem', letterSpacing: '0.05rem', fontSize: '1.5rem', fontWeight: 'bold' }}>❌ Akses Ditolak: Cabang Tidak Cocok</h2>
          <p style={{ color: '#94a3b8', lineHeight: '1.6', marginBottom: '2rem', fontSize: '1.05rem' }}>
            Akun kasir Anda terdaftar di <strong>{branchMismatchInfo.userBranch}</strong>, sedangkan kassa/terminal ini terdaftar di <strong>{branchMismatchInfo.terminalBranch}</strong>. Anda tidak diperbolehkan membuka kassa atau melakukan transaksi di kassa milik cabang lain demi keamanan data.
          </p>
          <button
            className="btn-danger"
            style={{ width: '100%', padding: '1rem', fontSize: '1.1rem', fontWeight: 'bold', borderRadius: '8px', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '0.5rem' }}
            onClick={onLogout}
          >
            <LogOut size={20} /> ➔ KEMBALI KE LOGIN
          </button>
        </div>
      </div>
    );
  }

  if (lockScreenInfo) {
    return (
      <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#0f172a', color: 'white', padding: '1rem' }}>
        <div className="fade-in" style={{ maxWidth: '600px', width: '100%', background: '#1e293b', border: '1px solid rgba(239, 68, 68, 0.3)', borderRadius: '16px', padding: '2.5rem', textAlign: 'center', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)' }}>
          <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
            <Lock size={40} />
          </div>
          <h2 style={{ color: '#ef4444', marginBottom: '1rem', letterSpacing: '0.05rem', fontSize: '1.5rem', fontWeight: 'bold' }}>❌ Akses Terkunci</h2>
          <p style={{ color: '#94a3b8', lineHeight: '1.6', marginBottom: '2rem', fontSize: '1.05rem' }}>
            {lockScreenInfo.message}
          </p>
          <button
            className="btn-danger"
            style={{ width: '100%', padding: '1rem', fontSize: '1.1rem', fontWeight: 'bold', borderRadius: '8px', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '0.5rem' }}
            onClick={onLogout}
          >
            <LogOut size={20} /> ➔ KELUAR & KEMBALI
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="pos-terminal-new">
      {/* --- MODALS --- */}

      {/* Terminal Selection Modal */}
      {isTerminalModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content terminal-select-card fade-in">
            <Settings size={48} className="text-primary" />
            <h2>Pilih Terminal / Kassa</h2>
            <p>Silakan pilih terminal yang digunakan saat ini.</p>
            <div className="terminal-list-grid">
              {allTerminals.length > 0 ? (
                allTerminals.map(terminal => (
                  <button key={terminal.id} className="terminal-item-btn" onClick={() => handleSelectTerminal(terminal)}>
                    <span className="name">{terminal.name}</span>
                    <span className="code">{terminal.code}</span>
                  </button>
                ))
              ) : (
                <p className="text-muted">Tidak ada terminal aktif untuk cabang ini.</p>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Buka Shift Modal */}
      {isOpenShiftModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <div className="modal-header-icon" style={{ background: 'rgba(34, 197, 94, 0.1)', color: '#22c55e', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
              <LogIn size={40} />
            </div>
            <h2 style={{ textAlign: 'center' }}>Buka Shift Kasir</h2>
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Pilih shift dan masukkan modal awal untuk memulai.</p>

            <div className="form-group" style={{ marginBottom: '1rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Nama Shift</label>
              <select
                className="modern-barcode-input"
                style={{ width: '100%', padding: '0.75rem' }}
                value={selectedShiftName}
                onChange={(e) => setSelectedShiftName(e.target.value)}
              >
                <option value="Shift 1">Shift 1 (Pagi)</option>
                <option value="Shift 2">Shift 2 (Siang)</option>
                <option value="Shift 3">Shift 3 (Malam)</option>
                <option value="Shift Umum">Shift Umum</option>
              </select>
            </div>

            <div className="form-group" style={{ marginBottom: '1.5rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Modal Awal (Cash)</label>
              <input
                type="number"
                className="modern-barcode-input"
                style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
                placeholder="0"
                value={startingCash}
                onChange={(e) => setStartingCash(e.target.value)}
              />
            </div>

            <button className="btn-primary" style={{ width: '100%', padding: '1rem', fontSize: '1.1rem' }} onClick={handleOpenShift}>
              BUKA SHIFT SEKARANG
            </button>
          </div>
        </div>
      )}

      {/* Tutup Shift Modal */}
      {isCloseShiftModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '450px' }}>
            <div className="modal-header-icon" style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
              <LogOut size={40} />
            </div>
            <h2 style={{ textAlign: 'center' }}>Tutup Kasir / End Shift</h2>

            <div className="shift-summary-mini" style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: '12px', marginBottom: '1.5rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <span style={{ color: 'var(--text-muted)' }}>Mulai Shift</span>
                <span>{activeShift?.start_time ? new Date(activeShift.start_time).toLocaleTimeString('id-ID') : '-'}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <span style={{ color: 'var(--text-muted)' }}>Modal Awal</span>
                <span>{formatCurrency(activeShift?.starting_cash || 0)}</span>
              </div>
            </div>

            <div className="form-group" style={{ marginBottom: '1.5rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Uang Fisik di Laci (Cash)</label>
              <input
                type="number"
                className="modern-barcode-input"
                style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
                placeholder="Masukkan jumlah uang fisik..."
                value={actualCash}
                onChange={(e) => setActualCash(e.target.value)}
                autoFocus
              />
            </div>

            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => setIsCloseShiftModalOpen(false)}>BATAL</button>
              <button
                className="btn-danger"
                style={{ flex: 2, background: isProcessing ? '#94a3b8' : '#ef4444' }}
                onClick={handleCloseShift}
                disabled={isProcessing}
              >
                {isProcessing ? 'MEMPROSES...' : 'TUTUP KASIR (LOGOUT)'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Recall (HOLD) Modal */}
      {isRecallModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content bank-select-card fade-in" style={{ maxWidth: '600px' }}>
            <History size={48} className="text-primary" />
            <h2>Daftar Transaksi Ditunda (HOLD)</h2>
            <div className="held-list-grid" style={{ maxHeight: '400px', overflowY: 'auto', width: '100%' }}>
              {heldTransactions.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-muted)' }}>
                  <p>Tidak ada transaksi yang ditunda.</p>
                </div>
              ) : (
                heldTransactions.map(tx => (
                  <div key={tx.id} className="held-item-card" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1rem', background: 'rgba(255,255,255,0.05)', borderRadius: '12px', marginBottom: '0.75rem' }}>
                    <div className="held-info" style={{ textAlign: 'left' }}>
                      <div style={{ fontWeight: '700' }}>#{String(tx.id).substring(0, 8)}</div>
                      <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{tx.itemCount} Items | {formatCurrency(tx.finalAmount || tx.total)}</div>
                      <small style={{ color: 'var(--text-muted)' }}>{new Date(tx.timestamp || tx.time).toLocaleTimeString('id-ID')}</small>
                    </div>
                    <div className="held-actions" style={{ display: 'flex', gap: '0.5rem' }}>
                      <button className="btn-primary-sm" onClick={() => handleRecallTransaction(tx)}>PANGGIL</button>
                      <button className="btn-danger-sm" onClick={() => {
                        const newHeld = heldTransactions.filter(h => h.id !== tx.id);
                        setHeldTransactions(newHeld);
                        safeSetItem('pos_held_transactions', JSON.stringify(newHeld));
                      }}><Trash2 size={14} /></button>
                    </div>
                  </div>
                ))
              )}
            </div>
            <button className="btn-secondary" style={{ marginTop: '1rem', width: '100%' }} onClick={() => setIsRecallModalOpen(false)}>TUTUP (ESC)</button>
          </div>
        </div>
      )}

      {/* Member Selection Modal */}
      {isMemberModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content bank-select-card fade-in" style={{ maxWidth: '600px' }}>
            <User size={48} className="text-primary" />
            <h2>Pilih Member</h2>
            <div className="search-box-modern" style={{ width: '100%', marginTop: '1rem' }}>
              <input
                type="text"
                placeholder="Cari member (Nama/HP)..."
                className="modern-barcode-input"
                style={{ paddingLeft: '1rem', marginBottom: '1rem' }}
                value={memberSearchQuery}
                onChange={(e) => setMemberSearchQuery(e.target.value)}
                autoFocus
              />
            </div>
            <div className="member-list-results" style={{ maxHeight: '300px', overflowY: 'auto', width: '100%' }}>
              {customers
                .filter(c => c.name.toLowerCase().includes(memberSearchQuery.toLowerCase()) || c.phone?.includes(memberSearchQuery))
                .map(customer => (
                  <div key={customer.id} className="held-item" style={{ cursor: 'pointer', padding: '0.75rem', borderBottom: '1px solid rgba(255,255,255,0.05)' }} onClick={() => { setSelectedCustomer(customer); setIsMemberModalOpen(false); setMemberSearchQuery(''); }}>
                    <div style={{ textAlign: 'left' }}>
                      <div style={{ fontWeight: '700' }}>{customer.name}</div>
                      <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{customer.phone || '-'} | {customer.member_tier || 'REGULAR'}</div>
                    </div>
                  </div>
                ))
              }
            </div>
            <div style={{ display: 'flex', gap: '1rem', marginTop: '1rem', width: '100%' }}>
              <button className="btn-danger" style={{ flex: 1 }} onClick={() => { setSelectedCustomer(null); setIsMemberModalOpen(false); }}>HAPUS MEMBER</button>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => setIsMemberModalOpen(false)}>BATAL (ESC)</button>
            </div>
          </div>
        </div>
      )}

      {/* Change Modal Overlay */}
      {changeModalInfo && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in">
            <CheckCircle size={64} className="text-online" />
            <h2>Transaksi Berhasil!</h2>
            <div className="change-amount-display">
              <label>KEMBALIAN</label>
              <div className="amount">{formatCurrency(changeModalInfo.amount)}</div>
            </div>
            <div style={{ display: 'flex', gap: '1rem', marginTop: '2rem' }}>
              <button className="btn-secondary" onClick={() => setChangeModalInfo(null)}>TUTUP (ESC)</button>
              <button className="btn-primary" onClick={() => { setChangeModalInfo(null); setShowReceiptPreview(true); }}>STRUK (F12)</button>
            </div>
          </div>
        </div>
      )}

      {/* Receipt Preview */}
      {showReceiptPreview && lastTransaction && (
        <ReceiptPreview
          transaction={lastTransaction}
          onPrint={() => { window.print(); setShowReceiptPreview(false); }}
          onClose={() => setShowReceiptPreview(false)}
        />
      )}

      {/* Bank Selection Modal */}
      {isBankSelectOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content bank-select-card fade-in">
            <CreditCard size={48} className="text-primary" />
            <h2>Pilih Bank / Mesin EDC</h2>
            <div className="bank-grid-large">
              {banks.map(bank => (
                <button key={bank.id} className="bank-item-btn" onClick={() => { setSelectedBank(bank); processTransaction('CARD', bank.id); }}>
                  <span className="bank-name">{bank.name}</span>
                  <span className="bank-code">{bank.code || 'EDC'}</span>
                </button>
              ))}
            </div>
            <button className="btn-secondary" onClick={() => setIsBankSelectOpen(false)}>BATAL (ESC)</button>
          </div>
        </div>
      )}

      {/* --- MAIN UI --- */}

      <header className="pos-header-modern glassmorphism">
        <div className="pos-branding">
          <div className="logo-box">SM</div>
          <div className="brand-info">
            <h1>{orgName}</h1>
            <p>{branchName} | {terminalInfo?.name || 'Terminal'}</p>
            {activeShift && (
              <div className="active-shift-badge" style={{ display: 'inline-flex', alignItems: 'center', gap: '0.4rem', background: 'rgba(59, 130, 246, 0.15)', color: '#60a5fa', padding: '2px 8px', borderRadius: '4px', fontSize: '0.7rem', fontWeight: '700', marginTop: '4px', border: '1px solid rgba(59, 130, 246, 0.3)' }}>
                <Clock size={12} />
                <span>AKTIF: {activeShift.shift_name}</span>
              </div>
            )}
          </div>
        </div>

        <div className="pos-time-section">
          <div className="time">{currentTime.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour12: false })}</div>
          <div className="date">{currentTime.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' })}</div>
        </div>

        <div className="pos-user-status">
          {pendingCount > 0 && (
            <div className={`sync-status mr-4 ${!isOnline ? 'warning-pulse' : ''}`} style={{
              background: !isOnline ? 'rgba(239, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.1)',
              border: !isOnline ? '1px solid #ef4444' : '1px solid #3b82f6',
              padding: '4px 12px',
              borderRadius: '20px',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              color: !isOnline ? '#ef4444' : '#3b82f6',
              fontWeight: 'bold'
            }}>
              <RefreshCw size={18} className={syncStatus === 'syncing' ? 'spin' : ''} />
              <span>Antrean: {pendingCount} {!isOnline && <span style={{ fontSize: '0.7rem' }}>(OFFLINE - sync otomatis)</span>}</span>
            </div>
          )}
          <div className="status-indicator">
            {isOnline ? <Wifi size={18} className="text-online" /> : <WifiOff size={18} className="text-offline" />}
            <span>{isOnline ? 'Online' : 'Offline'}</span>
          </div>
          <div className="user-info">
            <User size={18} />
            <span>{userName}</span>
          </div>
          <button onClick={() => onLogout()} className="btn-logout-icon" title="Logout Kasir (Istirahat)" style={{ color: '#ef4444' }}>
            <LogOut size={18} />
          </button>
        </div>
      </header>

      {alertMsg && (
        <div className={`pos-toast slide-down ${alertMsg.type}`}>
          {alertMsg.text}
          {!alertMsg.persist && setTimeout(() => setAlertMsg(null), 3000) && null}
        </div>
      )}

      {isReturnMode && (
        <div style={{ background: '#f59e0b', color: '#fff', padding: '0.5rem', textAlign: 'center', fontWeight: 'bold', letterSpacing: '0.1rem' }}>
          MODE RETUR AKTIF
        </div>
      )}

      {pendingAuthAction && (
        <AuthorizationModal
          actionName={pendingAuthAction.name}
          authToken={authToken}
          onSuccess={(user) => {
            setPendingAuthAction(null);
            pendingAuthAction.callback();
          }}
          onCancel={() => setPendingAuthAction(null)}
        />
      )}

      {isReturnModalOpen && (
        <ReturnItemModal
          authToken={authToken}
          onSuccess={handleReturnSuccess}
          onCancel={() => setIsReturnModalOpen(false)}
        />
      )}

      {discountModal && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
              Masukkan Nilai Diskon ${discountModal.target === 'TOTAL' ? 'Total' : 'Item'} (${discountModal.type === 'PERCENT' ? 'Persen %' : 'Nominal Rp'})
            </h3>
            <input
              type="text"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              placeholder="0"
              value={discountInputVal}
              onChange={(e) => setDiscountInputVal(e.target.value.replace(/[^0-9.]/g, ''))}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  applyEnteredDiscount();
                } else if (e.key === 'Escape') {
                  setDiscountModal(null);
                  setDiscountInputVal('');
                  barcodeInput.current?.focus();
                }
              }}
              autoFocus
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setDiscountModal(null); setDiscountInputVal(''); barcodeInput.current?.focus(); }}>BATAL (Esc)</button>
              <button className="btn-success" style={{ flex: 1 }} onClick={applyEnteredDiscount}>OK (Enter)</button>
            </div>
          </div>
        </div>
      )}



      <div className="pos-main-layout">
        <main className="pos-cart-container">
          <div className="cart-header">
            <h3><ShoppingCart size={20} /> Keranjang Belanja</h3>
            <span className="item-count">{items.length} Items</span>
          </div>

          <div className="cart-table-wrapper" style={{ flex: 1 }}>
            <table className="modern-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Kode / Nama Barang</th>
                  <th>Harga</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                {items.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="empty-state">
                      <div className="empty-content">
                        <Search size={48} />
                        <p>Belum ada produk. Silakan scan barcode atau ketik SKU.</p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  items.map((item, index) => (
                    <tr key={item.productId} className={`cart-row fade-in ${item.productId === lastScannedProductId ? 'highlight-row' : ''}`}>
                      <td>{index + 1}</td>
                      <td>
                        <div className="prod-cell">
                          <span className="sku">{item.sku}</span>
                          <span className="name">{item.name}</span>
                          {item.manualDiscount > 0 && <span className="item-discount-tag">Manual Disc: -{formatCurrency(item.manualDiscount)}</span>}
                        </div>
                      </td>
                      <td>{formatCurrency(item.unitPrice)}</td>
                      <td>
                        <div className="qty-control-modern">
                          <button onClick={() => updateQuantity(item.productId, item.quantity - 1)}><Minus size={14} /></button>
                          <input type="number" value={item.quantity} onChange={(e) => updateQuantity(item.productId, parseInt(e.target.value) || 0)} />
                          <button onClick={() => updateQuantity(item.productId, item.quantity + 1)}><Plus size={14} /></button>
                        </div>
                      </td>
                      <td className="subtotal-cell">{formatCurrency((item.quantity * item.unitPrice) - (item.quantity * (item.manualDiscount || 0)))}</td>
                      <td>
                        <button className="btn-remove-item" onClick={() => requestAuthorization("VOID", () => removeItem(item.productId))}><Trash2 size={16} /></button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </main>

        <aside className="pos-functions-sidebar" style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <div className="summary-section">
            <div className="grand-total-box" style={{ border: 'none', padding: 0, marginTop: 0 }}>
              <label>GRAND TOTAL</label>
              <div className="total-amount" style={{ fontSize: '3.5rem' }}>{formatCurrency(finalAmount)}</div>
            </div>

            <div className="sub-summary-box" style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: '12px', marginTop: '1rem' }}>
              <div className="summary-line">
                <span>Subtotal</span>
                <span>{formatCurrency(subtotal)}</span>
              </div>
              {(totalDiscount + manualTotalDiscount) > 0 && (
                <div className="summary-line discount">
                  <span>Diskon</span>
                  <span>-{formatCurrency(totalDiscount + manualTotalDiscount)}</span>
                </div>
              )}
            </div>

            {selectedCustomer && (
              <div className="selected-member-box fade-in" style={{ marginTop: '1rem', padding: '0.75rem', background: 'rgba(59, 130, 246, 0.1)', borderRadius: '12px', border: '1px solid rgba(59, 130, 246, 0.3)' }}>
                <div style={{ fontSize: '0.75rem', textTransform: 'uppercase', color: 'var(--primary)', fontWeight: '700' }}>Member Aktif</div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={{ fontWeight: '700' }}>{selectedCustomer.name}</span>
                  <span style={{ fontSize: '0.75rem', background: 'var(--primary)', padding: '2px 6px', borderRadius: '4px' }}>{selectedCustomer.member_tier}</span>
                </div>
                <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Points: {selectedCustomer.points} | {selectedCustomer.phone}</div>
              </div>
            )}

            {isSubtotalMode && (
              <div className="payment-calc fade-in" style={{ marginTop: '1rem' }}>
                <div className="calc-row">
                  <label>Diterima</label>
                  <span className="text-primary">{formatCurrency(receivedAmount || 0)}</span>
                </div>
                <div className="calc-row">
                  <label>Kembali</label>
                  <span className={changeAmount >= 0 ? "text-online" : "text-danger"}>
                    {formatCurrency(changeAmount)}
                  </span>
                </div>
              </div>
            )}
          </div>

          <div className="function-grid">
            <button className="func-btn danger" onClick={handleClearDiscount}><X size={18} /><span>Clear Disc</span></button>
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualDiscountItem('NOMINAL'))}><Tag size={18} /><span>Disc Item Rp</span><span className="key-hint">F1</span></button>
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualDiscountItem('PERCENT'))}><Tag size={18} /><span>Disc Item %</span><span className="key-hint">F2</span></button>
            <button className="func-btn primary"><Package size={18} /><span>Qty</span></button>
            <button className="func-btn secondary" onClick={() => requestAuthorization("RETURN", () => setIsReturnModalOpen(true))}><RotateCcw size={18} /><span>Retur</span></button>
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('NOMINAL'))}><Tag size={18} /><span>Disc Total Rp</span><span className="key-hint">F3</span></button>
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('PERCENT'))}><Tag size={18} /><span>Disc Total %</span><span className="key-hint">F4</span></button>

            <button className={`func-btn payment ${paymentMethod === 'CARD' ? 'active' : ''}`} onClick={() => startPayment('CARD')}><CreditCard size={18} /><span>Card</span><span className="key-hint">F6</span></button>
            <button className={`func-btn payment ${paymentMethod === 'CASH' ? 'active' : ''}`} onClick={() => startPayment('CASH')}><Banknote size={18} /><span>Tunai</span><span className="key-hint">F5</span></button>

            <button className="func-btn action" onClick={() => requestAuthorization("HOLD_RECALL", () => handleHoldTransaction())}><Lock size={18} /><span>Hold</span></button>
            <button className="func-btn action" onClick={() => requestAuthorization("HOLD_RECALL", () => setIsRecallModalOpen(true))}><History size={18} /><span>Recall</span></button>
            <button className="func-btn action" onClick={() => setIsMemberModalOpen(true)}><User size={18} /><span>Member</span></button>
            <button className="func-btn secondary" onClick={() => setIsCloseShiftModalOpen(true)}><LogOut size={18} /><span>Tutup Kasir</span></button>
            <button className="func-btn danger" onClick={() => requestAuthorization("VOID", () => updateQuantity(items[items.length - 1]?.productId, 0))}><Eraser size={18} /><span>Void Item</span><span className="key-hint">Del</span></button>
            <button className="func-btn danger" onClick={() => requestAuthorization("VOID", () => { setItems([]); setManualTotalDiscount(0); setIsReturnMode(false); })}><Trash2 size={18} /><span>Void All</span><span className="key-hint">Esc</span></button>
            <button className="func-btn secondary" onClick={() => requestAuthorization("SETTINGS", () => setIsTerminalModalOpen(true))}><Settings size={18} /><span>Menu</span></button>
          </div>

          <div className="input-action-section" style={{ marginTop: 'auto', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <div className="barcode-input-wrapper" style={{ maxWidth: 'none', position: 'relative' }}>
              <div className="input-icon"><Search size={20} /></div>
              <input
                ref={barcodeInput}
                type="text"
                className={`modern-barcode-input ${isSubtotalMode ? 'input-payment' : ''}`}
                style={{ width: '100%', paddingLeft: '3rem' }}
                value={inputValue}
                placeholder={isSubtotalMode ? "Masukkan jumlah uang..." : "Scan Barcode / Cari Produk..."}
                onChange={(e) => handleInputChange(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (highlightedIndex < searchResults.length - 1) {
                      const newIndex = highlightedIndex + 1;
                      setHighlightedIndex(newIndex);
                      setTimeout(() => {
                        const items = document.querySelectorAll('.search-item');
                        if (items[newIndex]) items[newIndex].scrollIntoView({ block: 'nearest' });
                      }, 0);
                    }
                  } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (highlightedIndex > 0) {
                      const newIndex = highlightedIndex - 1;
                      setHighlightedIndex(newIndex);
                      setTimeout(() => {
                        const items = document.querySelectorAll('.search-item');
                        if (items[newIndex]) items[newIndex].scrollIntoView({ block: 'nearest' });
                      }, 0);
                    }
                  } else if (e.key === 'Enter') {
                    if (isSubtotalMode) {
                      const amount = e.target.value;
                      setReceivedAmount(amount);
                      setInputValue('');
                      setIsSubtotalMode(false);
                      if (amount) {
                        setTimeout(() => processTransaction('CASH', null, amount), 100);
                      }
                    } else if (highlightedIndex >= 0 && highlightedIndex < searchResults.length) {
                      addItemToTransaction(searchResults[highlightedIndex]);
                      handleClearInput();
                    } else if (searchResults.length === 1) {
                      addItemToTransaction(searchResults[0]);
                      handleClearInput();
                    } else {
                      handleBarcodeScan(e.target.value);
                    }
                  }
                  if (e.key === 'Escape') handleClearInput();
                }}
                autoFocus
              />
              {searchResults.length > 0 && (
                <div className="search-results-floating fade-in" style={{ bottom: '100%', left: 0, width: '100%', maxHeight: '300px', overflowY: 'auto' }}>
                  {searchResults.map((p, index) => (
                    <div key={p.id}
                      className={`search-item ${index === highlightedIndex ? 'highlighted' : ''}`}
                      style={{
                        padding: '10px 15px',
                        cursor: 'pointer',
                        borderBottom: '1px solid #f1f5f9',
                        backgroundColor: index === highlightedIndex ? '#dbeafe' : 'white',
                        borderLeft: index === highlightedIndex ? '4px solid #2563eb' : '4px solid transparent',
                        transition: 'all 0.1s ease-in-out',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                      }}
                      onClick={() => { addItemToTransaction(p); handleClearInput(); }}>
                      <div style={{ display: 'flex', flexDirection: 'column' }}>
                        <span className="sku" style={{ fontWeight: '700', color: index === highlightedIndex ? '#1e40af' : '#1f2937' }}>{p.sku}</span>
                        <span className="name" style={{ fontSize: '0.75rem', color: index === highlightedIndex ? '#3b82f6' : '#6b7280' }}>{p.name}</span>
                      </div>
                      <span className="price" style={{ fontWeight: '600', color: '#3b82f6' }}>{formatCurrency(p.selling_price)}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div style={{ display: 'flex', gap: '0.5rem' }}>
              <button
                className={`btn-subtotal ${isSubtotalMode ? 'active' : ''}`}
                style={{ flex: 1, padding: '1rem' }}
                onClick={() => { setIsSubtotalMode(true); barcodeInput.current.focus(); }}
              >
                SUBTOTAL (F9)
              </button>
              <button
                className="btn-process-payment"
                style={{ flex: 2, padding: '1rem', fontSize: '1.25rem', background: activeShift ? '#10b981' : '#64748b' }}
                disabled={isProcessing || items.length === 0 || (isSubtotalMode && !receivedAmount) || !activeShift}
                onClick={() => processTransaction()}
              >
                {isProcessing ? '...' : (activeShift ? 'BAYAR (F10)' : 'BELUM BUKA SHIFT')}
              </button>
            </div>
          </div>
        </aside>
      </div>
    </div>
  );
};
