import { useState, useEffect, useCallback } from 'react';

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    console.warn(`[Storage Warning] Failed to save key "${key}" to localStorage:`, e);
  }
};

// Purge obsolete pos_active_shift and any cross-polluted/mismatched keys immediately
try {
  localStorage.removeItem('pos_active_shift');
  for (let i = localStorage.length - 1; i >= 0; i--) {
    const k = localStorage.key(i);
    if (k && k.startsWith('pos_active_shift_')) {
      try {
        const parts = k.split('_');
        if (parts.length >= 5) {
          const expectedUserId = parts[3];
          const raw = localStorage.getItem(k);
          if (raw) {
            const parsed = JSON.parse(raw);
            if (parsed && String(parsed.user_id) !== String(expectedUserId)) {
              localStorage.removeItem(k);
            }
          }
        }
      } catch (e) {}
    }
  }
} catch (e) {}

const getCachedShift = (userId, terminalId) => {
  if (!userId) return null;

  // 1. Check exact user + terminal key
  if (terminalId) {
    const key = `pos_active_shift_${userId}_${terminalId}`;
    const val = localStorage.getItem(key);
    if (val) {
      try {
        const parsed = JSON.parse(val);
        if (parsed && String(parsed.user_id) === String(userId)) {
          return val;
        } else {
          localStorage.removeItem(key);
        }
      } catch (e) {
        localStorage.removeItem(key);
      }
    }
  }

  // 2. Check user-specific key across terminals if terminalId not yet resolved
  for (let i = 0; i < localStorage.length; i++) {
    const k = localStorage.key(i);
    if (k && k.startsWith(`pos_active_shift_${userId}_`)) {
      const val = localStorage.getItem(k);
      if (val) {
        try {
          const parsed = JSON.parse(val);
          if (parsed && String(parsed.user_id) === String(userId)) {
            return val;
          } else {
            localStorage.removeItem(k);
          }
        } catch (e) {
          localStorage.removeItem(k);
        }
      }
    }
  }

  return null;
};

const saveActiveShift = (shift, userId, terminalId) => {
  // CRITICAL: A shift MUST strictly be saved under its actual owner (shift.user_id)
  const uId = shift?.user_id || userId;
  const tId = shift?.terminal_id || terminalId;
  if (uId && tId) {
    safeSetItem(`pos_active_shift_${uId}_${tId}`, JSON.stringify(shift));
  }
};

const removeActiveShift = (userId, terminalId) => {
  if (userId && terminalId) {
    localStorage.removeItem(`pos_active_shift_${userId}_${terminalId}`);
  }
  if (userId) {
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i);
      if (k && k.startsWith(`pos_active_shift_${userId}_`)) {
        localStorage.removeItem(k);
      }
    }
  }
  localStorage.removeItem('pos_active_shift');
};

