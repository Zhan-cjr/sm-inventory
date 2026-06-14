import React, { useState, useRef, useEffect } from 'react';
import { useOfflineSync } from '../hooks/useOfflineSync';
import { DiscountEngine } from '../utils/DiscountEngine';
import { AuthorizationModal } from './AuthorizationModal';
import { ReturnItemModal } from './ReturnItemModal';
import { ReceiptPreview } from './ReceiptPreview';
import { EODReportPreview } from './EODReportPreview';
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
  Clock,
  Ticket,
  Layers,
  Edit3,
  Wallet,
  Printer,
  Sun,
  Moon
} from 'lucide-react';

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    console.warn(`[Storage Warning] Failed to save key "${key}" to localStorage:`, e);
  }
};

export const POSTransaction = ({
  branchId,
  branchName,
  branchCode,
  branchAddress,
  orgName,
  authToken,
  userName,
  userRole,
  onLogout,
  lockedTerminalId,
  lockedTerminalName
}) => {
  const pointConversionRate = (() => {
    try {
      const userObj = JSON.parse(localStorage.getItem('pos_user'));
      return parseInt(userObj?.point_conversion_rate || '1000', 10);
    } catch (e) {
      return 1000;
    }
  })();

  const pointRedemptionValue = (() => {
    try {
      const userObj = JSON.parse(localStorage.getItem('pos_user'));
      return parseFloat(userObj?.point_redemption_value || '1');
    } catch (e) {
      return 1;
    }
  })();

  const minimumPointsToRedeem = (() => {
    try {
      const userObj = JSON.parse(localStorage.getItem('pos_user'));
      return parseInt(userObj?.minimum_points_to_redeem || '100', 10);
    } catch (e) {
      return 100;
    }
  })();

  const pointRedemptionEnabled = (() => {
    try {
      const userObj = JSON.parse(localStorage.getItem('pos_user'));
      return userObj?.point_redemption_enabled !== false;
    } catch (e) {
      return true;
    }
  })();

  const formatThousandSeparator = (valStr) => {
    if (valStr === null || valStr === undefined) return '';
    const clean = valStr.toString().replace(/[^0-9]/g, '');
    return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  };

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
  const [payments, setPayments] = useState([]); // Array of { method: 'CASH'|'CARD'|'VOUCHER', amount: number, bankId: string|null, voucherId: string|null, label: string }
  const [isMultiPaymentModalOpen, setIsMultiPaymentModalOpen] = useState(false);
  const [isVoucherModalOpen, setIsVoucherModalOpen] = useState(false);
  const [voucherInput, setVoucherInput] = useState('');
  const [voucherSource, setVoucherSource] = useState(null); // 'MULTI' or null
  const [isMultiCashModalOpen, setIsMultiCashModalOpen] = useState(false);
  const [multiCashInput, setMultiCashInput] = useState('');

  const [isPpobMenuOpen, setIsPpobMenuOpen] = useState(false);
  const [ppobTransactions, setPpobTransactions] = useState([]);
  const [isFetchingPpobTransactions, setIsFetchingPpobTransactions] = useState(false);
  const [ppobSearchQuery, setPpobSearchQuery] = useState('');

  // Direct Payment State
  const [isDirectCashModalOpen, setIsDirectCashModalOpen] = useState(false);
  const [directCashInput, setDirectCashInput] = useState('');
  const [isDirectCardAmountModalOpen, setIsDirectCardAmountModalOpen] = useState(false);
  const [directCardInput, setDirectCardInput] = useState('');

  const [isOpenPriceModalOpen, setIsOpenPriceModalOpen] = useState(false);
  const [openPriceTargetItem, setOpenPriceTargetItem] = useState(null);
  const [newOpenPrice, setNewOpenPrice] = useState('');

  // Point Redemption State
  const [isRedeemPointModalOpen, setIsRedeemPointModalOpen] = useState(false);
  const [pointsToRedeemInput, setPointsToRedeemInput] = useState('');
  const [banks, setBanks] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_cached_banks') || '[]'); } catch (e) { return []; }
  });
  const [selectedBank, setSelectedBank] = useState(null);
  const [isBankSelectOpen, setIsBankSelectOpen] = useState(false);
  const [isMultiBankSelectOpen, setIsMultiBankSelectOpen] = useState(false);
  const [isMultiCardAmountModalOpen, setIsMultiCardAmountModalOpen] = useState(false);
  const [multiCardInput, setMultiCardInput] = useState('');
  const [pendingCardAmount, setPendingCardAmount] = useState(0);

  // Initialize terminalInfo: locked terminal takes absolute priority, then local storage cache
  const [terminalInfo, setTerminalInfo] = useState(() => {
    if (lockedTerminalId) {
      return { id: lockedTerminalId, name: lockedTerminalName || 'Terminal Terkunci', orgName: orgName };
    }
    return { id: localStorage.getItem('pos_terminal_id'), name: localStorage.getItem('pos_terminal_name') || 'Belum Diatur', orgName: orgName };
  });

  const [theme, setTheme] = useState(() => localStorage.getItem('pos_theme') || 'dark');
  const [changeModalInfo, setChangeModalInfo] = useState(null);
  const [isPrinterSettingsOpen, setIsPrinterSettingsOpen] = useState(false);
  const [localPrinterSettings, setLocalPrinterSettings] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('pos_printer_settings')) || { autoPrint: false, printMode: 'TEXT', receiptType: 1, columns: 32, feedLines: 4, printerName: '' };
    } catch (e) {
      return { autoPrint: false, printMode: 'TEXT', receiptType: 1, columns: 32, feedLines: 4, printerName: '' };
    }
  });
  const [posSettings, setPosSettings] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_cached_settings') || '[]'); } catch (e) { return []; }
  });
  const [branchSettings, setBranchSettings] = useState(() => {
    try { return JSON.parse(localStorage.getItem('pos_cached_branch_settings') || 'null'); } catch (e) { return null; }
  });
  const [searchResults, setSearchResults] = useState([]);
  const [highlightedIndex, setHighlightedIndex] = useState(-1);
  const [inputValue, setInputValue] = useState('');
  const [allTerminals, setAllTerminals] = useState([]);

  // Initialize terminal select modal state: if locked, it's permanently closed (false)
  const [isTerminalModalOpen, setIsTerminalModalOpen] = useState(() => {
    if (lockedTerminalId) {
      return false;
    }
    return !localStorage.getItem('pos_terminal_id');
  });
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

  // New State for Qty and Reprint
  const [nextItemQty, setNextItemQty] = useState('');
  const [isQtyModalOpen, setIsQtyModalOpen] = useState(false);
  const [isReprintOldModalOpen, setIsReprintOldModalOpen] = useState(false);
  const [oldReceiptInput, setOldReceiptInput] = useState('');

  // Cash Management State
  const [isCashMovementModalOpen, setIsCashMovementModalOpen] = useState(false);
  const [cashMovementType, setCashMovementType] = useState('CASH_IN');
  const [cashMovementAmount, setCashMovementAmount] = useState('');
  const [cashMovementDesc, setCashMovementDesc] = useState('');

  // EOD Report State
  const [eodReportData, setEodReportData] = useState(null);

  // Digital Product Input State
  const [isDigitalInputModalOpen, setIsDigitalInputModalOpen] = useState(false);
  const [pendingDigitalProduct, setPendingDigitalProduct] = useState(null);
  const [customerNoInput, setCustomerNoInput] = useState('');

  const { storeLocalTransaction, syncTransactions, pendingCount, syncStatus } = useOfflineSync(branchId, authToken);
  const discountEngine = useRef(new DiscountEngine([]));

  // --- IndexedDB Helper for Large Caches ---
  const idbCache = {
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

  useEffect(() => {
    if (window.electronAPI) {
      window.electronAPI.getConfig().then(config => {
        if (config) {
          setLocalPrinterSettings(prev => ({
            ...prev,
            autoPrint: config.autoPrint !== undefined ? !!config.autoPrint : prev.autoPrint,
            printMode: config.printMode || prev.printMode,
            printerName: config.printerName !== undefined ? config.printerName : prev.printerName,
            receiptType: config.receiptType || prev.receiptType,
            columns: config.columns || prev.columns,
            feedLines: config.feedLines !== undefined ? config.feedLines : prev.feedLines
          }));
        }
      });
    }
  }, []);

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('pos_theme', theme);
  }, [theme]);

  const toggleTheme = () => {
    setTheme(prev => prev === 'dark' ? 'light' : 'dark');
  };

  useEffect(() => {
    const checkServerConnection = async () => {
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

    checkServerConnection();
    const connectionInterval = setInterval(checkServerConnection, 10000);

    const timer = setInterval(() => {
      const offset = parseInt(localStorage.getItem('pos_server_offset') || '0');
      setCurrentTime(new Date(Date.now() + offset));
    }, 1000);

    const handleGlobalKeyPress = (e) => {
      if (isPpobMenuOpen) {
        if (e.key === 'Escape') {
          e.preventDefault();
          setIsPpobMenuOpen(false);
        }
        return;
      }

      if (changeModalInfo) {
        if (e.key === 'Enter') {
          e.preventDefault();
          setChangeModalInfo(null);
          setShowReceiptPreview(true);
        } else if (e.key === 'Escape') {
          e.preventDefault();
          setChangeModalInfo(null);
        }
      }
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
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
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
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          setDbProducts(prev => {
            const services = prev.filter(p => p.is_service);
            return [...data, ...services];
          });
          idbCache.set('pos_cached_products', data);
        }
      })
      .catch(async err => {
        console.warn('Failed to load products, using cache:', err);
        const parsed = await idbCache.get('pos_cached_products');
        if (parsed && Array.isArray(parsed)) {
          setDbProducts(prev => {
            const services = prev.filter(p => p.is_service);
            return [...parsed, ...services];
          });
        }
      });

    fetch('/api/v1/promotions', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
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
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
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

    fetch('/api/v1/branches', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          const activeBranch = data.find(b => b.id === branchId) || data[0];
          if (activeBranch) {
            setBranchSettings(activeBranch);
            safeSetItem('pos_cached_branch_settings', JSON.stringify(activeBranch));
          }
        }
      })
      .catch(err => {
        console.error('Failed to load branch settings:', err);
        const cached = localStorage.getItem('pos_cached_branch_settings');
        if (cached) {
          try { setBranchSettings(JSON.parse(cached)); } catch (e) { }
        }
      });

    fetch('/api/v1/pos-settings', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
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

    // Refresh user settings (e.g. allow_minus_stock) on load
    fetch('/api/v1/user', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        if (data.user) {
          // Merge with existing pos_user to preserve token and other local properties
          const existingUser = JSON.parse(localStorage.getItem('pos_user') || '{}');
          const updatedUser = { ...existingUser, ...data.user };
          safeSetItem('pos_user', JSON.stringify(updatedUser));
        }
      })
      .catch(err => console.error('Failed to refresh user profile:', err));

    fetch('/api/v1/customers', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
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
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          const servicesAsProducts = data.map(s => ({
            id: s.id,
            sku: s.code,
            barcode: s.code,
            name: s.name + ' (Jasa)',
            selling_price: s.price,
            category_id: null,
            is_service: true
          }));
          setDbProducts(prev => {
            const productsOnly = prev.filter(p => !p.is_service);
            return [...productsOnly, ...servicesAsProducts];
          });
          safeSetItem('pos_cached_services', JSON.stringify(data));
        }
      })
      .catch(err => {
        console.warn('Failed to load services, using cache:', err);
        const cached = localStorage.getItem('pos_cached_services');
        if (cached) {
          try {
            const parsed = JSON.parse(cached);
            if (Array.isArray(parsed)) {
              const servicesAsProducts = parsed.map(s => ({
                id: s.id,
                sku: s.code,
                barcode: s.code,
                name: s.name + ' (Jasa)',
                selling_price: s.price,
                category_id: null,
                is_service: true
              }));
              setDbProducts(prev => {
                const productsOnly = prev.filter(p => !p.is_service);
                return [...productsOnly, ...servicesAsProducts];
              });
            }
          } catch (e) { }
        }
      });

    fetch(`/api/v1/terminals?branch_id=${branchId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
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
          const terminal = data.find(t => t.id === activeTerminalId);
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
        if (!lockedTerminalId && activeTerminalId && terminalBranchId && terminalBranchId !== branchId && userRole !== 'ADMIN') {
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
          const terminal = terminals.find(t => t.id === activeTerminalId);
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
      let isScale = false;
      let scaleItemCode = '';
      let scaleQty = 1;

      // Extract scale barcode settings
      const userObj = JSON.parse(localStorage.getItem('pos_user')) || {};
      const scaleEnabled = userObj.scale_barcode_enabled === true;

      if (scaleEnabled) {
        const prefix = userObj.scale_barcode_prefix || '20';
        const itemCodeLen = parseInt(userObj.scale_barcode_item_code_length) || 5;
        const weightLen = parseInt(userObj.scale_barcode_weight_length) || 5;
        const weightDecimals = parseInt(userObj.scale_barcode_weight_decimal_places) || 3;
        const expectedLen = prefix.length + itemCodeLen + weightLen + 1; // +1 for checksum

        if (barcode.startsWith(prefix) && barcode.length === expectedLen) {
          isScale = true;
          scaleItemCode = barcode.substring(prefix.length, prefix.length + itemCodeLen);
          const weightStr = barcode.substring(prefix.length + itemCodeLen, prefix.length + itemCodeLen + weightLen);
          scaleQty = parseFloat(weightStr) / Math.pow(10, weightDecimals);
        }
      }

      const searchCode = isScale ? scaleItemCode : barcode;
      const product = dbProducts.find(p => p.sku === searchCode || p.barcode === searchCode);

      if (product) {
        addItemToTransaction(product, isScale ? scaleQty : null);
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
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
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
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
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

  const handleCashMovement = () => {
    if (!cashMovementAmount || isNaN(cashMovementAmount) || cashMovementAmount <= 0) {
      setAlertMsg({ text: 'Nominal harus lebih besar dari 0.', type: 'error' });
      return;
    }
    if (!cashMovementDesc.trim()) {
      setAlertMsg({ text: 'Keterangan wajib diisi.', type: 'error' });
      return;
    }
    if (!activeShift) {
      setAlertMsg({ text: 'Tidak ada shift aktif.', type: 'error' });
      return;
    }

    setIsProcessing(true);
    fetch('/api/v1/shifts/cash-movement', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
      },
      body: JSON.stringify({
        shift_id: activeShift.id,
        terminal_id: terminalInfo.id,
        type: cashMovementType,
        amount: parseFloat(cashMovementAmount),
        description: cashMovementDesc
      })
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        setIsProcessing(false);
        setAlertMsg({ text: data.message, type: 'success' });
        setIsCashMovementModalOpen(false);
        setCashMovementAmount('');
        setCashMovementDesc('');
        // Update local active shift total cash
        const newShift = { ...activeShift };
        if (cashMovementType === 'CASH_IN') {
          newShift.total_cash_in = (newShift.total_cash_in || 0) + parseFloat(cashMovementAmount);
        } else {
          newShift.total_cash_out = (newShift.total_cash_out || 0) + parseFloat(cashMovementAmount);
        }
        setActiveShift(newShift);
        safeSetItem('pos_active_shift', JSON.stringify(newShift));
      })
      .catch(err => {
        setIsProcessing(false);
        setAlertMsg({ text: 'Gagal mencatat manajemen kas (pastikan online).', type: 'error' });
      });
  };

  const fetchPpobTransactions = async () => {
    setIsFetchingPpobTransactions(true);
    try {
      const res = await fetch('/api/v1/transactions/ppob/today', {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      if (!res.ok) throw new Error('Gagal mengambil data PPOB hari ini');
      const data = await res.json();
      setPpobTransactions(data);
    } catch (err) {
      console.error(err);
      setAlertMsg({ text: 'Error: ' + err.message, type: 'error' });
    } finally {
      setIsFetchingPpobTransactions(false);
    }
  };

  const handleCheckPpobStatus = async (ppobId) => {
    try {
      const res = await fetch(`/api/v1/transactions/ppob/${ppobId}/check-status`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      if (!res.ok) throw new Error('Gagal cek status PPOB');
      const { data } = await res.json();

      setPpobTransactions(prev => prev.map(tx => {
        const updatedPpobs = tx.ppob_transactions.map(p => p.id === ppobId ? data : p);
        return { ...tx, ppob_transactions: updatedPpobs };
      }));
      setAlertMsg({ text: 'Status berhasil dicek!', type: 'success' });
      setTimeout(() => setAlertMsg(null), 2000);
    } catch (err) {
      console.error(err);
      setAlertMsg({ text: 'Error: ' + err.message, type: 'error' });
    }
  };

  const handleReprintPpob = (tx) => {
    setLastTransaction({ ...mapApiTransactionToLocal(tx), isReprint: true });
    setShowReceiptPreview(true);
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
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        setIsProcessing(false);
        if (data.shift) {
          localStorage.removeItem('pos_active_shift');
          setActiveShift(null);
          setIsCloseShiftModalOpen(false);
          setAlertMsg({ text: 'Shift berhasil ditutup. Menyiapkan Laporan EOD...', type: 'success' });
          setEodReportData(data.shift);
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
      setItems(items.map(i =>
        (i.productId === product.id && i.customerNo === customerNo) ? { ...i, quantity: i.quantity + qtyToAdd, manualDiscount: manualDiscount || i.manualDiscount } : i
      ));
    } else {
      setItems([...items, {
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
      }]);
    }

    // Reset nextItemQty back to empty
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

  const subtotal = items.reduce((sum, item) => sum + (item.quantity * parseFloat(item.unitPrice)) - (item.quantity * (item.manualDiscount || 0)), 0);
  const { totalDiscount, appliedPromos } = discountEngine.current.calculateTotalDiscount(
    items,
    selectedCustomer ? { memberTier: selectedCustomer.member_tier, tierDiscountPercent: selectedCustomer.tier_discount_percent } : { memberTier: 'REGULAR', tierDiscountPercent: 0 },
    subtotal
  );
  const finalAmount = Math.round(subtotal - totalDiscount - manualTotalDiscount);
  const totalPaid = payments.length > 0
    ? payments.reduce((sum, p) => sum + p.amount, 0)
    : (receivedAmount ? parseFloat(receivedAmount) : 0);
  const changeAmount = totalPaid - finalAmount;

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
    setTimeout(() => barcodeInput.current?.focus(), 100);
  };

  const handleApplyPoints = () => {
    if (!pointRedemptionEnabled) {
      setAlertMsg({ text: 'Penukaran poin saat ini dinonaktifkan oleh Perusahaan.', type: 'error' });
      setTimeout(() => setAlertMsg(null), 3000);
      return;
    }

    const pointsToUse = parseInt(pointsToRedeemInput, 10);

    if (isNaN(pointsToUse) || pointsToUse <= 0) {
      setAlertMsg({ text: 'Jumlah poin tidak valid.', type: 'error' });
      setTimeout(() => setAlertMsg(null), 3000);
      return;
    }

    if (pointsToUse < minimumPointsToRedeem) {
      setAlertMsg({ text: `Minimal penukaran adalah ${minimumPointsToRedeem} Poin.`, type: 'error' });
      setTimeout(() => setAlertMsg(null), 3000);
      return;
    }

    if (!selectedCustomer) return;

    if (pointsToUse > selectedCustomer.points) {
      setAlertMsg({ text: `Poin pelanggan tidak mencukupi (Sisa: ${selectedCustomer.points}).`, type: 'error' });
      setTimeout(() => setAlertMsg(null), 3000);
      return;
    }

    const valueInRp = pointsToUse * pointRedemptionValue;
    const currentPaid = payments.reduce((sum, p) => sum + p.amount, 0);
    const remainingToPay = finalAmount - currentPaid;

    if (valueInRp > remainingToPay && remainingToPay > 0) {
      setAlertMsg({ text: `Nilai poin (Rp ${formatCurrency(valueInRp)}) melebihi sisa tagihan (Rp ${formatCurrency(remainingToPay)}). Kurangi poin yang ditukar.`, type: 'error' });
      setTimeout(() => setAlertMsg(null), 3500);
      return;
    }

    setPayments([...payments, {
      method: 'POINT',
      amount: valueInRp > remainingToPay ? remainingToPay : valueInRp,
      points_deducted: pointsToUse,
      label: `Tukar Poin (${pointsToUse})`
    }]);

    setAlertMsg({ text: `Berhasil menukar ${pointsToUse} poin senilai Rp ${formatCurrency(valueInRp > remainingToPay ? remainingToPay : valueInRp)}.`, type: 'success' });
    setTimeout(() => setAlertMsg(null), 2500);
    setIsRedeemPointModalOpen(false);
    setPointsToRedeemInput('');
  };

  const handleClearDiscount = () => {
    // Preserve items in the cart!
    setManualTotalDiscount(0);
    setQueuedDiscount(null);
    setReceivedAmount('');
    setPayments([]);
    setInputValue('');
    setIsSubtotalMode(false);
    setSelectedBank(null);
    setSearchResults([]);
    setHighlightedIndex(-1);

    setAlertMsg({ text: 'Diskon & pembayaran dibersihkan. POS siap scan barang kembali.', type: 'success' });
    setTimeout(() => setAlertMsg(null), 2500);

    setTimeout(() => {
      if (barcodeInput.current) {
        barcodeInput.current.value = '';
        barcodeInput.current.focus();
      }
    }, 50);
  };

  const requestAuthorization = (actionName, callback) => {
    const userObjStr = localStorage.getItem('pos_user');
    let hasAuth = false;
    if (userObjStr) {
      try {
        const userObj = JSON.parse(userObjStr);
        if (userObj.pos_authorizations && Array.isArray(userObj.pos_authorizations)) {
          if (userObj.pos_authorizations.includes(actionName)) {
            hasAuth = true;
          }
        }
      } catch (e) { }
    }

    if (hasAuth) {
      callback();
    } else {
      setPendingAuthAction({ name: actionName, callback });
    }
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
    } else if (method === 'CASH') {
      const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
      setDirectCashInput(sisa > 0 ? formatThousandSeparator(sisa) : '');
      setIsDirectCashModalOpen(true);
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

      let finalPayments = [...payments];
      if (overrideReceived !== null) {
        finalPayments.push({ method, amount: parseFloat(overrideReceived), bankId, label: method === 'CASH' ? 'Tunai' : (method === 'CARD' ? 'Card' : method) });
      } else if (payments.length === 0) {
        finalPayments = [{ method, amount: parseFloat(receivedAmount) || finalAmount, bankId }];
      }

      const currentReceived = finalPayments.reduce((sum, p) => sum + p.amount, 0);

      if (currentReceived < currentFinalAmount) {
        setAlertMsg({ text: `Pembayaran kurang! Kurang: ${formatCurrency(currentFinalAmount - currentReceived)}`, type: 'error' });
        setTimeout(() => setAlertMsg(null), 3000);
        setIsProcessing(false);
        return;
      }

      const currentChange = currentReceived - currentFinalAmount;

      const grossTotal = items.reduce((sum, item) => sum + (item.quantity * parseFloat(item.unitPrice)), 0);
      const totalItemManualDiscount = items.reduce((sum, item) => sum + (item.quantity * (item.manualDiscount || 0)), 0);

      const actualPaymentMethod = finalPayments.length > 1 ? 'MULTI' : finalPayments[0].method;

      const transaction = {
        items,
        totalAmount: grossTotal,
        discountAmount: totalItemManualDiscount + totalDiscount + manualTotalDiscount,
        manualDiscount: totalItemManualDiscount + manualTotalDiscount,
        promoDiscount: totalDiscount,
        finalAmount: currentFinalAmount,
        paymentMethod: actualPaymentMethod,
        payments: finalPayments,
        bankId: bankId,
        terminalId: terminalInfo.id,
        terminalCode: terminalInfo?.code,
        customerId: selectedCustomer?.id,
        shiftId: activeShift?.id,
        receivedAmount: currentReceived,
        changeAmount: currentChange,
        appliedPromos,
        receipt_number: (branchCode || 'SMI') + '-' + Math.random().toString(36).substring(2, 8).toUpperCase(),
        transaction_type: isReturnMode ? 'RETURN' : 'SALES',
      };

      const localTx = await storeLocalTransaction(transaction);
      let syncResult = null;
      if (isOnline) {
        syncResult = await syncTransactions();
      }

      let finalItemsForReceipt = [...transaction.items];
      if (syncResult && syncResult.ppobData && syncResult.ppobData[localTx.localId]) {
        const ppobList = syncResult.ppobData[localTx.localId];
        finalItemsForReceipt = finalItemsForReceipt.map(item => {
          if (item.productType === 'digital') {
            const ppob = ppobList.find(p => p.productId == item.productId);
            if (ppob) {
              return {
                ...item,
                sn: ppob.sn,
                ppobStatus: ppob.status,
                ppobMessage: ppob.message
              };
            }
          }
          return item;
        });
      }

      const currentCustomer = selectedCustomer;

      // Clear states BEFORE showing modal to avoid flicker
      setItems([]);
      setPayments([]);
      setManualTotalDiscount(0);
      setReceivedAmount('');
      setInputValue('');
      setIsSubtotalMode(false);
      setSelectedBank(null);

      // Update customer points locally if selected (offline-safe)
      if (currentCustomer) {
        const earnedPoints = Math.floor(currentFinalAmount / pointConversionRate);
        const updatedPoints = (currentCustomer.points || 0) + earnedPoints;

        // Recalculate tier locally
        let updatedTier = 'BRONZE';
        if (updatedPoints >= 10000) updatedTier = 'PLATINUM';
        else if (updatedPoints >= 5000) updatedTier = 'GOLD';
        else if (updatedPoints >= 1000) updatedTier = 'SILVER';

        const updatedCustomer = {
          ...currentCustomer,
          points: updatedPoints,
          member_tier: updatedTier
        };

        const updatedCustomersList = customers.map(c =>
          c.id === currentCustomer.id ? updatedCustomer : c
        );

        setCustomers(updatedCustomersList);
        safeSetItem('pos_cached_customers', JSON.stringify(updatedCustomersList));
      }

      setSelectedCustomer(null);
      setIsBankSelectOpen(false);
      setIsReturnMode(false);

      // Small delay to ensure the key event that triggered this doesn't immediately close the modal
      setTimeout(() => {
        setLastTransaction({
          ...transaction,
          items: finalItemsForReceipt,
          branchName,
          branchAddress,
          orgName,
          userName,
          customerName: currentCustomer?.name,
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
    setPayments([]);
    setManualTotalDiscount(0);
    setIsReturnMode(false);
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

  const mapApiTransactionToLocal = (data) => {
    const ppobs = data.ppob_transactions ? [...data.ppob_transactions] : [];
    return {
      receiptNumber: data.receipt_number,
      terminalId: data.terminal_id,
      terminalCode: data.terminal?.code || data.terminal_code || allTerminals?.find(t => t.id === data.terminal_id)?.code || data.terminal_id?.substring(0, 8),
      timestamp: data.created_at,
      items: data.items.map(item => {
        let sn = null;
        let customerNo = null;
        let customerName = null;
        let ppobStatus = null;
        let ppobMessage = null;
        if (item.product?.product_type === 'digital' && ppobs.length > 0) {
          const ppob = ppobs.shift();
          sn = ppob.sn;
          customerNo = ppob.customer_no;
          customerName = ppob.customer_name;
          ppobStatus = ppob.status;
          ppobMessage = ppob.message;
        }
        return {
          name: item.product ? item.product.name : item.product_name,
          quantity: item.quantity,
          unitPrice: item.unit_price,
          manualDiscount: item.manual_discount || 0,
          sn,
          customerNo,
          customerName,
          ppobStatus,
          ppobMessage
        };
      }),
      totalAmount: data.total_amount,
      totalDiscount: data.total_discount,
      manualTotalDiscount: data.manual_discount || 0,
      finalAmount: data.final_amount,
      paymentMethod: data.payment_method,
      receivedAmount: data.received_amount,
      changeAmount: data.change_amount,
      branchName: data.branch?.name || branchName,
      branchAddress: data.branch?.address || branchAddress,
      orgName: data.organization?.name || orgName,
      userName: data.cashier?.name || userName,
    };
  };

  const handleReprintLast = async () => {
    try {
      const res = await fetch('/api/v1/transactions/latest', {
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'X-Terminal-ID': terminalInfo?.id || ''
        }
      });
      if (!res.ok) throw new Error('Tidak ada transaksi di kassa ini.');
      const data = await res.json();

      setLastTransaction({ ...mapApiTransactionToLocal(data), isReprint: true });
      setShowReceiptPreview(true);
    } catch (e) {
      setAlertMsg({ text: e.message || 'Gagal memuat nota terakhir', type: 'error' });
      setTimeout(() => setAlertMsg(null), 3000);
    }
  };

  const handleReprintOld = async () => {
    if (!oldReceiptInput) return;
    try {
      const res = await fetch(`/api/v1/transactions/receipt/${encodeURIComponent(oldReceiptInput)}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      if (!res.ok) throw new Error('Nota tidak ditemukan.');
      const data = await res.json();

      setLastTransaction({ ...mapApiTransactionToLocal(data), isReprint: true });
      setIsReprintOldModalOpen(false);
      setOldReceiptInput('');
      setShowReceiptPreview(true);
    } catch (e) {
      setAlertMsg({ text: e.message || 'Gagal mencari nota', type: 'error' });
      setTimeout(() => setAlertMsg(null), 3000);
    }
  };

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
  };

  const getShortcutHint = (keyName, fallback) => {
    const setting = posSettings?.find(s => s.key_name === keyName);
    return setting && setting.is_active ? setting.shortcut_key : fallback;
  };

  const renderBtnLabel = (keyName, defaultName, defaultShortcut) => {
    const setting = posSettings?.find(s => s.key_name === keyName);
    const displayName = setting ? setting.display_name : defaultName;
    const shortcut = setting ? setting.shortcut_key : defaultShortcut;

    return (
      <>
        <span style={{ fontWeight: '600', fontSize: '0.8rem', lineHeight: '1.2', textAlign: 'center' }}>{displayName}</span>
        {shortcut && <span style={{ fontSize: '0.65rem', opacity: 0.8, fontWeight: 'normal', lineHeight: '1' }}>({shortcut})</span>}
      </>
    );
  };


  useEffect(() => {
    const handleShortcuts = (e) => {
      const settingsList = posSettings && posSettings.length > 0 ? posSettings : [
        { key_name: 'btn_subtotal', shortcut_key: 'F9', is_active: 1 },
        { key_name: 'btn_disc_item_rp', shortcut_key: 'F1', is_active: 1 },
        { key_name: 'btn_disc_item_pct', shortcut_key: 'F2', is_active: 1 },
        { key_name: 'btn_disc_total_rp', shortcut_key: 'F3', is_active: 1 },
        { key_name: 'btn_disc_total_pct', shortcut_key: 'F4', is_active: 1 },
        { key_name: 'btn_tunai', shortcut_key: 'F5', is_active: 1 },
        { key_name: 'btn_card', shortcut_key: 'F6', is_active: 1 },
        { key_name: 'btn_qty', shortcut_key: 'F7', is_active: 1 },
        { key_name: 'btn_close_shift', shortcut_key: 'F8', is_active: 1 },
        { key_name: 'btn_reprint_last', shortcut_key: 'F11', is_active: 1 },
        { key_name: 'btn_reprint_old', shortcut_key: 'F12', is_active: 1 },
        { key_name: 'btn_ppob_menu', shortcut_key: 'F10', is_active: 1 },
        { key_name: 'btn_member', shortcut_key: 'Home', is_active: 1 },
        { key_name: 'btn_retur', shortcut_key: 'End', is_active: 1 },
        { key_name: 'btn_hold', shortcut_key: 'PageUp', is_active: 1 },
        { key_name: 'btn_recall', shortcut_key: 'PageDown', is_active: 1 },
        { key_name: 'btn_clear', shortcut_key: 'Insert', is_active: 1 },
        { key_name: 'btn_void_item', shortcut_key: 'Delete', is_active: 1 },
        { key_name: 'btn_void_all', shortcut_key: 'Escape', is_active: 1 },
        { key_name: 'btn_voucher', shortcut_key: '', is_active: 1 },
        { key_name: 'btn_multi_pay', shortcut_key: '', is_active: 1 },
        { key_name: 'btn_open_price', shortcut_key: '', is_active: 1 },
        { key_name: 'btn_kas', shortcut_key: '', is_active: 1 }
      ];

      // Find if the key pressed is one of the active registered shortcut keys (case-insensitive)
      const setting = settingsList.find(s => s.shortcut_key && s.shortcut_key.toLowerCase() === e.key.toLowerCase());
      const isActive = (val) => val === true || val === 1 || val === "1";
      if (!setting || !isActive(setting.is_active)) return;

      // If focusing on input, we only allow key presses that are exactly registered in settingsList
      if (e.target.tagName === 'INPUT') {
        const allowedKeys = settingsList
          .filter(s => isActive(s.is_active) && s.shortcut_key)
          .map(s => s.shortcut_key.toLowerCase());
        if (!allowedKeys.includes(e.key.toLowerCase())) {
          return;
        }
      }

      e.preventDefault();
      switch (setting.key_name) {
        case 'btn_pay':
          processTransaction();
          break;
        case 'btn_subtotal':
          setIsSubtotalMode(true);
          barcodeInput.current?.focus();
          break;
        case 'btn_disc_item_rp':
          requestAuthorization("DISCOUNT", () => handleManualDiscountItem('NOMINAL'));
          break;
        case 'btn_disc_item_pct':
          requestAuthorization("DISCOUNT", () => handleManualDiscountItem('PERCENT'));
          break;
        case 'btn_disc_total_rp':
          requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('NOMINAL'));
          break;
        case 'btn_disc_total_pct':
          requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('PERCENT'));
          break;
        case 'btn_tunai':
          startPayment('CASH');
          break;
        case 'btn_card':
          startPayment('CARD');
          break;
        case 'btn_voucher':
          requestAuthorization("VOUCHER", () => setIsVoucherModalOpen(true));
          break;
        case 'btn_multi_pay':
          requestAuthorization("MULTI_PAYMENT", () => setIsMultiPaymentModalOpen(true));
          break;
        case 'btn_open_price':
          requestAuthorization("OPEN_PRICE", () => {
            if (items.length > 0) {
              setOpenPriceTargetItem(items[items.length - 1]);
              setIsOpenPriceModalOpen(true);
            } else {
              setAlertMsg({ text: 'Pilih item terlebih dahulu', type: 'error' });
              setTimeout(() => setAlertMsg(null), 2000);
            }
          });
          break;
        case 'btn_kas':
          setIsCashMovementModalOpen(true);
          break;
        case 'btn_void_item':
          requestAuthorization("VOID", () => updateQuantity(items[items.length - 1]?.productId, 0));
          break;
        case 'btn_void_all':
          requestAuthorization("VOID", () => { setItems([]); setPayments([]); setManualTotalDiscount(0); setIsReturnMode(false); });
          break;
        case 'handleClearDiscount':
        case 'btn_clear':
        case 'btn_clear_discount':
          handleClearDiscount();
          break;
        case 'btn_hold':
        case 'btn_hold_transaction':
        case 'handleHoldTransaction':
          requestAuthorization("HOLD_RECALL", () => handleHoldTransaction());
          break;
        case 'btn_recall':
        case 'btn_recall_transaction':
        case 'setIsRecallModalOpen':
          requestAuthorization("HOLD_RECALL", () => setIsRecallModalOpen(true));
          break;
        case 'btn_member':
        case 'setIsMemberModalOpen':
          setIsMemberModalOpen(true);
          break;
        case 'btn_retur':
        case 'btn_return':
        case 'setIsReturnModalOpen':
          requestAuthorization("RETURN", () => setIsReturnModalOpen(true));
          break;
        case 'btn_qty':
          setIsQtyModalOpen(true);
          break;
        case 'btn_close_shift':
          setIsCloseShiftModalOpen(true);
          break;
        case 'btn_reprint_last':
          requestAuthorization("REPRINT_LAST", () => handleReprintLast());
          break;
        case 'btn_reprint_old':
          requestAuthorization("REPRINT_OLD", () => setIsReprintOldModalOpen(true));
          break;
        case 'btn_ppob_menu':
          setIsPpobMenuOpen(true);
          fetchPpobTransactions();
          break;
        default:
          break;
      }
    };

    window.addEventListener('keydown', handleShortcuts);
    return () => window.removeEventListener('keydown', handleShortcuts);
  }, [posSettings, items, isSubtotalMode, receivedAmount, activeShift, paymentMethod, subtotal, finalAmount, totalDiscount, manualTotalDiscount, dbProducts]);

  if (isCheckingShift) {
    return (
      <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'var(--bg-dark)', color: 'var(--text-main)' }}>
        <div style={{ textAlign: 'center' }}>
          <RefreshCw className="spin" size={48} style={{ marginBottom: '1rem', color: '#3b82f6' }} />
          <p style={{ fontSize: '1.25rem', fontWeight: '500' }}>Menyiapkan Terminal Kasir...</p>
        </div>
      </div>
    );
  }

  if (branchMismatchInfo) {
    return (
      <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'var(--bg-dark)', color: 'var(--text-main)', padding: '1rem' }}>
        <div className="fade-in" style={{ maxWidth: '600px', width: '100%', background: 'var(--bg-card)', border: '1px solid rgba(239, 68, 68, 0.3)', borderRadius: '16px', padding: '2.5rem', textAlign: 'center', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)' }}>
          <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
            <Lock size={40} />
          </div>
          <h2 style={{ color: '#ef4444', marginBottom: '1rem', letterSpacing: '0.05rem', fontSize: '1.5rem', fontWeight: 'bold' }}>❌ Akses Ditolak: Cabang Tidak Cocok</h2>
          <p style={{ color: 'var(--text-muted)', lineHeight: '1.6', marginBottom: '2rem', fontSize: '1.05rem' }}>
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
      <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'var(--bg-dark)', color: 'var(--text-main)', padding: '1rem' }}>
        <div className="fade-in" style={{ maxWidth: '600px', width: '100%', background: 'var(--bg-card)', border: '1px solid rgba(239, 68, 68, 0.3)', borderRadius: '16px', padding: '2.5rem', textAlign: 'center', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)' }}>
          <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
            <Lock size={40} />
          </div>
          <h2 style={{ color: '#ef4444', marginBottom: '1rem', letterSpacing: '0.05rem', fontSize: '1.5rem', fontWeight: 'bold' }}>❌ Akses Terkunci</h2>
          <p style={{ color: 'var(--text-muted)', lineHeight: '1.6', marginBottom: '2rem', fontSize: '1.05rem' }}>
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
                <option value="Shift 1">Shift 1</option>
                <option value="Shift 2">Shift 2</option>
                <option value="Shift 3">Shift 3</option>
                <option value="Shift Umum">Shift Umum</option>
              </select>
            </div>

            <div className="form-group" style={{ marginBottom: '1.5rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Modal Awal (Cash)</label>
              <input
                type="text"
                className="modern-barcode-input"
                style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
                placeholder="0"
                value={startingCash ? formatThousandSeparator(startingCash) : ''}
                onChange={(e) => setStartingCash(e.target.value.replace(/[^0-9]/g, ''))}
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
                type="text"
                className="modern-barcode-input"
                style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
                placeholder="0"
                value={actualCash ? formatThousandSeparator(actualCash) : ''}
                onChange={(e) => setActualCash(e.target.value.replace(/[^0-9]/g, ''))}
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
                {isProcessing ? 'MEMPROSES...' : 'TUTUP KASIR (BLIND CLOSE)'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Cash Movement (Kas Masuk/Keluar) Modal */}
      {isCashMovementModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '450px' }}>
            <div className="modal-header-icon" style={{ background: 'rgba(234, 179, 8, 0.1)', color: '#eab308', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
              <Banknote size={40} />
            </div>
            <h2 style={{ textAlign: 'center' }}>Manajemen Kas</h2>
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Catat pengeluaran atau penambahan kas laci (Petty Cash).</p>

            <div className="form-group" style={{ marginBottom: '1rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Jenis Transaksi</label>
              <div style={{ display: 'flex', gap: '10px' }}>
                <button
                  className={`btn-${cashMovementType === 'CASH_IN' ? 'primary' : 'secondary'}`}
                  style={{ flex: 1, padding: '0.75rem' }}
                  onClick={() => setCashMovementType('CASH_IN')}
                >
                  <Plus size={16} style={{ display: 'inline', marginRight: '5px' }} /> KAS MASUK
                </button>
                <button
                  className={`btn-${cashMovementType === 'CASH_OUT' ? 'danger' : 'secondary'}`}
                  style={{ flex: 1, padding: '0.75rem' }}
                  onClick={() => setCashMovementType('CASH_OUT')}
                >
                  <Minus size={16} style={{ display: 'inline', marginRight: '5px' }} /> KAS KELUAR
                </button>
              </div>
            </div>

            <div className="form-group" style={{ marginBottom: '1rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Nominal (Rp)</label>
              <input
                type="number"
                className="modern-barcode-input"
                style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem' }}
                placeholder="0"
                value={cashMovementAmount}
                onChange={(e) => setCashMovementAmount(e.target.value)}
                autoFocus
              />
            </div>

            <div className="form-group" style={{ marginBottom: '1.5rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Keterangan / Catatan</label>
              <input
                type="text"
                className="modern-barcode-input"
                style={{ width: '100%', padding: '0.75rem' }}
                placeholder="Contoh: Beli air minum galon..."
                value={cashMovementDesc}
                onChange={(e) => setCashMovementDesc(e.target.value)}
              />
            </div>

            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => setIsCashMovementModalOpen(false)}>BATAL</button>
              <button
                className="btn-primary"
                style={{ flex: 2, background: isProcessing ? '#94a3b8' : undefined }}
                onClick={handleCashMovement}
                disabled={isProcessing}
              >
                {isProcessing ? 'MENYIMPAN...' : 'SIMPAN CATATAN KAS'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Printer Settings Modal */}
      {isPrinterSettingsOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content settings-card fade-in" style={{ maxWidth: '450px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
              <h2 style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', margin: 0, fontSize: '1.25rem' }}>
                <Printer size={24} className="text-primary" /> Pengaturan Printer Lokal
              </h2>
              <button onClick={() => setIsPrinterSettingsOpen(false)} style={{ background: 'transparent', border: 'none', cursor: 'pointer', color: '#64748b' }}>
                <X size={24} />
              </button>
            </div>

            <div style={{ marginBottom: '1.5rem', textAlign: 'left' }}>
              <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Mode Tampilan Cetak</label>
              <div style={{ display: 'flex', gap: '1rem', marginBottom: '1.5rem' }}>
                <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
                  <input type="radio" name="autoPrint" checked={!localPrinterSettings.autoPrint} onChange={() => setLocalPrinterSettings(p => ({ ...p, autoPrint: false }))} />
                  Preview Dahulu
                </label>
                <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
                  <input type="radio" name="autoPrint" checked={localPrinterSettings.autoPrint} onChange={() => setLocalPrinterSettings(p => ({ ...p, autoPrint: true }))} />
                  Cetak Langsung
                </label>
              </div>

              <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Kualitas / Driver Cetak</label>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px' }} title="Pilih ini jika menggunakan driver TM-U220 standar (Lambat tapi rapi)">
                  <input type="radio" name="printMode" checked={localPrinterSettings.printMode === 'GRAPHIC'} onChange={() => setLocalPrinterSettings(p => ({ ...p, printMode: 'GRAPHIC' }))} />
                  <span>
                    <strong>Grafis (Driver Bawaan)</strong>
                    <span style={{ display: 'block', fontSize: '0.8rem', color: '#64748b' }}>Cetak presisi, butuh setting auto-cut manual di printer.</span>
                  </span>
                </label>
                <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px' }} title="Pilih ini jika menggunakan driver Generic / Text Only (Cepat & Buka laci)">
                  <input type="radio" name="printMode" checked={localPrinterSettings.printMode === 'TEXT'} onChange={() => setLocalPrinterSettings(p => ({ ...p, printMode: 'TEXT' }))} />
                  <span>
                    <strong>Text Only (ESC/POS)</strong>
                    <span style={{ display: 'block', fontSize: '0.8rem', color: '#64748b' }}>Sangat cepat, otomatis buka laci (khusus Generic Text Only).</span>
                  </span>
                </label>
              </div>

              <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', marginTop: '1.5rem', color: '#334155' }}>Format Struk</label>
              <div style={{ display: 'flex', gap: '1rem', marginBottom: '1.5rem' }}>
                <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
                  <input type="radio" name="receiptType" checked={localPrinterSettings.receiptType !== 2} onChange={() => setLocalPrinterSettings(p => ({ ...p, receiptType: 1 }))} />
                  Standar (Header di Atas)
                </label>
                <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
                  <input type="radio" name="receiptType" checked={localPrinterSettings.receiptType === 2} onChange={() => setLocalPrinterSettings(p => ({ ...p, receiptType: 2 }))} />
                  Hemat (Header di Bawah)
                </label>
              </div>

              <div style={{ display: 'flex', gap: '1rem', marginBottom: '1.5rem' }}>
                <div style={{ flex: 1 }}>
                  <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Banyak Huruf (Kolom)</label>
                  <input 
                    type="number" 
                    className="modern-barcode-input" 
                    value={localPrinterSettings.columns || 32} 
                    onChange={(e) => setLocalPrinterSettings(p => ({ ...p, columns: parseInt(e.target.value) || 32 }))}
                    style={{ width: '100%', padding: '0.5rem' }}
                  />
                  <small style={{ color: '#64748b', fontSize: '0.75rem' }}>Biasa 32 atau 40 (Thermal 58mm/80mm)</small>
                </div>
                <div style={{ flex: 1 }}>
                  <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Tambahkan Feed</label>
                  <input 
                    type="number" 
                    className="modern-barcode-input" 
                    value={localPrinterSettings.feedLines || 0} 
                    onChange={(e) => setLocalPrinterSettings(p => ({ ...p, feedLines: parseInt(e.target.value) || 0 }))}
                    style={{ width: '100%', padding: '0.5rem' }}
                  />
                  <small style={{ color: '#64748b', fontSize: '0.75rem' }}>Baris kosong di bawah struk</small>
                </div>
              </div>

              <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Nama Printer Target (Opsional)</label>
              <input 
                type="text" 
                className="modern-barcode-input" 
                placeholder="Biarkan kosong untuk default"
                value={localPrinterSettings.printerName || ''} 
                onChange={(e) => setLocalPrinterSettings(p => ({ ...p, printerName: e.target.value }))}
                style={{ width: '100%', padding: '0.5rem', marginBottom: '1.5rem' }}
              />
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
              <button className="btn-secondary" onClick={() => {
                const saved = JSON.parse(localStorage.getItem('pos_printer_settings')) || { autoPrint: false, printMode: 'TEXT', receiptType: 1 };
                setLocalPrinterSettings(saved);
                setIsPrinterSettingsOpen(false);
              }}>Batal</button>
              <button className="btn-primary" onClick={() => {
                localStorage.setItem('pos_printer_settings', JSON.stringify(localPrinterSettings));
                setIsPrinterSettingsOpen(false);
              }}>Simpan</button>
            </div>
          </div>
        </div>
      )}

      {/* EOD Report Preview */}
      {eodReportData && (
        <EODReportPreview
          eodData={eodReportData}
          branchSettings={branchSettings}
          onPrint={() => {
            // Lakukan print (opsional: panggil handlePrint langsung di komponen jika butuh grafis khusus)
            // Di sini kita delegasikan ke EODReportPreview
          }}
          onClose={() => {
            setEodReportData(null);
            onLogout();
          }}
        />
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
                placeholder="Cari member berdasarkan nama atau nomor HP..."
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
                    <div style={{ textAlign: 'left', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <div>
                        <div style={{ fontWeight: '700', fontSize: '1rem', color: '#f8fafc' }}>{customer.name}</div>
                        <div style={{ fontSize: '0.8rem', color: '#94a3b8', marginTop: '2px' }}>
                          No. HP: <span style={{ color: '#cbd5e1', fontWeight: '500' }}>{customer.phone || '-'}</span>
                        </div>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <div style={{ fontSize: '0.85rem', color: '#10b981', fontWeight: 'bold' }}>{customer.points || 0} Pts</div>
                        <div style={{ fontSize: '0.75rem', color: '#3b82f6', background: 'rgba(59, 130, 246, 0.1)', padding: '2px 6px', borderRadius: '4px', marginTop: '4px', display: 'inline-block', fontWeight: '600' }}>
                          {customer.member_tier || 'BRONZE'}
                        </div>
                      </div>
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

      {/* Point Redemption Modal */}
      {isRedeemPointModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '450px' }}>
            <div className="modal-header-icon" style={{ background: 'rgba(59, 130, 246, 0.1)', color: 'var(--primary)', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
              <Wallet size={40} />
            </div>
            <h2 style={{ textAlign: 'center' }}>Penukaran Poin</h2>
            {!pointRedemptionEnabled && (
              <div className="device-auth-error-card" style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.2)', padding: '0.75rem', borderRadius: '8px', marginBottom: '1rem', textAlign: 'center', fontSize: '0.85rem' }}>
                <strong>Penukaran poin saat ini dinonaktifkan oleh Perusahaan.</strong>
              </div>
            )}
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>
              1 Poin = {formatCurrency(pointRedemptionValue)}<br />
              Minimal Tukar = {minimumPointsToRedeem} Poin
            </p>

            <div className="shift-summary-mini" style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: '12px', marginBottom: '1.5rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <span style={{ color: 'var(--text-muted)' }}>Sisa Poin Member</span>
                <span style={{ fontWeight: 'bold' }}>{selectedCustomer?.points || 0}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: 'var(--text-muted)' }}>Sisa Tagihan</span>
                <span style={{ fontWeight: 'bold', color: 'var(--primary)' }}>{formatCurrency(finalAmount - payments.reduce((sum, p) => sum + p.amount, 0))}</span>
              </div>
            </div>

            <div className="form-group" style={{ marginBottom: '1.5rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Jumlah Poin yang Ditukar</label>
              <input
                type="number"
                className="modern-barcode-input"
                style={{ width: '100%', fontSize: '1.5rem', textAlign: 'center', padding: '1rem', opacity: pointRedemptionEnabled ? 1 : 0.5 }}
                placeholder="0"
                value={pointsToRedeemInput}
                onChange={(e) => setPointsToRedeemInput(e.target.value)}
                disabled={!pointRedemptionEnabled}
                autoFocus
              />
              {pointsToRedeemInput && !isNaN(parseInt(pointsToRedeemInput, 10)) && (
                <div style={{ textAlign: 'center', marginTop: '0.5rem', color: '#10b981', fontWeight: 'bold' }}>
                  Nilai Diskon: {formatCurrency(parseInt(pointsToRedeemInput, 10) * pointRedemptionValue)}
                </div>
              )}
            </div>

            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => setIsRedeemPointModalOpen(false)}>BATAL</button>
              <button
                className="btn-primary"
                style={{ flex: 2, opacity: pointRedemptionEnabled ? 1 : 0.5 }}
                onClick={handleApplyPoints}
                disabled={!pointRedemptionEnabled}
              >
                TERAPKAN POIN
              </button>
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
              <button className="btn-primary" onClick={() => { setChangeModalInfo(null); setShowReceiptPreview(true); }}>
                {localPrinterSettings.autoPrint ? 'CETAK (ENTER)' : 'PREVIEW (ENTER)'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Receipt Preview */}
      {showReceiptPreview && lastTransaction && (
        <ReceiptPreview
          transaction={lastTransaction}
          branchSettings={branchSettings}
          autoPrintSettings={localPrinterSettings}
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
                <button key={bank.id} className="bank-item-btn" onClick={() => {
                  setSelectedBank(bank);
                  const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
                  setDirectCardInput(sisa > 0 ? formatThousandSeparator(sisa) : '');
                  setIsBankSelectOpen(false);
                  setIsDirectCardAmountModalOpen(true);
                }}>
                  <span className="bank-name">{bank.name}</span>
                  <span className="bank-code">{bank.code || 'EDC'}</span>
                </button>
              ))}
            </div>
            <button className="btn-secondary" onClick={() => setIsBankSelectOpen(false)}>BATAL (ESC)</button>
          </div>
        </div>
      )}

      {/* Multi Payment Bank Selection Modal */}
      {isMultiBankSelectOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content bank-select-card fade-in">
            <CreditCard size={48} className="text-primary" />
            <h2>Pilih Bank / Mesin EDC (Multi Payment)</h2>
            <div className="bank-grid-large">
              {banks.map(bank => (
                <button key={bank.id} className="bank-item-btn" onClick={() => {
                  setPayments([...payments, { method: 'CARD', amount: pendingCardAmount, bankId: bank.id, label: `Card: ${bank.name}` }]);
                  setIsMultiBankSelectOpen(false);
                  setIsMultiPaymentModalOpen(true);
                }}>
                  <span className="bank-name">{bank.name}</span>
                  <span className="bank-code">{bank.code || 'EDC'}</span>
                </button>
              ))}
            </div>
            <button className="btn-secondary" onClick={() => { setIsMultiBankSelectOpen(false); setIsMultiPaymentModalOpen(true); }}>BATAL (ESC)</button>
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
          {!window.electronAPI && (
            <button className="btn-icon" onClick={() => setIsPrinterSettingsOpen(true)} title="Pengaturan Printer" style={{ background: 'transparent', padding: '4px', border: 'none' }}>
              <Settings size={18} />
            </button>
          )}
          <button className="btn-icon" onClick={toggleTheme} title="Ganti Tema (Terang/Gelap)" style={{ background: 'transparent', padding: '4px', border: 'none', color: 'inherit', cursor: 'pointer' }}>
            {theme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
          </button>
          <div className="user-info" onClick={() => setIsTerminalModalOpen(true)} style={{ cursor: 'pointer' }} title="Ganti Terminal">
            <User size={20} />
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
          isOnline={isOnline}
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
              onChange={(e) => {
                if (discountModal?.type === 'RUPIAH') {
                  setDiscountInputVal(formatThousandSeparator(e.target.value));
                } else {
                  setDiscountInputVal(e.target.value.replace(/[^0-9.]/g, ''));
                }
              }}
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



      {isVoucherModalOpen && (() => {
        const handleProcessVoucher = async () => {
          try {
            const res = await fetch(`/api/v1/vouchers/validate?code=${voucherInput}`, {
              headers: { 'Authorization': `Bearer ${authToken}` }
            });
            const data = await res.json();
            if (!res.ok || !data.valid) {
              setAlertMsg({ text: data.message || 'Voucher tidak valid', type: 'error' });
              return;
            }

            setPayments(prev => [...prev, {
              method: 'VOUCHER',
              amount: parseFloat(data.voucher.nominal_value),
              voucherId: data.voucher.id,
              label: `Voucher: ${data.voucher.code}`
            }]);
            setAlertMsg({ text: `Voucher Rp ${formatCurrency(data.voucher.nominal_value)} ditambahkan!`, type: 'success' });
            setIsVoucherModalOpen(false);
            setVoucherInput('');
            if (voucherSource === 'MULTI') {
              setIsMultiPaymentModalOpen(true);
              setVoucherSource(null);
            } else {
              setTimeout(() => barcodeInput.current?.focus(), 100);
            }
          } catch (err) {
            setAlertMsg({ text: 'Gagal memvalidasi voucher (offline/error)', type: 'error' });
          }
        };

        return (
          <div className="change-modal-overlay">
            <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
              <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Validasi Voucher</h3>
              <input
                type="text"
                className="modern-barcode-input"
                style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.25rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
                placeholder="Masukkan Kode Voucher"
                value={voucherInput}
                onChange={(e) => setVoucherInput(e.target.value.toUpperCase())}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    handleProcessVoucher();
                  } else if (e.key === 'Escape') {
                    setIsVoucherModalOpen(false);
                    setVoucherInput('');
                    if (voucherSource === 'MULTI') {
                      setIsMultiPaymentModalOpen(true);
                      setVoucherSource(null);
                    } else {
                      setTimeout(() => barcodeInput.current?.focus(), 100);
                    }
                  }
                }}
                autoFocus
              />
              <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
                <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsVoucherModalOpen(false); setVoucherInput(''); if (voucherSource === 'MULTI') { setIsMultiPaymentModalOpen(true); setVoucherSource(null); } else { barcodeInput.current?.focus(); } }}>BATAL (Esc)</button>
                <button className="btn-success" style={{ flex: 1 }} onClick={handleProcessVoucher}>PROSES (Enter)</button>
              </div>
            </div>
          </div>
        );
      })()}

      {isOpenPriceModalOpen && openPriceTargetItem && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Open Price</h3>
            <div style={{ textAlign: 'center', marginBottom: '1rem', fontWeight: 'bold' }}>{openPriceTargetItem.name}</div>
            <div style={{ textAlign: 'center', marginBottom: '1rem', fontSize: '0.85rem' }}>Harga Asli: {formatCurrency(openPriceTargetItem.originalUnitPrice || openPriceTargetItem.unitPrice)}</div>
            <input
              type="text"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              placeholder="Harga Baru"
              value={newOpenPrice}
              onChange={(e) => setNewOpenPrice(e.target.value.replace(/[^0-9]/g, ''))}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  const val = parseFloat(newOpenPrice);
                  if (!isNaN(val) && val >= 0) {
                    setItems(items.map(i => i.productId === openPriceTargetItem.productId ? { ...i, unitPrice: val, originalUnitPrice: i.originalUnitPrice || i.unitPrice } : i));
                    setAlertMsg({ text: 'Harga berhasil diubah', type: 'success' });
                    setIsOpenPriceModalOpen(false);
                    setNewOpenPrice('');
                    setOpenPriceTargetItem(null);
                    setTimeout(() => barcodeInput.current?.focus(), 100);
                  }
                } else if (e.key === 'Escape') {
                  setIsOpenPriceModalOpen(false);
                  setNewOpenPrice('');
                  setOpenPriceTargetItem(null);
                  setTimeout(() => barcodeInput.current?.focus(), 100);
                }
              }}
              autoFocus
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsOpenPriceModalOpen(false); setNewOpenPrice(''); setOpenPriceTargetItem(null); barcodeInput.current?.focus(); }}>BATAL (Esc)</button>
            </div>
          </div>
        </div>
      )}

      {isMultiPaymentModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '600px', width: '90%' }}>
            <h2 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Multi Payment</h2>

            <div style={{ background: 'rgba(59, 130, 246, 0.1)', padding: '1rem', borderRadius: '8px', marginBottom: '1.5rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.1rem', marginBottom: '0.5rem' }}>
                <span>Tagihan:</span>
                <span style={{ fontWeight: 'bold' }}>{formatCurrency(finalAmount)}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.1rem', marginBottom: '0.5rem', color: '#10b981' }}>
                <span>Total Dibayar:</span>
                <span style={{ fontWeight: 'bold' }}>{formatCurrency(payments.reduce((sum, p) => sum + p.amount, 0))}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.25rem', fontWeight: 'bold', color: payments.reduce((sum, p) => sum + p.amount, 0) >= finalAmount ? '#10b981' : '#ef4444' }}>
                <span>{payments.reduce((sum, p) => sum + p.amount, 0) >= finalAmount ? 'Kembali:' : 'Sisa:'}</span>
                <span>{formatCurrency(Math.abs(finalAmount - payments.reduce((sum, p) => sum + p.amount, 0)))}</span>
              </div>
            </div>

            <div style={{ marginBottom: '1.5rem', maxHeight: '150px', overflowY: 'auto' }}>
              {payments.length === 0 ? (
                <div style={{ textAlign: 'center', color: '#6b7280', padding: '1rem' }}>Belum ada pembayaran ditambahkan</div>
              ) : (
                <table className="modern-table" style={{ width: '100%', fontSize: '0.9rem' }}>
                  <tbody>
                    {payments.map((p, idx) => (
                      <tr key={idx}>
                        <td>{p.label || p.method}</td>
                        <td style={{ textAlign: 'right', fontWeight: 'bold' }}>{formatCurrency(p.amount)}</td>
                        <td style={{ width: '40px', textAlign: 'center' }}>
                          <button onClick={() => setPayments(payments.filter((_, i) => i !== idx))} style={{ color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer' }}><Trash2 size={16} /></button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>

            <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '1.5rem' }}>
              <button className="btn-secondary" style={{ flex: 1, padding: '0.75rem', fontSize: '0.9rem' }} onClick={() => { setVoucherSource('MULTI'); setIsVoucherModalOpen(true); setIsMultiPaymentModalOpen(false); }}>+ Voucher</button>
              <button className="btn-secondary" style={{ flex: 1, padding: '0.75rem', fontSize: '0.9rem' }} onClick={() => {
                const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
                setMultiCashInput(sisa > 0 ? formatThousandSeparator(sisa) : '');
                setIsMultiCashModalOpen(true);
                setIsMultiPaymentModalOpen(false);
              }}>+ Tunai</button>
              <button className="btn-secondary" style={{ flex: 1, padding: '0.75rem', fontSize: '0.9rem' }} onClick={() => {
                const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
                if (sisa > 0) {
                  setMultiCardInput(formatThousandSeparator(sisa));
                  setIsMultiCardAmountModalOpen(true);
                  setIsMultiPaymentModalOpen(false);
                }
              }}>+ Card</button>
            </div>

            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsMultiPaymentModalOpen(false); barcodeInput.current?.focus(); }}>TUTUP</button>
              <button className="btn-success" style={{ flex: 1 }} disabled={payments.reduce((sum, p) => sum + p.amount, 0) < finalAmount} onClick={() => { setIsMultiPaymentModalOpen(false); processTransaction('MULTI'); }}>PROSES BAYAR</button>
            </div>
          </div>
        </div>
      )}

      {/* Direct Cash Modal */}
      {isDirectCashModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Nominal Tunai</h3>
            <p style={{ textAlign: 'center', fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem' }}>
              Tagihan: {formatCurrency(finalAmount - payments.reduce((sum, p) => sum + p.amount, 0))}
            </p>
            <input
              type="text"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              value={directCashInput}
              onChange={(e) => {
                setDirectCashInput(formatThousandSeparator(e.target.value));
              }}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  const val = parseFloat(directCashInput.replace(/\./g, ''));
                  if (!isNaN(val) && val > 0) {
                    setIsDirectCashModalOpen(false);
                    processTransaction('CASH', null, val);
                  }
                } else if (e.key === 'Escape') {
                  setIsDirectCashModalOpen(false);
                  setDirectCashInput('');
                }
              }}
              autoFocus
              onFocus={(e) => e.target.select()}
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsDirectCashModalOpen(false); setDirectCashInput(''); }}>BATAL (Esc)</button>
              <button className="btn-success" style={{ flex: 1 }} onClick={() => {
                const val = parseFloat(directCashInput.replace(/\./g, ''));
                if (!isNaN(val) && val > 0) {
                  setIsDirectCashModalOpen(false);
                  processTransaction('CASH', null, val);
                }
              }}>BAYAR (Enter)</button>
            </div>
          </div>
        </div>
      )}

      {/* Direct Card Amount Modal */}
      {isDirectCardAmountModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Nominal Pembayaran {selectedBank?.name}</h3>
            <p style={{ textAlign: 'center', fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem' }}>
              Sisa Tagihan: {formatCurrency(finalAmount - payments.reduce((sum, p) => sum + p.amount, 0))}
            </p>
            <input
              type="text"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              value={directCardInput}
              onChange={(e) => {
                setDirectCardInput(formatThousandSeparator(e.target.value));
              }}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  const val = parseFloat(directCardInput.replace(/\./g, ''));
                  if (!isNaN(val) && val > 0) {
                    setIsDirectCardAmountModalOpen(false);
                    processTransaction('CARD', selectedBank?.id, val);
                  }
                } else if (e.key === 'Escape') {
                  setIsDirectCardAmountModalOpen(false);
                  setDirectCardInput('');
                }
              }}
              autoFocus
              onFocus={(e) => e.target.select()}
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsDirectCardAmountModalOpen(false); setDirectCardInput(''); }}>BATAL (Esc)</button>
              <button className="btn-success" style={{ flex: 1 }} onClick={() => {
                const val = parseFloat(directCardInput.replace(/\./g, ''));
                if (!isNaN(val) && val > 0) {
                  setIsDirectCardAmountModalOpen(false);
                  processTransaction('CARD', selectedBank?.id, val);
                }
              }}>BAYAR (Enter)</button>
            </div>
          </div>
        </div>
      )}

      {isMultiCashModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Input Nominal Tunai</h3>
            <p style={{ textAlign: 'center', fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem' }}>
              Sisa Tagihan: {formatCurrency(finalAmount - payments.reduce((sum, p) => sum + p.amount, 0))}
            </p>
            <input
              type="text"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              placeholder="0"
              value={multiCashInput}
              onChange={(e) => setMultiCashInput(formatThousandSeparator(e.target.value))}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  const val = parseFloat(multiCashInput.replace(/\./g, ''));
                  if (!isNaN(val) && val > 0) {
                    setPayments([...payments, { method: 'CASH', amount: val, label: 'Tunai' }]);
                    setIsMultiCashModalOpen(false);
                    setMultiCashInput('');
                    setIsMultiPaymentModalOpen(true);
                  }
                } else if (e.key === 'Escape') {
                  setIsMultiCashModalOpen(false);
                  setMultiCashInput('');
                  setIsMultiPaymentModalOpen(true);
                }
              }}
              autoFocus
              onFocus={(e) => e.target.select()}
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsMultiCashModalOpen(false); setMultiCashInput(''); setIsMultiPaymentModalOpen(true); }}>BATAL (Esc)</button>
              <button className="btn-success" style={{ flex: 1 }} onClick={() => {
                const val = parseFloat(multiCashInput.replace(/\./g, ''));
                if (!isNaN(val) && val > 0) {
                  setPayments([...payments, { method: 'CASH', amount: val, label: 'Tunai' }]);
                  setIsMultiCashModalOpen(false);
                  setMultiCashInput('');
                  setIsMultiPaymentModalOpen(true);
                }
              }}>TAMBAH (Enter)</button>
            </div>
          </div>
        </div>
      )}

      {isMultiCardAmountModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>Input Nominal Card</h3>
            <p style={{ textAlign: 'center', fontSize: '0.9rem', color: '#6b7280', marginBottom: '1rem' }}>
              Sisa Tagihan: {formatCurrency(finalAmount - payments.reduce((sum, p) => sum + p.amount, 0))}
            </p>
            <input
              type="text"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              placeholder="0"
              value={multiCardInput}
              onChange={(e) => setMultiCardInput(formatThousandSeparator(e.target.value))}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  const val = parseFloat(multiCardInput.replace(/\./g, ''));
                  if (!isNaN(val) && val > 0) {
                    setPendingCardAmount(val);
                    setIsMultiCardAmountModalOpen(false);
                    setMultiCardInput('');
                    setIsMultiBankSelectOpen(true);
                  }
                } else if (e.key === 'Escape') {
                  setIsMultiCardAmountModalOpen(false);
                  setMultiCardInput('');
                  setIsMultiPaymentModalOpen(true);
                }
              }}
              autoFocus
              onFocus={(e) => e.target.select()}
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsMultiCardAmountModalOpen(false); setMultiCardInput(''); setIsMultiPaymentModalOpen(true); }}>BATAL (Esc)</button>
              <button className="btn-success" style={{ flex: 1 }} onClick={() => {
                const val = parseFloat(multiCardInput.replace(/\./g, ''));
                if (!isNaN(val) && val > 0) {
                  setPendingCardAmount(val);
                  setIsMultiCardAmountModalOpen(false);
                  setMultiCardInput('');
                  setIsMultiBankSelectOpen(true);
                }
              }}>LANJUT (Enter)</button>
            </div>
          </div>
        </div>
      )}

      {isQtyModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
              Masukkan Qty Barang
            </h3>
            <input
              type="number"
              step="any"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              placeholder="1"
              value={nextItemQty}
              onChange={(e) => setNextItemQty(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  setIsQtyModalOpen(false);
                  setTimeout(() => barcodeInput.current?.focus(), 100);
                } else if (e.key === 'Escape') {
                  setNextItemQty('');
                  setIsQtyModalOpen(false);
                  setTimeout(() => barcodeInput.current?.focus(), 100);
                }
              }}
              autoFocus
              onFocus={(e) => e.target.select()}
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setNextItemQty(''); setIsQtyModalOpen(false); barcodeInput.current?.focus(); }}>BATAL (Esc)</button>
              <button className="btn-success" style={{ flex: 1 }} onClick={() => { setIsQtyModalOpen(false); setTimeout(() => barcodeInput.current?.focus(), 100); }}>OK (Enter)</button>
            </div>
          </div>
        </div>
      )}

      {isReprintOldModalOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
            <h3 style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
              Reprint Nota Lama
            </h3>
            <p style={{ textAlign: 'center', color: '#6b7280', marginBottom: '1rem', fontSize: '0.875rem' }}>
              Masukkan nomor struk (Misal: SMI-ABCD12)
            </p>
            <input
              type="text"
              className="modern-barcode-input"
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', fontSize: '1.25rem', fontWeight: 'bold', marginBottom: '1.5rem' }}
              placeholder="SMI-..."
              value={oldReceiptInput}
              onChange={(e) => setOldReceiptInput(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  handleReprintOld();
                } else if (e.key === 'Escape') {
                  setIsReprintOldModalOpen(false);
                  setOldReceiptInput('');
                  setTimeout(() => barcodeInput.current?.focus(), 100);
                }
              }}
              autoFocus
            />
            <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={() => { setIsReprintOldModalOpen(false); setOldReceiptInput(''); barcodeInput.current?.focus(); }}>BATAL (Esc)</button>
            </div>
          </div>
        </div>
      )}

      {isPpobMenuOpen && (
        <div className="change-modal-overlay">
          <div className="change-modal-content fade-in" style={{ maxWidth: '900px', width: '90vw' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
              <h3 style={{ margin: 0 }}>Menu PPOB Hari Ini</h3>
              <button onClick={() => setIsPpobMenuOpen(false)} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#6b7280' }}>
                <X size={24} />
              </button>
            </div>

            <div style={{ marginBottom: '1rem', display: 'flex', gap: '0.5rem' }}>
              <div className="barcode-input-wrapper" style={{ flex: 1, maxWidth: '300px' }}>
                <div className="input-icon"><Search size={20} /></div>
                <input
                  type="text"
                  className="barcode-input"
                  placeholder="Cari No Tujuan/SN..."
                  value={ppobSearchQuery}
                  onChange={(e) => setPpobSearchQuery(e.target.value)}
                  style={{ paddingLeft: '2.5rem' }}
                />
              </div>
              <button className="btn-secondary" onClick={fetchPpobTransactions} disabled={isFetchingPpobTransactions}>
                <RotateCcw size={16} style={{ marginRight: '0.5rem' }} /> Refresh
              </button>
            </div>

            <div className="table-container" style={{ maxHeight: '60vh', overflowY: 'auto', background: 'var(--bg-card)', borderRadius: '0.5rem', border: '1px solid var(--border-light)' }}>
              {isFetchingPpobTransactions ? (
                <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>Memuat data...</div>
              ) : (
                <table className="pos-table" style={{ width: '100%' }}>
                  <thead>
                    <tr>
                      <th style={{ textAlign: 'left', padding: '0.75rem' }}>Waktu</th>
                      <th style={{ textAlign: 'left', padding: '0.75rem' }}>Produk</th>
                      <th style={{ textAlign: 'left', padding: '0.75rem' }}>No Tujuan</th>
                      <th style={{ textAlign: 'center', padding: '0.75rem' }}>Status</th>
                      <th style={{ textAlign: 'left', padding: '0.75rem' }}>SN / Token</th>
                      <th style={{ textAlign: 'center', padding: '0.75rem' }}>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    {ppobTransactions
                      .filter(tx => tx.ppob_transactions?.some(p => p.customer_no?.includes(ppobSearchQuery) || p.sn?.includes(ppobSearchQuery)))
                      .map(tx => {
                        return tx.ppob_transactions.map(ppob => (
                          <tr key={ppob.id} style={{ borderBottom: '1px solid var(--border-light)' }}>
                            <td style={{ padding: '0.75rem', fontSize: '0.85rem' }}>{new Date(tx.created_at).toLocaleTimeString('id-ID')}</td>
                            <td style={{ padding: '0.75rem', fontSize: '0.85rem' }}>{tx.items.find(i => i.product?.ppob_sku === ppob.buyer_sku_code)?.product?.name || ppob.buyer_sku_code}</td>
                            <td style={{ padding: '0.75rem', fontSize: '0.85rem', fontWeight: 'bold' }}>{ppob.customer_no}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'center' }}>
                              <span style={{
                                padding: '0.2rem 0.5rem',
                                borderRadius: '4px',
                                fontSize: '0.75rem',
                                background: ppob.status === 'Gagal' ? '#ef444420' : (ppob.status === 'Sukses' ? '#10b98120' : '#f59e0b20'),
                                color: ppob.status === 'Gagal' ? '#ef4444' : (ppob.status === 'Sukses' ? '#10b981' : '#f59e0b')
                              }}>
                                {ppob.status}
                              </span>
                            </td>
                            <td style={{ padding: '0.75rem', fontSize: '0.8rem', color: 'var(--text-muted)' }}>
                              {ppob.sn || '-'}
                            </td>
                            <td style={{ padding: '0.75rem', textAlign: 'center', display: 'flex', gap: '0.5rem', justifyContent: 'center' }}>
                              {ppob.status === 'Pending' && (
                                <button className="btn-secondary" style={{ padding: '0.3rem 0.5rem', fontSize: '0.75rem' }} onClick={() => handleCheckPpobStatus(ppob.id)}>
                                  <RotateCcw size={12} style={{ marginRight: '0.2rem' }} /> Cek
                                </button>
                              )}
                              <button className="btn-success" style={{ padding: '0.3rem 0.5rem', fontSize: '0.75rem' }} onClick={() => { setIsPpobMenuOpen(false); handleReprintPpob(tx); }}>
                                <Printer size={12} style={{ marginRight: '0.2rem' }} /> Print
                              </button>
                            </td>
                          </tr>
                        ));
                      })}
                    {ppobTransactions.length === 0 && (
                      <tr>
                        <td colSpan="6" style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-muted)' }}>Tidak ada transaksi PPOB hari ini</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              )}
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

          {/* BOTTOM SUMMARY BAR */}
          <div className="bottom-summary-bar fade-in" style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem', marginTop: '1rem', background: 'var(--glass-white-05)', padding: '0.75rem 1rem', borderRadius: '12px', border: '1px solid var(--border-light)' }}>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px dashed var(--border-light)', paddingBottom: '0.25rem' }}>
              <div style={{ display: 'flex', gap: '1.5rem', fontSize: '0.9rem', color: 'var(--text-main)' }}>
                <div>
                  <span style={{ color: 'var(--text-muted)' }}>Subtotal: </span>
                  <span style={{ fontWeight: '700' }}>{formatCurrency(subtotal)}</span>
                </div>
                {(totalDiscount + manualTotalDiscount) > 0 && (
                  <div>
                    <span style={{ color: 'var(--text-muted)' }}>Diskon: </span>
                    <span style={{ fontWeight: '700', color: 'var(--danger)' }}>-{formatCurrency(totalDiscount + manualTotalDiscount)}</span>
                  </div>
                )}
              </div>

              {selectedCustomer && (
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.8rem' }}>
                  <span style={{ background: 'var(--primary)', color: 'white', padding: '2px 4px', borderRadius: '4px', fontWeight: 'bold' }}>{selectedCustomer.member_tier}</span>
                  <span style={{ fontWeight: '600', color: 'var(--text-main)' }}>{selectedCustomer.name}</span>
                  <span style={{ color: 'var(--text-muted)' }}>(Pts: {selectedCustomer.points})</span>
                  {pointRedemptionEnabled && selectedCustomer.points >= minimumPointsToRedeem && (
                    <button
                      type="button"
                      onClick={() => {
                        setPointsToRedeemInput('');
                        setIsRedeemPointModalOpen(true);
                      }}
                      style={{
                        marginLeft: '0.25rem',
                        padding: '2px 8px',
                        fontSize: '0.75rem',
                        borderRadius: '4px',
                        background: 'var(--primary)',
                        color: 'white',
                        border: 'none',
                        cursor: 'pointer',
                        fontWeight: 'bold',
                        transition: 'opacity 0.2s'
                      }}
                      onMouseOver={(e) => e.currentTarget.style.opacity = '0.8'}
                      onMouseOut={(e) => e.currentTarget.style.opacity = '1'}
                    >
                      Tukar Poin
                    </button>
                  )}
                </div>
              )}
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', paddingTop: '0.25rem' }}>
              <div className="grand-total-box" style={{ border: 'none', padding: 0, marginTop: 0, background: 'transparent' }}>
                <label style={{ fontSize: '0.75rem', letterSpacing: '1px', color: 'var(--text-muted)', marginBottom: '2px', display: 'block' }}>GRAND TOTAL</label>
                <div className="total-amount" style={{ fontSize: '2rem', lineHeight: '1', fontWeight: '800', color: 'var(--primary)' }}>{formatCurrency(finalAmount)}</div>
              </div>

              {isSubtotalMode && (
                <div style={{ display: 'flex', gap: '2rem', alignItems: 'flex-end' }}>
                  <div style={{ textAlign: 'right' }}>
                    <label style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'block', marginBottom: '2px', fontWeight: '600' }}>DITERIMA</label>
                    <div style={{ fontSize: '1.25rem', fontWeight: '700', color: 'var(--text-main)', lineHeight: '1' }}>{formatCurrency(totalPaid || 0)}</div>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    <label style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'block', marginBottom: '2px', fontWeight: '600' }}>KEMBALI</label>
                    <div className={changeAmount >= 0 ? "text-online" : "text-danger"} style={{ fontSize: '1.5rem', fontWeight: '800', lineHeight: '1' }}>
                      {formatCurrency(changeAmount)}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </main>

        <aside className="pos-functions-sidebar" style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          <div className="function-grid" style={{ display: 'grid', gap: '8px', gridTemplateColumns: 'repeat(4, 80px)', justifyContent: 'center' }}>
            {/* Kategori Pembayaran */}
            <button className={`func-btn payment ${paymentMethod === 'CASH' ? 'active' : ''}`} onClick={() => startPayment('CASH')}><Banknote size={16} />{renderBtnLabel('btn_tunai', 'Tunai', 'F5')}</button>
            <button className={`func-btn payment ${paymentMethod === 'CARD' ? 'active' : ''}`} onClick={() => startPayment('CARD')}><CreditCard size={16} />{renderBtnLabel('btn_card', 'Card', 'F6')}</button>
            <button className="func-btn payment" onClick={() => requestAuthorization("VOUCHER", () => setIsVoucherModalOpen(true))}><Ticket size={16} />{renderBtnLabel('btn_voucher', 'Voucher', '')}</button>
            <button className="func-btn payment" onClick={() => requestAuthorization("MULTI_PAYMENT", () => setIsMultiPaymentModalOpen(true))}><Layers size={16} />{renderBtnLabel('btn_multi_pay', 'Multi-Pay', '')}</button>
            <button className={`func-btn payment ${isSubtotalMode ? 'active' : ''}`} onClick={() => { setIsSubtotalMode(true); barcodeInput.current.focus(); }}><Calculator size={16} />{renderBtnLabel('btn_subtotal', 'Subtotal', 'F9')}</button>

            {/* Kategori Diskon & Harga */}
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualDiscountItem('NOMINAL'))}><Tag size={16} />{renderBtnLabel('btn_disc_item_rp', 'Disc Item Rp', 'F1')}</button>
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualDiscountItem('PERCENT'))}><Tag size={16} />{renderBtnLabel('btn_disc_item_pct', 'Disc Item %', 'F2')}</button>
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('NOMINAL'))}><Tag size={16} />{renderBtnLabel('btn_disc_total_rp', 'Disc Total Rp', 'F3')}</button>
            <button className="func-btn discount" onClick={() => requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('PERCENT'))}><Tag size={16} />{renderBtnLabel('btn_disc_total_pct', 'Disc Total %', 'F4')}</button>
            <button className="func-btn discount" onClick={() => requestAuthorization("OPEN_PRICE", () => {
              if (items.length > 0) {
                setOpenPriceTargetItem(items[items.length - 1]);
                setIsOpenPriceModalOpen(true);
              } else {
                setAlertMsg({ text: 'Pilih item terlebih dahulu', type: 'error' });
                setTimeout(() => setAlertMsg(null), 2000);
              }
            })}><Edit3 size={16} />{renderBtnLabel('btn_open_price', 'Open Price', '')}</button>

            {/* Kategori Aksi */}
            <button className="func-btn primary" onClick={() => setIsQtyModalOpen(true)}><Package size={16} />{renderBtnLabel('btn_qty', 'Qty', 'F7')}</button>
            <button className="func-btn action" onClick={() => requestAuthorization("HOLD_RECALL", () => handleHoldTransaction())}><Lock size={16} />{renderBtnLabel('btn_hold', 'Hold', 'PgUp')}</button>
            <button className="func-btn action" onClick={() => requestAuthorization("HOLD_RECALL", () => setIsRecallModalOpen(true))}><History size={16} />{renderBtnLabel('btn_recall', 'Recall', 'PgDn')}</button>
            <button className="func-btn action" onClick={() => setIsMemberModalOpen(true)}><User size={16} />{renderBtnLabel('btn_member', 'Member', 'Home')}</button>
            <button className="func-btn action" onClick={() => setIsCashMovementModalOpen(true)}><Wallet size={16} />{renderBtnLabel('btn_kas', 'Kas M/K', '')}</button>
            <button className="func-btn secondary" onClick={() => requestAuthorization("RETURN", () => setIsReturnModalOpen(true))}><RotateCcw size={16} />{renderBtnLabel('btn_retur', 'Retur', 'End')}</button>
            <button className="func-btn secondary" onClick={() => requestAuthorization("REPRINT_LAST", () => handleReprintLast())}><History size={16} />{renderBtnLabel('btn_reprint_last', 'Reprint 1', 'F11')}</button>
            <button className="func-btn action" onClick={() => requestAuthorization("REPRINT_OLD", () => setIsReprintOldModalOpen(true))}><Search size={16} />{renderBtnLabel('btn_reprint_old', 'Reprint L', 'F12')}</button>
            <button className="func-btn primary" onClick={() => { setIsPpobMenuOpen(true); fetchPpobTransactions(); }}><Package size={16} />{renderBtnLabel('btn_ppob_menu', 'Menu PPOB', 'F10')}</button>

            {/* Kategori Void/Clear */}
            <button className="func-btn danger" onClick={() => requestAuthorization("VOID", () => updateQuantity(items[items.length - 1]?.productId, 0))}><Eraser size={16} />{renderBtnLabel('btn_void_item', 'Void Item', 'Delete')}</button>
            <button className="func-btn danger" onClick={() => requestAuthorization("VOID", () => { setItems([]); setPayments([]); setManualTotalDiscount(0); setIsReturnMode(false); })}><Trash2 size={16} />{renderBtnLabel('btn_void_all', 'Void All', 'Escape')}</button>
            <button className="func-btn danger" onClick={handleClearDiscount} style={{ background: 'rgba(245, 158, 11, 0.15)', borderColor: '#f59e0b' }}><X size={16} />{renderBtnLabel('btn_clear', 'Clear', 'Insert')}</button>
            <button className="func-btn secondary" onClick={() => setIsCloseShiftModalOpen(true)}><LogOut size={16} />{renderBtnLabel('btn_close_shift', 'Tutup Shift', 'F8')}</button>
          </div>

          <div className="input-action-section" style={{ marginTop: 'auto', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <div className="barcode-input-wrapper" style={{ maxWidth: 'none', position: 'relative' }}>
              <div className="input-icon"><Search size={20} /></div>
              <input
                ref={barcodeInput}
                type="text"
                className="modern-barcode-input"
                style={{ width: '100%', paddingLeft: '3rem' }}
                value={inputValue}
                placeholder="Scan Barcode / Cari Produk..."
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
                    if (highlightedIndex >= 0 && highlightedIndex < searchResults.length) {
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

            <div style={{ display: 'flex', justifyContent: 'center', marginTop: '1rem', borderTop: '1px solid var(--border-color)', paddingTop: '0.75rem' }}>
              <a
                href="https://api.whatsapp.com/send/?phone=6285861094485&text=Halo%20Zhan_soft,%20Saya%20ingin%20bertanya%20seputar%20Aplikasi%20Sistem%20POS%20Kasir"
                target="_blank"
                rel="noopener noreferrer"
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '6px',
                  color: 'var(--text-muted)',
                  textDecoration: 'none',
                  fontSize: '0.75rem',
                  fontWeight: '500',
                  transition: 'color 0.2s',
                }}
                onMouseEnter={(e) => { e.currentTarget.style.color = 'var(--text-main)'; }}
                onMouseLeave={(e) => { e.currentTarget.style.color = 'var(--text-muted)'; }}
              >
                <svg width="16" height="16" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style={{ borderRadius: '4px' }}>
                  <defs>
                    <linearGradient id="zGradPos" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stopColor="#4f46e5" />
                      <stop offset="100%" stopColor="#06b6d4" />
                    </linearGradient>
                  </defs>
                  <rect width="100" height="100" rx="30" fill="url(#zGradPos)" />
                  <path d="M30 30H70L30 70H70" stroke="white" strokeWidth="12" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
                <span>Zhan_soft &copy; {new Date().getFullYear()}</span>
              </a>
            </div>
          </div>
        </aside>
      </div>

      {/* Digital Product Modal */}
      {isDigitalInputModalOpen && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]" style={{ position: 'fixed', top: 0, left: 0, width: '100vw', height: '100vh', backgroundColor: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
          <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm" style={{ backgroundColor: 'white', padding: '20px', borderRadius: '8px', width: '400px' }}>
            <h3 className="text-lg font-bold mb-4" style={{ marginBottom: '15px', color: 'black' }}>Input Data Tujuan</h3>
            <p className="text-sm text-gray-600 mb-4" style={{ marginBottom: '15px', color: 'black' }}>Masukkan Nomor HP / ID Pelanggan untuk produk digital <b>{pendingDigitalProduct?.product?.name}</b></p>
            <form onSubmit={handleDigitalProductSubmit}>
              <input
                type="text"
                autoFocus
                className="w-full border p-2 rounded mb-4 focus:ring-2 focus:ring-blue-500"
                style={{ width: '100%', padding: '10px', marginBottom: '15px', border: '1px solid #ccc', borderRadius: '4px', color: 'black' }}
                placeholder="Misal: 081234567890"
                value={customerNoInput}
                onChange={(e) => setCustomerNoInput(e.target.value)}
              />
              <div className="flex justify-end gap-2" style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
                <button
                  type="button"
                  style={{ padding: '8px 16px', backgroundColor: '#e5e7eb', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
                  onClick={() => {
                    setIsDigitalInputModalOpen(false);
                    setPendingDigitalProduct(null);
                  }}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  style={{ padding: '8px 16px', backgroundColor: '#2563eb', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
                >
                  Lanjut
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
};
