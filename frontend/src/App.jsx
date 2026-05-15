import React, { useState } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { POSTransaction } from './components/POSTransaction';
import { ManagerDashboard } from './components/ManagerDashboard';
import { Login } from './components/Login';
import './index.css';

function App() {
  const [token, setToken] = useState(localStorage.getItem('pos_token') || null);
  const [user, setUser] = useState(JSON.parse(localStorage.getItem('pos_user')) || null);

  React.useEffect(() => {
    if (token && !user?.organization_name) {
      fetch('/api/v1/user', {
        headers: { 'Authorization': `Bearer ${token}` }
      })
      .then(res => res.json())
      .then(data => {
        if (data.user) {
          localStorage.setItem('pos_user', JSON.stringify(data.user));
          setUser(data.user);
        }
      })
      .catch(err => console.error('Failed to refresh user profile:', err));
    }
  }, [token, user]);

  const handleLoginSuccess = (newToken, userData) => {
    localStorage.setItem('pos_token', newToken);
    localStorage.setItem('pos_user', JSON.stringify(userData));
    setToken(newToken);
    setUser(userData);
  };

  const handleLogout = () => {
    localStorage.removeItem('pos_token');
    localStorage.removeItem('pos_user');
    setToken(null);
    setUser(null);
  };

  if (!token || !user) {
    return <Login onLoginSuccess={handleLoginSuccess} />;
  }

  return (
    <Router>
      <Routes>
        <Route path="/" element={
          user.role === 'MANAGER' ? <Navigate to="/dashboard" replace /> : <Navigate to="/pos" replace />
        } />
        
        <Route path="/pos" element={
          user.role === 'CASHIER' ? (
            <div className="app-container">
              <POSTransaction 
                branchId={user.branch_id} 
                branchName={user.branch_name} 
                orgName={user.organization_name}
                authToken={token} 
                userName={user.name}
                onLogout={handleLogout}
              />
            </div>
          ) : <Navigate to="/dashboard" replace />
        } />

        <Route path="/dashboard" element={
          user.role === 'MANAGER' ? (
            <ManagerDashboard user={user} authToken={token} onLogout={handleLogout} />
          ) : <Navigate to="/pos" replace />
        } />
      </Routes>
    </Router>
  );
}

export default App;
