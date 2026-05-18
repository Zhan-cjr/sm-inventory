import React, { useState, useEffect } from 'react';
import { Lock, X } from 'lucide-react';

export const AuthorizationModal = ({ actionName, authToken, onSuccess, onCancel }) => {
  const [authorizers, setAuthorizers] = useState(() => {
    try {
      const cached = localStorage.getItem('pos_cached_authorizers');
      return cached ? JSON.parse(cached) : [];
    } catch (e) {
      return [];
    }
  });
  const [selectedEmail, setSelectedEmail] = useState(() => {
    try {
      const cached = localStorage.getItem('pos_cached_authorizers');
      if (cached) {
        const parsed = JSON.parse(cached);
        if (parsed.length > 0) return parsed[0].email;
      }
      return '';
    } catch (e) {
      return '';
    }
  });
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    // Fetch users who have POS authorization for this branch
    fetch('/api/v1/pos-authorizers', {
      headers: { 'Authorization': `Bearer ${authToken}` }
    })
      .then(res => res.json())
      .then(data => {
        setAuthorizers(data);
        localStorage.setItem('pos_cached_authorizers', JSON.stringify(data));
        if (data.length > 0) {
          setSelectedEmail(data[0].email);
        }
      })
      .catch(err => {
        console.error('Failed to fetch authorizers:', err);
        const cached = localStorage.getItem('pos_cached_authorizers');
        if (cached) {
          try {
            const parsed = JSON.parse(cached);
            setAuthorizers(parsed);
            if (parsed.length > 0) {
              setSelectedEmail(parsed[0].email);
            }
          } catch (e) {
            console.error('Failed to parse cached authorizers:', e);
            setError('Gagal memuat daftar otorisator. Periksa koneksi internet.');
          }
        } else {
          setError('Gagal memuat daftar otorisator. Periksa koneksi internet.');
        }
      });
  }, [authToken]);

  const handleAuthorize = async (e) => {
    e.preventDefault();
    if (!selectedEmail || !password) {
      setError('Pilih otorisator dan masukkan password.');
      return;
    }

    setIsLoading(true);
    setError(null);

    if (!navigator.onLine) {
      try {
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

        const offlineUsers = JSON.parse(localStorage.getItem('pos_offline_users') || '{}');
        const cachedUser = offlineUsers[selectedEmail.toLowerCase().trim()];

        if (!cachedUser) {
          throw new Error('Otorisator belum pernah login di perangkat ini saat online. Otorisasi offline tidak dapat dilakukan.');
        }

        const enteredHash = hashPasswordLocal(selectedEmail, password);
        if (enteredHash !== cachedUser.hash) {
          throw new Error('Password salah (Mode Offline)');
        }

        const authorizer = authorizers.find(a => a.email.toLowerCase().trim() === selectedEmail.toLowerCase().trim());
        if (!authorizer) {
          throw new Error('Data otorisator tidak ditemukan di cache peramban.');
        }

        const authorizations = authorizer.pos_authorizations || [];
        const isAuthorized = authorizer.role === 'ADMIN' || authorizations.includes(actionName);

        if (!isAuthorized) {
          throw new Error(`Otorisator ini tidak memiliki izin untuk aksi: ${actionName}`);
        }

        onSuccess(cachedUser.user);
      } catch (err) {
        setError(err.message);
      } finally {
        setIsLoading(false);
      }
      return;
    }

    try {
      const res = await fetch('/api/v1/authorize-action', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify({
          email: selectedEmail,
          password: password,
          action: actionName
        })
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.message || 'Otorisasi gagal.');
      }

      onSuccess(data.user);
    } catch (err) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content fade-in" style={{ maxWidth: '400px' }}>
        <div className="modal-header-icon" style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', width: '80px', height: '80px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem' }}>
          <Lock size={40} />
        </div>
        <h2 style={{ textAlign: 'center', color: '#ef4444' }}>Otorisasi Diperlukan</h2>
        <p style={{ textAlign: 'center', color: 'var(--text-muted)', marginBottom: '1.5rem' }}>
          Tindakan <strong style={{ color: 'white' }}>{actionName}</strong> membutuhkan otorisasi.
        </p>

        {error && (
          <div style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', padding: '0.75rem', borderRadius: '8px', marginBottom: '1rem', fontSize: '0.9rem', border: '1px solid rgba(239, 68, 68, 0.2)', textAlign: 'center' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleAuthorize}>
          <div className="form-group" style={{ marginBottom: '1rem' }}>
            <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Otorisator</label>
            <select 
              className="modern-barcode-input" 
              style={{ width: '100%', padding: '0.75rem' }}
              value={selectedEmail}
              onChange={(e) => setSelectedEmail(e.target.value)}
              disabled={authorizers.length === 0}
            >
              {authorizers.length === 0 ? (
                <option value="">Memuat data...</option>
              ) : (
                authorizers.map(a => (
                  <option key={a.id} value={a.email}>{a.name} ({a.role})</option>
                ))
              )}
            </select>
          </div>

          <div className="form-group" style={{ marginBottom: '1.5rem' }}>
            <label style={{ display: 'block', marginBottom: '0.5rem', fontWeight: '600' }}>Password</label>
            <input 
              type="password" 
              className="modern-barcode-input" 
              style={{ width: '100%', padding: '0.75rem', textAlign: 'center', letterSpacing: '0.2rem' }}
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoFocus
            />
          </div>

          <div style={{ display: 'flex', gap: '1rem', width: '100%' }}>
            <button type="button" className="btn-secondary" style={{ flex: 1 }} onClick={onCancel} disabled={isLoading}>BATAL</button>
            <button type="submit" className="btn-danger" style={{ flex: 1 }} disabled={isLoading}>
              {isLoading ? 'MEMERIKSA...' : 'OTORISASI'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
