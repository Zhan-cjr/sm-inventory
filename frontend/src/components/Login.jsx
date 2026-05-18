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
    <div className="login-container">
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
    </div>
  );
};
