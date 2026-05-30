import React from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import '../../index.css';

export function MobileLayout({ user, onLogout }) {
  const navigate = useNavigate();
  const location = useLocation();

  if (!user) {
    return null;
  }

  const isActive = (path) => location.pathname.startsWith(path);
  const isManagerOrAdmin = ['MANAGER', 'ADMIN', 'SUPERVISOR', 'SPV', 'EDP', 'SUPERADMIN', 'SUPER_ADMIN'].includes(user?.role?.toUpperCase());
  const isRestrictedRole = !isManagerOrAdmin;

  return (
    <div className="mobile-layout">
      {/* Top Header */}
      <div className="mobile-header">
        <div className="mobile-header-title">SM Inventory PWA</div>
        <div className="mobile-header-user">
          <span className="mobile-user-name">{user.name}</span>
          <button className="mobile-logout-btn" onClick={onLogout}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
          </button>
        </div>
      </div>

      {/* Main Content Area */}
      <div className="mobile-content">
        <Outlet />
      </div>

      {/* Bottom Navigation */}
      <div className="mobile-bottom-nav">
        {!isRestrictedRole && (
          <button
            className={`nav-item ${isActive('/mobile/dashboard') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/dashboard')}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Dashboard</span>
          </button>
        )}

        <button
          className={`nav-item ${isActive('/mobile/scanner') ? 'active' : ''}`}
          onClick={() => navigate('/mobile/scanner')}
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M4 4h4v4H4z"></path>
            <path d="M4 16h4v4H4z"></path>
            <path d="M16 4h4v4h-4z"></path>
            <path d="M16 16h4v4h-4z"></path>
            <line x1="12" y1="4" x2="12" y2="20"></line>
            <line x1="4" y1="12" x2="20" y2="12"></line>
          </svg>
          <span>Cek Barang</span>
        </button>

        {!isRestrictedRole && (
          <button
            className={`nav-item ${isActive('/mobile/auth') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/auth')}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            <span>Otorisasi</span>
          </button>
        )}

        {!isRestrictedRole && (
          <button
            className="nav-item"
            onClick={() => navigate('/dashboard')}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="3" y1="9" x2="21" y2="9"></line>
              <line x1="9" y1="21" x2="9" y2="9"></line>
            </svg>
            <span>Panel Admin</span>
          </button>
        )}
      </div>
    </div>
  );
}
