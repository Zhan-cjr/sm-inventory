import React from 'react';
import { Settings } from 'lucide-react';

export const TerminalSelectModal = ({ isOpen, allTerminals, onSelectTerminal }) => {
  if (!isOpen) return null;

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content terminal-select-card fade-in">
        <Settings size={48} className="text-primary" />
        <h2>Pilih Terminal / Kassa</h2>
        <p>Silakan pilih terminal yang digunakan saat ini.</p>
        <div className="terminal-list-grid">
          {allTerminals.length > 0 ? (
            allTerminals.map(terminal => (
              <button key={terminal.id} className="terminal-item-btn" onClick={() => onSelectTerminal(terminal)}>
                <span className="name">{terminal.name}</span>
                <span className="code">{terminal.code}</span>
              </button>
            ))
          ) : (
            <p className="text-muted">Tidak ada terminal aktif untuk cabang ini.</p>
          )}
        </div>
      </div>
    </div>
  );
};
