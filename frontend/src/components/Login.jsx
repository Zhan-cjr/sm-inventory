import React, { useState, useEffect } from 'react';

export const Login = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);

  useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth <= 768);
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const hashPasswordLocal = (username, password) => {
    const salt = "sminventory_salt_2026";
    const str = username.toLowerCase().trim() + "|" + password + "|" + salt;
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = (hash << 5) - hash + char;
      hash = hash & hash;
    }
    return hash.toString(36);
  };

  const handleLogin = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      let isOffline = !navigator.onLine;
      let res = null;
      let data = null;

      if (!isOffline) {
        try {
          res = await fetch('/api/v1/login', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({ username, password })
          });
          
          if (!res.ok && res.status >= 500) {
            // Cloudflare (530, 521, etc) atau server error -> anggap offline
            isOffline = true;
          } else {
            data = await res.json();
          }
        } catch (fetchErr) {
          isOffline = true;
        }
      }

      if (isOffline) {
        const offlineUsers = JSON.parse(localStorage.getItem('pos_offline_users') || '{}');
        const cachedUser = offlineUsers[username.toLowerCase().trim()];

        if (cachedUser) {
          const enteredHash = hashPasswordLocal(username, password);
          if (enteredHash === cachedUser.hash) {
            localStorage.setItem('pos_preselected_shift', shift);
            onLoginSuccess(cachedUser.token, cachedUser.user);
            return;
          } else {
            throw new Error('Password salah (Mode Offline)');
          }
        } else {
          throw new Error('Koneksi offline. Kasir ini belum terdaftar di perangkat ini untuk login offline.');
        }
      }

      if (!res.ok) {
        throw new Error(data?.message || `Login failed (HTTP ${res?.status})`);
      }

      // Save offline credentials hash for future use
      const hash = hashPasswordLocal(username, password);
      const offlineUsers = JSON.parse(localStorage.getItem('pos_offline_users') || '{}');
      offlineUsers[username.toLowerCase().trim()] = {
        hash,
        token: data.token,
        user: data.user
      };
      localStorage.setItem('pos_offline_users', JSON.stringify(offlineUsers));

      // Store selected shift name to be used in POS
      localStorage.setItem('pos_preselected_shift', shift);

      onLoginSuccess(data.token, data.user);
    } catch (err) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  const [shift, setShift] = useState('Shift 1');

  return (
    <div className="login-container" style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh', justifyContent: 'center', alignItems: 'center' }}>
      <div className="login-box glass-panel">
        <h2 style={{ marginBottom: '0.5rem' }}>Toserba Selamat POS</h2>
        <p style={{ color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Silakan masuk untuk memulai sesi kasir Anda</p>

        {error && <div className="alert-msg slide-down" style={{ marginBottom: '1rem' }}>{error}</div>}

        <form onSubmit={handleLogin} autoComplete="off">
          <div className="form-group">
            <label>Username Karyawan</label>
            <input
              type="text"
              value={username}
              onChange={e => setUsername(e.target.value)}
              required
              className="login-input"
              placeholder="username"
              autoComplete="off"
            />
          </div>
          <div className="form-group">
            <label>Password</label>
            <input
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
              className="login-input"
              placeholder="••••••••"
              autoComplete="new-password"
            />
          </div>
          <div className="form-group">
            <label>Pilih Shift</label>
            <select
              value={shift}
              onChange={e => setShift(e.target.value)}
              className="login-input"
              style={{ width: '100%', cursor: 'pointer' }}
            >
              <option value="Shift 1">Shift 1</option>
              <option value="Shift 2">Shift 2</option>
              <option value="Shift 3">Shift 3</option>
              <option value="Shift Umum">Shift Umum</option>
            </select>
          </div>
          <button type="submit" className="btn-primary btn-login" disabled={isLoading} style={{ marginTop: '0.5rem' }}>
            {isLoading ? <span className="spinner"></span> : 'Masuk POS Terminal'}
          </button>
          <button 
            type="button" 
            className="btn-secondary" 
            disabled={!isMobile || isLoading}
            onClick={() => {
              if (isMobile) window.location.href = '/mobile';
            }}
            style={{ 
              marginTop: '0.75rem', 
              width: '100%', 
              padding: '1rem', 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'center', 
              gap: '0.5rem',
              opacity: isMobile ? 1 : 0.5,
              cursor: isMobile ? 'pointer' : 'not-allowed',
              border: isMobile ? '1px solid var(--accent)' : '1px solid var(--border-light)',
              color: isMobile ? 'var(--accent)' : 'var(--text-muted)'
            }}
            title={!isMobile ? "Hanya tersedia di perangkat HP/Mobile" : "Buka versi PWA Mobile"}
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
              <line x1="12" y1="18" x2="12.01" y2="18"></line>
            </svg>
            Mode Mobile PWA
          </button>
        </form>
      </div>

      <div style={{ marginTop: '2rem', display: 'flex', justifyContent: 'center', zIndex: 10 }}>
        <a
          href="https://www.instagram.com/amn4ll?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw=="
          target="_blank"
          rel="noopener noreferrer"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '8px',
            color: 'var(--text-muted)',
            textDecoration: 'none',
            fontSize: '0.85rem',
            fontWeight: '500',
            transition: 'color 0.2s',
          }}
          onMouseEnter={(e) => {
            const link = e.currentTarget;
            link.style.color = 'var(--text-main)';
          }}
          onMouseLeave={(e) => {
            const link = e.currentTarget;
            link.style.color = 'var(--text-muted)';
          }}
        >
          <svg width="20" height="20" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style={{ borderRadius: '6px' }}>
            <defs>
              <linearGradient id="zGradLogin" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="var(--primary)" />
                <stop offset="100%" stopColor="var(--accent)" />
              </linearGradient>
            </defs>
            <rect width="100" height="100" rx="30" fill="url(#zGradLogin)" />
            <path d="M30 30H70L30 70H70" stroke="white" strokeWidth="12" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
          <span>Copyright &copy; {new Date().getFullYear()} Zhan_soft</span>
        </a>
      </div>
    </div>
  );
};
