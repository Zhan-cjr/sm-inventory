import { useState, useEffect, useCallback } from 'react';

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    console.warn(`[Storage Warning] Failed to save key "${key}" to localStorage:`, e);
  }
};

export const usePosShift = ({
  authToken,
  terminalInfo,
  onLogout,
  setAlertMsg,
  localPrinterSettings,
  syncTransactions
}) => {
  const [activeShift, setActiveShift] = useState(null);
  const [isOpenShiftModalOpen, setIsOpenShiftModalOpen] = useState(false);
  const [isCloseShiftModalOpen, setIsCloseShiftModalOpen] = useState(false);
  const [startingCash, setStartingCash] = useState('');
  const [actualCash, setActualCash] = useState('');
  const initialShift = localStorage.getItem('pos_preselected_shift');
  const validInitialShift = (initialShift === 'Shift 1' || initialShift === 'Shift 2') ? initialShift : 'Shift 1';
  const [selectedShiftName, setSelectedShiftName] = useState(validInitialShift);
  const [closedShiftsToday, setClosedShiftsToday] = useState([]);
  const [isCheckingShift, setIsCheckingShift] = useState(true);
  const [lockScreenInfo, setLockScreenInfo] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  // Cash Movement
  const [isCashMovementModalOpen, setIsCashMovementModalOpen] = useState(false);
  const [cashMovementType, setCashMovementType] = useState('CASH_IN');
  const [cashMovementAmount, setCashMovementAmount] = useState('');
  const [cashMovementDesc, setCashMovementDesc] = useState('');

  // EOD Report
  const [eodReportData, setEodReportData] = useState(null);

  const checkActiveShift = useCallback((terminalId) => {
    if (!terminalId) return;
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
          } catch {
            setIsOpenShiftModalOpen(true);
          }
        } else {
          setIsOpenShiftModalOpen(true);
        }
      })
      .finally(() => setIsCheckingShift(false));
  }, [authToken, setAlertMsg]);

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
            safeSetItem('pos_active_shift', JSON.stringify(data.shift));
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
  }, [activeShift, terminalInfo?.id, authToken, setAlertMsg, syncTransactions]);

  const handleOpenShift = () => {
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
        is_offline: true,
        shift_name: selectedShiftName,
        start_time: new Date().toISOString(),
        starting_cash: startingCashVal,
        status: 'OPEN'
      };
      setActiveShift(localShift);
      safeSetItem('pos_active_shift', JSON.stringify(localShift));
      setIsOpenShiftModalOpen(false);
      if (window.electronAPI && window.electronAPI.openCashDrawer) {
        window.electronAPI.openCashDrawer(localPrinterSettings?.printerName || 'LPT1').catch(e => console.error(e));
      }
      setAlertMsg({ text: 'Tidak ada koneksi internet. Shift offline dibuka secara lokal.', type: 'info' });
      return;
    }

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
          safeSetItem('pos_active_shift', JSON.stringify(data.shift));
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
          is_offline: true,
          shift_name: selectedShiftName,
          start_time: new Date().toISOString(),
          starting_cash: startingCashVal,
          status: 'OPEN'
        };
        setActiveShift(localShift);
        safeSetItem('pos_active_shift', JSON.stringify(localShift));
        setIsOpenShiftModalOpen(false);
        if (window.electronAPI && window.electronAPI.openCashDrawer) {
          window.electronAPI.openCashDrawer(localPrinterSettings?.printerName || 'LPT1').catch(e => console.error(e));
        }
        setAlertMsg({ text: 'Koneksi server terputus. Shift offline dibuka secara lokal.', type: 'info' });
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
        safeSetItem('pos_active_shift', JSON.stringify(newShift));
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
