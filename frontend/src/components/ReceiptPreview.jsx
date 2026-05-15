import React from 'react';
import { Printer, X } from 'lucide-react';

export const ReceiptPreview = ({ transaction, onPrint, onClose }) => {
  const { items, totalAmount, discountAmount, finalAmount, paymentMethod, bankId, terminalId, receivedAmount, changeAmount, appliedPromos, branchName, orgName, userName, customerName, timestamp } = transaction;

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
  };

  return (
    <div className="change-modal-overlay">
      <div className="receipt-preview-card fade-in">
        <header className="receipt-preview-header">
          <h3>Pratinjau Struk</h3>
          <button onClick={onClose} className="btn-close-preview"><X size={20} /></button>
        </header>
        
        <div className="receipt-paper" id="printable-receipt">
          <div className="receipt-header">
            <h2 className="org-name">{orgName}</h2>
            <p className="branch-name">{branchName}</p>
            <p className="divider">--------------------------------</p>
          </div>
          
          <div className="receipt-info">
            <p>Kasir: {userName}</p>
            {customerName && <p>Member: {customerName}</p>}
            <p>Tgl  : {new Date(timestamp).toLocaleString('id-ID')}</p>
            <p>Kassa: {terminalId?.split('-')[0] || 'T01'}</p>
            <p className="divider">--------------------------------</p>
          </div>

          <div className="receipt-items">
            {items.map((item, idx) => (
              <div key={idx} className="receipt-item">
                <div className="item-name">{item.name}</div>
                <div className="item-detail">
                  <span>{item.quantity} x {formatCurrency(item.unitPrice)}</span>
                  <span>{formatCurrency(item.quantity * item.unitPrice)}</span>
                </div>
                {item.manualDiscount > 0 && (
                    <div className="item-discount">
                        <span>  (Diskon Item)</span>
                        <span>-{formatCurrency(item.quantity * item.manualDiscount)}</span>
                    </div>
                )}
              </div>
            ))}
            <p className="divider">--------------------------------</p>
          </div>

          <div className="receipt-summary">
            <div className="summary-row">
              <span>Total Gross</span>
              <span>{formatCurrency(totalAmount)}</span>
            </div>
            {discountAmount > 0 && (
              <div className="summary-row">
                <span>Total Diskon</span>
                <span>-{formatCurrency(discountAmount)}</span>
              </div>
            )}
            <div className="summary-row total">
              <span>GRAND TOTAL</span>
              <span>{formatCurrency(finalAmount)}</span>
            </div>
            <div className="summary-row">
              <span>Bayar ({paymentMethod})</span>
              <span>{formatCurrency(receivedAmount)}</span>
            </div>
            <div className="summary-row">
              <span>Kembalian</span>
              <span>{formatCurrency(changeAmount)}</span>
            </div>
            <p className="divider">--------------------------------</p>
          </div>

          {appliedPromos?.length > 0 && (
            <div className="receipt-promos">
              <p>Promosi Diterapkan:</p>
              {appliedPromos.map((p, idx) => (
                <p key={idx}>* {p.name}</p>
              ))}
              <p className="divider">--------------------------------</p>
            </div>
          )}

          <div className="receipt-footer">
            <p>Terima Kasih</p>
            <p>Selamat Belanja Kembali</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
          </div>
        </div>

        <footer className="receipt-preview-footer">
          <button className="btn-print-now" onClick={onPrint}>
            <Printer size={18} /> CETAK STRUK
          </button>
        </footer>
      </div>
    </div>
  );
};
