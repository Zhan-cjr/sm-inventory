import { useState } from 'react';

export const usePosPpob = ({ authToken, setAlertMsg }) => {
  const [isPpobMenuOpen, setIsPpobMenuOpen] = useState(false);
  const [ppobTransactions, setPpobTransactions] = useState([]);
  const [isFetchingPpobTransactions, setIsFetchingPpobTransactions] = useState(false);
  const [ppobSearchQuery, setPpobSearchQuery] = useState('');

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
    handleCheckPpobStatus
  };
};
