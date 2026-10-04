import { useState, useEffect, useMemo, useCallback } from 'react';
import { initEcho } from '../utils/echo';

export const usePosPpob = ({ authToken, branchId, terminalInfo, setAlertMsg }) => {
  const [isPpobMenuOpen, setIsPpobMenuOpen] = useState(false);
  const [ppobTransactions, setPpobTransactions] = useState([]);
  const [isFetchingPpobTransactions, setIsFetchingPpobTransactions] = useState(false);
  const [ppobSearchQuery, setPpobSearchQuery] = useState('');
  
  // Refund modal states
  const [isRefundModalOpen, setIsRefundModalOpen] = useState(false);
  const [selectedPpobForRefund, setSelectedPpobForRefund] = useState(null);
  const [isRefunding, setIsRefunding] = useState(false);

  const fetchPpobTransactions = useCallback(async (isSilent = false) => {
    if (!authToken) return;
    if (!isSilent) setIsFetchingPpobTransactions(true);
    try {
      const res = await fetch('/api/v1/transactions/ppob/today', {
        headers: { 
          'Accept': 'application/json',
          'Authorization': `Bearer ${authToken}` 
        }
      });
      if (!res.ok) throw new Error('Gagal mengambil data PPOB hari ini');
      const contentType = res.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        throw new Error(`Server returned HTTP ${res.status}`);
      }
      const data = await res.json();
      setPpobTransactions(data);
    } catch (err) {
      console.error(err);
      if (!isSilent) {
        setAlertMsg({ text: 'Error: ' + err.message, type: 'error' });
      }
    } finally {
      if (!isSilent) setIsFetchingPpobTransactions(false);
    }
  }, [authToken, setAlertMsg]);

  // Initial load
  useEffect(() => {
    if (authToken) {
      fetchPpobTransactions(true);
    }
  }, [authToken, fetchPpobTransactions]);

  // Real-time WebSocket Listeners via Laravel Reverb
  useEffect(() => {
    if (!authToken || !branchId) return;

    let echoInstance = null;
    try {
      echoInstance = initEcho(authToken);
      if (echoInstance) {
        const channelName = `branch.${branchId}.ppob`;
        echoInstance.private(channelName).listen('PpobStatusUpdated', (event) => {
          const updatedPpob = event.ppob;
          if (!updatedPpob) return;

          setPpobTransactions((prev) => {
            let found = false;
            const next = prev.map((tx) => {
              if (tx.id === updatedPpob.transaction_id || tx.ppob_transactions?.some((p) => p.id === updatedPpob.id)) {
                found = true;
                const newPpobs = (tx.ppob_transactions || []).map((p) =>
                  p.id === updatedPpob.id ? { ...p, ...updatedPpob } : p
                );
                return { ...tx, ppob_transactions: newPpobs };
              }
              return tx;
            });

            // If not found in current list, refetch in background
            if (!found) {
              fetchPpobTransactions(true);
            }
            return next;
          });

          // Show non-blocking alert if status changed to Gagal
          if (updatedPpob.status === 'Gagal' && updatedPpob.refund_status !== 'REFUNDED') {
            setAlertMsg({
              text: `⚠️ PPOB GAGAL: ${updatedPpob.buyer_sku_code} (${updatedPpob.customer_no}) ditolak operator. Silakan lakukan refund jika konsumen klaim.`,
              type: 'warning',
              persist: true
            });
          }
        });
      }
    } catch (err) {
      console.error('Echo PPOB connection error:', err);
    }

    return () => {
      if (echoInstance) {
        try {
          echoInstance.leave(`branch.${branchId}.ppob`);
        } catch (e) {}
      }
    };
  }, [authToken, branchId, fetchPpobTransactions, setAlertMsg]);

  // Background Auto-Polling Fallback: Every 45s if there is any pending PPOB
  useEffect(() => {
    const hasPending = ppobTransactions.some((tx) =>
      tx.ppob_transactions?.some((p) => p.status === 'Pending')
    );

    if (!hasPending) return;

    const interval = setInterval(() => {
      fetchPpobTransactions(true);
    }, 45000);

    return () => clearInterval(interval);
  }, [ppobTransactions, fetchPpobTransactions]);

  // Manual Check Single Status
  const handleCheckPpobStatus = async (ppobId) => {
    try {
      const res = await fetch(`/api/v1/transactions/ppob/${ppobId}/check-status`, {
        method: 'POST',
        headers: { 
          'Accept': 'application/json',
          'Authorization': `Bearer ${authToken}` 
        }
      });
      if (!res.ok) throw new Error('Gagal cek status PPOB');
      const contentType = res.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        throw new Error(`Server returned HTTP ${res.status}`);
      }
      const { data } = await res.json();

      setPpobTransactions((prev) =>
        prev.map((tx) => {
          const updatedPpobs = (tx.ppob_transactions || []).map((p) =>
            p.id === ppobId ? { ...p, ...data } : p
          );
          return { ...tx, ppob_transactions: updatedPpobs };
        })
      );
      setAlertMsg({ text: 'Status berhasil diperbarui!', type: 'success' });
      setTimeout(() => setAlertMsg(null), 2500);
    } catch (err) {
      console.error(err);
      setAlertMsg({ text: 'Error: ' + err.message, type: 'error' });
    }
  };

  // Open Refund Modal
  const openRefundModal = (ppobItem) => {
    setSelectedPpobForRefund(ppobItem);
    setIsRefundModalOpen(true);
  };

  const closeRefundModal = () => {
    setSelectedPpobForRefund(null);
    setIsRefundModalOpen(false);
  };

  // Execute Refund
  const handleRefundPpob = async ({ refund_method, notes }) => {
    if (!selectedPpobForRefund) return;
    setIsRefunding(true);
    try {
      const res = await fetch(`/api/v1/transactions/ppob/${selectedPpobForRefund.id}/refund`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${authToken}`,
          'X-Terminal-Id': terminalInfo?.id || ''
        },
        body: JSON.stringify({
          refund_method,
          notes,
          terminal_id: terminalInfo?.id
        })
      });

      const contentType = res.headers.get('content-type') || '';
      const json = contentType.includes('application/json') ? await res.json() : {};
      if (!res.ok) {
        throw new Error(json.message || 'Gagal memproses refund');
      }

      setPpobTransactions((prev) =>
        prev.map((tx) => {
          const updatedPpobs = (tx.ppob_transactions || []).map((p) =>
            p.id === selectedPpobForRefund.id ? { ...p, ...json.data } : p
          );
          return { ...tx, ppob_transactions: updatedPpobs };
        })
      );

      const methodLabel = refund_method === 'CASH' ? 'Tunai (Laci Kasir)' : 'Transfer / E-Wallet';
      setAlertMsg({
        text: `✓ Refund Rp ${Number(json.data?.refund_amount || 0).toLocaleString('id-ID')} via ${methodLabel} BERHASIL dicatat.`,
        type: 'success'
      });
      setTimeout(() => setAlertMsg(null), 4000);
    } catch (err) {
      console.error(err);
      setAlertMsg({ text: 'Refund Gagal: ' + err.message, type: 'error' });
    } finally {
      setIsRefunding(false);
    }
  };

  // Count unrefunded failed PPOBs for badge in header and sidebar
  const unrefundedFailedPpobCount = useMemo(() => {
    if (!Array.isArray(ppobTransactions)) return 0;
    let count = 0;
    ppobTransactions.forEach((tx) => {
      (tx.ppob_transactions || []).forEach((p) => {
        if (p.status === 'Gagal' && p.refund_status !== 'REFUNDED') {
          count++;
        }
      });
    });
    return count;
  }, [ppobTransactions]);

  return {
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
    openRefundModal,
    closeRefundModal,
    isRefundModalOpen,
    selectedPpobForRefund,
    handleRefundPpob,
    isRefunding,
    unrefundedFailedPpobCount
  };
};
