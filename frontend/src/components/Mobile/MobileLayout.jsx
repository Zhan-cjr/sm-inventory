import React, { useState, useEffect } from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { 
  LayoutDashboard, 
  QrCode, 
  Sparkles, 
  ShieldCheck, 
  ShoppingBag, 
  TrendingUp, 
  LogOut, 
  Sun, 
  Moon, 
  Wifi 
} from 'lucide-react';
import '../../index.css';

export function MobileLayout({ user, onLogout }) {
  const navigate = useNavigate();
  const location = useLocation();

  // State for theme: 'dark' or 'light'
  const [theme, setTheme] = useState(() => {
    return localStorage.getItem('pos_theme') || 'dark';
  });

  // Apply theme change dynamically
  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('pos_theme', theme);
  }, [theme]);

  const toggleTheme = () => {
    setTheme(prev => (prev === 'dark' ? 'light' : 'dark'));
  };

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

  // Get user initials for avatar
  const userInitials = user?.name 
    ? user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() 
    : 'US';

  return (
    <div className="mobile-layout">
      {/* Top Header */}
      <header className="mobile-header">
        <div className="mobile-header-left">
          <div className="mobile-brand-logo">SM</div>
          <div>
            <div className="mobile-header-title">SM Inventory PWA</div>
            <div className="mobile-header-subtitle">
              <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#10b981', display: 'inline-block' }}></span>
              Online &bull; {user?.name || 'Kasir'}
            </div>
          </div>
        </div>

        <div className="mobile-header-right">
          {/* Light / Dark Mode Toggle */}
          <button 
            className="theme-toggle-btn" 
            onClick={toggleTheme} 
            title={theme === 'dark' ? "Mode Terang" : "Mode Gelap"}
            aria-label="Toggle Theme"
          >
            {theme === 'dark' ? <Sun size={19} color="#f59e0b" /> : <Moon size={19} color="#6366f1" />}
          </button>

          {/* User Avatar */}
          <div className="mobile-user-avatar" title={user.name}>
            {userInitials}
          </div>

          {/* Logout Button */}
          <button className="mobile-logout-btn" onClick={onLogout} title="Keluar">
            <LogOut size={18} />
          </button>
        </div>
      </header>

      {/* Main Content Area */}
      <main className="mobile-content">
        <Outlet />
      </main>

      {/* Floating Blurred Glass Bottom Dock */}
      <nav className="mobile-bottom-dock-container" aria-label="Mobile Navigation">
        {!isRestrictedRole && (
          <button
            className={`dock-item ${isActive('/mobile/dashboard') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/dashboard')}
          >
            <LayoutDashboard size={22} />
            <span>Dashboard</span>
          </button>
        )}

        <button
          className={`dock-item ${isActive('/mobile/scanner') ? 'active' : ''}`}
          onClick={() => navigate('/mobile/scanner')}
        >
          <QrCode size={22} />
          <span>Cek Barang</span>
        </button>

        {hasSmartOrderAuth && (
          <button
            className={`dock-item ${isActive('/mobile/suggested-orders') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/suggested-orders')}
          >
            <TrendingUp size={22} />
            <span>Order Pintar</span>
          </button>
        )}

        {hasAuthMenu && (
          <button
            className={`dock-item ${isActive('/mobile/auth') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/auth')}
          >
            <ShieldCheck size={22} />
            <span>Otorisasi</span>
          </button>
        )}

        {hasEcommerceAuth && (
          <button
            className={`dock-item ${isActive('/mobile/ecommerce') ? 'active' : ''}`}
            onClick={() => navigate('/mobile/ecommerce')}
          >
            <ShoppingBag size={22} />
            <span>E-Commerce</span>
          </button>
        )}

        {hasBIAIAuth && (
          <button
            className="dock-item"
            onClick={() => navigate('/dashboard')}
          >
            <Sparkles size={22} color="#8b5cf6" />
            <span>AI & BI</span>
          </button>
        )}
      </nav>
    </div>
  );
}
