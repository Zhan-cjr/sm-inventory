import React from 'react';
import { Printer, X } from 'lucide-react';
import Barcode from 'react-barcode';

import { useReceiptPrinter } from '../hooks/useReceiptPrinter';

export const ReceiptPreview = ({ transaction, branchSettings, onPrint, onClose, autoPrintSettings }) => {
  const { items, totalAmount, discountAmount, finalAmount, paymentMethod, bankId, terminalId, receivedAmount, changeAmount, appliedPromos, branchName, branchAddress, orgName, userName, customerName, timestamp, isReprint } = transaction;

  const receipt_number = transaction.receipt_number || transaction.receiptNumber;
  const terminal_code = transaction.terminalCode || transaction.terminal_code || terminalId?.split('-')[0] || 'T01';

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
  };

  const isHeaderBottom = branchSettings?.receipt_type == 2 || autoPrintSettings?.receiptType == 2;

  const { executeGraphicPrint, handlePrintRawText } = useReceiptPrinter({
    transaction,
    branchSettings,
    autoPrintSettings,
    isHeaderBottom,
    onClose,
    onPrint
  });

  const formatPlaceholder = (lineText) => {
    if (!lineText) return '';
    let result = lineText
      .replace(/{org_name}/g, orgName || '')
      .replace(/{branch_name}/g, branchName || '')
      .replace(/{branch_address}/g, branchAddress || '');

    if (result.includes('{branch_phone}')) {
      const phone = branchSettings?.phone || '';
      if (!phone) {
        result = result.replace(/Telp:\s*{branch_phone}/i, '').trim();
        result = result.replace(/{branch_phone}/g, '').trim();
      } else {
        result = result.replace(/{branch_phone}/g, phone);
      }
    }
    return result;
  };

  // Header and footer configurations for HTML layout
  const headerLines = [];
  for (let i = 1; i <= 4; i++) {
    const textKey = `receipt_header_line${i}`;
    const boldKey = `receipt_header_line${i}_bold`;
    const textVal = branchSettings?.[textKey];
    if (textVal) {
      const formatted = formatPlaceholder(textVal);
      if (formatted.trim()) {
        headerLines.push({
          text: formatted,
          bold: !!branchSettings?.[boldKey]
        });
      }
    }
  }

  const footerLines = [];
  const footerCount = parseInt(branchSettings?.receipt_footer_layout ?? 4, 10);
  for (let i = 1; i <= footerCount; i++) {
    const textKey = `receipt_footer_line${i}`;
    const boldKey = `receipt_footer_line${i}_bold`;
    const textVal = branchSettings?.[textKey];
    if (textVal) {
      footerLines.push({
        text: textVal,
        bold: !!branchSettings?.[boldKey]
      });
    }
  }

  const isHidden = autoPrintSettings?.autoPrint;
  const overlayStyle = isHidden ? { position: 'fixed', top: 0, left: 0, width: '1px', height: '1px', overflow: 'hidden', opacity: 0, pointerEvents: 'none' } : {};

  return (
    <div className={isHidden ? "" : "change-modal-overlay"} style={overlayStyle}>
      <div className={`receipt-preview-card ${!isHidden ? 'fade-in' : ''}`}>
        <header className="receipt-preview-header">
          <h3>Pratinjau Struk</h3>
          <button onClick={onClose} className="btn-close-preview"><X size={20} /></button>
        </header>

        <div className="receipt-paper" id="printable-receipt">
          {!isHeaderBottom && (
            <div className="receipt-header">
              {!!branchSettings?.receipt_show_logo && (
                <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '0.6rem' }}>
                  <svg width="42" height="42" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="100" height="100" rx="30" fill="#4f46e5" />
                    <path d="M30 30H70L30 70H70" stroke="white" strokeWidth="12" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                </div>
              )}

              {headerLines.length === 0 ? (
                <>
                  <h2 className="org-name">{orgName}</h2>
                  <p className="branch-name">{branchName}</p>
                  {branchAddress && <p className="branch-address" style={{ fontSize: '10px', margin: '2px 0 0 0' }}>{branchAddress}</p>}
                </>
              ) : (
                headerLines.map((line, idx) => (
                  <p key={idx} style={{
                    margin: '2px 0',
                    fontSize: '11px',
                    fontWeight: line.bold ? 'bold' : 'normal',
                    textAlign: 'center',
                    color: 'black'
                  }}>
                    {line.text}
                  </p>
                ))
              )}
              <p className="divider">----------------------------------------</p>
            </div>
          )}

          <div className="receipt-info" style={{ marginTop: isHeaderBottom ? '0.5rem' : '0' }}>
            {isReprint && <p style={{ textAlign: 'center', fontWeight: 'bold', margin: '0 0 4px 0', fontSize: '12px' }}>*** COPY / REPRINT ***</p>}
            <p>Kasir: {userName}</p>
            {customerName && <p>Member: {customerName}</p>}
            <p>Tgl  : {new Date(timestamp).toLocaleString('id-ID')}</p>
            <p>Kassa: {terminal_code}</p>
            <p className="divider">----------------------------------------</p>
          </div>

          <div className="receipt-items">
            {items.map((item, idx) => (
              <div key={idx} className="receipt-item">
                <div className="item-name">{item.name}</div>
                {item.customerNo && <div className="item-detail" style={{ fontSize: '10px', marginTop: '2px', color: '#4b5563' }}>Tujuan: {item.customerNo}</div>}
                {item.customerName && <div className="item-detail" style={{ fontSize: '10px', marginTop: '2px', color: '#4b5563', fontWeight: 'bold' }}>Atas Nama: {item.customerName}</div>}
                {item.sn && <div className="item-detail" style={{ fontSize: '10px', marginTop: '2px', color: '#4b5563', fontWeight: 'bold' }}>SN: {item.sn}</div>}
                {item.ppobStatus && <div className="item-detail" style={{ fontSize: '10px', marginTop: '2px', color: '#4b5563' }}>Status: {item.ppobStatus}</div>}
                {item.ppobMessage && <div className="item-detail" style={{ fontSize: '10px', marginTop: '2px', color: '#4b5563' }}>Ket: {item.ppobMessage}</div>}
                {item.ppobStatus === 'Pending' && (
                  <div style={{ fontSize: '9px', marginTop: '4px', padding: '4px', background: '#fef3c7', borderRadius: '3px', color: '#92400e', border: '1px dashed #f59e0b', lineHeight: 1.3 }}>
                    * PPOB Sedang Diproses provider. Jika belum masuk dalam 1x24 jam, hubungi toko di Telp: {transaction.branchPhone || branchSettings?.phone || '-'} dengan membawa struk ini.
                  </div>
                )}
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
            <p className="divider">----------------------------------------</p>
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

            {transaction.payments && transaction.payments.length > 1 ? (
              <>
                <div className="summary-row" style={{ fontWeight: 'bold', marginTop: '4px' }}>
                  <span>Pembayaran:</span>
                </div>
                {transaction.payments.map((p, idx) => (
                  <div key={idx} className="summary-row" style={{ fontSize: '10px' }}>
                    <span style={{ paddingLeft: '8px' }}>- {p.label || p.method}</span>
                    <span>{formatCurrency(p.amount)}</span>
                  </div>
                ))}
                <div className="summary-row" style={{ marginTop: '2px' }}>
                  <span>Total Bayar</span>
                  <span>{formatCurrency(receivedAmount)}</span>
                </div>
              </>
            ) : (
              <div className="summary-row">
                <span>Bayar ({paymentMethod})</span>
                <span>{formatCurrency(receivedAmount)}</span>
              </div>
            )}

            <div className="summary-row">
              <span>Kembalian</span>
              <span>{formatCurrency(changeAmount)}</span>
            </div>

            {/* PPN / Tax Details */}
            {!!branchSettings?.receipt_show_tax && (() => {
              const taxRate = parseFloat(branchSettings.receipt_tax_rate ?? 11);
              const dppRate = 1 + (taxRate / 100);
              const dppVal = Math.round(finalAmount / dppRate);
              const taxVal = finalAmount - dppVal;

              return (
                <div style={{ fontSize: '10px', marginTop: '6px', borderTop: '1px dashed #ccc', paddingTop: '6px' }}>
                  <p style={{ textAlign: 'center', margin: '2px 0', fontStyle: 'italic' }}>{branchSettings.receipt_tax_message || 'Harga di atas sudah termasuk PPN'}</p>
                  <div style={{ display: 'flex', justifyContent: 'space-between', margin: '2px 0' }}>
                    <span>{branchSettings.receipt_dpp_message || 'SblmPPn'}</span>
                    <span>{formatCurrency(dppVal)}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', margin: '2px 0' }}>
                    <span>{branchSettings.receipt_tax_rate_message || 'Tarif PPn'} ({taxRate}%)</span>
                    <span>{formatCurrency(taxVal)}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', margin: '2px 0' }}>
                    <span>{branchSettings.receipt_total_tax_message || 'NilPPn'}</span>
                    <span>{formatCurrency(taxVal)}</span>
                  </div>
                </div>
              );
            })()}
            <p className="divider">----------------------------------------</p>
          </div>

          {appliedPromos?.length > 0 && (
            <div className="receipt-promos">
              <p>Promosi Diterapkan:</p>
              {appliedPromos.map((p, idx) => (
                <p key={idx}>* {p.promoName || p.name}</p>
              ))}
              <p className="divider">----------------------------------------</p>
            </div>
          )}

          <div className="receipt-footer">
            {footerLines.length === 0 ? (
              <>
                <p>Terima Kasih</p>
                <p>Selamat Belanja Kembali</p>
                <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
              </>
            ) : (
              footerLines.map((line, idx) => (
                <p key={idx} style={{
                  margin: '2px 0',
                  fontSize: '11px',
                  fontWeight: line.bold ? 'bold' : 'normal',
                  textAlign: 'center',
                  color: 'black'
                }}>
                  {line.text}
                </p>
              ))
            )}
            {receipt_number && <p style={{ marginTop: '0.6rem', fontWeight: 'bold', fontSize: '0.9rem', color: 'black' }}>NOTA: {receipt_number}</p>}
            {receipt_number && (
              <div style={{ marginTop: '0.5rem', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                <Barcode
                  value={receipt_number}
                  format="CODE128"
                  width={1.5}
                  height={40}
                  fontSize={12}
                  margin={0}
                  displayValue={false}
                />
              </div>
            )}
          </div>

          {isHeaderBottom && (
            <div className="receipt-header" style={{ marginTop: '1rem', paddingTop: '1rem' }}>
              {!!branchSettings?.receipt_show_logo && (
                <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '0.6rem' }}>
                  <svg width="42" height="42" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="100" height="100" rx="30" fill="#4f46e5" />
                    <path d="M30 30H70L30 70H70" stroke="white" strokeWidth="12" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                </div>
              )}

              {headerLines.length === 0 ? (
                <>
                  <h2 className="org-name">{orgName}</h2>
                  <p className="branch-name">{branchName}</p>
                  {branchAddress && <p className="branch-address" style={{ fontSize: '10px', margin: '2px 0 0 0' }}>{branchAddress}</p>}
                </>
              ) : (
                headerLines.map((line, idx) => (
                  <p key={idx} style={{
                    margin: '2px 0',
                    fontSize: '11px',
                    fontWeight: line.bold ? 'bold' : 'normal',
                    textAlign: 'center',
                    color: 'black'
                  }}>
                    {line.text}
                  </p>
                ))
              )}
              <p className="divider">----------------------------------------</p>
            </div>
          )}
        </div>

        <footer className="receipt-preview-footer" style={{ display: 'flex', gap: '10px' }}>
          <button className="btn-print-now" onClick={executeGraphicPrint} style={{ flex: 1 }}>
            <Printer size={18} /> CETAK GRAFIS
          </button>
          <button className="btn-print-now" onClick={handlePrintRawText} style={{ flex: 1, backgroundColor: '#0284c7' }}>
            <Printer size={18} /> CETAK TEKS (ESC/POS)
          </button>
        </footer>
      </div>
    </div>
  );
};
