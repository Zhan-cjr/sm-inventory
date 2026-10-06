import React from 'react';
import { Clock, RefreshCw, Wifi, WifiOff, Settings, Sun, Moon, User, LogOut, AlertTriangle, BarChart3 } from 'lucide-react';
import LiveClock from './LiveClock';

const PosHeader = ({
  orgName,
  branchName,
  terminalInfo,
  activeShift,
  pendingCount,
  unrefundedFailedPpobCount = 0,
  onOpenPpobMenu,
  isOnline,
  syncStatus,
  syncTransactions,
  setIsPrinterSettingsOpen,
  theme,
  toggleTheme,
  userName,
  userRole,
  setIsTerminalModalOpen,
  onLogout
}) => {
  return (
    <header className="pos-header-modern glassmorphism">
      <div className="pos-branding">
        <div className="logo-box">SM</div>
        <div className="brand-info">
          <h1>{orgName}</h1>
          <p>{branchName} | {terminalInfo?.name || 'Terminal'}</p>
          {activeShift && (
            <div
              className="active-shift-badge"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '0.4rem',
                background: 'rgba(36, 42, 122, 0.15)',
                color: 'var(--primary)',
                padding: '2px 8px',
                borderRadius: '4px',
                fontSize: '0.7rem',
                fontWeight: '700',
                marginTop: '4px',
                border: '1px solid rgba(36, 42, 122, 0.3)'
              }}
            >
              <Clock size={12} />
              <span>AKTIF: {activeShift.shift_name}</span>
            </div>
          )}
        </div>
      </div>

      <LiveClock />

      <div className="pos-user-status">
        {unrefundedFailedPpobCount > 0 && (
          <button
            type="button"
            className="ppob-failed-badge mr-4"
            onClick={onOpenPpobMenu}
            title="Ada transaksi PPOB yang gagal dan perlu direfund ke konsumen! Klik untuk buka."
            style={{
              background: 'rgba(230, 0, 18, 0.15)',
              border: '1px solid var(--danger)',
              padding: '4px 12px',
              borderRadius: '20px',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              color: 'var(--danger)',
              fontWeight: 'bold',
              cursor: 'pointer',
              fontSize: '0.8rem'
            }}
          >
            <AlertTriangle size={16} />
            <span>⚠️ {unrefundedFailedPpobCount} PPOB Gagal (Refund)</span>
          </button>
        )}

        {pendingCount > 0 && (
          <div
            className={`sync-status mr-4 ${!isOnline ? 'warning-pulse' : ''}`}
            onClick={() => syncTransactions()}
            title="Klik untuk paksa sinkronisasi antrean ke server"
            style={{
              background: !isOnline ? 'rgba(230, 0, 18, 0.2)' : 'rgba(36, 42, 122, 0.1)',
              border: !isOnline ? '1px solid var(--danger)' : '1px solid var(--primary)',
              padding: '4px 12px',
              borderRadius: '20px',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              color: !isOnline ? 'var(--danger)' : 'var(--primary)',
              fontWeight: 'bold',
              cursor: 'pointer'
            }}
          >
            <RefreshCw size={18} className={syncStatus === 'syncing' ? 'spin' : ''} />
            <span>Antrean: {pendingCount} {!isOnline && <span style={{ fontSize: '0.7rem' }}>(OFFLINE - sync otomatis)</span>}</span>
          </div>
        )}

        <div className="status-indicator">
          {isOnline ? <Wifi size={18} className="text-online" /> : <WifiOff size={18} className="text-offline" />}
          <span style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
            {!isOnline && <span className="status-dot-offline"></span>}
            {isOnline && <span className="status-dot-online"></span>}
            {isOnline ? 'Online' : 'Offline'}
          </span>
        </div>

        {!window.electronAPI && (
          <button
            className="btn-icon"
            onClick={() => setIsPrinterSettingsOpen(true)}
            title="Pengaturan Printer"
            style={{ background: 'transparent', padding: '4px', border: 'none' }}
          >
            <Settings size={18} />
          </button>
        )}

        <button
          className="btn-icon"
          onClick={toggleTheme}
          title="Ganti Tema (Terang/Gelap)"
          style={{ background: 'transparent', padding: '4px', border: 'none', color: 'inherit', cursor: 'pointer' }}
        >
          {theme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
        </button>

        {["MANAGER", "ADMIN", "SUPERVISOR", "SPV", "EDP", "SUPERADMIN", "SUPER_ADMIN"].includes(userRole?.toUpperCase()) && (
          <button
            className="btn-icon"
            onClick={() => window.location.href = "/dashboard"}
            title="Buka Dashboard BI / Analitik"
            style={{ background: "transparent", padding: "4px", border: "none", color: "var(--primary)", cursor: "pointer" }}
          >
            <BarChart3 size={18} />
          </button>
        )}

        <div
          className="user-info"
          onClick={() => setIsTerminalModalOpen(true)}
          style={{ cursor: 'pointer' }}
          title="Ganti Terminal"
        >
          <User size={20} />
          <span>{userName}</span>
        </div>

        <button
          onClick={() => onLogout()}
          className="btn-logout-icon"
          title="Logout Kasir (Istirahat)"
          style={{ color: 'var(--danger)' }}
        >
          <LogOut size={18} />
        </button>
      </div>
    </header>
  );
};

export default PosHeader;
