import { useState, useEffect, useCallback, useMemo } from 'react';

/**
 * Custom hook to encapsulate Suggested Orders / Smart Restock logic.
 * Shares state and API interactions between Desktop (SuggestedOrders.jsx)
 * and Mobile (MobileSuggestedOrders.jsx).
 */
export function useSuggestedOrders({ user, authToken, filterOnlyReorder = false }) {
  const [suggestions, setSuggestions] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  const [branches, setBranches] = useState([]);
  const [selectedBranchId, setSelectedBranchId] = useState('');
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState(null);
  const [successMsg, setSuccessMsg] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [supplierFilter, setSupplierFilter] = useState('');
  const [selectedItems, setSelectedItems] = useState(new Set());
  const [showFaq, setShowFaq] = useState(false);

  // Status color helper
  const getStatusColor = useCallback((status) => {
    switch (status) {
      case 'CRITICAL':
        return '#ef4444';
      case 'REORDER':
        return '#f59e0b';
      case 'OK':
        return '#10b981';
      default:
        return 'var(--text-muted, #94a3b8)';
    }
  }, []);

  // Fetch branches
  const fetchBranches = useCallback(async () => {
    if (!authToken) return;
    try {
      const res = await fetch('/api/v1/branches', {
        headers: { Authorization: `Bearer ${authToken}` }
      });
      if (res.ok) {
        const data = await res.json();
        setBranches(data);
        if (data.length > 0) {
          const deviceBranchId = localStorage.getItem('pos_device_branch_id');
          const found = data.find(b => b.id === deviceBranchId);
          setSelectedBranchId(found ? found.id : data[0].id);
        } else {
          const fallbackId = user?.branch_id || localStorage.getItem('pos_device_branch_id') || '';
          setSelectedBranchId(fallbackId);
        }
      }
    } catch (err) {
      console.error('Failed to fetch branches:', err);
      const fallbackId = user?.branch_id || localStorage.getItem('pos_device_branch_id') || '';
      setSelectedBranchId(fallbackId);
    }
  }, [authToken, user?.branch_id]);

  // Fetch suppliers
  const fetchSuppliers = useCallback(async () => {
    if (!authToken) return;
    try {
      const res = await fetch('/api/v1/suppliers', {
        headers: { Authorization: `Bearer ${authToken}` }
      });
      if (res.ok) {
        const data = await res.json();
        setSuppliers(data);
      }
    } catch (err) {
      console.error('Failed to fetch suppliers:', err);
    }
  }, [authToken]);

  // Fetch suggestions
  const fetchSuggestions = useCallback(async (branchIdOverride) => {
    if (!authToken) return;
    setLoading(true);
    setError(null);
    try {
      const branchId = branchIdOverride !== undefined 
        ? branchIdOverride 
        : (selectedBranchId || user?.branch_id || localStorage.getItem('pos_device_branch_id') || '');
      
      const url = branchId 
        ? `/api/v1/suggested-orders?branch_id=${branchId}` 
        : '/api/v1/suggested-orders';

      const res = await fetch(url, {
        headers: { Authorization: `Bearer ${authToken}` }
      });

      if (!res.ok) {
        throw new Error('Gagal mengambil data peramalan');
      }

      const json = await res.json();
      let list = json.data || [];

      if (filterOnlyReorder) {
        list = list.filter(item => item.status === 'REORDER' || item.status === 'CRITICAL');
        list.sort((a, b) => {
          if (a.status === 'CRITICAL' && b.status !== 'CRITICAL') return -1;
          if (a.status !== 'CRITICAL' && b.status === 'CRITICAL') return 1;
          return 0;
        });
        setSuggestions(list);
        setSelectedItems(new Set(list.map(item => item.product_id)));
      } else {
        setSuggestions(list);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [authToken, selectedBranchId, user?.branch_id, filterOnlyReorder]);

  // Initial load
  useEffect(() => {
    fetchBranches();
    fetchSuppliers();
  }, [fetchBranches, fetchSuppliers]);

  // Trigger suggestions fetch when branch or token changes
  useEffect(() => {
    fetchSuggestions(selectedBranchId);
  }, [selectedBranchId, fetchSuggestions]);

  // Selection helpers
  const toggleSelect = useCallback((productId) => {
    setSelectedItems(prev => {
      const next = new Set(prev);
      if (next.has(productId)) {
        next.delete(productId);
      } else {
        next.add(productId);
      }
      return next;
    });
  }, []);

  const selectAll = useCallback((productIds) => {
    setSelectedItems(new Set(productIds));
  }, []);

  const clearSelection = useCallback(() => {
    setSelectedItems(new Set());
  }, []);

  // Update item quantity
  const handleQtyChange = useCallback((productId, newQty) => {
    setSuggestions(prev => prev.map(item => 
      item.product_id === productId ? { ...item, edited_qty: newQty } : item
    ));
  }, []);

  // Filtered suggestions for desktop view
  const filteredSuggestions = useMemo(() => {
    return suggestions.filter(s => {
      const name = (s.name || '').toLowerCase();
      const sku = (s.sku || '').toLowerCase();
      const query = searchTerm.toLowerCase();
      const matchesSearch = !query || name.includes(query) || sku.includes(query);
      const matchesSupplier = !supplierFilter || s.supplier_id === supplierFilter;
      return matchesSearch && matchesSupplier;
    });
  }, [suggestions, searchTerm, supplierFilter]);

  // Create single PO
  const handleCreateSinglePO = useCallback(async (item) => {
    if (!window.confirm(`Buat draft Pesanan Pembelian untuk ${item.name} sebanyak ${item.suggested_qty} unit?`)) {
      return false;
    }

    setLoading(true);
    try {
      const res = await fetch('/api/v1/purchase-orders/create-from-suggestion', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          product_id: item.product_id,
          suggested_qty: item.suggested_qty
        })
      });

      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.message || 'Gagal membuat Pesanan Pembelian');
      }

      const data = await res.json();
      alert(data.message);
      await fetchSuggestions();
      return true;
    } catch (err) {
      alert(err.message);
      return false;
    } finally {
      setLoading(false);
    }
  }, [authToken, fetchSuggestions]);

  // Create bulk PO
  const handleCreateBulkPO = useCallback(async ({ customItems, redirectFilament = false } = {}) => {
    const itemsToOrder = customItems || suggestions
      .filter(item => selectedItems.has(item.product_id) && (item.edited_qty !== undefined ? (parseFloat(item.edited_qty) || 0) > 0 : item.suggested_qty > 0))
      .map(item => ({
        product_id: item.product_id,
        suggested_qty: item.edited_qty !== undefined && item.edited_qty !== ''
          ? parseFloat(item.edited_qty) || 0
          : item.suggested_qty,
        original_qty: item.suggested_qty
      }));

    if (itemsToOrder.length === 0) {
      alert('Pilih produk yang memiliki saran jumlah pesanan terlebih dahulu.');
      return false;
    }

    if (!redirectFilament && !window.confirm(`Buat satu draft Pesanan Pembelian untuk ${itemsToOrder.length} produk terpilih?`)) {
      return false;
    }

    setProcessing(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const branchId = selectedBranchId || user?.branch_id || localStorage.getItem('pos_device_branch_id');
      const payload = { items: itemsToOrder };
      if (branchId) payload.branch_id = branchId;

      const res = await fetch('/api/v1/purchase-orders/create-bulk-from-suggestions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${authToken}`
        },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.message || 'Gagal membuat Pesanan Pembelian Massal');
      }

      const data = await res.json();

      if (redirectFilament && data.po?.id) {
        alert(data.message);
        clearSelection();
        window.location.href = `/admin/purchase-orders/${data.po.id}/edit`;
        return true;
      }

      const poNumbers = data.po_numbers ? data.po_numbers.join(', ') : (data.po?.po_number || '');
      setSuccessMsg(`${data.message}${poNumbers ? ` (${poNumbers})` : ''}`);
      setSuggestions(prev => prev.filter(item => !selectedItems.has(item.product_id)));
      clearSelection();
      return true;
    } catch (err) {
      if (redirectFilament) {
        alert(err.message);
      } else {
        setError(err.message);
      }
      return false;
    } finally {
      setProcessing(false);
    }
  }, [suggestions, selectedItems, selectedBranchId, user?.branch_id, authToken, clearSelection]);

  return {
    suggestions,
    setSuggestions,
    suppliers,
    branches,
    selectedBranchId,
    setSelectedBranchId,
    loading,
    setLoading,
    processing,
    error,
    setError,
    successMsg,
    setSuccessMsg,
    searchTerm,
    setSearchTerm,
    supplierFilter,
    setSupplierFilter,
    selectedItems,
    setSelectedItems,
    showFaq,
    setShowFaq,
    getStatusColor,
    fetchBranches,
    fetchSuppliers,
    fetchSuggestions,
    toggleSelect,
    selectAll,
    clearSelection,
    handleQtyChange,
    filteredSuggestions,
    handleCreateSinglePO,
    handleCreateBulkPO
  };
}
