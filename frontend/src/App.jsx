import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { POSTransaction } from './components/POSTransaction';
import { ManagerDashboard } from './components/ManagerDashboard';
import { Login } from './components/Login';
import './index.css';

// Interceptor global fetch untuk otomatis menyertakan header X-Device-UUID di semua request API v1
const originalFetch = window.fetch;
window.fetch = async function (url, options = {}) {
  const deviceUuid = localStorage.getItem('pos_device_uuid');
  if (deviceUuid && url.toString().includes('/api/v1/')) {
    options.headers = options.headers || {};
    if (options.headers instanceof Headers) {
      options.headers.set('X-Device-UUID', deviceUuid);
    } else if (Array.isArray(options.headers)) {
      options.headers.push(['X-Device-UUID', deviceUuid]);
    } else {
      options.headers['X-Device-UUID'] = deviceUuid;
    }
  }
  return originalFetch(url, options);
};

function App() {
  const [token, setToken] = useState(localStorage.getItem('pos_token') || null);
  const [user, setUser] = useState(() => {
    try {
      const val = localStorage.getItem('pos_user');
      return val && val !== 'undefined' ? JSON.parse(val) : null;
    } catch (e) {
      return null;
    }
  });

  // States for Device Authorization
  const [deviceUuid, setDeviceUuid] = useState(localStorage.getItem('pos_device_uuid') || null);
  const [deviceStatus, setDeviceStatus] = useState('checking'); // checking, pending, blocked, approved
  const [deviceInfo, setDeviceInfo] = useState(null); // { branchId, branchName, terminalId, terminalName }
  const [deviceError, setDeviceError] = useState(null); // Branch mismatch error messages
  const [copied, setCopied] = useState(false);

  // Initialize and handshake device UUID
  useEffect(() => {
    let activeUuid = localStorage.getItem('pos_device_uuid');
    if (!activeUuid) {
      activeUuid = crypto.randomUUID();
      localStorage.setItem('pos_device_uuid', activeUuid);
      setDeviceUuid(activeUuid);
    }

    const performHandshake = async () => {
      try {
        const response = await fetch('/api/v1/devices/handshake', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            device_uuid: activeUuid,
            name: 'Kasir ' + (navigator.platform || 'Desktop')
          })
        });

        const data = await response.json();
        if (data.status === 'APPROVED') {
          setDeviceStatus('approved');
          setDeviceInfo({
            branchId: data.branch_id,
            branchName: data.branch_name,
            terminalId: data.terminal_id,
            terminalName: data.terminal_name
          });
          // Cache in local storage for offline fallback
          localStorage.setItem('pos_device_status', 'APPROVED');
          localStorage.setItem('pos_device_branch_id', data.branch_id || '');
          localStorage.setItem('pos_device_branch_name', data.branch_name || '');
          localStorage.setItem('pos_device_terminal_id', data.terminal_id || '');
          localStorage.setItem('pos_device_terminal_name', data.terminal_name || '');
        } else if (data.status === 'BLOCKED') {
          setDeviceStatus('blocked');
          localStorage.setItem('pos_device_status', 'BLOCKED');
        } else {
          setDeviceStatus('pending');
          localStorage.setItem('pos_device_status', 'PENDING');
          // Retry handshake every 8 seconds if pending
          setTimeout(performHandshake, 8000);
        }
      } catch (error) {
        console.warn('Handshake failed, trying offline cache fallback:', error);
        const cachedStatus = localStorage.getItem('pos_device_status');
        if (cachedStatus === 'APPROVED') {
          setDeviceStatus('approved');
          setDeviceInfo({
            branchId: localStorage.getItem('pos_device_branch_id'),
            branchName: localStorage.getItem('pos_device_branch_name'),
            terminalId: localStorage.getItem('pos_device_terminal_id'),
            terminalName: localStorage.getItem('pos_device_terminal_name')
          });
        } else if (cachedStatus === 'BLOCKED') {
          setDeviceStatus('blocked');
        } else if (cachedStatus === 'PENDING') {
          setDeviceStatus('pending');
          setTimeout(performHandshake, 10000);
        } else {
          // Keep trying to connect
          setTimeout(performHandshake, 5000);
        }
      }
    };

    performHandshake();
  }, []);

  // Fetch /api/v1/user on token change to sync profile
  useEffect(() => {
    if (token && !user?.organization_name && deviceStatus === 'approved') {
      fetch('/api/v1/user', {
        headers: { 'Authorization': `Bearer ${token}` }
      })
        .then(res => {
          if (res.status === 401) {
            handleLogout();
            throw new Error('Session expired');
          }
          return res.json();
        })
        .then(data => {
          if (data.user) {
            // Strict Branch check between logged-in user and approved device branch
            if (deviceInfo?.branchId && data.user.branch_id && data.user.branch_id !== deviceInfo.branchId) {
              const errMsg = `Akun Anda terdaftar di cabang "${data.user.branch_name || 'Cabang Lain'}", sedangkan perangkat ini dikunci untuk cabang "${deviceInfo.branchName}". Hubungi Admin!`;
              setDeviceError(errMsg);
              handleLogout();
              return;
            }
            localStorage.setItem('pos_user', JSON.stringify(data.user));
            setUser(data.user);
          }
        })
        .catch(err => {
          console.warn('Failed to refresh user profile:', err.message);
        });
    }
  }, [token, user, deviceStatus, deviceInfo]);

  const handleLoginSuccess = (newToken, userData) => {
    // Strict Branch verification before setting session
    if (deviceInfo?.branchId && userData.branch_id && userData.branch_id !== deviceInfo.branchId) {
      const errMsg = `Akun Anda terdaftar di cabang "${userData.branch_name || 'Cabang Lain'}", sedangkan perangkat ini dikunci untuk cabang "${deviceInfo.branchName}". Hubungi Admin!`;
      setDeviceError(errMsg);
      handleLogout();
      throw new Error(errMsg);
    }

    setDeviceError(null);
    localStorage.setItem('pos_token', newToken);
    localStorage.setItem('pos_user', JSON.stringify(userData));
    setToken(newToken);
    setUser(userData);
  };

  const handleLogout = () => {
    localStorage.removeItem('pos_token');
    localStorage.removeItem('pos_user');
    localStorage.removeItem('pos_active_shift');
    setToken(null);
    setUser(null);
  };

  const copyToClipboard = () => {
    if (deviceUuid) {
      navigator.clipboard.writeText(deviceUuid);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  // 1. Device Authorization Status: Checking / Loading
  if (deviceStatus === 'checking') {
    return (
      <div className="device-auth-screen">
        <div className="device-auth-card">
          <div className="device-auth-icon-wrapper checking">
            <svg className="animate-spin" width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
          </div>
          <h2 className="device-auth-title">Memeriksa Perangkat</h2>
          <p className="device-auth-description">Mengamankan koneksi dan memeriksa otorisasi terminal kasir Anda...</p>
        </div>
      </div>
    );
  }

  // 2. Device Authorization Status: Pending (Waiting Approval)
  if (deviceStatus === 'pending') {
    return (
      <div className="device-auth-screen">
        <div className="device-auth-card">
          <div className="device-auth-icon-wrapper pending">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
            </svg>
          </div>
          <h2 className="device-auth-title">Otorisasi Tertunda</h2>
          <p className="device-auth-description">
            Perangkat ini belum disetujui untuk digunakan. Silakan hubungi Administrator untuk menyetujui perangkat ini di panel Admin.
          </p>

          <div className="device-auth-uuid-container">
            <div className="device-auth-uuid-label">Device UUID Perangkat Anda</div>
            <div className="device-auth-uuid-value-row">
              <span className="device-auth-uuid-value">{deviceUuid}</span>
              <button className="device-auth-copy-btn" onClick={copyToClipboard}>
                {copied ? 'Tersalin!' : 'Salin ID'}
              </button>
            </div>
          </div>

          <div className="device-auth-status-bar">
            <div className="device-auth-pulse-dot"></div>
            <span>Menunggu persetujuan Administrator...</span>
          </div>

          <div className="device-auth-footer-brand">
            SM Inventory &copy; 2026. Powered by <a href="https://instagram.com/zhan_soft" target="_blank" rel="noreferrer">Zhan_soft</a>
          </div>
        </div>
      </div>
    );
  }

  // 3. Device Authorization Status: Blocked
  if (deviceStatus === 'blocked') {
    return (
      <div className="device-auth-screen">
        <div className="device-auth-card">
          <div className="device-auth-icon-wrapper blocked">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <h2 className="device-auth-title" style={{ color: '#ef4444' }}>Akses Diblokir</h2>
          <p className="device-auth-description">
            Perangkat kasir ini telah diblokir secara permanen oleh Administrator. Anda tidak dapat melakukan transaksi POS menggunakan perangkat ini.
          </p>

          <div className="device-auth-uuid-container" style={{ border: '1px solid rgba(239,68,68,0.2)' }}>
            <div className="device-auth-uuid-label" style={{ color: '#f87171' }}>Device UUID Diblokir</div>
            <div className="device-auth-uuid-value-row">
              <span className="device-auth-uuid-value" style={{ color: '#f87171' }}>{deviceUuid}</span>
            </div>
          </div>

          <div className="device-auth-footer-brand">
            SM Inventory &copy; 2026. Powered by <a href="https://instagram.com/zhan_soft" target="_blank" rel="noreferrer">Zhan_soft</a>
          </div>
        </div>
      </div>
    );
  }

  // 4. Device Approved -> Load normal App flow
  if (!token || !user) {
    return (
      <div className="device-auth-screen">
        <Login onLoginSuccess={handleLoginSuccess} />
        {deviceError && (
          <div className="device-auth-card" style={{ marginTop: '1.5rem', padding: '1.5rem', maxWidth: '400px' }}>
            <div className="device-auth-error-card">
              <strong>Gagal Login:</strong> {deviceError}
            </div>
          </div>
        )}
      </div>
    );
  }

  const isManagerOrAdmin = user?.role === 'MANAGER' || user?.role === 'ADMIN';

  return (
    <Router>
      <Routes>
        <Route path="/" element={
          isManagerOrAdmin ? <Navigate to="/dashboard" replace /> : <Navigate to="/pos" replace />
        } />

        <Route path="/pos" element={
          user.role === 'CASHIER' || isManagerOrAdmin ? (
            <div className="app-container">
              <POSTransaction
                branchId={deviceInfo?.branchId || user.branch_id}
                branchName={deviceInfo?.branchName || user.branch_name}
                orgName={user.organization_name}
                authToken={token}
                userName={user.name}
                userRole={user.role}
                onLogout={handleLogout}
                // Locked Device Terminal Info (Props)
                lockedTerminalId={deviceInfo?.terminalId}
                lockedTerminalName={deviceInfo?.terminalName}
              />
            </div>
          ) : <Navigate to="/dashboard" replace />
        } />

        <Route path="/dashboard" element={
          isManagerOrAdmin ? (
            <ManagerDashboard user={user} authToken={token} onLogout={handleLogout} />
          ) : <Navigate to="/pos" replace />
        } />
      </Routes>
    </Router>
  );
}

export default App;
