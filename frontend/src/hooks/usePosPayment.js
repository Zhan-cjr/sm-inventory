import { useState } from 'react';

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    console.warn(`[Storage Warning] Failed to save key "${key}" to localStorage:`, e);
  }
};

export const usePosPayment = ({
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
}) => {
  const [paymentMethod, setPaymentMethod] = useState('CASH');
  const [isProcessing, setIsProcessing] = useState(false);
  const [receivedAmount, setReceivedAmount] = useState('');
  const [payments, setPayments] = useState([]);

  // Modals for payment
  const [isMultiPaymentModalOpen, setIsMultiPaymentModalOpen] = useState(false);
  const [isVoucherModalOpen, setIsVoucherModalOpen] = useState(false);
  const [voucherInput, setVoucherInput] = useState('');
  const [voucherSource, setVoucherSource] = useState(null);
  const [isMultiCashModalOpen, setIsMultiCashModalOpen] = useState(false);
  const [multiCashInput, setMultiCashInput] = useState('');

  // Direct payment
  const [isDirectCashModalOpen, setIsDirectCashModalOpen] = useState(false);
  const [directCashInput, setDirectCashInput] = useState('');
  const [isDirectCardAmountModalOpen, setIsDirectCardAmountModalOpen] = useState(false);
  const [directCardInput, setDirectCardInput] = useState('');

  // Bank selection
  const [selectedBank, setSelectedBank] = useState(null);
  const [isBankSelectOpen, setIsBankSelectOpen] = useState(false);
  const [isMultiBankSelectOpen, setIsMultiBankSelectOpen] = useState(false);
  const [isMultiCardAmountModalOpen, setIsMultiCardAmountModalOpen] = useState(false);
  const [multiCardInput, setMultiCardInput] = useState('');
  const [pendingCardAmount, setPendingCardAmount] = useState(0);

  // Point redemption
  const [isRedeemPointModalOpen, setIsRedeemPointModalOpen] = useState(false);
  const [pointsToRedeemInput, setPointsToRedeemInput] = useState('');

  // Receipt & Change
  const [changeModalInfo, setChangeModalInfo] = useState(null);
  const [showReceiptPreview, setShowReceiptPreview] = useState(false);
  const [lastTransaction, setLastTransaction] = useState(null);

  // Reprint Old
  const [isReprintOldModalOpen, setIsReprintOldModalOpen] = useState(false);
  const [oldReceiptInput, setOldReceiptInput] = useState('');

  // PPOB Pre-flight Failure Correction
  const [isPpobFailedModalOpen, setIsPpobFailedModalOpen] = useState(false);
  const [ppobFailedModalData, setPpobFailedModalData] = useState(null);

  const totalPaid = payments.length > 0
    ? payments.reduce((sum, p) => sum + p.amount, 0)
    : (receivedAmount ? parseFloat(receivedAmount) : 0);
  const changeAmount = totalPaid - finalAmount;

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

  const startPayment = (method) => {
    setPaymentMethod(method);
    if (method === 'CARD') {
      setIsBankSelectOpen(true);
    } else if (method === 'CASH') {
      const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
      setDirectCashInput(sisa !== 0 ? formatThousandSeparator(sisa) : '');
      setIsDirectCashModalOpen(true);
    } else {
      processTransaction(method);
    }
  };

  const processTransaction = async (method = paymentMethod, bankId = selectedBank?.id, overrideReceived = null, explicitItems = null) => {
    const currentItems = explicitItems || items;
    if (currentItems.length === 0) return;
    setIsProcessing(true);
    try {
      const nowCorrected = new Date(Date.now() + serverOffset);
      const lastSync = parseInt(localStorage.getItem('pos_last_sync_time') || '0');

      // Safety check: Prevent backdating or extreme forward dating
      if (lastSync > 0) {
        const driftLimit = 24 * 60 * 60 * 1000;
        const diffFromLast = nowCorrected.getTime() - lastSync;

        if (diffFromLast < -300000) {
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

      const grossTotal = currentItems.reduce((sum, item) => sum + (item.quantity * parseFloat(item.unitPrice)), 0);
      const totalItemManualDiscount = currentItems.reduce((sum, item) => sum + (item.quantity * (item.manualDiscount || 0)), 0);
      const currentFinalAmount = explicitItems 
        ? Math.max(0, grossTotal - (totalItemManualDiscount + totalDiscount + manualTotalDiscount))
        : finalAmount;

      let finalPayments = [...payments];
      if (overrideReceived !== null) {
        finalPayments.push({ method, amount: parseFloat(overrideReceived), bankId, label: method === 'CASH' ? 'Tunai' : (method === 'CARD' ? 'Card' : method) });
      } else if (payments.length === 0) {
        finalPayments = [{ method, amount: parseFloat(receivedAmount) || currentFinalAmount, bankId }];
      }

      const currentReceived = finalPayments.reduce((sum, p) => sum + p.amount, 0);

      if (currentReceived < currentFinalAmount) {
        setAlertMsg({ text: `Pembayaran kurang! Kurang: ${formatCurrency(currentFinalAmount - currentReceived)}`, type: 'error' });
        setTimeout(() => setAlertMsg(null), 3000);
        setIsProcessing(false);
        return;
      }

      const currentChange = currentReceived - currentFinalAmount;
      const actualPaymentMethod = finalPayments.length > 1 ? 'MULTI' : finalPayments[0].method;

      const transaction = {
        items: currentItems,
        totalAmount: grossTotal,
        discountAmount: totalItemManualDiscount + totalDiscount + manualTotalDiscount,
        manualDiscount: totalItemManualDiscount + manualTotalDiscount,
        promoDiscount: totalDiscount,
        finalAmount: currentFinalAmount,
        paymentMethod: actualPaymentMethod,
        payments: finalPayments,
        bankId: bankId,
        terminalId: terminalInfo?.id,
        terminalCode: terminalInfo?.code,
        customerId: selectedCustomer?.id,
        shiftId: activeShift?.id,
        receivedAmount: currentReceived,
        changeAmount: currentChange,
        appliedPromos,
        receipt_number: (branchCode || 'SMI') + '-' + Math.random().toString(36).substring(2, 8).toUpperCase(),
        transaction_type: isReturnMode ? 'RETURN' : 'SALES',
      };

      // --- KHUSUS PPOB (PRODUK DIGITAL): PRE-FLIGHT CHECK LANGSUNG KE BACKEND ---
      const hasDigitalProducts = currentItems.some(i => i.productType === 'digital');
      if (hasDigitalProducts && !isOnline) {
        setAlertMsg({ text: 'Produk digital (PPOB) membutuhkan koneksi internet aktif.', type: 'error' });
        setIsProcessing(false);
        return;
      }

      if (hasDigitalProducts && isOnline) {
        const directRes = await fetch('/api/v1/transactions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${authToken}`,
            'X-Terminal-Id': terminalInfo?.id || '',
          },
          body: JSON.stringify({
            items: currentItems.map(item => ({
              product_id: item.productId,
              productId: item.productId,
              quantity: item.quantity,
              unit_price: item.unitPrice,
              unitPrice: item.unitPrice,
              manual_discount: item.manualDiscount || 0,
              manualDiscount: item.manualDiscount || 0,
              discount_per_item: item.discountPerItem || 0,
              discountPerItem: item.discountPerItem || 0,
              promotionId: item.promotionId || null,
              originalTransactionId: item.originalTransactionId || null,
              customer_no: item.customerNo || null,
              customer_wa_phone: item.customerWaPhone || null,
            })),
            payment_method: actualPaymentMethod,
            received_amount: currentReceived,
            change_amount: currentChange,
            total_amount: grossTotal,
            discount_amount: totalItemManualDiscount + totalDiscount + manualTotalDiscount,
            manual_discount: totalItemManualDiscount + manualTotalDiscount,
            promo_discount: totalDiscount,
            final_amount: currentFinalAmount,
            customer_id: selectedCustomer?.id || null,
            shift_id: activeShift?.id || null,
            payments: finalPayments,
            bank_id: bankId,
            terminal_id: terminalInfo?.id || null,
            receipt_number: transaction.receipt_number,
            transaction_type: transaction.transaction_type,
          })
        });

        let directJson = {};
        const contentType = directRes.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
          directJson = await directRes.json();
        } else {
          const rawText = await directRes.text();
          throw new Error(`Server returned HTTP ${directRes.status}: ${rawText.substring(0, 100)}`);
        }

        if (!directRes.ok) {
          if (directJson.error === 'PPOB_FAILED') {
            // PROVIDER GAGAL LANGSUNG (Nomor Salah / Gangguan).
            // Transaksi TIDAK DIBUAT di database, laci kasir TIDAK selisih, kasir TIDAK PERLU VOID!
            setIsProcessing(false);
            setPpobFailedModalData(directJson.failed_item || {
              product_name: 'Produk Digital',
              customer_no: currentItems.find(i => i.productType === 'digital')?.customerNo,
              provider_message: directJson.message
            });
            setIsPpobFailedModalOpen(true);
            return;
          }
          throw new Error(directJson.error || directJson.message || 'Transaksi gagal diproses');
        }

        // Ambil transaksi segar dengan status & SN PPOB
        let finalItemsForReceipt = [...currentItems];
        try {
          const freshRes = await fetch(`/api/v1/transactions/receipt/${encodeURIComponent(transaction.receipt_number)}`, {
            headers: { 
              'Accept': 'application/json',
              'Authorization': `Bearer ${authToken}` 
            }
          });
          if (freshRes.ok) {
            const freshContentType = freshRes.headers.get('content-type') || '';
            if (freshContentType.includes('application/json')) {
              const freshData = await freshRes.json();
              if (freshData.ppob_transactions) {
                finalItemsForReceipt = finalItemsForReceipt.map(item => {
                  if (item.productType === 'digital') {
                    const ppob = freshData.ppob_transactions.find(p => p.customer_no === item.customerNo);
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
            }
          }
        } catch (e) {}

        const currentCustomer = selectedCustomer;

        // Reset state transaksi
        setItems([]);
        setPwpUpsellPrompt(null);
        setPayments([]);
        setManualTotalDiscount(0);
        setReceivedAmount('');
        if (typeof setInputValue === 'function') setInputValue('');
        if (typeof setIsSubtotalMode === 'function') setIsSubtotalMode(false);
        setSelectedBank(null);

        // Update poin member
        if (currentCustomer) {
          const earnedPoints = Math.floor(currentFinalAmount / pointConversionRate);
          const updatedPoints = (currentCustomer.points || 0) + earnedPoints;
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

        const fullTransactionData = {
          ...transaction,
          items: finalItemsForReceipt,
          timestamp: new Date().toISOString(),
          branchName,
          branchAddress,
          branchPhone,
          orgName,
          userName,
          terminal_code: terminalInfo?.code || 'KASSA-1',
          customerName: currentCustomer ? currentCustomer.name : null,
          memberCardNumber: currentCustomer ? (currentCustomer.member_card_number || currentCustomer.card_number || currentCustomer.phone) : null,
          earnedPoints: currentCustomer ? Math.floor(currentFinalAmount / pointConversionRate) : 0,
          totalPoints: currentCustomer ? ((currentCustomer.points || 0) + Math.floor(currentFinalAmount / pointConversionRate)) : 0,
        };

        setLastTransaction(fullTransactionData);
        setShowReceiptPreview(true);
        setAlertMsg({ text: 'Transaksi berhasil disimpan!', type: 'success' });
        setTimeout(() => setAlertMsg(null), 3000);
        setIsProcessing(false);
        setIsMultiPaymentModalOpen(false);
        return;
      }

      // --- ALUR TRANSAKSI NON-PPOB (OFFLINE-FIRST DENGAN SYNC) ---
      let localTx = null;
      try {
        localTx = await storeLocalTransaction(transaction);
        if (!localTx) {
          throw new Error('Gagal menyimpan transaksi ke IndexedDB. Transaksi DIBATALKAN.');
        }
      } catch (err) {
        console.error('CRITICAL: Local transaction storage failed:', err);
        setAlertMsg({ text: `TRANSAKSI GAGAL: ${err.message}. Struk DILARANG dicetak!`, type: 'error', persist: true });
        setIsProcessing(false);
        return;
      }

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
      setPwpUpsellPrompt(null);
      setPayments([]);
      setManualTotalDiscount(0);
      setReceivedAmount('');
      if (typeof setInputValue === 'function') setInputValue('');
      if (typeof setIsSubtotalMode === 'function') setIsSubtotalMode(false);
      setSelectedBank(null);

      // Update customer points locally if selected
      if (currentCustomer) {
        const earnedPoints = Math.floor(currentFinalAmount / pointConversionRate);
        const updatedPoints = (currentCustomer.points || 0) + earnedPoints;

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

      if (barcodeInput?.current) {
        barcodeInput.current.focus();
      }
    } catch (error) {
      console.error('Transaction error:', error);
      setAlertMsg({ text: `Error: ${error.message}`, type: 'error' });
    } finally {
      setIsProcessing(false);
    }
  };

  const mapApiTransactionToLocal = (data) => {
    const ppobs = data.ppob_transactions ? [...data.ppob_transactions] : [];
    return {
      receiptNumber: data.receipt_number,
      terminalId: data.terminal_id,
      terminalCode: data.terminal?.code || data.terminal_code || allTerminals?.find(t => t.id === data.terminal_id)?.code || data.terminal_id?.substring(0, 8),
      timestamp: data.created_at,
      items: (data.items || []).map(item => {
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

  const handleReprintPpob = (tx) => {
    setLastTransaction({ ...mapApiTransactionToLocal(tx), isReprint: true });
    setShowReceiptPreview(true);
  };

  const handleRetryPpobWithNewNumber = async (newNumber) => {
    const updatedItems = items.map(item => {
      if (item.productType === 'digital') {
        return { ...item, customerNo: newNumber };
      }
      return item;
    });
    setItems(updatedItems);
    setIsPpobFailedModalOpen(false);
    return processTransaction(paymentMethod, selectedBank?.id, null, updatedItems);
  };

  const handleRemovePpobAndContinue = () => {
    const remainingItems = items.filter(item => item.productType !== 'digital');
    setItems(remainingItems);
    setIsPpobFailedModalOpen(false);
    setAlertMsg({ text: 'Item PPOB dihapus. Silakan selesaikan pembayaran barang fisik.', type: 'info' });
  };

  return {
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
    // PPOB Pre-flight failure correction
    isPpobFailedModalOpen,
    setIsPpobFailedModalOpen,
    ppobFailedModalData,
    setPpobFailedModalData,
    handleRetryPpobWithNewNumber,
    handleRemovePpobAndContinue
  };
};
