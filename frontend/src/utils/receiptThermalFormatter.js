/**
 * Utilitas pemformatan teks struk thermal (ESC/POS / Plain Text)
 * Mendukung kustomisasi kolom (32, 48, 58, 80 kolom), padding, centering, PPN, dan feed lines.
 */

export const generateRawTextReceipt = (transaction, branchSettings, isHeaderBottom, columns = 32, feedLines = 4) => {
  const {
    items = [],
    totalAmount,
    discountAmount,
    finalAmount,
    paymentMethod,
    terminalId,
    receivedAmount,
    changeAmount,
    branchName,
    branchAddress,
    orgName,
    userName,
    customerName,
    timestamp,
    isReprint
  } = transaction;

  const receipt_number = transaction.receipt_number || transaction.receiptNumber;
  const terminal_code = transaction.terminalCode || transaction.terminal_code || terminalId?.split('-')[0] || 'T01';

  const pad = (str, len, char = ' ') => {
    str = String(str);
    if (str.length >= len) return str.substring(0, len);
    return str + char.repeat(len - str.length);
  };

  const padLeft = (str, len, char = ' ') => {
    str = String(str);
    if (str.length >= len) return str.substring(0, len);
    return char.repeat(len - str.length) + str;
  };

  const center = (str, len) => {
    str = String(str);
    if (str.length >= len) return str.substring(0, len);
    const left = Math.floor((len - str.length) / 2);
    const right = len - str.length - left;
    return ' '.repeat(left) + str + ' '.repeat(right);
  };

  const wrapAndCenter = (str, len) => {
    const linesToPrint = [];
    let remaining = String(str).trim();
    while (remaining.length > 0) {
      if (remaining.length <= len) {
        linesToPrint.push(center(remaining, len));
        break;
      }
      let breakPoint = remaining.lastIndexOf(' ', len);
      if (breakPoint === -1 || breakPoint === 0) breakPoint = len;
      linesToPrint.push(center(remaining.substring(0, breakPoint).trim(), len));
      remaining = remaining.substring(breakPoint).trim();
    }
    return linesToPrint;
  };

  const wrapText = (str, len) => {
    const linesToPrint = [];
    let remaining = String(str).trim();
    while (remaining.length > 0) {
      if (remaining.length <= len) {
        linesToPrint.push(pad(remaining, len));
        break;
      }
      let breakPoint = remaining.lastIndexOf(' ', len);
      if (breakPoint === -1 || breakPoint === 0) breakPoint = len;
      linesToPrint.push(pad(remaining.substring(0, breakPoint).trim(), len));
      remaining = remaining.substring(breakPoint).trim();
    }
    return linesToPrint;
  };

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

  const divider = '-'.repeat(columns);
  let lines = [];

  // Parse Headers
  const headerLines = [];
  for (let i = 1; i <= 4; i++) {
    const textKey = `receipt_header_line${i}`;
    const textVal = branchSettings?.[textKey];
    if (textVal) {
      const formatted = formatPlaceholder(textVal);
      if (formatted.trim()) {
        headerLines.push(formatted);
      }
    }
  }

  const headerOutputLines = [];

  if (headerLines.length === 0) {
    headerOutputLines.push(...wrapAndCenter(orgName || 'TOSERBA SELAMAT', columns));
    headerOutputLines.push(...wrapAndCenter(branchName || 'Cabang Utama', columns));
    headerOutputLines.push(...wrapAndCenter(branchAddress || 'THE MOSLEM FAMILY', columns));
  } else {
    headerLines.forEach(text => {
      headerOutputLines.push(...wrapAndCenter(text, columns));
    });
  }

  headerOutputLines.push(divider);

  if (!isHeaderBottom) {
    lines.push(...headerOutputLines);
  }

  if (isReprint) {
    lines.push(center('*** COPY / REPRINT ***', columns));
  }

  lines.push(pad(`Kasir: ${userName || 'Kasir'}`, columns));
  if (customerName) {
    lines.push(pad(`Member: ${customerName}`, columns));
  }
  const dateStr = new Date(timestamp).toLocaleString('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
  lines.push(pad(`Tgl  : ${dateStr}`, columns));
  lines.push(pad(`Kassa: ${terminal_code}`, columns));
  if (receipt_number) {
    lines.push(pad(`Nota : ${receipt_number}`, columns));
  }
  lines.push(divider);

  items.forEach(item => {
    lines.push(pad(item.name || 'Item', columns));
    if (item.customerNo) lines.push(pad(`  Tujuan: ${item.customerNo}`, columns));
    if (item.customerName) lines.push(pad(`  Atas Nama: ${item.customerName}`, columns));
    if (item.sn) lines.push(pad(`  SN: ${item.sn}`, columns));
    if (item.ppobStatus) lines.push(pad(`  Status: ${item.ppobStatus}`, columns));
    if (item.ppobMessage) lines.push(pad(`  Ket: ${item.ppobMessage}`, columns));
    if (item.ppobStatus === 'Pending') {
      const phone = transaction.branchPhone || branchSettings?.phone || '';
      const pendingMsg = `* PPOB Sedang Diproses. Jika belum masuk dlm 1x24 jam, hubungi toko di Telp: ${phone || '-'} dgn struk ini.`;
      lines.push(...wrapText(pendingMsg, columns));
    }

    const unitPriceNum = Number(item.unitPrice);
    const qtyNum = Number(item.quantity);
    const qtyPrice = `${item.quantity} x ${unitPriceNum.toLocaleString('id-ID')}`;
    const sub = (qtyNum * unitPriceNum).toLocaleString('id-ID');
    const space = columns - qtyPrice.length - sub.length;
    if (space > 0) {
      lines.push(qtyPrice + ' '.repeat(space) + sub);
    } else {
      lines.push(qtyPrice + '\n' + padLeft(sub, columns));
    }

    if (item.manualDiscount > 0) {
      const discLabel = '  (Diskon Item)';
      const discVal = `-${(item.quantity * item.manualDiscount).toLocaleString('id-ID')}`;
      const discSpace = columns - discLabel.length - discVal.length;
      if (discSpace > 0) {
        lines.push(discLabel + ' '.repeat(discSpace) + discVal);
      } else {
        lines.push(discLabel + '\n' + padLeft(discVal, columns));
      }
    }
  });
  lines.push(divider);

  const formatRow = (label, val) => {
    let valStr = String(val);
    if (!isNaN(val) && val !== null && val !== '') {
      valStr = Number(val).toLocaleString('id-ID');
    }
    const space = columns - label.length - valStr.length;
    return label + ' '.repeat(Math.max(1, space)) + valStr;
  };

  lines.push(formatRow('Total Gross', totalAmount));
  if (discountAmount > 0) {
    lines.push(formatRow('Total Diskon', -discountAmount));
  }
  lines.push(formatRow('GRAND TOTAL', finalAmount));
  if (transaction.payments && transaction.payments.length > 1) {
    lines.push(formatRow('Pembayaran:', ''));
    transaction.payments.forEach(p => {
      lines.push(formatRow(`  ${p.label || p.method}`, p.amount));
    });
    lines.push(formatRow('Total Bayar', receivedAmount));
  } else {
    lines.push(formatRow(`Bayar (${paymentMethod})`, receivedAmount));
  }
  lines.push(formatRow('Kembalian', changeAmount));

  // PPN / Tax information
  if (branchSettings?.receipt_show_tax) {
    const taxRate = parseFloat(branchSettings.receipt_tax_rate ?? 11);
    const dppRate = 1 + (taxRate / 100);
    const taxMessage = branchSettings.receipt_tax_message || 'Harga di atas sudah termasuk PPN';
    const taxRateMsg = branchSettings.receipt_tax_rate_message || 'Tarif PPn';
    const dppMsg = branchSettings.receipt_dpp_message || 'SblmPPn';
    const totalTaxMsg = branchSettings.receipt_total_tax_message || 'NilPPn';

    const dppVal = Math.round(finalAmount / dppRate);
    const taxVal = finalAmount - dppVal;

    lines.push(divider);
    lines.push(center(taxMessage, columns));
    lines.push(formatRow(dppMsg, dppVal));
    lines.push(formatRow(`${taxRateMsg} (${taxRate}%)`, taxVal));
    lines.push(formatRow(totalTaxMsg, taxVal));
  }

  lines.push(divider);

  // Parse Footers
  const footerLines = [];
  const footerCount = parseInt(branchSettings?.receipt_footer_layout ?? 4, 10);
  for (let i = 1; i <= footerCount; i++) {
    const textKey = `receipt_footer_line${i}`;
    const textVal = branchSettings?.[textKey];
    if (textVal) {
      footerLines.push(textVal);
    }
  }

  if (footerLines.length === 0) {
    lines.push(center('TERIMA KASIH', columns));
    lines.push(center('SELAMAT BELANJA KEMBALI', columns));
    lines.push(...wrapAndCenter('Barang yang sudah dibeli tidak dapat ditukar/dikembalikan', columns));
  } else {
    footerLines.forEach(text => {
      lines.push(...wrapAndCenter(text, columns));
    });
  }

  // Feed paper (Feed selalu setelah footer)
  for (let i = 0; i < feedLines; i++) {
    // Gunakan satu spasi (' ') alih-alih baris kosong seutuhnya.
    // Ini mencegah Windows Generic/Text Only driver menghapus/mengabaikan baris kosong berturut-turut.
    lines.push(' ');
  }

  // Header di bawah (Mode Hemat: dicetak setelah feed agar menjadi header untuk struk berikutnya)
  if (isHeaderBottom) {
    lines.push(...headerOutputLines);
  } else if (feedLines > 0) {
    // Trik untuk Generic Text Only jika header tidak di bawah:
    // Windows Print Spooler sering membuang baris di akhir dokumen meski ada spasinya.
    // Kita tambahkan satu karakter titik (.) di paling akhir dokumen.
    lines.push('.');
  }

  return lines.join('\r\n');
};
