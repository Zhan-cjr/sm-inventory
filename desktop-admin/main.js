const { app, BrowserWindow, ipcMain, Menu, shell, dialog } = require('electron');
const path = require('path');
const fs = require('fs');
const http = require('http');
const https = require('https');

const configPath = path.join(app.getPath('userData'), 'admin-config.json');

let mainWindow;
let isNavigatingToSettings = false;

// Default configuration
const DEFAULT_CONFIG = {
  adminUrl: 'https://admin.toserbaselamat.id',
  rememberUrl: true
};

function loadConfig() {
  try {
    if (fs.existsSync(configPath)) {
      const data = fs.readFileSync(configPath, 'utf8');
      return { ...DEFAULT_CONFIG, ...JSON.parse(data) };
    }
  } catch (error) {
    console.error('Error loading config:', error);
  }
  return { ...DEFAULT_CONFIG };
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

function normalizeUrl(url) {
  if (!url) return '';
  let trimmed = url.trim();
  if (!/^https?:\/\//i.test(trimmed)) {
    trimmed = 'http://' + trimmed;
  }
  return trimmed;
}

function createMainWindow() {
  mainWindow = new BrowserWindow({
    width: 1366,
    height: 820,
    minWidth: 1024,
    minHeight: 600,
    title: 'SM Inventory - Admin Panel',
    icon: path.join(__dirname, 'icon.png'),
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js'),
      partition: 'persist:admin-session'
    },
    autoHideMenuBar: false
  });

  setupMenu();
  setupDownloadHandling();

  const config = loadConfig();

  if (config && config.adminUrl) {
    loadAdminUrl(config.adminUrl);
  } else {
    openSettingsPage();
  }

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

function loadAdminUrl(url) {
  const targetUrl = normalizeUrl(url);
  isNavigatingToSettings = false;

  mainWindow.loadURL(targetUrl).catch(err => {
    console.error('Failed to load Admin URL:', err);
    showErrorPage(targetUrl, err.message || err.toString());
  });
}

function showErrorPage(attemptedUrl, errorMessage) {
  const errorHtml = `
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <title>Koneksi Gagal - SM Inventory Admin</title>
      <style>
        body {
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
          background: #f1f5f9;
          color: #1e293b;
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 100vh;
          margin: 0;
          padding: 20px;
          box-sizing: border-box;
        }
        .card {
          background: white;
          padding: 2.5rem;
          border-radius: 16px;
          box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
          max-width: 520px;
          width: 100%;
          text-align: center;
        }
        .icon {
          font-size: 3.5rem;
          margin-bottom: 1rem;
        }
        h2 {
          margin: 0 0 0.5rem 0;
          color: #dc2626;
          font-size: 1.5rem;
        }
        p {
          margin: 0.5rem 0;
          color: #475569;
          font-size: 0.95rem;
          line-height: 1.5;
        }
        .url-badge {
          display: inline-block;
          background: #e2e8f0;
          padding: 0.4rem 0.8rem;
          border-radius: 8px;
          font-family: monospace;
          font-size: 0.85rem;
          color: #0f172a;
          word-break: break-all;
          margin: 0.75rem 0;
        }
        .btn-group {
          display: flex;
          gap: 10px;
          margin-top: 1.75rem;
        }
        button {
          flex: 1;
          padding: 0.85rem 1rem;
          border-radius: 8px;
          font-size: 0.95rem;
          font-weight: 600;
          cursor: pointer;
          border: none;
          transition: all 0.2s;
        }
        .btn-primary {
          background: #2563eb;
          color: white;
        }
        .btn-primary:hover {
          background: #1d4ed8;
        }
        .btn-secondary {
          background: #e2e8f0;
          color: #1e293b;
        }
        .btn-secondary:hover {
          background: #cbd5e1;
        }
      </style>
    </head>
    <body>
      <div class="card">
        <div class="icon">⚠️</div>
        <h2>Tidak Dapat Terhubung ke Server</h2>
        <p>Aplikasi gagal memuat halaman Admin dari alamat URL berikut:</p>
        <div class="url-badge">${attemptedUrl}</div>
        <p>Periksa koneksi jaringan LAN/Wi-Fi Anda, atau pastikan alamat IP / Domain server sudah benar.</p>
        <div class="btn-group">
          <button class="btn-secondary" onclick="window.location.reload()">🔄 Coba Lagi</button>
          <button class="btn-primary" onclick="window.location.href='settings://open'">⚙️ Ganti URL Admin</button>
        </div>
      </div>
      <script>
        document.querySelector('.btn-primary').onclick = () => {
          if (window.electronAPI && window.electronAPI.goBackToAdmin) {
            // will trigger loadFile('settings.html')
            window.location.href = '${path.join(__dirname, 'settings.html')}';
          }
        };
      </script>
    </body>
    </html>
  `;

  mainWindow.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(errorHtml)}`);
}

function openSettingsPage() {
  if (mainWindow) {
    isNavigatingToSettings = true;
    mainWindow.loadFile(path.join(__dirname, 'settings.html'));
  }
}

function setupDownloadHandling() {
  mainWindow.webContents.session.on('will-download', (event, item, webContents) => {
    // Determine download path or let user choose save location
    const fileName = item.getFilename();
    const defaultPath = path.join(app.getPath('downloads'), fileName);
    item.setSavePath(defaultPath);

    item.on('updated', (event, state) => {
      if (state === 'interrupted') {
        console.log('Download is interrupted but can be resumed');
      } else if (state === 'progressing') {
        if (item.isPaused()) {
          console.log('Download is paused');
        }
      }
    });

    item.once('done', (event, state) => {
      if (state === 'completed') {
        console.log('Download successfully completed:', item.getSavePath());
      } else {
        console.log(`Download failed: ${state}`);
      }
    });
  });
}

function setupMenu() {
  const template = [
    {
      label: 'Pengaturan',
      submenu: [
        {
          label: '⚙️ Ganti URL Server Admin...',
          accelerator: 'F2',
          click: () => openSettingsPage()
        },
        {
          label: 'Pengaturan Tambahan (Ctrl+,)',
          accelerator: 'CmdOrCtrl+,',
          click: () => openSettingsPage()
        },
        { type: 'separator' },
        {
          label: 'Keluar',
          accelerator: 'CmdOrCtrl+Q',
          click: () => app.quit()
        }
      ]
    },
    {
      label: 'Navigasi',
      submenu: [
        {
          label: 'Kembali',
          accelerator: 'Alt+Left',
          click: () => {
            if (mainWindow && mainWindow.webContents.canGoBack()) {
              mainWindow.webContents.goBack();
            }
          }
        },
        {
          label: 'Maju',
          accelerator: 'Alt+Right',
          click: () => {
            if (mainWindow && mainWindow.webContents.canGoForward()) {
              mainWindow.webContents.goForward();
            }
          }
        },
        { type: 'separator' },
        {
          label: 'Muat Ulang',
          accelerator: 'CmdOrCtrl+R',
          click: () => {
            if (mainWindow) mainWindow.webContents.reload();
          }
        },
        {
          label: 'Paksa Muat Ulang (Bypass Cache)',
          accelerator: 'CmdOrCtrl+Shift+R',
          click: () => {
            if (mainWindow) mainWindow.webContents.reloadIgnoringCache();
          }
        }
      ]
    },
    {
      label: 'Tampilan',
      submenu: [
        { role: 'resetZoom', label: 'Ukuran Normal' },
        { role: 'zoomIn', label: 'Perbesar' },
        { role: 'zoomOut', label: 'Perkecil' },
        { type: 'separator' },
        { role: 'togglefullscreen', label: 'Layar Penuh (F11)' },
        { type: 'separator' },
        { role: 'toggleDevTools', label: 'Developer Tools (F12)' }
      ]
    },
    {
      label: 'Bantuan',
      submenu: [
        {
          label: 'Tentang SM Inventory Admin',
          click: () => {
            dialog.showMessageBox(mainWindow, {
              type: 'info',
              title: 'Tentang SM Inventory Admin',
              message: 'SM Inventory Desktop Admin Panel',
              detail: 'Versi: ' + app.getVersion() + '\nKompatibel: Windows 7 / 8 / 10 / 11 (32-bit & 64-bit)\nRuntime: Electron 22.3.27 (Chromium 108)'
            });
          }
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

// Ensure single instance
const gotTheLock = app.requestSingleInstanceLock();

if (!gotTheLock) {
  app.quit();
} else {
  app.on('second-instance', () => {
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore();
      mainWindow.focus();
    }
  });

  app.whenReady().then(() => {
    createMainWindow();

    app.on('activate', () => {
      if (BrowserWindow.getAllWindows().length === 0) {
        createMainWindow();
      }
    });
  });
}

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

// IPC Handlers
ipcMain.handle('get-config', () => {
  return loadConfig();
});

ipcMain.handle('save-config', (event, config) => {
  const success = saveConfig(config);
  if (success && mainWindow && config.adminUrl) {
    loadAdminUrl(config.adminUrl);
  }
  return success;
});

ipcMain.handle('go-back-to-admin', () => {
  const config = loadConfig();
  if (config && config.adminUrl) {
    loadAdminUrl(config.adminUrl);
  } else {
    openSettingsPage();
  }
});

ipcMain.handle('open-external', (event, url) => {
  shell.openExternal(url);
});

ipcMain.handle('get-app-version', () => {
  return app.getVersion();
});

// Connection Test IPC Handler
ipcMain.handle('test-connection', async (event, testUrl) => {
  return new Promise((resolve) => {
    try {
      const target = normalizeUrl(testUrl);
      const parsed = new URL(target);
      const client = parsed.protocol === 'https:' ? https : http;

      const req = client.get(target, { timeout: 4000 }, (res) => {
        // Any HTTP response (200, 302, 401, 403, 404) proves server is reachable
        resolve({
          success: true,
          statusCode: res.statusCode,
          message: `Server merespons (Kode HTTP: ${res.statusCode})`
        });
      });

      req.on('error', (err) => {
        resolve({
          success: false,
          message: `Gagal terhubung: ${err.message}`
        });
      });

      req.on('timeout', () => {
        req.destroy();
        resolve({
          success: false,
          message: 'Koneksi timeout (server tidak merespons dalam 4 detik)'
        });
      });
    } catch (err) {
      resolve({
        success: false,
        message: `Format URL tidak valid: ${err.message}`
      });
    }
  });
});
