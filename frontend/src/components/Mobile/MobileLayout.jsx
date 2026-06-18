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

  const hasEcommerceAuth = user?.custom_authorizations?.includes('PROCESS_ECOMMERCE');
  const hasSmartOrderAuth = user?.custom_authorizations?.includes('ACCESS_SMART_ORDER');
  const hasBIAIAuth = user?.custom_authorizations?.includes('ACCESS_BI_AI');
  const hasAuthMenu = (user?.pos_authorizations && user.pos_authorizations.length > 0) || 
                      user?.custom_authorizations?.includes('APPROVE_PO') || 
                      user?.custom_authorizations?.includes('APPROVE_STOCK_ADJUSTMENT');

  return (
    <div className="mobile-layout">
      {/* Top Header */}
      <div className="mobile-header">
        <div className="mobile-header-title">SM Inventory PWA (v1.2)</div>
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

        {hasSmartOrderAuth && (
          <button
            className={`nav-item ${isActive('/mobile/suggested-orders') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/suggested-orders')}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            <span>Order Pintar</span>
          </button>
        )}

        {hasAuthMenu && (
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

        {hasEcommerceAuth && (
          <button
            className={`nav-item ${isActive('/mobile/ecommerce') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/ecommerce')}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span>E-Commerce</span>
          </button>
        )}

        {hasBIAIAuth && (
          <button
            className="nav-item"
            onClick={() => navigate('/dashboard')}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            <span>AI & BI</span>
          </button>
        )}
      </div>
    </div>
  );
}
