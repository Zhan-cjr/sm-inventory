import React from 'react';
import { Printer, X, LogOut } from 'lucide-react';

export const EODReportPreview = ({ eodData, branchSettings, onPrint, onClose }) => {
  if (!eodData) return null;

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
  };

  const handlePrintRawText = () => {
    const columns = 32;
    const divider = '-'.repeat(columns);
    const center = (str, len) => {
      str = String(str);
      if (str.length >= len) return str.substring(0, len);
      const left = Math.floor((len - str.length) / 2);
      const right = len - str.length - left;
      return ' '.repeat(left) + str + ' '.repeat(right);
    };
    const formatRow = (label, val) => {
      const numVal = parseFloat(val);
      const valStr = (!isNaN(numVal) && val !== '' && val !== null) ? numVal.toLocaleString('id-ID') : String(val);
      const space = columns - label.length - valStr.length;
      return label + ' '.repeat(Math.max(1, space)) + valStr;
    };

    let lines = [];
    const orgName = eodData.branch?.organization?.name || 'SMI POS';
    const branchName = eodData.branch?.name || 'Cabang Utama';
    const branchAddress = eodData.branch?.address || '';
    
    lines.push(center(orgName, columns));
    lines.push(center(branchName, columns));
    if (branchAddress) {
      lines.push(center(branchAddress, columns));
    }
    lines.push(divider);

    lines.push(center('LAPORAN END OF DAY', columns));
    lines.push(divider);
    lines.push(`Kasir : ${eodData.user?.name || 'Unknown'}`);
    lines.push(`Kassa : ${eodData.terminal?.name || 'Unknown'}`);
    lines.push(`Shift : ${eodData.shift_name}`);
    lines.push(`Mulai : ${new Date(eodData.start_time).toLocaleString('id-ID')}`);
    lines.push(`Selesai: ${new Date(eodData.end_time).toLocaleString('id-ID')}`);
    lines.push(divider);
    
    lines.push(formatRow('Modal Awal', eodData.starting_cash));
    lines.push(formatRow('Penjualan Tunai', eodData.total_cash_sales));
    lines.push(formatRow('Penjualan Non-Tunai', eodData.total_card_sales));
    if ((eodData.total_cash_returns || 0) > 0) {
      lines.push(formatRow('Retur Tunai', `-${eodData.total_cash_returns}`));
    }
    if ((eodData.total_card_returns || 0) > 0) {
      lines.push(formatRow('Retur Non-Tunai', `-${eodData.total_card_returns}`));
    }
    
    if (eodData.card_sales_by_bank && eodData.card_sales_by_bank.length > 0) {
      eodData.card_sales_by_bank.forEach(b => {
        lines.push(formatRow(`  - ${b.name}`, b.total_amount));
      });
    } else {
      lines.push(formatRow(`  - Belum ada rincian bank`, 0));
    }

    lines.push(formatRow('Kas Masuk', eodData.total_cash_in || 0));
    lines.push(formatRow('Kas Keluar', eodData.total_cash_out || 0));
    lines.push(divider);

    lines.push(center('POTONGAN & DISKON', columns));
    const dDetails = eodData.discount_details || { manual_discount: 0, promo_discount: 0, point_deduction: 0 };
    lines.push(formatRow('Diskon Manual', dDetails.manual_discount));
    lines.push(formatRow('Diskon Promo', dDetails.promo_discount));
    lines.push(formatRow('Poin Member', dDetails.point_deduction));
    lines.push(divider);
    
    lines.push(formatRow('EXPECTED CASH', eodData.expected_cash));
    lines.push(formatRow('ACTUAL CASH', eodData.actual_cash));
    lines.push(formatRow('SELISIH', eodData.difference));
    lines.push(divider);
    
    if (eodData.cash_movements && eodData.cash_movements.length > 0) {
      lines.push(center('DETAIL KAS', columns));
      eodData.cash_movements.forEach(m => {
        lines.push(`${m.type === 'CASH_IN' ? '[IN] ' : '[OUT]'}${m.description.substring(0,15)}`);
        lines.push(formatRow('', m.amount));
      });
      lines.push(divider);
    }

    lines.push(center('DETAIL RETUR', columns));
    if (eodData.returns_detail && eodData.returns_detail.length > 0) {
      eodData.returns_detail.forEach(r => {
        lines.push(`${r.quantity}x ${r.product_name.substring(0,15)}`);
        lines.push(formatRow('', r.total));
      });
    } else {
      lines.push(center('Tidak ada retur', columns));
    }
    lines.push(divider);
    
    lines.push(center('Tanda Tangan', columns));
    lines.push('\n\n\n');
    lines.push(center('(.......................)', columns));
    lines.push(center('Kasir / SPV', columns));
    
    // Feed paper
    lines.push('\n\n\n\n\n');

    const rawText = lines.join('\n');
    
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.width = '0px';
    iframe.style.height = '0px';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);
    
    const doc = iframe.contentWindow.document || iframe.contentDocument;
    doc.open();
    doc.write('<html><head><title>EOD ESC/POS</title><style>@page { margin: 0; size: auto; } body { margin: 0; padding: 0; font-family: "Courier New", Courier, monospace; font-size: 10px; line-height: 1.1; background-color: white; color: black; } pre { margin: 0; padding: 0; white-space: pre-wrap; word-break: break-all; }</style></head><body><pre>' + rawText + '</pre></body></html>');
    doc.close();
    
    setTimeout(() => {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
      setTimeout(() => {
        document.body.removeChild(iframe);
      }, 1000);
    }, 500);
  };

  return (
    <div className="change-modal-overlay">
      <div className="receipt-preview-card fade-in" style={{ width: '400px', maxWidth: '90%' }}>
        <header className="receipt-preview-header">
          <h3>Laporan End of Day</h3>
        </header>
        
        <div className="receipt-paper" id="printable-eod" style={{ minHeight: '400px' }}>
          <div className="receipt-header">
            <h2 style={{ fontSize: '1.2rem', textAlign: 'center', margin: '0 0 5px 0' }}>{eodData.branch?.organization?.name || 'SMI POS'}</h2>
            <h3 style={{ fontSize: '1rem', textAlign: 'center', margin: '0 0 5px 0' }}>{eodData.branch?.name || 'Cabang Utama'}</h3>
            {eodData.branch?.address && (
              <p style={{ textAlign: 'center', margin: '0 0 10px 0', fontSize: '0.85em' }}>{eodData.branch.address}</p>
            )}
            <p className="divider">--------------------------------</p>
            <h2 style={{ fontSize: '1.1rem', textAlign: 'center', margin: '10px 0' }}>LAPORAN END OF DAY</h2>
            <p className="divider">--------------------------------</p>
          </div>
          
          <div className="receipt-info" style={{ marginBottom: '10px' }}>
            <p>Kasir : {eodData.user?.name || 'Unknown'}</p>
            <p>Kassa : {eodData.terminal?.name || 'Unknown'}</p>
            <p>Shift : {eodData.shift_name}</p>
            <p>Mulai : {new Date(eodData.start_time).toLocaleString('id-ID')}</p>
            <p>Selesai: {new Date(eodData.end_time).toLocaleString('id-ID')}</p>
            <p className="divider">--------------------------------</p>
          </div>

          <div className="receipt-summary">
            <div className="summary-row">
              <span>Modal Awal</span>
              <span>{formatCurrency(eodData.starting_cash)}</span>
            </div>
            <div className="summary-row">
              <span>Penjualan Tunai</span>
              <span>{formatCurrency(eodData.total_cash_sales)}</span>
            </div>
            <div className="summary-row" style={{ marginBottom: 0 }}>
              <span>Penjualan Non-Tunai</span>
              <span>{formatCurrency(eodData.total_card_sales)}</span>
            </div>
            <div style={{ paddingLeft: '15px', fontSize: '0.85em', color: '#555', marginBottom: '8px' }}>
              {eodData.card_sales_by_bank && eodData.card_sales_by_bank.length > 0 ? (
                eodData.card_sales_by_bank.map((b, i) => (
                  <div key={i} style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span>- {b.name}</span>
                    <span>{formatCurrency(b.total_amount)}</span>
                  </div>
                ))
              ) : (
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span>- Belum ada rincian bank</span>
                  <span>{formatCurrency(0)}</span>
                </div>
              )}
            </div>

            {(eodData.total_cash_returns || 0) > 0 && (
              <div className="summary-row" style={{ color: '#ef4444' }}>
                <span>Retur Tunai</span>
                <span>-{formatCurrency(eodData.total_cash_returns)}</span>
              </div>
            )}
            {(eodData.total_card_returns || 0) > 0 && (
              <div className="summary-row" style={{ color: '#ef4444' }}>
                <span>Retur Non-Tunai</span>
                <span>-{formatCurrency(eodData.total_card_returns)}</span>
              </div>
            )}

            <div className="summary-row">
              <span>Kas Masuk</span>
              <span>{formatCurrency(eodData.total_cash_in || 0)}</span>
            </div>
            <div className="summary-row">
              <span>Kas Keluar</span>
              <span>{formatCurrency(eodData.total_cash_out || 0)}</span>
            </div>
            <p className="divider">--------------------------------</p>

            <p style={{ textAlign: 'center', fontWeight: 'bold' }}>POTONGAN & DISKON</p>
            <div className="summary-row">
              <span>Diskon Manual</span>
              <span>{formatCurrency(eodData.discount_details?.manual_discount || 0)}</span>
            </div>
            <div className="summary-row">
              <span>Diskon Promo</span>
              <span>{formatCurrency(eodData.discount_details?.promo_discount || 0)}</span>
            </div>
            <div className="summary-row">
              <span>Poin Member</span>
              <span>{formatCurrency(eodData.discount_details?.point_deduction || 0)}</span>
            </div>
            <p className="divider">--------------------------------</p>
            
            <div className="summary-row" style={{ fontWeight: 'bold' }}>
              <span>EXPECTED CASH</span>
              <span>{formatCurrency(eodData.expected_cash)}</span>
            </div>
            <div className="summary-row" style={{ fontWeight: 'bold' }}>
              <span>ACTUAL CASH</span>
              <span>{formatCurrency(eodData.actual_cash)}</span>
            </div>
            <div className="summary-row" style={{ fontWeight: 'bold', color: eodData.difference < 0 ? '#ef4444' : 'black' }}>
              <span>SELISIH</span>
              <span>{formatCurrency(eodData.difference)}</span>
            </div>
            <p className="divider">--------------------------------</p>
          </div>

          {eodData.cash_movements && eodData.cash_movements.length > 0 && (
            <div className="receipt-items" style={{ marginTop: '10px' }}>
              <p style={{ textAlign: 'center', fontWeight: 'bold' }}>DETAIL KAS</p>
              {eodData.cash_movements.map((m, idx) => (
                <div key={idx} className="receipt-item" style={{ marginBottom: '5px' }}>
                  <div className="item-name">{m.type === 'CASH_IN' ? '[IN]' : '[OUT]'} {m.description}</div>
                  <div className="item-detail" style={{ justifyContent: 'flex-end' }}>
                    <span>{formatCurrency(m.amount)}</span>
                  </div>
                </div>
              ))}
              <p className="divider">--------------------------------</p>
            </div>
          )}

          <div className="receipt-items" style={{ marginTop: '10px' }}>
            <p style={{ textAlign: 'center', fontWeight: 'bold' }}>DETAIL RETUR</p>
            {eodData.returns_detail && eodData.returns_detail.length > 0 ? (
              eodData.returns_detail.map((r, idx) => (
                <div key={idx} className="receipt-item" style={{ marginBottom: '5px' }}>
                  <div className="item-name">{r.quantity}x {r.product_name}</div>
                  <div className="item-detail" style={{ justifyContent: 'flex-end' }}>
                    <span>{formatCurrency(r.total)}</span>
                  </div>
                </div>
              ))
            ) : (
              <p style={{ textAlign: 'center', margin: 0 }}>Tidak ada retur</p>
            )}
            <p className="divider">--------------------------------</p>
          </div>
          
          <div className="receipt-footer" style={{ marginTop: '30px' }}>
             <p style={{ textAlign: 'center' }}>Tanda Tangan</p>
             <br /><br /><br />
             <p style={{ textAlign: 'center' }}>(.......................)</p>
             <p style={{ textAlign: 'center' }}>Kasir / SPV</p>
          </div>
        </div>

        <footer className="receipt-preview-footer" style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
          <button className="btn-print-now" onClick={onPrint} style={{ flex: 1, minWidth: '45%' }}>
            <Printer size={18} /> GRAFIS
          </button>
          <button className="btn-print-now" onClick={handlePrintRawText} style={{ flex: 1, minWidth: '45%', backgroundColor: '#0284c7' }}>
            <Printer size={18} /> ESC/POS
          </button>
          <button className="btn-danger" onClick={onClose} style={{ flex: '1 1 100%', padding: '0.75rem', borderRadius: '8px', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '5px', fontWeight: 'bold' }}>
            <LogOut size={18} /> SELESAI & LOGOUT
          </button>
        </footer>
      </div>
    </div>
  );
};
