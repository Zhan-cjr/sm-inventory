import React, { useState, useRef } from 'react';
import { Search, RotateCcw, X, ShoppingCart } from 'lucide-react';

export const ReturnItemModal = ({ authToken, onSuccess, onCancel }) => {
  const [receiptNumber, setReceiptNumber] = useState('');
  const [transaction, setTransaction] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [returnItems, setReturnItems] = useState({});

  const searchInput = useRef(null);

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
  };

  const normalizeLocalTransaction = (localTx) => {
    return {
      id: localTx.localId,
      transaction_date: localTx.createdAt || new Date().toISOString(),
      final_amount: localTx.finalAmount,
      cashier_id: localTx.userName || 'Kasir',
      items: localTx.items.map((item, idx) => ({
        id: `local-item-${idx}`,
        product_id: item.productId,
        quantity: Math.abs(item.quantity),
        unit_price: item.unitPrice,
        discount_per_item: item.manualDiscount || 0,
        product: {
          id: item.productId,
          name: item.name,
          category_id: item.categoryId || null,
          sku: item.sku || 'LOCAL',
          is_service: item.isService || false
        }
      }))
    };
  };

  const searchLocalTransaction = (receipt) => {
    return new Promise((resolve) => {
      const request = indexedDB.open('PosDatabase', 2);
      
      request.onsuccess = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains('transactions')) {
          resolve(null);
          return;
        }
        
        try {
          const store = db.transaction('transactions', 'readonly').objectStore('transactions');
          const req = store.getAll();
          
          req.onsuccess = () => {
            const matched = req.result.find(tx => 
              tx.receipt_number === receipt || 
              tx.localId === receipt || 
              tx.id === receipt
            );
            resolve(matched ? normalizeLocalTransaction(matched) : null);
          };
          
          req.onerror = () => resolve(null);
        } catch (err) {
          console.error('Local IndexedDB search transaction error:', err);
          resolve(null);
        }
      };
      
      request.onerror = () => resolve(null);
    });
  };

  const searchTransaction = async (e) => {
    e.preventDefault();
    if (!receiptNumber) return;

    setIsLoading(true);
    setError(null);
    setTransaction(null);
    setReturnItems({});

    try {
      // 1. Search locally in IndexedDB first
      const localMatch = await searchLocalTransaction(receiptNumber.trim());
      if (localMatch) {
        setTransaction(localMatch);
        const initialReturns = {};
        localMatch.items.forEach(item => {
          initialReturns[item.id] = 0;
        });
        setReturnItems(initialReturns);
        setIsLoading(false);
        return;
      }

      // 2. Fallback to Backend API if not found locally
      const res = await fetch(`/api/v1/transactions/receipt/${receiptNumber.trim()}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.message || 'Transaksi tidak ditemukan.');
      }

      setTransaction(data);
      // Initialize return quantities to 0
      const initialReturns = {};
      data.items.forEach(item => {
        initialReturns[item.id] = 0;
      });
      setReturnItems(initialReturns);
    } catch (err) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  const handleQuantityChange = (itemId, maxQty, value) => {
    let qty = parseInt(value);
    if (isNaN(qty)) qty = 0;
    if (qty < 0) qty = 0;
    if (qty > maxQty) qty = maxQty;

    setReturnItems(prev => ({ ...prev, [itemId]: qty }));
  };

  const handleConfirm = () => {
    const itemsToReturn = [];
    transaction.items.forEach(item => {
      const returnQty = returnItems[item.id];
      if (returnQty > 0) {
        itemsToReturn.push({
          productId: item.product_id,
          categoryId: item.product?.category_id || null,
          sku: item.product?.sku || 'RETUR',
          name: item.product?.name || 'Item Retur',
          quantity: -returnQty, // Negative quantity for return
          unitPrice: item.unit_price,
          manualDiscount: item.discount_per_item || 0,
          discountPerItem: 0,
          isService: item.product?.is_service || false,
          originalTransactionId: transaction.id
        });
      }
    });

    if (itemsToReturn.length === 0) {
      setError('Pilih minimal 1 barang untuk diretur.');
      return;
    }

    onSuccess(itemsToReturn, transaction.id);
  };

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '600px', width: '90%' }}>
        <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
          <h2 style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', margin: 0, color: '#f59e0b' }}>
            <RotateCcw size={24} /> Retur Transaksi
          </h2>
          <button onClick={onCancel} style={{ background: 'transparent', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}>
            <X size={24} />
          </button>
        </header>

        <form onSubmit={searchTransaction} style={{ marginBottom: '1.5rem' }}>
          <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Nomor Nota / Scan Barcode Struk</label>
          <div style={{ display: 'flex', gap: '0.5rem' }}>
            <input 
              ref={searchInput}
              type="text" 
              className="modern-barcode-input" 
              style={{ flex: 1, padding: '0.75rem' }}
              placeholder="Contoh: SMI-A1B2C3"
              value={receiptNumber}
              onChange={(e) => setReceiptNumber(e.target.value)}
              autoFocus
            />
            <button type="submit" className="btn-primary" disabled={isLoading} style={{ padding: '0 1.5rem' }}>
              <Search size={20} />
            </button>
          </div>
        </form>

        {error && (
          <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', padding: '0.75rem', borderRadius: '8px', marginBottom: '1rem', border: '1px solid rgba(239, 68, 68, 0.2)', textAlign: 'center' }}>
            {error}
          </div>
        )}

        {transaction && (
          <div className="transaction-details" style={{ textAlign: 'left' }}>
            <div style={{ background: 'rgba(255,255,255,0.05)', padding: '1rem', borderRadius: '8px', marginBottom: '1rem' }}>
              <p><strong>Tgl:</strong> {new Date(transaction.transaction_date).toLocaleString('id-ID')}</p>
              <p><strong>Total:</strong> {formatCurrency(transaction.final_amount)}</p>
              <p><strong>Kasir:</strong> {transaction.cashier_id}</p>
            </div>

            <h3 style={{ marginBottom: '0.75rem', fontSize: '1rem' }}>Pilih Barang yang Diretur:</h3>
            <div style={{ maxHeight: '250px', overflowY: 'auto', marginBottom: '1.5rem', border: '1px solid rgba(255,255,255,0.1)', borderRadius: '8px' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead style={{ background: 'rgba(255,255,255,0.05)', position: 'sticky', top: 0 }}>
                  <tr>
                    <th style={{ padding: '0.75rem', textAlign: 'left', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>Barang</th>
                    <th style={{ padding: '0.75rem', textAlign: 'center', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>Harga</th>
                    <th style={{ padding: '0.75rem', textAlign: 'center', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>Qty Beli</th>
                    <th style={{ padding: '0.75rem', textAlign: 'center', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>Qty Retur</th>
                  </tr>
                </thead>
                <tbody>
                  {transaction.items.map(item => (
                    <tr key={item.id} style={{ borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
                      <td style={{ padding: '0.75rem' }}>{item.product?.name || 'Item Tidak Dikenal'}</td>
                      <td style={{ padding: '0.75rem', textAlign: 'center' }}>{formatCurrency(item.unit_price)}</td>
                      <td style={{ padding: '0.75rem', textAlign: 'center' }}>{item.quantity}</td>
                      <td style={{ padding: '0.75rem', textAlign: 'center' }}>
                        <input 
                          type="number" 
                          min="0" 
                          max={item.quantity}
                          className="modern-barcode-input"
                          style={{ width: '70px', padding: '0.25rem', textAlign: 'center' }}
                          value={returnItems[item.id]}
                          onChange={(e) => handleQuantityChange(item.id, item.quantity, e.target.value)}
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div style={{ display: 'flex', gap: '1rem' }}>
              <button className="btn-secondary" style={{ flex: 1 }} onClick={onCancel}>BATAL</button>
              <button className="btn-primary" style={{ flex: 2, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem' }} onClick={handleConfirm}>
                <ShoppingCart size={18} /> TAMBAH KE KERANJANG RETUR
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
