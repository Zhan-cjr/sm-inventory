import React, { useState } from 'react';

export const Login = ({ onLoginSuccess }) => {
  const [email, setEmail] = useState('cashier@selamat.id');
  const [password, setPassword] = useState('password');
  const [error, setError] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  const handleLogin = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      const res = await fetch('/api/v1/login', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.message || 'Login failed');
      }

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
