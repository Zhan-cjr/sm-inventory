import { useEffect, useRef, useCallback } from 'react';
import { generateRawTextReceipt } from '../utils/receiptThermalFormatter';

/**
 * Custom hook to manage thermal/graphic receipt printing across
 * Web browser (iframe print dialog) and Electron desktop app
 * (silent printing, raw ESC/POS, cash drawer pulse).
 */
export function useReceiptPrinter({
  transaction,
  branchSettings,
  autoPrintSettings,
  isHeaderBottom,
  onClose,
  onPrint
}) {
  const hasPrinted = useRef(false);
  const isReprint = transaction?.isReprint;

  const executeGraphicPrint = useCallback(() => {
    if (window.electronAPI) {
      let css = '';
      try {
        css = Array.from(document.styleSheets)
          .map(styleSheet => {
            try {
              return Array.from(styleSheet.cssRules).map(rule => rule.cssText).join('');
            } catch (e) {
              return '';
            }
          })
          .join('\n');
      } catch (e) {
        // Ignore CSS access errors
      }

      const receiptEl = document.getElementById('printable-receipt');
      if (!receiptEl) return;
      const receiptHtml = receiptEl.outerHTML;
      const fullHtml = `<html><head><style>${css}</style></head><body style="background: white;">${receiptHtml}</body></html>`;

      if (!isReprint && window.electronAPI.openCashDrawer) {
        window.electronAPI.openCashDrawer(autoPrintSettings?.printerName).catch(e => console.error(e));
      }
      window.electronAPI.silentPrint(fullHtml, autoPrintSettings?.printerName);
      if (onClose) onClose();
    } else {
      if (onPrint) onPrint();
    }
  }, [isReprint, autoPrintSettings?.printerName, onClose, onPrint]);

  const handlePrintRawText = useCallback(() => {
    const printCols = autoPrintSettings?.columns || 32;
    const feedLines = autoPrintSettings?.feedLines ?? 4;
    const rawText = generateRawTextReceipt(transaction, branchSettings, isHeaderBottom, printCols, feedLines);

    if (window.electronAPI && window.electronAPI.printRaw) {
      if (!isReprint && window.electronAPI.openCashDrawer) {
        window.electronAPI.openCashDrawer(autoPrintSettings?.printerName).catch(e => console.error(e));
      }
      window.electronAPI.printRaw(rawText, autoPrintSettings?.printerName).then(() => {
        if (onClose) onClose();
      });
    } else {
      const htmlString = `<html><head><title>Struk ESC/POS</title><style>@page { margin: 0; } body { margin: 0; padding: 0; font-family: monospace; font-size: 11px; font-weight: bold; line-height: 1.1; background-color: white; color: black; } pre { margin: 0; padding: 0; white-space: pre-wrap; word-break: break-all; }</style></head><body><pre>${rawText}</pre></body></html>`;

      if (window.electronAPI) {
        if (!isReprint && window.electronAPI.openCashDrawer) {
          window.electronAPI.openCashDrawer(autoPrintSettings?.printerName).catch(e => console.error(e));
        }
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
  }, [transaction, branchSettings, isHeaderBottom, autoPrintSettings, isReprint, onClose]);

  useEffect(() => {
    if (autoPrintSettings?.autoPrint && !hasPrinted.current) {
      hasPrinted.current = true;
      if (autoPrintSettings.printMode === 'TEXT') {
        handlePrintRawText();
      } else {
        // Graphic mode - give DOM brief moment to render
        setTimeout(() => {
          executeGraphicPrint();
        }, 200);
      }
    }
  }, [autoPrintSettings, handlePrintRawText, executeGraphicPrint]);

  return {
    executeGraphicPrint,
    handlePrintRawText
  };
}
