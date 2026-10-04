import React from 'react';
import {
  Banknote,
  CreditCard,
  Ticket,
  Layers,
  Calculator,
  Tag,
  Edit3,
  Package,
  Lock,
  History,
  User,
  Wallet,
  RotateCcw,
  Search,
  Eraser,
  Trash2,
  X,
  LogOut
} from 'lucide-react';

const PosActionSidebar = ({
  paymentMethod,
  startPayment,
  requestAuthorization,
  setIsVoucherModalOpen,
  setIsMultiPaymentModalOpen,
  isSubtotalMode,
  setIsSubtotalMode,
  barcodeInput,
  handleManualDiscountItem,
  handleManualTotalDiscount,
  items,
  setOpenPriceTargetItem,
  setIsOpenPriceModalOpen,
  setAlertMsg,
  setIsQtyModalOpen,
  handleHoldTransaction,
  setIsRecallModalOpen,
  setIsMemberModalOpen,
  localPrinterSettings,
  setIsCashMovementModalOpen,
  setIsReturnModalOpen,
  handleReprintLast,
  setIsReprintOldModalOpen,
  setIsPpobMenuOpen,
  fetchPpobTransactions,
  unrefundedFailedPpobCount = 0,
  updateQuantity,
  setItems,
  setPwpUpsellPrompt,
  setPayments,
  setManualTotalDiscount,
  setIsReturnMode,
  handleClearDiscount,
  setIsCloseShiftModalOpen,
  renderBtnLabel,
  posSettings
}) => {
  const renderLabel = renderBtnLabel || ((keyName, defaultName, defaultShortcut) => {
    const setting = posSettings?.find(s => s.key_name === keyName);
    const displayName = setting ? setting.display_name : defaultName;
    const shortcut = setting ? setting.shortcut_key : defaultShortcut;

    return (
      <>
        <span style={{ fontWeight: '600', fontSize: '0.8rem', lineHeight: '1.2', textAlign: 'center' }}>{displayName}</span>
        {shortcut && <span style={{ fontSize: '0.65rem', opacity: 0.8, fontWeight: 'normal', lineHeight: '1' }}>({shortcut})</span>}
      </>
    );
  });
  return (
    <aside
      className="pos-functions-sidebar"
      style={{
        padding: '1.25rem',
        display: 'flex',
        flexDirection: 'column',
        gap: '1.25rem',
        height: '100%',
        minHeight: 0,
        overflowY: 'auto'
      }}
    >
      <div
        className="function-grid"
        style={{
          display: 'grid',
          gap: '8px',
          gridTemplateColumns: 'repeat(4, 1fr)',
          alignContent: 'start'
        }}
      >
        {/* Row 1: Bayar */}
        <button
          className={`func-btn payment ${paymentMethod === 'CASH' ? 'active' : ''}`}
          onClick={() => startPayment('CASH')}
        >
          <Banknote size={16} />
          {renderLabel('btn_tunai', 'Tunai', 'F5')}
        </button>
        <button
          className={`func-btn payment ${paymentMethod === 'CARD' ? 'active' : ''}`}
          onClick={() => startPayment('CARD')}
        >
          <CreditCard size={16} />
          {renderLabel('btn_card', 'Card', 'F6')}
        </button>
        <button
          className="func-btn payment"
          onClick={() => requestAuthorization('VOUCHER', () => setIsVoucherModalOpen(true))}
        >
          <Ticket size={16} />
          {renderLabel('btn_voucher', 'Voucher', '')}
        </button>
        <button
          className="func-btn payment"
          onClick={() => requestAuthorization('MULTI_PAYMENT', () => setIsMultiPaymentModalOpen(true))}
        >
          <Layers size={16} />
          {renderLabel('btn_multi_pay', 'Multi Pay', '')}
        </button>

        {/* Row 2: Subtotal & Diskon */}
        <button
          className={`func-btn payment ${isSubtotalMode ? 'active' : ''}`}
          onClick={() => {
            setIsSubtotalMode(true);
            barcodeInput.current?.focus();
          }}
        >
          <Calculator size={16} />
          {renderLabel('btn_subtotal', 'Subtotal', 'F9')}
        </button>
        <button
          className="func-btn discount"
          onClick={() => requestAuthorization('DISCOUNT', () => handleManualDiscountItem('NOMINAL'))}
        >
          <Tag size={16} />
          {renderLabel('btn_disc_item_rp', 'Disc Item Rp', 'F1')}
        </button>
        <button
          className="func-btn discount"
          onClick={() => requestAuthorization('DISCOUNT', () => handleManualDiscountItem('PERCENT'))}
        >
          <Tag size={16} />
          {renderLabel('btn_disc_item_pct', 'Disc Item %', 'F2')}
        </button>
        <button
          className="func-btn discount"
          onClick={() => requestAuthorization('DISCOUNT', () => handleManualTotalDiscount('NOMINAL'))}
        >
          <Tag size={16} />
          {renderLabel('btn_disc_total_rp', 'Disc Total Rp', 'F3')}
        </button>

        {/* Row 3: Total Disc, Open Price, Qty, Hold */}
        <button
          className="func-btn discount"
          onClick={() => requestAuthorization('DISCOUNT', () => handleManualTotalDiscount('PERCENT'))}
        >
          <Tag size={16} />
          {renderLabel('btn_disc_total_pct', 'Disc Total %', 'F4')}
        </button>
        <button
          className="func-btn discount"
          onClick={() =>
            requestAuthorization('OPEN_PRICE', () => {
              if (items.length > 0) {
                setOpenPriceTargetItem(items[items.length - 1]);
                setIsOpenPriceModalOpen(true);
              } else {
                setAlertMsg({ text: 'Pilih item terlebih dahulu', type: 'error' });
                setTimeout(() => setAlertMsg(null), 2000);
              }
            })
          }
        >
          <Edit3 size={16} />
          {renderLabel('btn_open_price', 'Open Price', '')}
        </button>
        <button className="func-btn primary" onClick={() => setIsQtyModalOpen(true)}>
          <Package size={16} />
          {renderLabel('btn_qty', 'Ubah Qty', 'F7')}
        </button>
        <button
          className="func-btn action"
          onClick={() => requestAuthorization('HOLD_RECALL', () => handleHoldTransaction())}
        >
          <Lock size={16} />
          {renderLabel('btn_hold', 'Hold', 'PgUp')}
        </button>

        {/* Row 4: Recall, Member, Kas, Retur */}
        <button
          className="func-btn action"
          onClick={() => requestAuthorization('HOLD_RECALL', () => setIsRecallModalOpen(true))}
        >
          <History size={16} />
          {renderLabel('btn_recall', 'Recall', 'PgDn')}
        </button>
        <button className="func-btn action" onClick={() => setIsMemberModalOpen(true)}>
          <User size={16} />
          {renderLabel('btn_member', 'Member', 'Home')}
        </button>
        <button
          className="func-btn action"
          onClick={() => {
            if (window.electronAPI && window.electronAPI.openCashDrawer) {
              window.electronAPI
                .openCashDrawer(localPrinterSettings?.printerName || 'LPT1')
                .catch((e) => console.error(e));
            }
            setIsCashMovementModalOpen(true);
          }}
        >
          <Wallet size={16} />
          {renderLabel('btn_kas', 'Kas M/K', '')}
        </button>
        <button
          className="func-btn secondary"
          onClick={() => requestAuthorization('RETURN', () => setIsReturnModalOpen(true))}
        >
          <RotateCcw size={16} />
          {renderLabel('btn_retur', 'Retur', 'End')}
        </button>

        {/* Row 5: Reprint, PPOB, Void Item */}
        <button
          className="func-btn secondary"
          onClick={() => requestAuthorization('REPRINT_LAST', () => handleReprintLast())}
        >
          <History size={16} />
          {renderLabel('btn_reprint_last', 'Reprint 1', 'F11')}
        </button>
        <button
          className="func-btn action"
          onClick={() => requestAuthorization('REPRINT_OLD', () => setIsReprintOldModalOpen(true))}
        >
          <Search size={16} />
          {renderLabel('btn_reprint_old', 'Reprint L', 'F12')}
        </button>
        <button
          className="func-btn primary"
          onClick={() => {
            setIsPpobMenuOpen(true);
            fetchPpobTransactions();
          }}
          style={unrefundedFailedPpobCount > 0 ? { position: 'relative', border: '2px solid var(--danger)' } : {}}
          title={unrefundedFailedPpobCount > 0 ? `${unrefundedFailedPpobCount} PPOB gagal butuh refund` : ''}
        >
          {unrefundedFailedPpobCount > 0 && (
            <span
              style={{
                position: 'absolute',
                top: '-6px',
                right: '-6px',
                background: 'var(--danger)',
                color: '#fff',
                borderRadius: '50%',
                width: '18px',
                height: '18px',
                fontSize: '10px',
                fontWeight: 'bold',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                boxShadow: '0 0 6px rgba(230,0,18,0.7)',
                zIndex: 10
              }}
            >
              {unrefundedFailedPpobCount}
            </span>
          )}
          <Package size={16} />
          {renderLabel('btn_ppob_menu', 'Menu PPOB', 'F10')}
        </button>
        <button
          className="func-btn danger"
          onClick={() =>
            requestAuthorization('VOID', () => updateQuantity(items[items.length - 1]?.productId, 0))
          }
        >
          <Eraser size={16} />
          {renderLabel('btn_void_item', 'Void Item', 'Del')}
        </button>

        {/* Row 6: Void All, Clear, Tutup Shift */}
        <button
          className="func-btn danger"
          onClick={() =>
            requestAuthorization('VOID', () => {
              setItems([]);
              setPwpUpsellPrompt(null);
              setPayments([]);
              setManualTotalDiscount(0);
              setIsReturnMode(false);
            })
          }
        >
          <Trash2 size={16} />
          {renderLabel('btn_void_all', 'Void All', 'Esc')}
        </button>
        <button className="func-btn danger" onClick={handleClearDiscount}>
          <X size={16} />
          {renderLabel('btn_clear', 'Clear', 'Ins')}
        </button>
        <button
          className="func-btn secondary"
          onClick={() => {
            if (window.electronAPI && window.electronAPI.openCashDrawer) {
              window.electronAPI
                .openCashDrawer(localPrinterSettings?.printerName || 'LPT1')
                .catch((e) => console.error(e));
            }
            setIsCloseShiftModalOpen(true);
          }}
        >
          <LogOut size={16} />
          {renderLabel('btn_close_shift', 'Tutup Shift', 'F8')}
        </button>
      </div>

      <div
        style={{
          display: 'flex',
          justifyContent: 'center',
          marginTop: 'auto',
          paddingTop: '0.75rem',
          borderTop: '1px solid var(--border-light)'
        }}
      >
        <a
          href="https://api.whatsapp.com/send/?phone=6285861094485&text=Halo%20Zhan_soft,%20Saya%20ingin%20bertanya%20seputar%20Aplikasi%20Sistem%20POS%20Kasir"
          target="_blank"
          rel="noopener noreferrer"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '6px',
            color: 'var(--text-muted)',
            textDecoration: 'none',
            fontSize: '0.75rem',
            fontWeight: '500',
            transition: 'color 0.2s'
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.color = 'var(--text-main)';
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.color = 'var(--text-muted)';
          }}
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            style={{ borderRadius: '4px' }}
          >
            <defs>
              <linearGradient id="zGradPos" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#059669" />
                <stop offset="100%" stopColor="#10b981" />
              </linearGradient>
            </defs>
            <rect width="100" height="100" rx="30" fill="url(#zGradPos)" />
            <path
              d="M30 30H70L30 70H70"
              stroke="white"
              strokeWidth="12"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
          <span>Zhan_soft &copy; {new Date().getFullYear()}</span>
        </a>
      </div>
    </aside>
  );
};

export default PosActionSidebar;
