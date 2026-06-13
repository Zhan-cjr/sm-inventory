const { app, BrowserWindow, ipcMain, Menu } = require('electron');
const path = require('path');
const fs = require('fs');
const { exec } = require('child_process');
const os = require('os');

const configPath = path.join(app.getPath('userData'), 'pos-config.json');

let mainWindow;

function loadConfig() {
  try {
    if (fs.existsSync(configPath)) {
      const data = fs.readFileSync(configPath, 'utf8');
      return JSON.parse(data);
    }
  } catch (error) {
    console.error('Error loading config:', error);
  }
  return null;
}

function saveConfig(config) {
  try {
    fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
    return true;
  } catch (error) {
    console.error('Error saving config:', error);
    return false;
  }
}

function createMainWindow() {
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 800,
    show: false,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js')
    },
    autoHideMenuBar: true
  });

  mainWindow.setFullScreen(true);
  mainWindow.show();

  const config = loadConfig();

  if (config && config.posUrl) {
    mainWindow.loadURL(config.posUrl).catch(err => {
      console.error('Failed to load POS URL', err);
      // Instead of falling back to settings, let's load a custom error string
      mainWindow.loadURL(`data:text/html;charset=utf-8,
        <!DOCTYPE html>
        <html>
        <head>
          <style>
            body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; background: #f3f4f6; color: #1f2937; }
          </style>
        </head>
        <body>
          <h2>Koneksi Terputus atau Tidak Ada Jaringan</h2>
          <p>Gagal memuat URL POS: ${config.posUrl}</p>
          <p>Pastikan Anda memiliki koneksi internet, atau Service Worker PWA sudah terinstall untuk mode offline.</p>
          <p>Silakan gunakan menu <b>View > Reload</b> (atau tekan Ctrl+R) untuk mencoba lagi.</p>
        </body>
        </html>
      `);
    });
  } else {
    mainWindow.loadFile('settings.html');
  }

  // Setup Menu
  const template = [
    {
      label: 'View',
      submenu: [
        { role: 'reload' },
        { role: 'forceReload' },
        { role: 'toggleDevTools' },
        { type: 'separator' },
        { role: 'resetZoom' },
        { role: 'zoomIn' },
        { role: 'zoomOut' },
        { type: 'separator' },
        { role: 'togglefullscreen' }
      ]
    },
    {
      label: 'Settings',
      submenu: [
        {
          label: 'Configure URLs',
          click: () => {
            if (mainWindow) {
              mainWindow.loadFile('settings.html');
            }
          }
        }
      ]
    }
  ];
  
  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

app.whenReady().then(() => {
  createMainWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createMainWindow();
    }
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

// IPC Handlers
ipcMain.handle('get-config', () => {
  return loadConfig() || { posUrl: '', serverUrl: '', printerName: '', printMode: 'TEXT', autoPrint: false };
});

ipcMain.handle('save-config', (event, config) => {
  const success = saveConfig(config);
  if (success && mainWindow) {
    if (config.posUrl) {
      mainWindow.loadURL(config.posUrl).catch(err => {
        console.error('Failed to load POS URL after save', err);
      });
    }
  }
  return success;
});

ipcMain.handle('get-printers', async (event) => {
  try {
    const printers = await event.sender.getPrintersAsync();
    return printers;
  } catch (error) {
    console.error('Error fetching printers:', error);
    return [];
  }
});

ipcMain.handle('silent-print', (event, htmlContent, printerName) => {
  return new Promise((resolve, reject) => {
    let printWindow = new BrowserWindow({
      show: false,
      webPreferences: {
        nodeIntegration: false,
        contextIsolation: true
      }
    });

    const htmlUrl = 'data:text/html;charset=utf-8,' + encodeURIComponent(htmlContent);
    
    printWindow.loadURL(htmlUrl).then(() => {
      printWindow.webContents.print({
        silent: true,
        printBackground: true,
        deviceName: printerName || ''
      }, (success, failureReason) => {
        printWindow.close();
        if (!success) {
          console.error('Print failed:', failureReason);
          resolve({ success: false, error: failureReason });
        } else {
          resolve({ success: true });
        }
      });
    }).catch(err => {
      printWindow.close();
      console.error('Load HTML failed:', err);
      resolve({ success: false, error: err.message });
    });
  });
});

ipcMain.handle('print-raw', async (event, rawText, printerName) => {
  try {
    const config = loadConfig();
    let finalText = rawText;
    
    // Fix header position if receiptType is 2 (Header di Bawah)
    if (config && config.receiptType === 2) {
      const lines = finalText.split(/\\r?\\n/);
      let dividerIndex = -1;
      for (let i = 0; i < Math.min(15, lines.length); i++) {
        if (lines[i].startsWith('---')) {
          dividerIndex = i;
          break;
        }
      }
      
      // If we found a divider and the receipt doesn't already start with Kasir or *** COPY
      if (dividerIndex !== -1 && !lines[0].startsWith('Kasir:') && !lines[0].includes('*** COPY')) {
        const header = lines.slice(0, dividerIndex + 1);
        const body = lines.slice(dividerIndex + 1);
        
        while (body.length > 0 && body[body.length - 1].trim() === '') {
          body.pop();
        }
        
        finalText = body.join('\\r\\n') + '\\r\\n' + header.join('\\r\\n') + '\\r\\n\\r\\n\\r\\n\\r\\n\\r\\n';
      }
    }

    const tempFile = path.join(os.tmpdir(), `pos-print-${Date.now()}.txt`);
    // Ensure Windows CRLF line endings
    const textWithCRLF = finalText.replace(/\\r?\\n/g, '\\r\\n');
    fs.writeFileSync(tempFile, textWithCRLF, { encoding: 'utf8' });
    
    let command = '';
    if (printerName) {
      command = `powershell -Command "Get-Content -Path '${tempFile}' -Raw | Out-Printer -Name '${printerName}'"`;
    } else {
      command = `powershell -Command "Get-Content -Path '${tempFile}' -Raw | Out-Printer"`;
    }
    
    return new Promise((resolve) => {
      exec(command, (error, stdout, stderr) => {
        try { fs.unlinkSync(tempFile); } catch (e) {} // clean up
        if (error) {
          console.error(`Raw print exec error: ${error}`);
          resolve({ success: false, error: error.message });
        } else {
          resolve({ success: true });
        }
      });
    });
  } catch (err) {
    return { success: false, error: err.message };
  }
});
