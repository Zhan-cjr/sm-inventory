import { useEffect } from 'react';

export const usePosShortcuts = ({
  posSettings,
  handlers,
  dependencies = []
}) => {
  useEffect(() => {
    const handleShortcuts = (e) => {
      const settingsList = posSettings && posSettings.length > 0 ? posSettings : [
        { key_name: 'btn_subtotal', shortcut_key: 'F9', is_active: 1 },
        { key_name: 'btn_disc_item_rp', shortcut_key: 'F1', is_active: 1 },
        { key_name: 'btn_disc_item_pct', shortcut_key: 'F2', is_active: 1 },
        { key_name: 'btn_disc_total_rp', shortcut_key: 'F3', is_active: 1 },
        { key_name: 'btn_disc_total_pct', shortcut_key: 'F4', is_active: 1 },
        { key_name: 'btn_tunai', shortcut_key: 'F5', is_active: 1 },
        { key_name: 'btn_card', shortcut_key: 'F6', is_active: 1 },
        { key_name: 'btn_qty', shortcut_key: 'F7', is_active: 1 },
        { key_name: 'btn_close_shift', shortcut_key: 'F8', is_active: 1 },
        { key_name: 'btn_reprint_last', shortcut_key: 'F11', is_active: 1 },
        { key_name: 'btn_reprint_old', shortcut_key: 'F12', is_active: 1 },
        { key_name: 'btn_ppob_menu', shortcut_key: 'F10', is_active: 1 },
        { key_name: 'btn_member', shortcut_key: 'Home', is_active: 1 },
        { key_name: 'btn_retur', shortcut_key: 'End', is_active: 1 },
        { key_name: 'btn_hold', shortcut_key: 'PageUp', is_active: 1 },
        { key_name: 'btn_recall', shortcut_key: 'PageDown', is_active: 1 },
        { key_name: 'btn_clear', shortcut_key: 'Insert', is_active: 1 },
        { key_name: 'btn_void_item', shortcut_key: 'Delete', is_active: 1 },
        { key_name: 'btn_void_all', shortcut_key: 'Escape', is_active: 1 },
        { key_name: 'btn_voucher', shortcut_key: '', is_active: 1 },
        { key_name: 'btn_multi_pay', shortcut_key: '', is_active: 1 },
        { key_name: 'btn_open_price', shortcut_key: '', is_active: 1 },
        { key_name: 'btn_kas', shortcut_key: '', is_active: 1 }
      ];

      const setting = settingsList.find(s => s.shortcut_key && s.shortcut_key.toLowerCase() === e.key.toLowerCase());
      const isActive = (val) => val === true || val === 1 || val === "1";

      // Global Escape handler for modals
      if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.change-modal-overlay, .modal-overlay, [class*="fixed inset-0"]');
        if (modals.length > 0) {
          e.preventDefault();
          e.stopPropagation();

          const topModal = modals[modals.length - 1];
          const buttons = Array.from(topModal.querySelectorAll('button:not([disabled])'));

          // 1. Check button text for cancel/close/esc keywords
          const closeKeywords = ['batal', 'tutup', 'esc', 'cancel', 'close', 'kembali'];
          const matchedBtn = buttons.find(btn => {
            const text = (btn.textContent || '').trim().toLowerCase();
            return closeKeywords.some(kw => text.includes(kw));
          });

          if (matchedBtn) {
            matchedBtn.click();
            return;
          }

          // 2. Check classes or aria/title attributes
          const attrBtn = buttons.find(btn => {
            const cls = (btn.className || '').toString().toLowerCase();
            const aria = (btn.getAttribute('aria-label') || '').toLowerCase();
            const title = (btn.getAttribute('title') || '').toLowerCase();
            return cls.includes('btn-secondary') ||
                   cls.includes('close') ||
                   cls.includes('batal') ||
                   cls.includes('tutup') ||
                   aria.includes('close') ||
                   aria.includes('tutup') ||
                   aria.includes('batal') ||
                   title.includes('close') ||
                   title.includes('tutup');
          });

          if (attrBtn) {
            attrBtn.click();
            return;
          }

          // 3. Check for X icon / close SVG button
          const iconBtn = buttons.find(btn => {
            return btn.querySelector('svg.lucide-x') ||
                   (btn.querySelector('svg') && (btn.closest('header') || btn.parentElement?.querySelector('h2, h3')));
          });

          if (iconBtn) {
            iconBtn.click();
            return;
          }

          return;
        }
      }

      if (!setting || !isActive(setting.is_active)) return;

      // Block shortcuts if any modal overlay is present
      if (document.querySelector('.change-modal-overlay') || document.querySelector('.modal-overlay')) {
        return;
      }

      // If focusing on input, we only allow key presses that are exactly registered in settingsList
      if (e.target.tagName === 'INPUT') {
        const allowedKeys = settingsList
          .filter(s => isActive(s.is_active) && s.shortcut_key)
          .map(s => s.shortcut_key.toLowerCase());
        if (!allowedKeys.includes(e.key.toLowerCase())) {
          return;
        }
      }

      e.preventDefault();
      const fn = handlers[setting.key_name];
      if (typeof fn === 'function') {
        fn();
      }
    };

    window.addEventListener('keydown', handleShortcuts);
    return () => window.removeEventListener('keydown', handleShortcuts);
  }, [posSettings, handlers, ...dependencies]);
};
