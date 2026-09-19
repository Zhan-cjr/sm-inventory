# SM Inventory - Desktop Admin Panel (Windows 7 / 8 / 10 / 11)

Aplikasi desktop khusus untuk mengakses panel administrasi **SM Inventory (Filament Admin)** langsung dari desktop tanpa harus membuka browser secara manual.

---

### Fitur Utama
1. **Dukungan Penuh Windows 7 (32-bit & 64-bit)**:
   - Menggunakan **Electron 22.3.27** (versi resmi terakhir yang mendukung Windows 7 & 8).
2. **Pengaturan URL Fleksibel**:
   - URL domain/IP server dapat diganti kapan saja tanpa perlu kompilasi ulang.
   - Tersedia tombol cepat (Preset) untuk Server Produksi, Staging, dan Lokal.
   - Dilengkapi fitur **Uji Koneksi (Test Connection)** secara langsung ke server.
   - Dapat diakses kapan saja dengan menekan tombol **`F2`** pada keyboard atau melalui menu **Pengaturan > Ganti URL Server Admin**.
3. **Session Persistence**:
   - Sesi login tersimpan dengan aman, sehingga pengguna tidak perlu berulang kali login saat membuka dan menutup aplikasi.
4. **Download & Export Lancar**:
   - Mendukung download file hasil export Filament (Excel, PDF, CSV) langsung ke folder *Downloads* Windows.
5. **Halaman Error Cerdas**:
   - Jika koneksi terputus atau IP server salah/berubah, aplikasi menampilkan tombol instan untuk mengganti URL atau mencoba ulang.

---

### Cara Menjalankan & Mengembangkan (Development)

Pastikan Node.js (v18 atau v20) sudah terpasang di komputer Anda.

1. Masuk ke direktori:
   ```bash
   cd desktop-admin
   ```

2. Pasang dependensi:
   ```bash
   npm install
   ```

3. Jalankan aplikasi:
   ```bash
   npm start
   ```

---

### Cara Melakukan Build File `.exe` untuk Windows 7

Aplikasi dapat dikompilasi menjadi installer (`.exe`) mandiri untuk Windows 32-bit maupun 64-bit:

1. **Build Installer Windows (64-bit & 32-bit)**:
   ```bash
   npm run build
   ```
   *Perintah ini akan menghasilkan file installer setup dan portable di dalam folder `build_dist/`:*
   - `SM Inventory Admin Setup 1.0.0.exe` (Installer NSIS dengan shortcut desktop otomatis)
   - `SM Inventory Admin 1.0.0.exe` (Portable - langsung jalan tanpa install)

2. **Build Khusus 32-bit (PC Windows 7 Lama)**:
   ```bash
   npm run build:win32
   ```

3. **Build Khusus 64-bit**:
   ```bash
   npm run build:win64
   ```

---

### Cara Mengubah URL di Aplikasi Windows 7

1. Buka aplikasi **SM Inventory Admin**.
2. Jika server belum aktif atau URL berubah, tekan tombol **`F2`** pada keyboard atau klik menu atas **Pengaturan > ⚙️ Ganti URL Server Admin...**.
3. Masukkan alamat URL baru (misal: `https://admin.toserbaselamat.id` atau `https://adminstg.toserbaselamat.id` atau IP LAN `http://192.168.88.14:8080/admin`).
4. Atau klik tombol preset yang tersedia (🟢 Produksi / 🟡 Staging / 💻 IP LAN).
5. Klik **⚡ Uji Koneksi** untuk memastikan server dapat dijangkau.
6. Klik **💾 Simpan & Buka**. Konfigurasi akan tersimpan otomatis di komputer tersebut.
