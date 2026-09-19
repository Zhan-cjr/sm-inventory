document.addEventListener('DOMContentLoaded', async () => {
  const adminUrlInput = document.getElementById('adminUrl');
  const form = document.getElementById('settingsForm');
  const btnSave = document.getElementById('btnSave');
  const btnCancel = document.getElementById('btnCancel');
  const btnTest = document.getElementById('btnTest');
  const testResult = document.getElementById('testResult');
  const presetBtns = document.querySelectorAll('.preset-btn');

  // Load existing configuration
  if (window.electronAPI) {
    try {
      const config = await window.electronAPI.getConfig();
      if (config && config.adminUrl) {
        adminUrlInput.value = config.adminUrl;
      } else {
        adminUrlInput.value = 'https://admin.toserbaselamat.id';
      }
    } catch (e) {
      console.error('Error reading config:', e);
    }
  }

  // Handle Preset Buttons
  presetBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const url = btn.getAttribute('data-url');
      if (url) {
        adminUrlInput.value = url;
        testResult.className = 'test-result';
        testResult.textContent = 'Alamat diganti, silakan uji koneksi';
      }
    });
  });

  // Handle Test Connection
  btnTest.addEventListener('click', async () => {
    let url = adminUrlInput.value.trim();
    if (!url) {
      alert('Silakan masukkan alamat URL terlebih dahulu.');
      adminUrlInput.focus();
      return;
    }

    if (!/^https?:\/\//i.test(url)) {
      url = 'http://' + url;
      adminUrlInput.value = url;
    }

    testResult.className = 'test-result loading';
    testResult.textContent = '⏳ Menguji koneksi...';
    btnTest.disabled = true;

    try {
      if (window.electronAPI && window.electronAPI.testConnection) {
        const result = await window.electronAPI.testConnection(url);
        if (result.success) {
          testResult.className = 'test-result success';
          testResult.textContent = '✅ ' + result.message;
        } else {
          testResult.className = 'test-result error';
          testResult.textContent = '❌ ' + result.message;
        }
      } else {
        testResult.className = 'test-result';
        testResult.textContent = 'Pengujian koneksi tidak didukung di luar Electron';
      }
    } catch (err) {
      testResult.className = 'test-result error';
      testResult.textContent = '❌ Terjadi kesalahan: ' + err.message;
    } finally {
      btnTest.disabled = false;
    }
  });

  // Handle Cancel
  btnCancel.addEventListener('click', async () => {
    if (window.electronAPI && window.electronAPI.goBackToAdmin) {
      window.electronAPI.goBackToAdmin();
    }
  });

  // Handle Save
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    let url = adminUrlInput.value.trim();
    if (!url) return;

    if (!/^https?:\/\//i.test(url)) {
      url = 'http://' + url;
      adminUrlInput.value = url;
    }

    btnSave.disabled = true;
    btnSave.textContent = '💾 Menyimpan...';

    const config = {
      adminUrl: url
    };

    if (window.electronAPI) {
      const success = await window.electronAPI.saveConfig(config);
      if (!success) {
        alert('Gagal menyimpan konfigurasi.');
        btnSave.disabled = false;
        btnSave.textContent = '💾 Simpan & Buka';
      }
    } else {
      alert('Konfigurasi berhasil (Mode Browser).');
      btnSave.disabled = false;
      btnSave.textContent = '💾 Simpan & Buka';
    }
  });
});
