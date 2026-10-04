import React from 'react';
import { Printer, X } from 'lucide-react';

export const PrinterSettingsModal = ({
  isOpen,
  onClose,
  localPrinterSettings,
  setLocalPrinterSettings
}) => {
  if (!isOpen) return null;

  const handleCancel = () => {
    try {
      const saved = JSON.parse(localStorage.getItem('pos_printer_settings')) || { autoPrint: false, printMode: 'TEXT', receiptType: 1 };
      setLocalPrinterSettings(saved);
    } catch (e) {
      // ignore
    }
    onClose();
  };

  const handleSave = () => {
    localStorage.setItem('pos_printer_settings', JSON.stringify(localPrinterSettings));
    onClose();
  };

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content settings-card fade-in" style={{ maxWidth: '450px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
          <h2 style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', margin: 0, fontSize: '1.25rem' }}>
            <Printer size={24} className="text-primary" /> Pengaturan Printer Lokal
          </h2>
          <button onClick={onClose} style={{ background: 'transparent', border: 'none', cursor: 'pointer', color: '#64748b' }}>
            <X size={24} />
          </button>
        </div>

        <div style={{ marginBottom: '1.5rem', textAlign: 'left' }}>
          <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Mode Tampilan Cetak</label>
          <div style={{ display: 'flex', gap: '1rem', marginBottom: '1.5rem' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
              <input type="radio" name="autoPrint" checked={!localPrinterSettings.autoPrint} onChange={() => setLocalPrinterSettings(p => ({ ...p, autoPrint: false }))} />
              Preview Dahulu
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
              <input type="radio" name="autoPrint" checked={localPrinterSettings.autoPrint} onChange={() => setLocalPrinterSettings(p => ({ ...p, autoPrint: true }))} />
              Cetak Langsung
            </label>
          </div>

          <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Kualitas / Driver Cetak</label>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px' }} title="Pilih ini jika menggunakan driver TM-U220 standar (Lambat tapi rapi)">
              <input type="radio" name="printMode" checked={localPrinterSettings.printMode === 'GRAPHIC'} onChange={() => setLocalPrinterSettings(p => ({ ...p, printMode: 'GRAPHIC' }))} />
              <span>
                <strong>Grafis (Driver Bawaan)</strong>
                <span style={{ display: 'block', fontSize: '0.8rem', color: '#64748b' }}>Cetak presisi, butuh setting auto-cut manual di printer.</span>
              </span>
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px' }} title="Pilih ini jika menggunakan driver Generic / Text Only (Cepat & Buka laci)">
              <input type="radio" name="printMode" checked={localPrinterSettings.printMode === 'TEXT'} onChange={() => setLocalPrinterSettings(p => ({ ...p, printMode: 'TEXT' }))} />
              <span>
                <strong>Text Only (ESC/POS)</strong>
                <span style={{ display: 'block', fontSize: '0.8rem', color: '#64748b' }}>Sangat cepat, otomatis buka laci (khusus Generic Text Only).</span>
              </span>
            </label>
          </div>

          <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', marginTop: '1.5rem', color: '#334155' }}>Format Struk</label>
          <div style={{ display: 'flex', gap: '1rem', marginBottom: '1.5rem' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
              <input type="radio" name="receiptType" checked={localPrinterSettings.receiptType !== 2} onChange={() => setLocalPrinterSettings(p => ({ ...p, receiptType: 1 }))} />
              Standar (Header di Atas)
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', padding: '0.5rem', border: '1px solid #cbd5e1', borderRadius: '6px', flex: 1 }}>
              <input type="radio" name="receiptType" checked={localPrinterSettings.receiptType === 2} onChange={() => setLocalPrinterSettings(p => ({ ...p, receiptType: 2 }))} />
              Hemat (Header di Bawah)
            </label>
          </div>

          <div style={{ display: 'flex', gap: '1rem', marginBottom: '1.5rem' }}>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Banyak Huruf (Kolom)</label>
              <input
                type="number"
                className="modern-barcode-input"
                value={localPrinterSettings.columns || 32}
                onChange={(e) => setLocalPrinterSettings(p => ({ ...p, columns: parseInt(e.target.value) || 32 }))}
                style={{ width: '100%', padding: '0.5rem' }}
              />
              <small style={{ color: '#64748b', fontSize: '0.75rem' }}>Biasa 32 atau 40 (Thermal 58mm/80mm)</small>
            </div>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Tambahkan Feed</label>
              <input
                type="number"
                className="modern-barcode-input"
                value={localPrinterSettings.feedLines || 0}
                onChange={(e) => setLocalPrinterSettings(p => ({ ...p, feedLines: parseInt(e.target.value) || 0 }))}
                style={{ width: '100%', padding: '0.5rem' }}
              />
              <small style={{ color: '#64748b', fontSize: '0.75rem' }}>Baris kosong di bawah struk</small>
            </div>
          </div>

          <label style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#334155' }}>Nama Printer Target (Opsional)</label>
          <input
            type="text"
            className="modern-barcode-input"
            placeholder="Biarkan kosong untuk default"
            value={localPrinterSettings.printerName || ''}
            onChange={(e) => setLocalPrinterSettings(p => ({ ...p, printerName: e.target.value }))}
            style={{ width: '100%', padding: '0.5rem', marginBottom: '1.5rem' }}
          />
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
          <button className="btn-secondary" onClick={handleCancel}>Batal</button>
          <button className="btn-primary" onClick={handleSave}>Simpan</button>
        </div>
      </div>
    </div>
  );
};
