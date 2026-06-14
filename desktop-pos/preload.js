const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electronAPI', {
  getConfig: () => ipcRenderer.invoke('get-config'),
  saveConfig: (config) => ipcRenderer.invoke('save-config', config),
  getPrinters: () => ipcRenderer.invoke('get-printers'),
  silentPrint: (htmlContent, printerName) => ipcRenderer.invoke('silent-print', htmlContent, printerName),
  printRaw: (rawText, printerName) => ipcRenderer.invoke('print-raw', rawText, printerName),
  openCashDrawer: (printerName) => ipcRenderer.invoke('open-cash-drawer', printerName)
});