export const usePosShift = ({
  authToken,
  user,
  terminalInfo,
  onLogout,
  setAlertMsg,
  localPrinterSettings,
  syncTransactions
}) => {
  const currentUserId = user?.id || (() => {
    try {
      return JSON.parse(localStorage.getItem('pos_user'))?.id;
    } catch {
      return null;
    }
  })();

  const [activeShift, setActiveShift] = useState(() => {
    try {
      const uId = user?.id || JSON.parse(localStorage.getItem('pos_user'))?.id;
      const tId = terminalInfo?.id || localStorage.getItem('pos_terminal_id');
      if (uId) {
        const cached = getCachedShift(uId, tId);
        if (cached) {
          const parsed = JSON.parse(cached);
          if (parsed && String(parsed.user_id) === String(uId)) {
            return parsed;
          }
        }
      }
    } catch (e) {}
    return null;
  });
  const [isOpenShiftModalOpen, setIsOpenShiftModalOpen] = useState(false);
  const [isCloseShiftModalOpen, setIsCloseShiftModalOpen] = useState(false);
  const [startingCash, setStartingCash] = useState('');
  const [actualCash, setActualCash] = useState('');
  const initialShift = localStorage.getItem('pos_preselected_shift');
  const validInitialShift = (initialShift === 'Shift 1' || initialShift === 'Shift 2') ? initialShift : 'Shift 1';
  const [selectedShiftName, setSelectedShiftName] = useState(validInitialShift);
  const [closedShiftsToday, setClosedShiftsToday] = useState([]);
  const [isCheckingShift, setIsCheckingShift] = useState(() => {
    if (!navigator.onLine) return false;
    try {
      const uId = user?.id || JSON.parse(localStorage.getItem('pos_user'))?.id;
      const tId = terminalInfo?.id || localStorage.getItem('pos_terminal_id');
      if (uId && getCachedShift(uId, tId)) return false;
    } catch (e) {}
    return true;
  });
  const [lockScreenInfo, setLockScreenInfo] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  // Cash Movement
  const [isCashMovementModalOpen, setIsCashMovementModalOpen] = useState(false);
  const [cashMovementType, setCashMovementType] = useState('CASH_IN');
  const [cashMovementAmount, setCashMovementAmount] = useState('');
  const [cashMovementDesc, setCashMovementDesc] = useState('');

  // EOD Report
  const [eodReportData, setEodReportData] = useState(null);

  const triggerOpenShiftModalOffline = useCallback((terminalId) => {
    const runningShiftsOnTerminal = [];
    if (terminalId) {
      for (let i = 0; i < localStorage.length; i++) {
        const k = localStorage.key(i);
        if (k && k.startsWith('pos_active_shift_') && k.endsWith(`_${terminalId}`)) {
          try {
            const parts = k.split('_');
            const expectedUserId = parts.length >= 5 ? parts[3] : null;
            const s = JSON.parse(localStorage.getItem(k));
            if (s && expectedUserId && String(s.user_id) !== String(expectedUserId)) {
              localStorage.removeItem(k);
              continue;
            }
            if (s && s.status === 'OPEN' && s.shift_name) {
              runningShiftsOnTerminal.push(s.shift_name);
            }
          } catch (e) {}
        }
      }
    }
    if (runningShiftsOnTerminal.length > 0) {
      setClosedShiftsToday(prev => Array.from(new Set([...prev, ...runningShiftsOnTerminal])));
      if (runningShiftsOnTerminal.includes('Shift 1') && !runningShiftsOnTerminal.includes('Shift 2')) {
        setSelectedShiftName('Shift 2');
      } else if (runningShiftsOnTerminal.includes('Shift 2') && !runningShiftsOnTerminal.includes('Shift 1')) {
        setSelectedShiftName('Shift 1');
      }
    } else {
      const preselected = localStorage.getItem('pos_preselected_shift');
      if (preselected === 'Shift 1' || preselected === 'Shift 2') {
        setSelectedShiftName(preselected);
      }
    }
    setIsOpenShiftModalOpen(true);
  }, []);

  const checkActiveShift = useCallback((terminalId) => {
    if (!terminalId) return;

    const resolvedUserId = user?.id || currentUserId || (() => {
      try {
        return JSON.parse(localStorage.getItem('pos_user'))?.id;
      } catch {
        return null;
      }
    })();

    // 1. FAST PATH: Check offline cache immediately
    const cachedShift = getCachedShift(resolvedUserId, terminalId);
    let parsedCached = null;
    if (cachedShift) {
      try {
        const parsed = JSON.parse(cachedShift);
        if (parsed && String(parsed.user_id) === String(resolvedUserId) && (!parsed.terminal_id || parsed.terminal_id === terminalId)) {
          parsedCached = parsed;
          setActiveShift(parsed);
        }
      } catch (e) {}
    }

    // Completely offline: resolve synchronously with zero network lag
    if (!navigator.onLine) {
      setIsCheckingShift(false);
      setLockScreenInfo(null);
      if (parsedCached) {
        const shiftDate = parsedCached.start_time ? new Date(parsedCached.start_time).toDateString() : null;
        const todayDate = new Date().toDateString();
        if (shiftDate && shiftDate !== todayDate) {
          setLockScreenInfo({
            status: 'CROSS_DAY_SHIFT_EXPIRED',
            message: `Peringatan: Shift ${parsedCached.shift_name} (Offline) dibuka pada tanggal kemarin dan belum ditutup! Anda wajib menutup shift kemarin terlebih dahulu sebelum membuka shift hari ini.`,
            shift: parsedCached
          });
        }
      } else {
        setActiveShift(null);
        triggerOpenShiftModalOffline(terminalId);
      }
      return;
    }

    // Online check: query backend with 2000ms timeout
    if (!parsedCached) {
      setIsCheckingShift(true);
    }
    setLockScreenInfo(null);

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 2000);

    fetch(`/api/v1/shifts/active?terminal_id=${terminalId}`, {
      headers: { 'Authorization': `Bearer ${authToken}` },
      signal: controller.signal
    })
      .then(res => {
        clearTimeout(timeoutId);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        if (data.closed_shifts) {
          setClosedShiftsToday(data.closed_shifts);
          if (data.closed_shifts.includes('Shift 1') && !data.closed_shifts.includes('Shift 2')) {
            setSelectedShiftName('Shift 2');
          } else if (!data.closed_shifts.includes('Shift 1')) {
            setSelectedShiftName('Shift 1');
          }
        }

        if (data.status === 'USER_HAS_OTHER_SHIFT' || data.status === 'TERMINAL_IN_USE') {
          setLockScreenInfo({ status: data.status, message: data.message });
          setActiveShift(null);
          removeActiveShift(currentUserId, terminalId);
        } else if (data.status === 'CROSS_DAY_SHIFT_EXPIRED') {
          setActiveShift(data.shift);
          saveActiveShift(data.shift, currentUserId, terminalId);
          setLockScreenInfo({
            status: 'CROSS_DAY_SHIFT_EXPIRED',
            message: data.message,
            shift: data.shift
          });
          setAlertMsg({
            text: data.message,
            type: 'error',
            persist: true
          });
        } else if (data.shift) {
          setActiveShift(data.shift);
          saveActiveShift(data.shift, currentUserId, terminalId);
          setAlertMsg({
            text: `Melanjutkan ${data.shift.shift_name} yang belum ditutup. Harap tutup shift ini jika ingin membuka shift baru.`,
            type: 'info',
            persist: true
          });
          setTimeout(() => setAlertMsg(null), 7000);
        } else {
          removeActiveShift(currentUserId, terminalId);
          setIsOpenShiftModalOpen(true);
        }
      })
      .catch(err => {
        clearTimeout(timeoutId);
        console.warn('Failed to check shift online, falling back to cache:', err.message);

        if (parsedCached) {
          const shiftDate = parsedCached.start_time ? new Date(parsedCached.start_time).toDateString() : null;
          const todayDate = new Date().toDateString();
          if (shiftDate && shiftDate !== todayDate) {
            setActiveShift(parsedCached);
            setLockScreenInfo({
              status: 'CROSS_DAY_SHIFT_EXPIRED',
              message: `Peringatan: Shift ${parsedCached.shift_name} (Offline) dibuka pada tanggal kemarin dan belum ditutup! Anda wajib menutup shift kemarin terlebih dahulu sebelum membuka shift hari ini.`,
              shift: parsedCached
            });
            return;
          }

          setActiveShift(parsedCached);
          setAlertMsg({
            text: `Melanjutkan ${parsedCached.shift_name} (Offline) yang belum ditutup. Harap tutup shift ini jika ingin membuka shift baru.`,
            type: 'info',
            persist: true
          });
          setTimeout(() => setAlertMsg(null), 7000);
        } else {
          setActiveShift(null);
          triggerOpenShiftModalOffline(terminalId);
        }
      })
      .finally(() => setIsCheckingShift(false));
  }, [authToken, currentUserId, setAlertMsg, triggerOpenShiftModalOffline, user]);

  // Sync offline active shift to server when back online
  useEffect(() => {
    if (navigator.onLine && activeShift && activeShift.is_offline && terminalInfo?.id && authToken) {
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
            saveActiveShift(data.shift, currentUserId, terminalInfo?.id);
            setAlertMsg({ text: `Shift offline Anda ("${activeShift.shift_name}") telah disinkronkan ke server secara otomatis!`, type: 'success' });
            setTimeout(() => setAlertMsg(null), 5000);

            if (typeof syncTransactions === 'function') {
              syncTransactions();
            }
          } else {
            console.error('Failed to register offline shift on server:', data.message);
          }
        })
        .catch(err => console.error('Error registering offline shift:', err));
    }
  }, [activeShift, terminalInfo?.id, authToken, currentUserId, setAlertMsg, syncTransactions]);

  const handleOpenShift = () => {
    if (isProcessing) return;

    if (!startingCash || isNaN(startingCash)) {
      setAlertMsg({ text: 'Modal awal harus diisi dengan angka.', type: 'error' });
      return;
    }

    if (closedShiftsToday.includes(selectedShiftName)) {
      setAlertMsg({ 
        text: `${selectedShiftName} sudah ditutup untuk hari ini. Silakan pilih shift yang belum ditutup.`, 
        type: 'error',
        persist: true
      });
      return;
    }

    const startingCashVal = parseFloat(startingCash);

    // If genuinely offline (no network)
    if (!navigator.onLine) {
      const localShift = {
        id: null,
        user_id: currentUserId,
        terminal_id: terminalInfo?.id,
        is_offline: true,
        shift_name: selectedShiftName,
        start_time: new Date().toISOString(),
        starting_cash: startingCashVal,
        status: 'OPEN'
      };
      setActiveShift(localShift);
      saveActiveShift(localShift, currentUserId, terminalInfo?.id);
      setIsOpenShiftModalOpen(false);
      if (window.electronAPI && window.electronAPI.openCashDrawer) {
        window.electronAPI.openCashDrawer(localPrinterSettings?.printerName || 'LPT1').catch(e => console.error(e));
      }
      setAlertMsg({ text: 'Tidak ada koneksi internet. Shift offline dibuka secara lokal.', type: 'info' });
      return;
    }

    setIsProcessing(true);

    fetch('/api/v1/shifts/open', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
      },
      body: JSON.stringify({
        terminal_id: terminalInfo?.id,
        shift_name: selectedShiftName,
        starting_cash: startingCashVal
      })
    })
      .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          throw {
            isServerResponse: true,
            status: res.status,
            message: data?.message || `Gagal membuka shift (Error ${res.status})`
          };
        }
        return data;
      })
      .then(data => {
        if (data.shift) {
          setActiveShift(data.shift);
          saveActiveShift(data.shift, currentUserId, terminalInfo?.id);
          setIsOpenShiftModalOpen(false);
          if (window.electronAPI && window.electronAPI.openCashDrawer) {
            window.electronAPI.openCashDrawer(localPrinterSettings?.printerName || 'LPT1').catch(e => console.error(e));
          }
          setAlertMsg({ text: 'Shift berhasil dibuka. Selamat bertugas!', type: 'success' });
        } else {
          setAlertMsg({ text: data.message || 'Gagal membuka shift.', type: 'error' });
        }
      })
      .catch(err => {
        if (err.isServerResponse) {
          // Penolakan resmi dari server (422, 403, 400 dll):
          // JANGAN BUKA SHIFT OFFLINE DAN JANGAN TUTUP MODAL!
          setAlertMsg({ text: err.message, type: 'error', persist: true });
          if (terminalInfo?.id) {
            checkActiveShift(terminalInfo.id);
          }
          return;
        }

        // Murni error jaringan / network failure
        console.error('Failed to open shift online, falling back to offline:', err);
        const localShift = {
          id: null,
          user_id: currentUserId,
          terminal_id: terminalInfo?.id,
          is_offline: true,
          shift_name: selectedShiftName,
          start_time: new Date().toISOString(),
          starting_cash: startingCashVal,
          status: 'OPEN'
        };
        setActiveShift(localShift);
        saveActiveShift(localShift, currentUserId, terminalInfo?.id);
        setIsOpenShiftModalOpen(false);
        if (window.electronAPI && window.electronAPI.openCashDrawer) {
          window.electronAPI.openCashDrawer(localPrinterSettings?.printerName || 'LPT1').catch(e => console.error(e));
        }
        setAlertMsg({ text: 'Koneksi server terputus. Shift offline dibuka secara lokal.', type: 'info' });
      })
      .finally(() => {
        setIsProcessing(false);
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

    if (activeShift.is_offline || !navigator.onLine) {
      removeActiveShift(currentUserId, terminalInfo?.id);
      setActiveShift(null);
      setLockScreenInfo(null);
      setIsCloseShiftModalOpen(false);
      setIsProcessing(false);
      // Immediately clear storage session so next boot starts cleanly at login
      localStorage.removeItem('pos_token');
      localStorage.removeItem('pos_user');
      localStorage.removeItem('pos_login_date');
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
          removeActiveShift(currentUserId, terminalInfo?.id);
          setActiveShift(null);
          setLockScreenInfo(null);
          setIsCloseShiftModalOpen(false);
          // Immediately clear session from localStorage so closing the app / PC doesn't leave session for next cashier
          localStorage.removeItem('pos_token');
          localStorage.removeItem('pos_user');
          localStorage.removeItem('pos_login_date');
          setAlertMsg({ text: 'Shift berhasil ditutup. Menyiapkan Laporan EOD...', type: 'success' });
          setEodReportData(data.shift);
        } else {
          setAlertMsg({ text: data.message || 'Gagal menutup shift.', type: 'error' });
        }
      })
      .catch(err => {
        console.error('Failed to close shift online, closing locally:', err);
        removeActiveShift(currentUserId, terminalInfo?.id);
        setActiveShift(null);
        setLockScreenInfo(null);
        setIsCloseShiftModalOpen(false);
        setIsProcessing(false);
        localStorage.removeItem('pos_token');
        localStorage.removeItem('pos_user');
        localStorage.removeItem('pos_login_date');
        setAlertMsg({ text: 'Koneksi terputus. Shift ditutup secara lokal. Terima kasih!', type: 'success', persist: true });
        setTimeout(() => onLogout(), 2000);
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
        terminal_id: terminalInfo?.id,
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
        const newShift = { ...activeShift };
        if (cashMovementType === 'CASH_IN') {
          newShift.total_cash_in = (newShift.total_cash_in || 0) + parseFloat(cashMovementAmount);
        } else {
          newShift.total_cash_out = (newShift.total_cash_out || 0) + parseFloat(cashMovementAmount);
        }
        setActiveShift(newShift);
        saveActiveShift(newShift, currentUserId, terminalInfo?.id);
      })
      .catch(() => {
        setIsProcessing(false);
        setAlertMsg({ text: 'Gagal mencatat manajemen kas (pastikan online).', type: 'error' });
      });
  };

  return {
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
    isProcessing,
    setIsProcessing,
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
    closedShiftsToday,
    setClosedShiftsToday
  };
};
