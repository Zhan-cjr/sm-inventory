import React, { useState, useRef, useEffect } from 'react';
import { useOfflineSync } from '../hooks/useOfflineSync';
import { usePosShortcuts } from '../hooks/usePosShortcuts';
import { usePosCatalog } from '../hooks/usePosCatalog';
import { usePosShift } from '../hooks/usePosShift';
import { usePosCart } from '../hooks/usePosCart';
import { usePosPayment } from '../hooks/usePosPayment';
import { usePosPpob } from '../hooks/usePosPpob';
import { usePosBarcodeSearch } from '../hooks/usePosBarcodeSearch';
import { DiscountEngine } from '../utils/DiscountEngine';
import PosModalsContainer from './pos/PosModalsContainer';
import PosHeader from './pos/PosHeader';
import PosCartTable from './pos/PosCartTable';
import PosActionSidebar from './pos/PosActionSidebar';
import { PwpUpsellBanner } from './pos/PwpUpsellBanner';
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
  branchPhone,
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
    if (valStr === null || valStr === undefined || valStr === '') return '';
    const strVal = String(valStr);
    const isNegative = strVal.startsWith('-');
    const clean = strVal.replace(/[^0-9]/g, '');
    if (!clean) return isNegative ? '-' : '';
    const formatted = clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return isNegative ? '-' + formatted : formatted;
  };

  const [alertMsg, setAlertMsg] = useState(null);
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const barcodeInput = useRef(null);
  const cartSetItemsRef = useRef(null);
  const paymentOnAfterHoldRef = useRef(null);

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
  };

  const handleSetItems = (updater) => {
    if (typeof cartSetItemsRef.current === 'function') {
      cartSetItemsRef.current(updater);
    }
  };

  const [isSubtotalMode, setIsSubtotalMode] = useState(false);

  // Initialize terminalInfo: locked terminal takes absolute priority, then local storage cache
  const [terminalInfo, setTerminalInfo] = useState(() => {
    if (lockedTerminalId) {
      return { id: lockedTerminalId, name: lockedTerminalName || 'Terminal Terkunci', orgName: orgName };
    }
    return { id: localStorage.getItem('pos_terminal_id'), name: localStorage.getItem('pos_terminal_name') || 'Belum Diatur', orgName: orgName };
  });

  const [theme, setTheme] = useState(() => localStorage.getItem('pos_theme') || 'dark');
  const [isPrinterSettingsOpen, setIsPrinterSettingsOpen] = useState(false);
  const [localPrinterSettings, setLocalPrinterSettings] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('pos_printer_settings')) || { autoPrint: false, printMode: 'TEXT', receiptType: 1, columns: 32, feedLines: 4, printerName: '' };
    } catch (e) {
      return { autoPrint: false, printMode: 'TEXT', receiptType: 1, columns: 32, feedLines: 4, printerName: '' };
    }
  });

  // Initialize terminal select modal state: if locked, it's permanently closed (false)
  const [isTerminalModalOpen, setIsTerminalModalOpen] = useState(() => {
    if (lockedTerminalId) {
      return false;
    }
    return !localStorage.getItem('pos_terminal_id');
  });
  const [pendingAuthAction, setPendingAuthAction] = useState(null);
  const [branchMismatchInfo, setBranchMismatchInfo] = useState(null);

  const { storeLocalTransaction, syncTransactions, pendingCount, syncStatus } = useOfflineSync(branchId, authToken);
  const discountEngine = useRef(new DiscountEngine([]));

  const {
    activeShift,
    setActiveShift,
    isOpenShiftModalOpen,
    setIsOpenShiftModalOpen,
    isCloseShiftModalOpen,
    setIsCloseShiftModalOpen,
    startingCash,
    setStartingCash,
    actualCash,
    setActualCash,
    selectedShiftName,
    setSelectedShiftName,
    isCheckingShift,
    setIsCheckingShift,
    lockScreenInfo,
    setLockScreenInfo,
    isCashMovementModalOpen,
    setIsCashMovementModalOpen,
    cashMovementType,
    setCashMovementType,
    cashMovementAmount,
    setCashMovementAmount,
    cashMovementDesc,
    setCashMovementDesc,
    eodReportData,
    setEodReportData,
    checkActiveShift,
    handleOpenShift,
    handleCloseShift,
    handleCashMovement,
    closedShiftsToday
  } = usePosShift({
    authToken,
    terminalInfo,
    onLogout,
    setAlertMsg,
    localPrinterSettings,
    syncTransactions
  });

  const {
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
  } = usePosCatalog({
    branchId,
    branchName,
    authToken,
    lockedTerminalId,
    lockedTerminalName,
    userRole,
    isOnline,
    discountEngine,
    setItems: handleSetItems,
    checkActiveShift,
    setActiveShift,
    setTerminalInfo,
    setIsCheckingShift,
    setIsTerminalModalOpen,
    setIsOpenShiftModalOpen,
    setBranchMismatchInfo
  });

  const {
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
  } = usePosCart({
    dbProducts,
    dbPromos,
    aprioriRules,
    discountEngine,
    setAlertMsg,
    barcodeInput,
    onAfterHold: () => paymentOnAfterHoldRef.current?.()
  });
  cartSetItemsRef.current = setItems;

  const {
    isPpobMenuOpen,
    setIsPpobMenuOpen,
    ppobTransactions,
    setPpobTransactions,
    isFetchingPpobTransactions,
    setIsFetchingPpobTransactions,
    ppobSearchQuery,
    setPpobSearchQuery,
    fetchPpobTransactions,
    handleCheckPpobStatus,
    selectedPpobForRefund,
    isRefundModalOpen,
    isRefunding,
    openRefundModal,
    closeRefundModal,
    handleRefundPpob,
    unrefundedFailedPpobCount
  } = usePosPpob({ authToken, setAlertMsg, branchId, terminalInfo });

  const {
    inputValue,
    setInputValue,
    searchResults,
    setSearchResults,
    highlightedIndex,
    setHighlightedIndex,
    handleInputChange,
    handleClearInput,
    handleBarcodeScan
  } = usePosBarcodeSearch({
    dbProducts,
    isSubtotalMode,
    setIsSubtotalMode,
    barcodeInput,
    addItemToTransaction,
    setAlertMsg
  });

  const {
    paymentMethod,
    setPaymentMethod,
    isProcessing,
    setIsProcessing,
    receivedAmount,
    setReceivedAmount,
    payments,
    setPayments,
    isMultiPaymentModalOpen,
    setIsMultiPaymentModalOpen,
    isVoucherModalOpen,
    setIsVoucherModalOpen,
    voucherInput,
    setVoucherInput,
    voucherSource,
    setVoucherSource,
    isMultiCashModalOpen,
    setIsMultiCashModalOpen,
    multiCashInput,
    setMultiCashInput,
    isDirectCashModalOpen,
    setIsDirectCashModalOpen,
    directCashInput,
    setDirectCashInput,
    isDirectCardAmountModalOpen,
    setIsDirectCardAmountModalOpen,
    directCardInput,
    setDirectCardInput,
    selectedBank,
    setSelectedBank,
    isBankSelectOpen,
    setIsBankSelectOpen,
    isMultiBankSelectOpen,
    setIsMultiBankSelectOpen,
    isMultiCardAmountModalOpen,
    setIsMultiCardAmountModalOpen,
    multiCardInput,
    setMultiCardInput,
    pendingCardAmount,
    setPendingCardAmount,
    isRedeemPointModalOpen,
    setIsRedeemPointModalOpen,
    pointsToRedeemInput,
    setPointsToRedeemInput,
    changeModalInfo,
    setChangeModalInfo,
    showReceiptPreview,
    setShowReceiptPreview,
    lastTransaction,
    setLastTransaction,
    isReprintOldModalOpen,
    setIsReprintOldModalOpen,
    oldReceiptInput,
    setOldReceiptInput,
    totalPaid,
    changeAmount,
    handleApplyPoints,
    startPayment,
    processTransaction,
    mapApiTransactionToLocal,
    handleReprintLast,
    handleReprintOld,
    handleReprintPpob,
    isPpobFailedModalOpen,
    setIsPpobFailedModalOpen,
    ppobFailedModalData,
    handleRetryPpobWithNewNumber,
    handleRemovePpobAndContinue
  } = usePosPayment({
    items,
    setItems,
    setPwpUpsellPrompt,
    finalAmount,
    totalDiscount,
    manualTotalDiscount,
    setManualTotalDiscount,
    setIsReturnMode,
    isReturnMode,
    selectedCustomer,
    setSelectedCustomer,
    customers,
    setCustomers,
    pointConversionRate,
    pointRedemptionValue,
    minimumPointsToRedeem,
    pointRedemptionEnabled,
    appliedPromos,
    terminalInfo,
    activeShift,
    branchCode,
    branchName,
    branchAddress,
    branchPhone,
    orgName,
    userName,
    allTerminals,
    serverOffset,
    authToken,
    isOnline,
    storeLocalTransaction,
    syncTransactions,
    formatCurrency,
    formatThousandSeparator,
    setAlertMsg,
    barcodeInput,
    setInputValue,
    setIsSubtotalMode
  });
  paymentOnAfterHoldRef.current = () => setPayments([]);

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

  const handleSelectTerminal = (terminal) => {
    safeSetItem('pos_terminal_id', terminal.id);
    safeSetItem('pos_terminal_name', terminal.name);
    safeSetItem('pos_terminal_branch_id', terminal.branch_id);
    safeSetItem('pos_terminal_branch_name', terminal.branch_name || branchName);
    setTerminalInfo({ ...terminalInfo, id: terminal.id, name: terminal.name });
    setIsTerminalModalOpen(false);
    checkActiveShift(terminal.id);
  };





  const handleClearDiscount = () => {
    // Preserve items in the cart!
    setManualTotalDiscount(0);
    setQueuedDiscount(null);
    setIsReturnMode(false);
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





  usePosShortcuts({
    posSettings,
    handlers: {
      btn_pay: () => processTransaction(),
      btn_subtotal: () => {
        setIsSubtotalMode(true);
        barcodeInput.current?.focus();
      },
      btn_disc_item_rp: () => requestAuthorization("DISCOUNT", () => handleManualDiscountItem('NOMINAL')),
      btn_disc_item_pct: () => requestAuthorization("DISCOUNT", () => handleManualDiscountItem('PERCENT')),
      btn_disc_total_rp: () => requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('NOMINAL')),
      btn_disc_total_pct: () => requestAuthorization("DISCOUNT", () => handleManualTotalDiscount('PERCENT')),
      btn_tunai: () => startPayment('CASH'),
      btn_card: () => startPayment('CARD'),
      btn_voucher: () => requestAuthorization("VOUCHER", () => setIsVoucherModalOpen(true)),
      btn_multi_pay: () => requestAuthorization("MULTI_PAYMENT", () => setIsMultiPaymentModalOpen(true)),
      btn_open_price: () => {
        requestAuthorization("OPEN_PRICE", () => {
          if (items.length > 0) {
            setOpenPriceTargetItem(items[items.length - 1]);
            setIsOpenPriceModalOpen(true);
          } else {
            setAlertMsg({ text: 'Pilih item terlebih dahulu', type: 'error' });
            setTimeout(() => setAlertMsg(null), 2000);
          }
        });
      },
      btn_kas: () => {
        if (window.electronAPI && window.electronAPI.openCashDrawer) {
          window.electronAPI.openCashDrawer(localPrinterSettings?.printerName || 'LPT1').catch(e => console.error(e));
        }
        setIsCashMovementModalOpen(true);
      },
      btn_void_item: () => requestAuthorization("VOID", () => updateQuantity(items[items.length - 1]?.productId, 0)),
      btn_void_all: () => {
        requestAuthorization("VOID", () => {
          setItems([]);
          setPwpUpsellPrompt(null);
          setPayments([]);
          setManualTotalDiscount(0);
          setIsReturnMode(false);
        });
      },
      handleClearDiscount: () => handleClearDiscount(),
      btn_clear: () => handleClearDiscount(),
      btn_clear_discount: () => handleClearDiscount(),
      btn_hold: () => requestAuthorization("HOLD_RECALL", () => handleHoldTransaction()),
      btn_hold_transaction: () => requestAuthorization("HOLD_RECALL", () => handleHoldTransaction()),
      handleHoldTransaction: () => requestAuthorization("HOLD_RECALL", () => handleHoldTransaction()),
      btn_recall: () => requestAuthorization("HOLD_RECALL", () => setIsRecallModalOpen(true)),
      btn_recall_transaction: () => requestAuthorization("HOLD_RECALL", () => setIsRecallModalOpen(true)),
      setIsRecallModalOpen: () => requestAuthorization("HOLD_RECALL", () => setIsRecallModalOpen(true)),
      btn_member: () => setIsMemberModalOpen(true),
      setIsMemberModalOpen: () => setIsMemberModalOpen(true),
      btn_retur: () => requestAuthorization("RETURN", () => setIsReturnModalOpen(true)),
      btn_return: () => requestAuthorization("RETURN", () => setIsReturnModalOpen(true)),
      setIsReturnModalOpen: () => requestAuthorization("RETURN", () => setIsReturnModalOpen(true)),
      btn_qty: () => setIsQtyModalOpen(true),
      btn_close_shift: () => {
        if (window.electronAPI && window.electronAPI.openCashDrawer) {
          window.electronAPI.openCashDrawer(localPrinterSettings?.printerName || 'LPT1').catch(e => console.error(e));
        }
        setIsCloseShiftModalOpen(true);
      },
      btn_reprint_last: () => requestAuthorization("REPRINT_LAST", () => handleReprintLast()),
      btn_reprint_old: () => requestAuthorization("REPRINT_OLD", () => setIsReprintOldModalOpen(true)),
      btn_ppob_menu: () => {
        setIsPpobMenuOpen(true);
        fetchPpobTransactions();
      }
    },
    dependencies: [items, isSubtotalMode, receivedAmount, activeShift, paymentMethod, subtotal, finalAmount, totalDiscount, manualTotalDiscount, dbProducts]
  });

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
      <PosModalsContainer
        isTerminalModalOpen={isTerminalModalOpen}
        allTerminals={allTerminals}
        handleSelectTerminal={handleSelectTerminal}
        isOpenShiftModalOpen={isOpenShiftModalOpen}
        selectedShiftName={selectedShiftName}
        setSelectedShiftName={setSelectedShiftName}
        startingCash={startingCash}
        setStartingCash={setStartingCash}
        handleOpenShift={handleOpenShift}
        closedShiftsToday={closedShiftsToday}
        isCloseShiftModalOpen={isCloseShiftModalOpen}
        setIsCloseShiftModalOpen={setIsCloseShiftModalOpen}
        activeShift={activeShift}
        actualCash={actualCash}
        setActualCash={setActualCash}
        handleCloseShift={handleCloseShift}
        isProcessing={isProcessing}
        isCashMovementModalOpen={isCashMovementModalOpen}
        setIsCashMovementModalOpen={setIsCashMovementModalOpen}
        cashMovementType={cashMovementType}
        setCashMovementType={setCashMovementType}
        cashMovementAmount={cashMovementAmount}
        setCashMovementAmount={setCashMovementAmount}
        cashMovementDesc={cashMovementDesc}
        setCashMovementDesc={setCashMovementDesc}
        handleCashMovement={handleCashMovement}
        isPrinterSettingsOpen={isPrinterSettingsOpen}
        setIsPrinterSettingsOpen={setIsPrinterSettingsOpen}
        localPrinterSettings={localPrinterSettings}
        setLocalPrinterSettings={setLocalPrinterSettings}
        eodReportData={eodReportData}
        setEodReportData={setEodReportData}
        branchSettings={branchSettings}
        onLogout={onLogout}
        isRecallModalOpen={isRecallModalOpen}
        setIsRecallModalOpen={setIsRecallModalOpen}
        heldTransactions={heldTransactions}
        setHeldTransactions={setHeldTransactions}
        handleRecallTransaction={handleRecallTransaction}
        safeSetItem={safeSetItem}
        isMemberModalOpen={isMemberModalOpen}
        setIsMemberModalOpen={setIsMemberModalOpen}
        customers={customers}
        setSelectedCustomer={setSelectedCustomer}
        memberSearchQuery={memberSearchQuery}
        setMemberSearchQuery={setMemberSearchQuery}
        isRedeemPointModalOpen={isRedeemPointModalOpen}
        setIsRedeemPointModalOpen={setIsRedeemPointModalOpen}
        pointRedemptionEnabled={pointRedemptionEnabled}
        pointRedemptionValue={pointRedemptionValue}
        minimumPointsToRedeem={minimumPointsToRedeem}
        selectedCustomer={selectedCustomer}
        finalAmount={finalAmount}
        payments={payments}
        setPayments={setPayments}
        pointsToRedeemInput={pointsToRedeemInput}
        setPointsToRedeemInput={setPointsToRedeemInput}
        handleApplyPoints={handleApplyPoints}
        changeModalInfo={changeModalInfo}
        setChangeModalInfo={setChangeModalInfo}
        showReceiptPreview={showReceiptPreview}
        setShowReceiptPreview={setShowReceiptPreview}
        lastTransaction={lastTransaction}
        isBankSelectOpen={isBankSelectOpen}
        setIsBankSelectOpen={setIsBankSelectOpen}
        banks={banks}
        setSelectedBank={setSelectedBank}
        setDirectCardInput={setDirectCardInput}
        setIsDirectCardAmountModalOpen={setIsDirectCardAmountModalOpen}
        isMultiBankSelectOpen={isMultiBankSelectOpen}
        setIsMultiBankSelectOpen={setIsMultiBankSelectOpen}
        pendingCardAmount={pendingCardAmount}
        setPendingCardAmount={setPendingCardAmount}
        pendingAuthAction={pendingAuthAction}
        setPendingAuthAction={setPendingAuthAction}
        authToken={authToken}
        isOnline={isOnline}
        isReturnModalOpen={isReturnModalOpen}
        setIsReturnModalOpen={setIsReturnModalOpen}
        handleReturnSuccess={handleReturnSuccess}
        discountModal={discountModal}
        setDiscountModal={setDiscountModal}
        discountInputVal={discountInputVal}
        setDiscountInputVal={setDiscountInputVal}
        applyEnteredDiscount={applyEnteredDiscount}
        isVoucherModalOpen={isVoucherModalOpen}
        setIsVoucherModalOpen={setIsVoucherModalOpen}
        voucherInput={voucherInput}
        setVoucherInput={setVoucherInput}
        voucherSource={voucherSource}
        setVoucherSource={setVoucherSource}
        isOpenPriceModalOpen={isOpenPriceModalOpen}
        setIsOpenPriceModalOpen={setIsOpenPriceModalOpen}
        openPriceTargetItem={openPriceTargetItem}
        setOpenPriceTargetItem={setOpenPriceTargetItem}
        newOpenPrice={newOpenPrice}
        setNewOpenPrice={setNewOpenPrice}
        items={items}
        setItems={setItems}
        isMultiPaymentModalOpen={isMultiPaymentModalOpen}
        setIsMultiPaymentModalOpen={setIsMultiPaymentModalOpen}
        processTransaction={processTransaction}
        isDirectCashModalOpen={isDirectCashModalOpen}
        setIsDirectCashModalOpen={setIsDirectCashModalOpen}
        directCashInput={directCashInput}
        setDirectCashInput={setDirectCashInput}
        isDirectCardAmountModalOpen={isDirectCardAmountModalOpen}
        directCardInput={directCardInput}
        selectedBank={selectedBank}
        isMultiCashModalOpen={isMultiCashModalOpen}
        setIsMultiCashModalOpen={setIsMultiCashModalOpen}
        multiCashInput={multiCashInput}
        setMultiCashInput={setMultiCashInput}
        isMultiCardAmountModalOpen={isMultiCardAmountModalOpen}
        setIsMultiCardAmountModalOpen={setIsMultiCardAmountModalOpen}
        multiCardInput={multiCardInput}
        setMultiCardInput={setMultiCardInput}
        isQtyModalOpen={isQtyModalOpen}
        setIsQtyModalOpen={setIsQtyModalOpen}
        nextItemQty={nextItemQty}
        setNextItemQty={setNextItemQty}
        isReprintOldModalOpen={isReprintOldModalOpen}
        setIsReprintOldModalOpen={setIsReprintOldModalOpen}
        oldReceiptInput={oldReceiptInput}
        setOldReceiptInput={setOldReceiptInput}
        handleReprintOld={handleReprintOld}
        isPpobMenuOpen={isPpobMenuOpen}
        setIsPpobMenuOpen={setIsPpobMenuOpen}
        ppobSearchQuery={ppobSearchQuery}
        setPpobSearchQuery={setPpobSearchQuery}
        fetchPpobTransactions={fetchPpobTransactions}
        isFetchingPpobTransactions={isFetchingPpobTransactions}
        ppobTransactions={ppobTransactions}
        handleCheckPpobStatus={handleCheckPpobStatus}
        handleReprintPpob={handleReprintPpob}
        openRefundModal={openRefundModal}
        isRefundModalOpen={isRefundModalOpen}
        closeRefundModal={closeRefundModal}
        selectedPpobForRefund={selectedPpobForRefund}
        handleRefundPpob={handleRefundPpob}
        isRefunding={isRefunding}
        isPpobFailedModalOpen={isPpobFailedModalOpen}
        setIsPpobFailedModalOpen={setIsPpobFailedModalOpen}
        ppobFailedModalData={ppobFailedModalData}
        handleRetryPpobWithNewNumber={handleRetryPpobWithNewNumber}
        handleRemovePpobAndContinue={handleRemovePpobAndContinue}
        isDigitalInputModalOpen={isDigitalInputModalOpen}
        setIsDigitalInputModalOpen={setIsDigitalInputModalOpen}
        pendingDigitalProduct={pendingDigitalProduct}
        setPendingDigitalProduct={setPendingDigitalProduct}
        customerNoInput={customerNoInput}
        setCustomerNoInput={setCustomerNoInput}
        handleDigitalProductSubmit={handleDigitalProductSubmit}
        formatCurrency={formatCurrency}
        formatThousandSeparator={formatThousandSeparator}
        setAlertMsg={setAlertMsg}
        barcodeInput={barcodeInput}
      />

      {/* --- MAIN UI --- */}
      <PosHeader
        orgName={orgName}
        branchName={branchName}
        terminalInfo={terminalInfo}
        activeShift={activeShift}
        pendingCount={pendingCount}
        unrefundedFailedPpobCount={unrefundedFailedPpobCount}
        onOpenPpobMenu={() => {
          setIsPpobMenuOpen(true);
          fetchPpobTransactions();
        }}
        isOnline={isOnline}
        syncStatus={syncStatus}
        syncTransactions={syncTransactions}
        setIsPrinterSettingsOpen={setIsPrinterSettingsOpen}
        theme={theme}
        toggleTheme={toggleTheme}
        userName={userName}
        setIsTerminalModalOpen={setIsTerminalModalOpen}
        onLogout={onLogout}
      />

      <PwpUpsellBanner
        pwpUpsellPrompt={pwpUpsellPrompt}
        onAccept={(prompt) => {
          const prod = prompt.rewardProduct;
          const isBundling = prompt.type === 'BUNDLING';
          setPwpUpsellPrompt(null);
          addItemToTransaction(prod, 1);
          setAlertMsg({ 
            text: isBundling 
              ? `📦 Produk bundling "${prod.name}" ditambahkan! Diskon paket diterapkan.`
              : `🎁 Produk tebus murah "${prod.name}" berhasil ditambahkan!`, 
            type: 'success' 
          });
          barcodeInput.current?.focus();
        }}
        onDismiss={() => {
          setPwpUpsellPrompt(null);
          barcodeInput.current?.focus();
        }}
      />

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

      <div className="pos-main-layout" style={{ flex: 1, height: 'calc(100vh - 65px)', overflow: 'hidden', minHeight: 0 }}>
        <PosCartTable
          barcodeInput={barcodeInput}
          items={items}
          inputValue={inputValue}
          handleInputChange={handleInputChange}
          highlightedIndex={highlightedIndex}
          setHighlightedIndex={setHighlightedIndex}
          searchResults={searchResults}
          addItemToTransaction={addItemToTransaction}
          handleClearInput={handleClearInput}
          handleBarcodeScan={handleBarcodeScan}
          formatCurrency={formatCurrency}
          lastScannedProductId={lastScannedProductId}
          requestAuthorization={requestAuthorization}
          removeItem={removeItem}
          subtotal={subtotal}
          totalDiscount={totalDiscount}
          manualTotalDiscount={manualTotalDiscount}
          selectedCustomer={selectedCustomer}
          pointRedemptionEnabled={pointRedemptionEnabled}
          minimumPointsToRedeem={minimumPointsToRedeem}
          setPointsToRedeemInput={setPointsToRedeemInput}
          setIsRedeemPointModalOpen={setIsRedeemPointModalOpen}
          finalAmount={finalAmount}
          isSubtotalMode={isSubtotalMode}
          totalPaid={totalPaid}
          changeAmount={changeAmount}
        />

        <PosActionSidebar
          paymentMethod={paymentMethod}
          startPayment={startPayment}
          requestAuthorization={requestAuthorization}
          setIsVoucherModalOpen={setIsVoucherModalOpen}
          setIsMultiPaymentModalOpen={setIsMultiPaymentModalOpen}
          isSubtotalMode={isSubtotalMode}
          setIsSubtotalMode={setIsSubtotalMode}
          barcodeInput={barcodeInput}
          handleManualDiscountItem={handleManualDiscountItem}
          handleManualTotalDiscount={handleManualTotalDiscount}
          items={items}
          setOpenPriceTargetItem={setOpenPriceTargetItem}
          setIsOpenPriceModalOpen={setIsOpenPriceModalOpen}
          setAlertMsg={setAlertMsg}
          setIsQtyModalOpen={setIsQtyModalOpen}
          handleHoldTransaction={handleHoldTransaction}
          setIsRecallModalOpen={setIsRecallModalOpen}
          setIsMemberModalOpen={setIsMemberModalOpen}
          localPrinterSettings={localPrinterSettings}
          setIsCashMovementModalOpen={setIsCashMovementModalOpen}
          setIsReturnModalOpen={setIsReturnModalOpen}
          handleReprintLast={handleReprintLast}
          setIsReprintOldModalOpen={setIsReprintOldModalOpen}
          setIsPpobMenuOpen={setIsPpobMenuOpen}
          fetchPpobTransactions={fetchPpobTransactions}
          unrefundedFailedPpobCount={unrefundedFailedPpobCount}
          updateQuantity={updateQuantity}
          setItems={setItems}
          setPwpUpsellPrompt={setPwpUpsellPrompt}
          setPayments={setPayments}
          setManualTotalDiscount={setManualTotalDiscount}
          setIsReturnMode={setIsReturnMode}
          handleClearDiscount={handleClearDiscount}
          setIsCloseShiftModalOpen={setIsCloseShiftModalOpen}
          posSettings={posSettings}
        />
      </div>
    </div>
  );
};
