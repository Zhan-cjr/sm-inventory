import React from 'react';
import { Printer, LogOut } from 'lucide-react';

export const EODReportPreview = ({ eodData, branchSettings, onPrint, onClose }) => {
  if (!eodData) return null;

  const getReportText = () => {
    const columns = 35;
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
    const nonCashTotal = (eodData.total_card_sales || 0) + (eodData.total_voucher_sales || 0);
    lines.push(formatRow('Penjualan Non-Tunai', nonCashTotal));
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
    
    if ((eodData.total_voucher_sales || 0) > 0) {
      lines.push(formatRow(`  - Voucher`, eodData.total_voucher_sales));
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
    lines.push('');
    lines.push('');
    lines.push('');
    lines.push(center('(.......................)', columns));
    lines.push(center('Kasir / SPV', columns));
    
    return lines.join('\n');
  };

  const executeGraphicPrint = () => {
    if (window.electronAPI) {
      let css = '';
      try {
        css = Array.from(document.styleSheets)
          .map(styleSheet => {
            try { return Array.from(styleSheet.cssRules).map(rule => rule.cssText).join(''); } 
            catch (e) { return ''; }
          }).join('\n');
      } catch (e) {}
      
      const receiptHtml = document.getElementById('printable-eod').outerHTML;
      const fullHtml = `<html><head><style>${css}</style></head><body style="background: white;">${receiptHtml}</body></html>`;
      const autoPrintSettings = JSON.parse(localStorage.getItem('pos_printer_settings') || '{}');
      window.electronAPI.silentPrint(fullHtml, autoPrintSettings?.printerName);
      if (onClose) onClose();
    } else {
      if (onPrint) onPrint();
    }
  };

  const handlePrintRawText = () => {
    const rawText = getReportText() + '\n\n\n\n\n';
    const autoPrintSettings = JSON.parse(localStorage.getItem('pos_printer_settings') || '{}');
    
    if (window.electronAPI && window.electronAPI.printRaw) {
      window.electronAPI.printRaw(rawText, autoPrintSettings?.printerName).then(() => {
        if (onClose) onClose();
      });
    } else {
      const htmlString = '<html><head><title>EOD Report</title><style>@page { margin: 0; } body { margin: 0; padding: 0 0 0 12mm; font-family: monospace; font-size: 11px; font-weight: bold; line-height: 1.1; background-color: white; color: black; } pre { margin: 0; padding: 0; white-space: pre-wrap; word-break: break-all; }</style></head><body><pre>' + rawText + '</pre></body></html>';
      
      if (window.electronAPI) {
        window.electronAPI.silentPrint(htmlString, autoPrintSettings?.printerName);
        if (onClose) onClose();
      } else {
        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.width = '0px';
        iframe.style.height = '0px';
        iframe.style.border = 'none';
        document.body.appendChild(iframe);
        
        const doc = iframe.contentWindow.document || iframe.contentDocument;
        doc.open();
        doc.write(htmlString);
        doc.close();
        
        setTimeout(() => {
          iframe.contentWindow.focus();
          iframe.contentWindow.print();
          
          setTimeout(() => {
            document.body.removeChild(iframe);
            if (onClose) onClose();
          }, 1000);
        }, 500);
      }
    }
  };

  return (
    <div className="change-modal-overlay">
      <div className="receipt-preview-card fade-in" style={{ width: '380px', maxWidth: '90%' }}>
        <header className="receipt-preview-header">
          <h3>Laporan End of Day</h3>
        </header>
        
        <div className="receipt-paper" id="printable-eod" style={{ minHeight: '400px', padding: '15px' }}>
          <pre style={{ margin: 0, padding: 0, whiteSpace: 'pre-wrap', fontFamily: 'monospace', fontSize: '13px', lineHeight: '1.2' }}>
            {getReportText()}
          </pre>
        </div>

        <footer className="receipt-preview-footer" style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
          <button className="btn-print-now" onClick={executeGraphicPrint} style={{ flex: 1, minWidth: '45%' }}>
            <Printer size={18} /> CETAK GRAFIS
          </button>
          <button className="btn-print-now" onClick={handlePrintRawText} style={{ flex: 1, minWidth: '45%', backgroundColor: '#0284c7' }}>
            <Printer size={18} /> CETAK TEKS (ESC/POS)
          </button>
          <button className="btn-danger" onClick={onClose} style={{ flex: '1 1 100%', padding: '0.75rem', borderRadius: '8px', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '5px', fontWeight: 'bold' }}>
            <LogOut size={18} /> SELESAI & LOGOUT
          </button>
        </footer>
      </div>
    </div>
  );
};
