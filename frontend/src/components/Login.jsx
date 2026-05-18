import React, { useState } from 'react';

export const Login = ({ onLoginSuccess }) => {
  const [email, setEmail] = useState('cashier@selamat.id');
  const [password, setPassword] = useState('password');
  const [error, setError] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  const hashPasswordLocal = (email, password) => {
    const salt = "sminventory_salt_2026";
    const str = email.toLowerCase().trim() + "|" + password + "|" + salt;
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
            body: JSON.stringify({ email, password })
          });
          data = await res.json();
        } catch (fetchErr) {
          isOffline = true;
        }
      }

      if (isOffline) {
        const offlineUsers = JSON.parse(localStorage.getItem('pos_offline_users') || '{}');
        const cachedUser = offlineUsers[email.toLowerCase().trim()];

        if (cachedUser) {
          const enteredHash = hashPasswordLocal(email, password);
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
        throw new Error(data.message || 'Login failed');
      }

      // Save offline credentials hash for future use
      const hash = hashPasswordLocal(email, password);
      const offlineUsers = JSON.parse(localStorage.getItem('pos_offline_users') || '{}');
      offlineUsers[email.toLowerCase().trim()] = {
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

        <form onSubmit={handleLogin}>
          <div className="form-group">
            <label>Email Karyawan</label>
            <input 
              type="email" 
              value={email} 
              onChange={e => setEmail(e.target.value)}
              required 
              className="login-input"
              placeholder="email@perusahaan.com"
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
              <option value="Shift 1">Shift 1 (Pagi)</option>
              <option value="Shift 2">Shift 2 (Siang)</option>
              <option value="Shift 3">Shift 3 (Malam)</option>
              <option value="Shift Umum">Shift Umum</option>
            </select>
          </div>
          <button type="submit" className="btn-primary btn-login" disabled={isLoading} style={{ marginTop: '0.5rem' }}>
            {isLoading ? <span className="spinner"></span> : 'Masuk POS Terminal'}
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
            color: 'rgba(255, 255, 255, 0.6)', 
            textDecoration: 'none', 
            fontSize: '0.85rem', 
            fontWeight: '500',
            transition: 'color 0.2s',
          }}
          onMouseEnter={(e) => {
            const link = e.currentTarget;
            link.style.color = '#fff';
          }}
          onMouseLeave={(e) => {
            const link = e.currentTarget;
            link.style.color = 'rgba(255, 255, 255, 0.6)';
          }}
        >
          <svg width="20" height="20" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style={{ borderRadius: '6px' }}>
            <defs>
              <linearGradient id="zGradLogin" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#4f46e5" />
                <stop offset="100%" stopColor="#06b6d4" />
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
