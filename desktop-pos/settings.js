document.addEventListener('DOMContentLoaded', async () => {
  const posUrlInput = document.getElementById('posUrl');
  const serverUrlInput = document.getElementById('serverUrl');
  const printerNameSelect = document.getElementById('printerName');
  const printModeSelect = document.getElementById('printMode');
  const receiptTypeSelect = document.getElementById('receiptType');
  const autoPrintCheckbox = document.getElementById('autoPrint');
  
  const form = document.getElementById('settingsForm');
  const messageEl = document.getElementById('message');
  const submitBtn = form.querySelector('button');

  // Load available printers
  if (window.electronAPI) {
    const printers = await window.electronAPI.getPrinters();
    printerNameSelect.innerHTML = '<option value="">-- Cetak Ke PDF / Default --</option>';
    printers.forEach(p => {
      const option = document.createElement('option');
      option.value = p.name;
      option.textContent = p.displayName || p.name;
      printerNameSelect.appendChild(option);
    });

    const config = await window.electronAPI.getConfig();
    if (config) {
      if (config.posUrl) posUrlInput.value = config.posUrl;
      if (config.serverUrl) serverUrlInput.value = config.serverUrl;
      if (config.printerName) printerNameSelect.value = config.printerName;
      if (config.printMode) printModeSelect.value = config.printMode;
      if (config.receiptType) receiptTypeSelect.value = config.receiptType;
      if (config.columns !== undefined) document.getElementById('columns').value = config.columns;
      if (config.feedLines !== undefined) document.getElementById('feedLines').value = config.feedLines;
      if (config.autoPrint) autoPrintCheckbox.checked = config.autoPrint;
    }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';
    
    const config = {
      posUrl: posUrlInput.value,
      serverUrl: serverUrlInput.value,
      printerName: printerNameSelect.value,
      printMode: printModeSelect.value,
      receiptType: parseInt(receiptTypeSelect.value, 10),
      columns: parseInt(document.getElementById('columns').value, 10) || 32,
      feedLines: parseInt(document.getElementById('feedLines').value, 10) || 0,
      autoPrint: autoPrintCheckbox.checked
    };

    if (window.electronAPI) {
      const success = await window.electronAPI.saveConfig(config);
      if (success) {
        messageEl.style.display = 'block';
        // The main process will automatically load the URL upon successful save.
      } else {
        alert('Gagal menyimpan pengaturan.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Simpan & Jalankan';
      }
    } else {
      alert('Tidak berjalan di dalam Electron.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Simpan & Jalankan';
    }
  });
});
